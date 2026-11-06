<?php

namespace Tests\Feature\Archiving;

use App\Models\ArchiveClassification;
use App\Models\ArchiveDispositionRequest;
use App\Models\ArchiveEntry;
use App\Models\ArchiveLegalHold;
use App\Models\ArchiveRelationship;
use App\Models\ArchiveRetentionPolicy;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\ArchiveDispositionService;
use App\Services\ArchiveHoldService;
use App\Services\ArchiveRegistryService;
use App\Services\ArchiveRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveRetentionHoldAndDispositionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected User $adminUser;
    protected User $legalOfficer;
    protected DocumentType $docType;
    protected ArchiveClassification $classification;
    protected ArchiveRetentionPolicy $retentionReview;
    protected ArchiveRetentionPolicy $retentionPermanent;
    protected ArchiveRegistryService $registryService;
    protected ArchiveHoldService $holdService;
    protected ArchiveRetentionService $retentionService;
    protected ArchiveDispositionService $dispositionService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->registryService = app(ArchiveRegistryService::class);
        $this->holdService = app(ArchiveHoldService::class);
        $this->retentionService = app(ArchiveRetentionService::class);
        $this->dispositionService = app(ArchiveDispositionService::class);

        $this->company = Company::create(['name' => 'PT Garuda Medika', 'code' => 'GMD']);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Garuda Pusat',
            'code' => 'PST',
            'is_pusat' => true,
        ]);
        $this->unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'Hukum & Kepatuhan',
            'kode_unit_kerja' => 'LGL',
        ]);

        $this->adminUser = User::create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@garuda.test',
            'password' => 'password',
            'system_role' => 'direktur',
            'is_active' => true,
        ]);
        $this->company->users()->attach($this->adminUser);
        $this->adminUser->branches()->attach($this->branch->id);

        $this->legalOfficer = User::create([
            'name' => 'Staf Legal',
            'email' => 'legal@garuda.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->company->users()->attach($this->legalOfficer);
        $this->legalOfficer->branches()->attach($this->branch->id);
        $this->legalOfficer->unitKerjas()->attach($this->unitKerja->id, ['branch_id' => $this->branch->id]);

        $this->docType = DocumentType::create(['name' => 'Perjanjian Kerjasama', 'code' => 'PKS']);

        $this->retentionReview = ArchiveRetentionPolicy::create([
            'company_id' => $this->company->id,
            'code' => 'JRA-PKS-3Y',
            'name' => 'Retensi PKS 3 Tahun Musnah',
            'trigger_event' => 'registration_date',
            'active_retention_years' => 2,
            'inactive_retention_years' => 1,
            'total_retention_days' => 1095,
            'final_disposition' => 'destroy',
        ]);

        $this->retentionPermanent = ArchiveRetentionPolicy::create([
            'company_id' => $this->company->id,
            'code' => 'JRA-AKTA-PERM',
            'name' => 'Retensi Akta Pendirian Permanen',
            'trigger_event' => 'registration_date',
            'active_retention_years' => 10,
            'inactive_retention_years' => 0,
            'final_disposition' => 'permanent',
        ]);

        $this->classification = ArchiveClassification::create([
            'company_id' => $this->company->id,
            'default_retention_policy_id' => $this->retentionReview->id,
            'code' => 'LGL-KTR',
            'name' => 'Kontrak Bisnis',
            'confidentiality_level' => 'confidential',
            'requires_approval' => false,
        ]);
    }

    protected function createRegisteredArchive(
        ArchiveRetentionPolicy $policy,
        string $content = 'Official Agreement Content'
    ): ArchiveEntry {
        static $counter = 100;
        $counter++;

        $doc = Document::create([
            'title' => 'Perjanjian Kerjasama Vendor Medis',
            'document_number' => "GMD/PKS/2026/{$counter}",
            'document_type_id' => $this->docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->legalOfficer->id,
            'visibility' => Document::VISIBILITY_GENERAL,
        ]);

        $sourcePath = "documents/{$doc->id}/v1.pdf";
        Storage::disk('local')->put($sourcePath, $content);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Kontrak Resmi 2026</p>',
            'file_path' => $sourcePath,
            'file_original_name' => 'kontrak.pdf',
            'file_mime' => 'application/pdf',
            'author_id' => $this->legalOfficer->id,
            'author_name' => $this->legalOfficer->name,
            'status' => 'active',
        ]);

        $req = $this->registryService->createRequest(
            document: $doc,
            classification: $this->classification,
            requester: $this->legalOfficer,
            attributes: [
                'archive_title' => 'Arsip Resmi Perjanjian Kerjasama',
                'retention_policy_id' => $policy->id,
            ]
        );
        $this->registryService->submitRequest($req, $this->legalOfficer);

        return $this->registryService->finalizeRegistration($req, $this->adminUser);
    }

    public function test_retention_schedule_distinguishes_standard_and_permanent_records(): void
    {
        // 1. Standard review/destroy policy
        $standardArchive = $this->createRegisteredArchive($this->retentionReview);
        $this->assertEquals(ArchiveEntry::STATUS_REGISTERED, $standardArchive->status);
        $this->assertNotNull($standardArchive->retention_due_at);
        $this->assertEquals('destroy', $standardArchive->final_disposition_action);

        // 2. Permanent policy
        $permanentArchive = $this->createRegisteredArchive($this->retentionPermanent);
        $this->assertEquals(ArchiveEntry::STATUS_PERMANENT, $permanentArchive->status);
        $this->assertNull($permanentArchive->retention_due_at);
        $this->assertEquals('permanent', $permanentArchive->final_disposition_action);
    }

    public function test_multiple_legal_holds_synchronize_hold_flag_accurately(): void
    {
        $archive = $this->createRegisteredArchive($this->retentionReview);
        $this->assertFalse($archive->is_under_legal_hold);

        // Place Hold A (e.g. Pajak audit)
        $holdA = $this->holdService->applyHold(
            entry: $archive,
            caseReference: 'TAX-AUDIT-2026',
            title: 'Audit Pajak',
            reason: 'Pemeriksaan Pajak Badan Tahun Pajak 2026',
            actor: $this->adminUser
        );

        $fresh = $archive->fresh();
        $this->assertTrue($fresh->is_under_legal_hold);

        // Place Hold B (e.g. Sengketa Arbitrase)
        $holdB = $this->holdService->applyHold(
            entry: $archive,
            caseReference: 'ARB-BANI-042',
            title: 'Sengketa Kontrak',
            reason: 'Sengketa Kontrak Vendor di BANI',
            actor: $this->adminUser
        );

        $fresh = $archive->fresh();
        $this->assertTrue($fresh->is_under_legal_hold);
        $this->assertEquals(2, $fresh->activeLegalHolds()->count());

        // Release Hold A: archive MUST remain on hold because Hold B is still active
        $this->holdService->releaseHold(
            hold: $holdA,
            releaseReason: 'Pemeriksaan pajak telah selesai tanpa temuan',
            actor: $this->adminUser
        );

        $fresh = $archive->fresh();
        $this->assertTrue($fresh->is_under_legal_hold);
        $this->assertEquals(1, $fresh->activeLegalHolds()->count());

        // Release Hold B: archive is now free of legal holds
        $this->holdService->releaseHold(
            hold: $holdB,
            releaseReason: 'Arbitrase diselesaikan melalui perdamaian',
            actor: $this->adminUser
        );

        $fresh = $archive->fresh();
        $this->assertFalse($fresh->is_under_legal_hold);
        $this->assertEquals(0, $fresh->activeLegalHolds()->count());
    }

    public function test_disposition_is_blocked_when_legal_hold_is_active(): void
    {
        $archive = $this->createRegisteredArchive($this->retentionReview);

        // Place legal hold
        $this->holdService->applyHold(
            entry: $archive,
            caseReference: 'BPKP-2026',
            title: 'Audit BPKP',
            reason: 'Audit Investigasi Khusus BPKP',
            actor: $this->adminUser
        );

        // Attempt to request disposition while under legal hold via service
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot request disposition for an archive under active legal hold.');

        $this->dispositionService->requestDisposition(
            entry: $archive,
            actionType: 'destroy',
            justification: 'Jadwal retensi telah jatuh tempo',
            actor: $this->legalOfficer
        );
    }

    public function test_approved_disposition_destroys_file_generates_certificate_and_preserves_tombstone(): void
    {
        $content = 'Dokumen Kontrak yang Akan Dimusnahkan Resmi';
        $archive = $this->createRegisteredArchive($this->retentionReview, $content);

        $snapshot = $archive->snapshot;
        $snapshotKey = $snapshot->object_key;
        $originalHash = $snapshot->content_hash_sha256;

        $this->assertTrue(Storage::disk('local')->exists($snapshotKey));

        // Step 1: Request disposition
        $dispReq = $this->dispositionService->requestDisposition(
            entry: $archive,
            actionType: 'destroy',
            justification: 'Masa retensi telah lampau dan tidak ada kebutuhan operasional maupun hukum',
            actor: $this->legalOfficer
        );

        $this->assertEquals(ArchiveDispositionRequest::STATUS_PENDING, $dispReq->status);

        // Step 2: Execute disposition
        $executedReq = $this->dispositionService->executeDisposition(
            request: $dispReq,
            actor: $this->adminUser,
            reviewNotes: 'Disetujui pemusnahan total sesuai jadwal retensi'
        );

        // Verify Certificate created
        $this->assertNotNull($executedReq->certificate_number);
        $this->assertStringStartsWith('CERT-DISP/', $executedReq->certificate_number);

        // Verify snapshot binary file is physically deleted from storage
        $this->assertFalse(Storage::disk('local')->exists($snapshotKey));

        // Verify Archive Entry Status updated to disposed
        $freshArchive = $archive->fresh();
        $this->assertEquals(ArchiveEntry::STATUS_DISPOSED, $freshArchive->status);
        $this->assertNotNull($freshArchive->disposed_at);
        $this->assertEquals($executedReq->certificate_number, $freshArchive->disposition_certificate_number);

        // Verify Registry Tombstone is preserved
        $this->assertNotNull($freshArchive->original_document_number);
        $this->assertEquals($originalHash, $freshArchive->snapshot->content_hash_sha256);

        // Verify disposition certificate page renders
        $response = $this->actingAs($this->adminUser)
            ->get(route('archives.dispositions.certificate', $executedReq));

        $response->assertStatus(200);
        $response->assertSee($executedReq->certificate_number);
        $response->assertSee($freshArchive->archive_number);
    }

    public function test_reactivate_archive_creates_new_working_document_and_lineage_relationship(): void
    {
        $archive = $this->createRegisteredArchive($this->retentionReview, 'Base Template Draft Content');

        // Reactivate archive into a new working copy
        $workingDoc = $this->registryService->reactivateAsWorkingCopy(
            entry: $archive,
            actor: $this->legalOfficer,
            reason: 'Perpanjangan addendum kerjasama tahun berjalan'
        );

        $this->assertInstanceOf(Document::class, $workingDoc);
        $this->assertStringContainsString('Working Copy', $workingDoc->title);
        $this->assertEquals(1, $workingDoc->versions()->count());

        // Verify relationship created
        $this->assertDatabaseHas('archive_relationships', [
            'from_entry_id' => $archive->id,
            'relation_type' => ArchiveRelationship::TYPE_REACTIVATED_TO_DOCUMENT,
            'created_by' => $this->legalOfficer->id,
        ]);
    }
}
