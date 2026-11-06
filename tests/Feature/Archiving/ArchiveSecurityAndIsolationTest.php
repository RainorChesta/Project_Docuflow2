<?php

namespace Tests\Feature\Archiving;

use App\Models\ArchiveClassification;
use App\Models\ArchiveEntry;
use App\Models\ArchivePolicy;
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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveSecurityAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected UnitKerja $unitKerjaA;
    protected User $adminUser;
    protected User $staffA;
    protected User $staffB;
    protected User $headA;
    protected ArchiveRegistryService $registryService;
    protected ArchiveClassification $classificationA;
    protected DocumentType $docTypeA;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->registryService = app(ArchiveRegistryService::class);

        // Company A setup
        $this->companyA = Company::create(['name' => 'PT Alfa Indonesia', 'code' => 'ALF']);
        $this->branchA = Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Cabang Alfa Jakarta',
            'code' => 'JKT',
            'is_pusat' => true,
        ]);
        $this->unitKerjaA = UnitKerja::create([
            'nama_unit_kerja' => 'Keuangan Alfa',
            'kode_unit_kerja' => 'KEU',
        ]);

        // Company B setup
        $this->companyB = Company::create(['name' => 'PT Beta Global', 'code' => 'BET']);
        $this->branchB = Branch::create([
            'company_id' => $this->companyB->id,
            'name' => 'Cabang Beta Surabaya',
            'code' => 'SBY',
            'is_pusat' => true,
        ]);

        // Users
        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@alfa.test',
            'password' => 'password',
            'system_role' => 'admin',
            'is_active' => true,
        ]);
        $this->companyA->users()->attach($this->adminUser);
        $this->adminUser->branches()->attach($this->branchA->id);

        $this->headA = User::create([
            'name' => 'Head Unit Alfa',
            'email' => 'head@alfa.test',
            'password' => 'password',
            'system_role' => 'head',
            'is_active' => true,
        ]);
        $this->companyA->users()->attach($this->headA);
        $this->headA->branches()->attach($this->branchA->id);
        $this->headA->unitKerjas()->attach($this->unitKerjaA->id, ['branch_id' => $this->branchA->id]);

        $this->staffA = User::create([
            'name' => 'Staff Alfa',
            'email' => 'staff@alfa.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->companyA->users()->attach($this->staffA);
        $this->staffA->branches()->attach($this->branchA->id);

        $this->staffB = User::create([
            'name' => 'Staff Beta',
            'email' => 'staff@beta.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->companyB->users()->attach($this->staffB);
        $this->staffB->branches()->attach($this->branchB->id);

        $this->docTypeA = DocumentType::create(['name' => 'Keuangan', 'code' => 'FIN']);
        $retentionA = ArchiveRetentionPolicy::create([
            'company_id' => $this->companyA->id,
            'code' => 'RET-FIN-7',
            'name' => 'Retensi Keuangan 7 Tahun',
            'trigger_event' => 'registration_date',
            'active_retention_years' => 7,
            'inactive_retention_years' => 0,
            'final_disposition' => 'review',
        ]);

        $this->classificationA = ArchiveClassification::create([
            'company_id' => $this->companyA->id,
            'default_retention_policy_id' => $retentionA->id,
            'code' => 'LAP-KEU',
            'name' => 'Laporan Keuangan',
            'confidentiality_level' => 'internal',
            'requires_approval' => false,
        ]);
    }

    protected function createRegisteredArchiveInCompanyA(string $sensitivity = 'internal', ?User $owner = null): ArchiveEntry
    {
        $owner = $owner ?: $this->staffA;

        $doc = Document::create([
            'title' => 'Laporan Neraca Tahunan',
            'document_number' => 'KEU/2026/001',
            'document_type_id' => $this->docTypeA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'owner_id' => $owner->id,
            'visibility' => Document::VISIBILITY_GENERAL,
        ]);

        $filePath = "documents/{$doc->id}/v1.pdf";
        Storage::disk('local')->put($filePath, 'PDF Keuangan Perusahaan Alfa');

        $version = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Financial Statement Alfa 2026</p>',
            'file_path' => $filePath,
            'file_original_name' => 'neraca_2026.pdf',
            'file_mime' => 'application/pdf',
            'author_id' => $owner->id,
            'author_name' => $owner->name,
            'status' => 'active',
        ]);

        $req = $this->registryService->createRequest(
            document: $doc,
            classification: $this->classificationA,
            requester: $owner,
            attributes: [
                'archive_title' => 'Arsip Resmi Laporan Neraca Tahunan',
                'sensitivity_level' => $sensitivity,
            ]
        );

        $this->registryService->submitRequest($req, $owner);
        // Classification doesn't require approval or we approve
        if ($req->requires_approval) {
            $this->registryService->approveRequest($req, $this->adminUser);
        }

        return $this->registryService->finalizeRegistration($req, $this->adminUser);
    }

    public function test_archive_hub_index_page_loads_successfully_for_authenticated_users(): void
    {
        $this->createRegisteredArchiveInCompanyA();

        // Admin visiting archive index
        $adminResponse = $this->actingAs($this->adminUser)->get(route('archives.index'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('Buku Induk Arsip');

        // Staff visiting archive index
        $staffResponse = $this->actingAs($this->staffA)->get(route('archives.index'));
        $staffResponse->assertOk();

        // Staff filtering by company
        $filterResponse = $this->actingAs($this->staffA)->get(route('archives.index', ['company_id' => $this->companyA->id]));
        $filterResponse->assertOk();
    }

    public function test_user_from_company_b_cannot_access_company_a_archive_detail(): void
    {
        $archive = $this->createRegisteredArchiveInCompanyA();

        // Staff from Company B attempting to access Company A's archive
        $response = $this->actingAs($this->staffB)
            ->get(route('archives.show', $archive));

        $response->assertStatus(403);
    }

    public function test_user_from_company_b_cannot_download_company_a_archive_file(): void
    {
        $archive = $this->createRegisteredArchiveInCompanyA();

        $response = $this->actingAs($this->staffB)
            ->get(route('archives.download', $archive));

        $response->assertStatus(403);
    }

    public function test_confidential_archive_is_blocked_from_unauthorized_company_staff(): void
    {
        // Archive created with 'confidential' sensitivity owned by adminUser
        $archive = $this->createRegisteredArchiveInCompanyA('confidential', $this->adminUser);

        // Another staff in the same company who is neither owner nor head/admin
        $otherStaff = User::create([
            'name' => 'Other Staff Alfa',
            'email' => 'other_staff@alfa.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->companyA->users()->attach($otherStaff);
        $otherStaff->branches()->attach($this->branchA->id);

        $response = $this->actingAs($otherStaff)
            ->get(route('archives.show', $archive));

        $response->assertStatus(403);

        // Head of department in company A CAN access
        $headResponse = $this->actingAs($this->headA)
            ->get(route('archives.show', $archive));

        $headResponse->assertStatus(200);
    }

    public function test_metadata_correction_requires_permission_and_mandatory_reason(): void
    {
        $archive = $this->createRegisteredArchiveInCompanyA('internal', $this->staffA);

        // Unauthorized staff cannot correct metadata
        $unauthorizedUser = User::create([
            'name' => 'Junior Staff',
            'email' => 'junior@alfa.test',
            'password' => 'password',
            'system_role' => 'staff',
            'is_active' => true,
        ]);
        $this->companyA->users()->attach($unauthorizedUser);
        $unauthorizedUser->branches()->attach($this->branchA->id);

        $failResponse = $this->actingAs($unauthorizedUser)
            ->patch(route('archives.correct-metadata', $archive), [
                'title' => 'Judul Baru yang Tidak Sah',
                'sensitivity_level' => 'internal',
                'correction_reason' => 'Perbaikan sembarangan',
            ]);

        $failResponse->assertStatus(403);

        // Admin corrects metadata with reason
        $successResponse = $this->actingAs($this->adminUser)
            ->patch(route('archives.correct-metadata', $archive), [
                'title' => 'Arsip Resmi Laporan Neraca Audited 2026',
                'description' => 'Telah dilakukan perbaikan penamaan sesuai hasil audit eksternal',
                'sensitivity_level' => 'internal',
                'storage_location' => 'Lantai 3 Ruang Arsip Lemari A1',
                'correction_reason' => 'Pembaruan judul berdasarkan hasil audit KAP Ernst',
            ]);

        $successResponse->assertRedirect();
        $this->assertEquals('Arsip Resmi Laporan Neraca Audited 2026', $archive->fresh()->title);
        $this->assertEquals('Lantai 3 Ruang Arsip Lemari A1', $archive->fresh()->storage_location);

        // Verify audit trail logged
        $this->assertDatabaseHas('archive_events', [
            'archive_entry_id' => $archive->id,
            'event_type' => \App\Models\ArchiveEvent::EVENT_METADATA_CORRECTED,
            'actor_id' => $this->adminUser->id,
        ]);
    }

    public function test_voiding_archive_requires_admin_or_direktur_and_creates_void_record(): void
    {
        $archive = $this->createRegisteredArchiveInCompanyA('internal', $this->staffA);

        // Staff cannot void
        $staffResponse = $this->actingAs($this->staffA)
            ->post(route('archives.void', $archive), [
                'void_reason' => 'Ingin dibatalkan karena salah upload',
            ]);
        $staffResponse->assertStatus(403);

        // Admin voids the archive
        $adminResponse = $this->actingAs($this->adminUser)
            ->post(route('archives.void', $archive), [
                'void_reason' => 'Dokumen terbukti keliru dan telah digantikan oleh surat keputusan terbaru',
            ]);

        $adminResponse->assertRedirect();
        $freshArchive = $archive->fresh();
        $this->assertEquals(ArchiveEntry::STATUS_VOIDED, $freshArchive->status);

        // Verify void record created
        $this->assertDatabaseHas('archive_void_records', [
            'archive_entry_id' => $archive->id,
            'voided_by' => $this->adminUser->id,
            'void_reason' => 'Dokumen terbukti keliru dan telah digantikan oleh surat keputusan terbaru',
        ]);

        // Verify audit event logged
        $this->assertDatabaseHas('archive_events', [
            'archive_entry_id' => $archive->id,
            'event_type' => \App\Models\ArchiveEvent::EVENT_ARCHIVE_VOIDED,
            'actor_id' => $this->adminUser->id,
        ]);
    }
}
