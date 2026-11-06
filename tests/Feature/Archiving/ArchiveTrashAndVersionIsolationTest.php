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
use App\Services\ArchiveRegistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveTrashAndVersionIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Branch $branch;
    protected UnitKerja $unitKerja;
    protected User $adminUser;
    protected User $staffUser;
    protected DocumentType $docType;
    protected ArchiveClassification $classification;
    protected ArchiveRetentionPolicy $retentionPolicy;
    protected ArchiveRegistryService $registryService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->registryService = app(ArchiveRegistryService::class);

        $this->company = Company::create(['name' => 'PT Bio Farma', 'code' => 'BIO']);
        $this->branch = Branch::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Pusat',
            'code' => 'PST',
            'is_pusat' => true,
        ]);
        $this->unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'Riset dan Mutu',
            'kode_unit_kerja' => 'RND',
        ]);

        $this->adminUser = User::create([
            'name' => 'Admin Bio',
            'email' => 'admin@bio.test',
            'password' => 'password',
            'system_role' => 'admin',
            'is_active' => true,
        ]);
        $this->company->users()->attach($this->adminUser);
        $this->adminUser->branches()->attach($this->branch->id);

        $this->staffUser = User::create([
            'name' => 'Staff Bio',
            'email' => 'staff@bio.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->company->users()->attach($this->staffUser);
        $this->staffUser->branches()->attach($this->branch->id);
        $this->staffUser->unitKerjas()->attach($this->unitKerja->id, ['branch_id' => $this->branch->id]);

        $this->docType = DocumentType::create(['name' => 'SOP Teknis', 'code' => 'SOP']);

        $this->retentionPolicy = ArchiveRetentionPolicy::create([
            'company_id' => $this->company->id,
            'code' => 'RET-BIO-10',
            'name' => 'Retensi Bio 10 Tahun',
            'trigger_event' => 'registration_date',
            'active_retention_years' => 10,
            'inactive_retention_years' => 0,
            'final_disposition' => 'permanent',
        ]);

        $this->classification = ArchiveClassification::create([
            'company_id' => $this->company->id,
            'default_retention_policy_id' => $this->retentionPolicy->id,
            'code' => 'BIO-MUTU',
            'name' => 'Standar Mutu Produk',
            'confidentiality_level' => 'internal',
            'requires_approval' => false,
        ]);
    }

    protected function createDocumentWithVersion(string $fileContent = 'Official Formula Data'): array
    {
        $doc = Document::create([
            'title' => 'Formulasi Vaksin 2026',
            'document_number' => 'BIO/SOP/2026/001',
            'document_type_id' => $this->docType->id,
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'unit_kerja_id' => $this->unitKerja->id,
            'owner_id' => $this->staffUser->id,
            'visibility' => Document::VISIBILITY_GENERAL,
        ]);

        $sourcePath = "documents/{$doc->id}/v1.docx";
        Storage::disk('local')->put($sourcePath, $fileContent);

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Formulasi Vaksin 2026 Baku</p>',
            'file_path' => $sourcePath,
            'file_original_name' => 'vaksin_2026.docx',
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'author_id' => $this->staffUser->id,
            'author_name' => $this->staffUser->name,
            'status' => 'active',
        ]);

        return [$doc, $version, $sourcePath];
    }

    public function test_document_soft_delete_and_force_delete_does_not_affect_archive_snapshot(): void
    {
        [$doc, $version, $sourcePath] = $this->createDocumentWithVersion('Formula Rahasia A-1');

        // Create and register archive
        $req = $this->registryService->createRequest(
            document: $doc,
            classification: $this->classification,
            requester: $this->staffUser,
            attributes: ['archive_title' => 'Arsip Resmi Mutu Formula Vaksin']
        );
        $this->registryService->submitRequest($req, $this->staffUser);
        $archive = $this->registryService->finalizeRegistration($req, $this->adminUser);

        $snapshot = $archive->snapshot;
        $archivePath = $snapshot->object_key;

        // Verify snapshot exists and is different from document path
        $this->assertTrue(Storage::disk('local')->exists($archivePath));
        $this->assertNotEquals($sourcePath, $archivePath);

        // 1. Soft delete the original document
        $doc->delete();
        $this->assertTrue($doc->trashed());
        // Archive and snapshot MUST remain intact
        $this->assertNotNull($archive->fresh());
        $this->assertTrue(Storage::disk('local')->exists($archivePath));

        // 2. Force delete the document
        $doc->forceDelete();
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);

        // Archive entry and snapshot MUST still be 100% intact
        $freshArchive = $archive->fresh();
        $this->assertNotNull($freshArchive);
        $this->assertNotNull($freshArchive->snapshot);
        $this->assertTrue(Storage::disk('local')->exists($archivePath));
        $this->assertEquals(hash('sha256', 'Formula Rahasia A-1'), $freshArchive->snapshot->content_hash_sha256);
    }

    public function test_prune_trashed_command_does_not_destroy_archive_snapshot(): void
    {
        [$doc, $version, $sourcePath] = $this->createDocumentWithVersion('QC Standards Spec');

        $req = $this->registryService->createRequest(
            document: $doc,
            classification: $this->classification,
            requester: $this->staffUser,
            attributes: ['archive_title' => 'Arsip QC Standards']
        );
        $this->registryService->submitRequest($req, $this->staffUser);
        $archive = $this->registryService->finalizeRegistration($req, $this->adminUser);

        $archivePath = $archive->snapshot->object_key;

        // Simulate document trashed 40 days ago
        $doc->update(['deleted_at' => now()->subDays(40)]);

        // Run documents:prune-trashed
        Artisan::call('documents:prune-trashed');

        // Archive snapshot file MUST still exist
        $this->assertTrue(Storage::disk('local')->exists($archivePath));
        $this->assertNotNull($archive->fresh());
    }

    public function test_document_with_active_in_flight_archive_request_cannot_be_force_deleted_from_trash(): void
    {
        [$doc, $version, $sourcePath] = $this->createDocumentWithVersion('Draft in archive process');

        // Create an in-flight archive request
        $req = $this->registryService->createRequest(
            document: $doc,
            classification: $this->classification,
            requester: $this->staffUser,
            attributes: ['archive_title' => 'Permohonan Arsip yang Sedang Berjalan']
        );
        $this->registryService->submitRequest($req, $this->staffUser);

        $this->assertTrue(in_array($req->state, [ArchiveRequest::STATE_PENDING_APPROVAL, ArchiveRequest::STATE_APPROVED], true));

        // Soft delete the document
        $doc->delete();

        // Attempt to permanently delete the trashed document via TrashController
        $response = $this->actingAs($this->adminUser)
            ->delete(route('trash.force-delete', $doc->id));

        // Must be blocked with error flash message
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('documents', ['id' => $doc->id]);
    }

    public function test_expired_version_purge_command_preserves_archived_versions(): void
    {
        [$doc, $version, $sourcePath] = $this->createDocumentWithVersion('Preserved Version Content');

        // Register archive linking to this version
        $req = $this->registryService->createRequest(
            document: $doc,
            classification: $this->classification,
            requester: $this->staffUser,
            attributes: ['archive_title' => 'Arsip Berbasis Versi Terpilih']
        );
        $this->registryService->submitRequest($req, $this->staffUser);
        $archive = $this->registryService->finalizeRegistration($req, $this->adminUser);

        // Simulate version marked as deleted/expired 60 days ago
        $version->update(['deleted_at' => now()->subDays(60)]);

        // Run versions:purge if command exists
        if (array_key_exists('versions:purge', Artisan::all())) {
            Artisan::call('versions:purge');
        }

        // Version referenced in archive_entries MUST still exist in database
        $this->assertDatabaseHas('document_versions', ['id' => $version->id]);
    }
}
