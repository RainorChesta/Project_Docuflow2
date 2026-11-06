<?php

namespace Tests\Feature\Archiving;

use App\Models\ArchiveClassification;
use App\Models\ArchiveEntry;
use App\Models\ArchiveRequest;
use App\Models\ArchiveRetentionPolicy;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\ArchiveEligibilityService;
use App\Services\ArchiveRegistryService;
use App\Services\ArchiveSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveWorkflowAndSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected ArchiveRegistryService $registryService;
    protected ArchiveSnapshotService $snapshotService;
    protected ArchiveEligibilityService $eligibilityService;

    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected User $requester;
    protected User $approver;
    protected DocumentType $docType;
    protected ArchiveClassification $classification;
    protected ArchiveRetentionPolicy $retentionPolicy;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        Storage::fake('local');
        config(['onlyoffice.storage_disk' => 'local']);

        $this->registryService = app(ArchiveRegistryService::class);
        $this->snapshotService = app(ArchiveSnapshotService::class);
        $this->eligibilityService = app(ArchiveEligibilityService::class);

        $this->company = Company::create(['name' => 'PT MEDIKA SEHAT', 'code' => 'MDK']);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'CABANG UTAMA',
            'code' => 'UTM',
            'is_pusat' => true,
        ]);

        $this->unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'PELAYANAN MEDIK',
            'kode_unit_kerja' => 'MED',
        ]);

        $this->requester = User::create([
            'name' => 'Staf Pelayanan',
            'email' => 'staf@medika.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->company->users()->attach($this->requester);
        $this->requester->branches()->attach($this->branch->id);
        $this->requester->unitKerjas()->attach($this->unitKerja->id, ['branch_id' => $this->branch->id]);

        $this->approver = User::create([
            'name' => 'Kepala Klinik',
            'email' => 'head@medika.test',
            'password' => 'password',
            'system_role' => 'head',
            'is_active' => true,
        ]);
        $this->company->users()->attach($this->approver);
        $this->approver->branches()->attach($this->branch->id);
        $this->approver->unitKerjas()->attach($this->unitKerja->id, ['branch_id' => $this->branch->id]);

        $this->docType = DocumentType::create(['name' => 'Standar Medis', 'code' => 'MED']);

        $this->retentionPolicy = ArchiveRetentionPolicy::create([
            'company_id' => $this->company->id,
            'code' => 'JRA-MED-5TH',
            'name' => 'Retensi Rekam Medis 5 Tahun',
            'trigger_event' => 'registration_date',
            'active_retention_years' => 2,
            'inactive_retention_years' => 3,
            'total_retention_days' => 1825,
            'final_disposition' => 'review',
        ]);

        $this->classification = ArchiveClassification::create([
            'company_id' => $this->company->id,
            'default_retention_policy_id' => $this->retentionPolicy->id,
            'code' => 'SOP-MED',
            'name' => 'Prosedur Pelayanan Medik',
            'confidentiality_level' => 'internal',
            'requires_approval' => true,
        ]);
    }

    protected function createActiveDocumentWithFile(string $content = 'Sample Official Content'): array
    {
        $document = Document::create([
            'title' => 'SOP Tindakan Pasien',
            'document_number' => 'MED/2026/001',
            'document_type_id' => $this->docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->requester->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $filePath = "documents/{$document->id}/v1.docx";
        Storage::disk('local')->put($filePath, $content);

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => 1,
            'content' => '<p>Standard Operating Procedure Content</p>',
            'file_path' => $filePath,
            'file_original_name' => 'SOP_Tindakan_Pasien.docx',
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'author_id' => $this->requester->id,
            'author_name' => $this->requester->name,
            'status' => 'active',
        ]);

        $document->update(['current_version_id' => $version->id]);

        return [$document, $version];
    }

    public function test_ineligible_document_cannot_be_archived(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile();

        // 1. Ineligible when draft
        $version->update(['status' => 'draft']);
        $eligibility = $this->eligibilityService->checkEligibility($document, $version);
        $this->assertFalse($eligibility['eligible']);

        // 2. Ineligible when trashed
        $version->update(['status' => 'active']);
        $document->delete(); // Soft delete
        $eligibility = $this->eligibilityService->checkEligibility($document, $version);
        $this->assertFalse($eligibility['eligible']);

        // 3. Ineligible when signature request is pending
        $document->restore();
        $sigRequest = \App\Models\SignatureRequest::create([
            'requester_id' => $this->requester->id,
            'target_user_id' => $this->approver->id,
            'document_id' => $document->id,
            'status' => 'pending',
            'is_used' => false,
        ]);
        $eligibility = $this->eligibilityService->checkEligibility($document, $version);
        $this->assertFalse($eligibility['eligible']);
        $this->assertFalse($eligibility['checks']['no_pending_signatures']['passed']);

        // Mark signature request as approved/used
        $sigRequest->update(['status' => 'approved', 'is_used' => true]);
        $eligibility = $this->eligibilityService->checkEligibility($document, $version);
        $this->assertTrue($eligibility['eligible']);
        $this->assertTrue($eligibility['checks']['no_pending_signatures']['passed']);
    }

    public function test_archive_request_wizard_auto_selects_classification_from_document_type(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile();

        // Create matching classification for document's docType (MED)
        $medClass = ArchiveClassification::create([
            'company_id' => $this->company->id,
            'code' => 'MED',
            'name' => 'Klasifikasi Medis Khusus',
            'confidentiality_level' => 'confidential',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->requester)
            ->get(route('archive-requests.create', ['document_id' => $document->id]));

        $response->assertOk();
        $response->assertSee('Otomatis Sesuai Tipe');
        $response->assertSee('data-code="MED"', false);
        $response->assertViewHas('selectedClassification', function ($c) use ($medClass) {
            return $c && $c->id === $medClass->id;
        });
        $response->assertViewHas('defaultClassificationId', $medClass->id);
    }

    public function test_archive_request_preview_number_endpoint(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile();

        $response = $this->actingAs($this->requester)
            ->getJson(route('archive-requests.preview-number', [
                'classification_id' => $this->classification->id,
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'unit_kerja_id' => $this->unitKerja->id,
            ]));

        $response->assertOk();
        $response->assertJsonStructure(['number', 'sequence', 'scope_key', 'period_key']);
        $this->assertStringContainsString('SOP-MED', $response->json('number'));
    }

    public function test_full_archive_workflow_from_draft_to_approved_to_registered(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Binary bytes for official document');

        // Attach an approved approval step to the version to verify provenance snapshot
        \App\Models\DocumentApprovalStep::create([
            'document_id' => $document->id,
            'version_id' => $version->id,
            'step_order' => 1,
            'step_type' => 'head_approval',
            'step_name' => 'Review & Persetujuan Kepala Unit',
            'assigned_user_id' => $this->approver->id,
            'status' => 'approved',
            'action_by_id' => $this->approver->id,
            'action_at' => now(),
            'notes' => 'Lengkap dan disetujui.',
        ]);

        // Step 1: Create Archive Request
        $request = $this->registryService->createRequest(
            document: $document,
            classification: $this->classification,
            requester: $this->requester,
            attributes: [
                'archive_title' => 'Arsip Resmi SOP Tindakan Pasien',
                'archive_reason' => 'Pengesahan baku operasional klinik',
                'sensitivity_level' => 'internal',
            ]
        );

        $this->assertEquals(ArchiveRequest::STATE_DRAFT, $request->state);
        $this->assertTrue($request->requires_approval);

        // Step 2: Submit Request
        $this->registryService->submitRequest($request, $this->requester);
        $this->assertEquals(ArchiveRequest::STATE_PENDING_APPROVAL, $request->state);

        // Step 3: Approve Request
        $this->registryService->approveRequest($request, $this->approver, 'Disetujui untuk registrasi arsip');
        $this->assertEquals(ArchiveRequest::STATE_APPROVED, $request->state);
        $this->assertEquals($this->approver->id, $request->approved_by);

        // Step 4: Finalize Registration
        $entry = $this->registryService->finalizeRegistration($request, $this->approver);

        $this->assertInstanceOf(ArchiveEntry::class, $entry);
        $this->assertEquals(ArchiveRequest::STATE_REGISTERED, $request->fresh()->state);
        $this->assertEquals(ArchiveEntry::STATUS_REGISTERED, $entry->status);

        // Verify Numbering (CV MMC Approach A: D/B/E/F/A)
        $year = now()->format('Y');
        $romanMonths = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
        $romanMonth = $romanMonths[(int) now()->format('n')];
        $expectedNumber = "JBM/SOP-MED/{$romanMonth}/{$year}/001";
        $this->assertEquals($expectedNumber, $entry->archive_number);

        // Verify Immutable Snapshot
        $snapshot = $entry->snapshot;
        $this->assertNotNull($snapshot);
        $this->assertTrue(Storage::disk('local')->exists($snapshot->object_key));

        // Storage path MUST be inside archives/
        $this->assertStringStartsWith("archives/{$this->company->id}/{$year}/", $snapshot->object_key);

        // Content Hash verification
        $storedBytes = Storage::disk('local')->get($snapshot->object_key);
        $this->assertEquals(hash('sha256', 'Binary bytes for official document'), $snapshot->content_hash_sha256);
        $this->assertEquals($snapshot->content_hash_sha256, hash('sha256', $storedBytes));

        // Provenance verification: approval steps captured
        $this->assertNotEmpty($snapshot->manifest_payload['approval_steps']);
        $this->assertEquals(1, $snapshot->manifest_payload['approval_steps'][0]['step_order']);
        $this->assertEquals('head_approval', $snapshot->manifest_payload['approval_steps'][0]['step_type']);
        $this->assertEquals($this->approver->name, $snapshot->manifest_payload['approval_steps'][0]['user_name']);

        // Verify Retention schedule assigned
        $this->assertEquals($this->retentionPolicy->id, $entry->retention_policy_id);
        $this->assertNotNull($entry->retention_due_at);
        $this->assertEquals(now()->addDays(1825)->toDateString(), $entry->retention_due_at->toDateString());
    }

    public function test_integrity_verification_succeeds_on_intact_file_and_fails_on_tampering(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Pure Immutable Bytes');

        $request = $this->registryService->createRequest(
            document: $document,
            classification: $this->classification,
            requester: $this->approver
        );
        $this->registryService->submitRequest($request, $this->approver);
        $this->registryService->approveRequest($request, $this->approver);
        $entry = $this->registryService->finalizeRegistration($request, $this->approver);

        // 1. Initial verification must pass
        $verifyInitial = $this->snapshotService->verifyIntegrity($entry);
        $this->assertTrue($verifyInitial['passed']);
        $this->assertEquals('valid', $verifyInitial['status']);

        // 2. Tamper with the snapshot file on disk
        $snapshot = $entry->snapshot;
        Storage::disk('local')->put($snapshot->object_key, 'TAMPERED CORRUPTED CONTENT');

        // 3. Verification must immediately detect mismatch!
        $verifyTampered = $this->snapshotService->verifyIntegrity($entry);
        $this->assertFalse($verifyTampered['passed']);
        $this->assertEquals('mismatch', $verifyTampered['status']);
    }

    public function test_idempotent_finalization(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Idempotency Content');

        $request = $this->registryService->createRequest(
            document: $document,
            classification: $this->classification,
            requester: $this->approver
        );
        $this->registryService->submitRequest($request, $this->approver);
        $this->registryService->approveRequest($request, $this->approver);

        // First finalization
        $entry1 = $this->registryService->finalizeRegistration($request, $this->approver);

        // Second finalization call on same request
        $entry2 = $this->registryService->finalizeRegistration($request, $this->approver);

        $this->assertEquals($entry1->id, $entry2->id);
        $this->assertEquals($entry1->archive_number, $entry2->archive_number);
        $this->assertEquals(1, ArchiveEntry::where('request_id', $request->id)->count());
    }

    public function test_can_delete_canceled_archive_request(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Canceled Request Doc');

        $request = $this->registryService->createRequest(
            document: $document,
            classification: $this->classification,
            requester: $this->requester
        );

        $this->registryService->cancelRequest($request, $this->requester, 'User decided not to archive');
        $this->assertEquals(ArchiveRequest::STATE_CANCELED, $request->fresh()->state);

        // Delete the canceled request via DELETE route
        $response = $this->actingAs($this->requester)
            ->delete(route('archive-requests.destroy', $request->id));

        $response->assertRedirect(route('archive-requests.index'));
        $this->assertDatabaseMissing('archive_requests', ['id' => $request->id]);
    }

    public function test_cannot_delete_registered_archive_request(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Registered Request Doc');

        $request = $this->registryService->createRequest(
            document: $document,
            classification: $this->classification,
            requester: $this->approver
        );
        $this->registryService->submitRequest($request, $this->approver);
        $this->registryService->approveRequest($request, $this->approver);
        $entry = $this->registryService->finalizeRegistration($request, $this->approver);

        $this->assertEquals(ArchiveRequest::STATE_REGISTERED, $request->fresh()->state);

        // Deleting registered request must be forbidden (403)
        $response = $this->actingAs($this->approver)
            ->delete(route('archive-requests.destroy', $request->id));

        $response->assertForbidden();
        $this->assertDatabaseHas('archive_requests', ['id' => $request->id]);
    }

    public function test_document_detail_displays_archive_button_for_active_document(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Active Document Content');

        $response = $this->actingAs($this->requester)
            ->get(route('documents.show', $document));

        $response->assertOk();
        $response->assertSee(route('archive-requests.create', ['document_id' => $document->id]), false);
        $response->assertSee(__('Arsipkan'));
    }

    public function test_document_detail_displays_lihat_arsip_when_archived(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('Archived Document Content');

        $request = $this->registryService->createRequest(
            document: $document,
            classification: $this->classification,
            requester: $this->approver
        );
        $this->registryService->submitRequest($request, $this->approver);
        $this->registryService->approveRequest($request, $this->approver);
        $entry = $this->registryService->finalizeRegistration($request, $this->approver);

        $response = $this->actingAs($this->requester)
            ->get(route('documents.show', $document));

        $response->assertOk();
        $response->assertSee(route('archives.show', $entry), false);
        $response->assertSee(__('Lihat Arsip'));
    }

    public function test_my_documents_and_unit_kerja_display_archive_button_and_status(): void
    {
        [$document, $version] = $this->createActiveDocumentWithFile('My Document for Archive');

        // Test My Documents (mine)
        $responseMine = $this->actingAs($this->requester)
            ->get(route('documents.index', ['type' => 'mine']));

        $responseMine->assertOk();
        $responseMine->assertSee(route('archive-requests.create', ['document_id' => $document->id]), false);

        // Test Unit Kerja Documents
        $responseUnitKerja = $this->actingAs($this->requester)
            ->get(route('documents.index', ['type' => 'unit_kerja']));

        $responseUnitKerja->assertOk();
        $responseUnitKerja->assertSee(route('archive-requests.create', ['document_id' => $document->id]), false);
    }

    public function test_archive_info_button_and_modal_render_on_archive_pages(): void
    {
        $response = $this->actingAs($this->requester)
            ->get(route('archives.index'));

        $response->assertOk();
        $response->assertSee('archive-info-modal');
        $response->assertSee(__('Mengapa Dokumen Perlu Diarsipkan?'));
        $response->assertSee(__('Fungsi Pengarsipan'));
    }
}
