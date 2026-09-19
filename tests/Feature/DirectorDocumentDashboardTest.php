<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectorDocumentDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $director;
    protected User $admin;
    protected User $regularUser;
    protected Company $company;
    protected Branch $pusatBranch;
    protected Branch $cabangBranch;
    protected UnitKerja $hrdUnit;
    protected UnitKerja $financeUnit;
    protected DocumentType $policyType;
    protected DocumentType $letterType;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('id');

        $this->company = Company::create(['name' => 'Cahaya Medika Healthcare', 'code' => 'CMH']);
        $this->pusatBranch = Branch::create(['company_id' => $this->company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->cabangBranch = Branch::create(['company_id' => $this->company->id, 'name' => 'Klinik Manyar', 'is_pusat' => false, 'code' => 'KMY']);

        $this->hrdUnit = UnitKerja::create(['nama_unit_kerja' => 'Human Resources Department', 'kode_unit_kerja' => 'HRD']);
        $this->financeUnit = UnitKerja::create(['nama_unit_kerja' => 'Finance & Accounting', 'kode_unit_kerja' => 'FIN']);

        $this->policyType = DocumentType::create(['name' => 'Policy', 'code' => 'POL', 'category' => 'akreditasi']);
        $this->letterType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK', 'category' => 'akreditasi']);

        $this->director = User::factory()->create([
            'system_role' => 'direktur',
            'nip' => null,
            'unit_kerja_id' => null,
        ]);
        $this->director->companies()->sync([$this->company->id]);
        $this->director->branches()->sync([$this->pusatBranch->id, $this->cabangBranch->id]);

        $this->admin = User::factory()->create(['system_role' => 'admin']);
        $this->regularUser = User::factory()->create(['system_role' => 'user', 'unit_kerja_id' => $this->hrdUnit->id]);
    }

    public function test_director_can_access_dashboard_and_see_company_folders_at_root(): void
    {
        $response = $this->actingAs($this->director)->get(route('director.documents.index'));

        $response->assertOk();
        $response->assertSee(__('Semua Perusahaan'));
        $response->assertSee($this->company->name);
        $response->assertSee('CMH');
        // Filters must not appear on root company folder level
        $response->assertDontSee('placeholder="' . __('Search documents...') . '"', false);
        $response->assertDontSee('name="filter_unit_kerja_id"', false);
    }

    public function test_regular_user_cannot_access_director_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->get(route('director.documents.index'));
        $response->assertForbidden();
    }

    public function test_director_can_drill_into_company_and_see_branch_folders(): void
    {
        $response = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
        ]));

        $response->assertOk();
        $response->assertSee('PUSAT');
        $response->assertSee($this->cabangBranch->name);
        $response->assertSee(__('Folder Cabang'));
        // Filters must not appear on company level
        $response->assertDontSee('placeholder="' . __('Search documents...') . '"', false);
        $response->assertDontSee('name="filter_unit_kerja_id"', false);
    }

    public function test_director_can_drill_into_branch_and_see_unit_kerja_folders_without_documents_by_default(): void
    {
        $doc = Document::create([
            'title' => 'SOP Pelayanan Manyar',
            'document_number' => '001/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $response = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
        ]));

        $response->assertOk();
        // Search controls for unit kerja folders are present
        $response->assertSee('placeholder="' . __('Cari folder unit kerja...') . '"', false);
        // Documents must NOT be shown by default before search
        $response->assertDontSee('SOP Pelayanan Manyar');
        $response->assertDontSee('001/POL/KMY/2026');
    }

    public function test_director_can_drill_into_unit_kerja_level(): void
    {
        $doc = Document::create([
            'title' => 'HRD Handbook 2026',
            'document_number' => '002/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $response = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
        ]));

        $response->assertOk();
        $response->assertSee('Human Resources Department');
        $response->assertSee('HRD Handbook 2026');
    }

    public function test_director_can_search_and_filter_documents(): void
    {
        $authorUser = User::factory()->create(['name' => 'Dokter Spesi', 'unit_kerja_id' => $this->hrdUnit->id]);
        $authorUser->companies()->sync([$this->company->id]);
        $authorUser->branches()->sync([$this->cabangBranch->id]);

        $doc1 = Document::create([
            'title' => 'Standard Operating Procedure IGD',
            'document_number' => '010/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $authorUser->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $doc2 = Document::create([
            'title' => 'Laporan Anggaran Keuangan',
            'document_number' => '020/SK/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->financeUnit->id,
            'document_type_id' => $this->letterType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        // Search by keyword
        $searchResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'search' => 'IGD',
        ]));
        $searchResp->assertOk();
        $searchResp->assertSee('Standard Operating Procedure IGD');
        $searchResp->assertDontSee('Laporan Anggaran Keuangan');

        // Filter by Document Type
        $typeResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'document_type_id' => $this->letterType->id,
        ]));
        $typeResp->assertOk();
        $typeResp->assertSee('Laporan Anggaran Keuangan');
        $typeResp->assertDontSee('Standard Operating Procedure IGD');

        // Filter by Creator
        $creatorResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'owner_id' => $authorUser->id,
        ]));
        $creatorResp->assertOk();
        $creatorResp->assertSee('Standard Operating Procedure IGD');
        $creatorResp->assertDontSee('Laporan Anggaran Keuangan');
    }

    public function test_director_can_switch_between_grid_and_list_view_modes(): void
    {
        $doc = Document::create([
            'title' => 'Sample View Document',
            'document_number' => '099/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        // List view
        $listResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'search' => 'Sample',
            'view_mode' => 'list',
        ]));
        $listResp->assertOk();
        $listResp->assertSee('table-zebra', false);
        $listResp->assertSee('Sample View Document');

        // Grid view
        $gridResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'search' => 'Sample',
            'view_mode' => 'grid',
        ]));
        $gridResp->assertOk();
        $gridResp->assertSee('Sample View Document');
    }

    public function test_director_can_switch_folder_view_mode_for_unit_kerjas(): void
    {
        // 1. Unit Kerja Folders in List view
        $listResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'view_mode' => 'list',
        ]));
        $listResp->assertOk();
        $listResp->assertSee('Nama Unit Kerja');
        $listResp->assertSee('Human Resources Department');
        $listResp->assertSee('HRD');

        // 2. Unit Kerja Folders in Grid view
        $gridResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'view_mode' => 'grid',
        ]));
        $gridResp->assertOk();
        $gridResp->assertSee('Human Resources Department');
        $gridResp->assertSee('HRD');
    }

    public function test_director_can_search_documents_globally_from_root_across_all_companies_and_branches(): void
    {
        $docInPusat = Document::create([
            'title' => 'Peraturan Direksi Pusat 2026',
            'document_number' => '001/PST/DIR/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        $docInManyar = Document::create([
            'title' => 'Panduan Pasien Manyar',
            'document_number' => '002/KMY/MED/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
        ]);

        // Global search at Root level (no company_id, no branch_id)
        $resp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'search' => 'Peraturan Direksi',
        ]));

        $resp->assertOk();
        $resp->assertSee('Peraturan Direksi Pusat 2026');
        $resp->assertSee('001/PST/DIR/2026');
        $resp->assertDontSee('Panduan Pasien Manyar');
    }

    public function test_director_can_filter_documents_globally_by_status(): void
    {
        $activeDoc = Document::create([
            'title' => 'Dokumen Aktif Pusat',
            'document_number' => '091/AKT/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->pusatBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
            'is_expired' => false,
        ]);
        $version = \App\Models\DocumentVersion::create([
            'document_id' => $activeDoc->id,
            'version_number' => 1,
            'status' => 'active',
            'author_name' => 'Direktur Utama',
            'content' => '<p>Active document content</p>',
            'created_by_id' => $this->director->id,
        ]);
        $activeDoc->update(['current_version_id' => $version->id]);

        $expiredDoc = Document::create([
            'title' => 'Dokumen Kadaluarsa Manyar',
            'document_number' => '092/EXP/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'unit_kerja_id' => $this->hrdUnit->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_UNIT_KERJA,
            'is_expired' => true,
        ]);

        $respActive = $this->actingAs($this->director)->get(route('director.documents.index', [
            'status' => 'active',
        ]));
        $respActive->assertOk();
        $respActive->assertSee('Dokumen Aktif Pusat');
        $respActive->assertDontSee('Dokumen Kadaluarsa Manyar');

        $respExpired = $this->actingAs($this->director)->get(route('director.documents.index', [
            'status' => 'expired',
        ]));
        $respExpired->assertOk();
        $respExpired->assertSee('Dokumen Kadaluarsa Manyar');
        $respExpired->assertDontSee('Dokumen Aktif Pusat');
    }
}
