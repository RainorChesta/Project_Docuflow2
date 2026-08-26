<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
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
    protected Division $hrdDiv;
    protected Division $financeDiv;
    protected DocumentType $policyType;
    protected DocumentType $letterType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Cahaya Medika Healthcare', 'code' => 'CMH']);
        $this->pusatBranch = Branch::create(['company_id' => $this->company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $this->cabangBranch = Branch::create(['company_id' => $this->company->id, 'name' => 'Klinik Manyar', 'is_pusat' => false, 'code' => 'KMY']);

        $this->hrdDiv = Division::create(['name' => 'Human Resources Department', 'code' => 'HRD']);
        $this->financeDiv = Division::create(['name' => 'Finance & Accounting', 'code' => 'FIN']);

        $this->policyType = DocumentType::create(['name' => 'Policy', 'code' => 'POL']);
        $this->letterType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);

        $this->director = User::factory()->create([
            'system_role' => 'direktur',
            'nip' => null,
            'division_id' => null,
        ]);
        $this->director->companies()->sync([$this->company->id]);
        $this->director->branches()->sync([$this->pusatBranch->id, $this->cabangBranch->id]);

        $this->admin = User::factory()->create(['system_role' => 'admin']);
        $this->regularUser = User::factory()->create(['system_role' => 'karyawan', 'division_id' => $this->hrdDiv->id]);
    }

    public function test_director_can_access_dashboard_and_see_company_folders_at_root(): void
    {
        $response = $this->actingAs($this->director)->get(route('director.documents.index'));

        $response->assertOk();
        $response->assertSee('Semua Perusahaan');
        $response->assertSee('Cahaya Medika Healthcare');
        $response->assertSee('CMH');
        // Filters must not appear on root company folder level
        $response->assertDontSee('placeholder="Search documents..."', false);
        $response->assertDontSee('name="filter_division_id"', false);
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
        $response->assertSee('Pusat');
        $response->assertSee('Klinik Manyar');
        $response->assertSee('Folder Cabang');
        // Filters must not appear on company level
        $response->assertDontSee('placeholder="Search documents..."', false);
        $response->assertDontSee('name="filter_division_id"', false);
    }

    public function test_director_can_drill_into_branch_and_see_division_folders_without_documents_by_default(): void
    {
        $doc = Document::create([
            'title' => 'SOP Pelayanan Manyar',
            'document_number' => '001/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'division_id' => $this->hrdDiv->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        $response = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
        ]));

        $response->assertOk();
        $response->assertSee('Folder Divisi');
        $response->assertSee('HRD');
        // Search & Filter controls are present
        $response->assertSee('placeholder="Search documents..."', false);
        $response->assertSee('name="filter_division_id"', false);
        // Documents must NOT be shown by default before search
        $response->assertDontSee('SOP Pelayanan Manyar');
        $response->assertDontSee('001/POL/KMY/2026');
    }

    public function test_director_can_drill_into_division_level(): void
    {
        $doc = Document::create([
            'title' => 'HRD Handbook 2026',
            'document_number' => '002/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'division_id' => $this->hrdDiv->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        $response = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'division_id' => $this->hrdDiv->id,
        ]));

        $response->assertOk();
        $response->assertSee('Human Resources Department');
        $response->assertSee('HRD Handbook 2026');
    }

    public function test_director_can_search_and_filter_documents(): void
    {
        $authorUser = User::factory()->create(['name' => 'Dokter Spesi']);
        $this->company->users()->attach($authorUser);
        $this->cabangBranch->users()->attach($authorUser);

        $doc1 = Document::create([
            'title' => 'Standard Operating Procedure IGD',
            'document_number' => '010/POL/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'division_id' => $this->hrdDiv->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $authorUser->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        $doc2 = Document::create([
            'title' => 'Laporan Anggaran Keuangan',
            'document_number' => '020/SK/KMY/2026',
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
            'division_id' => $this->financeDiv->id,
            'document_type_id' => $this->letterType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_DIVISION,
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
            'division_id' => $this->hrdDiv->id,
            'document_type_id' => $this->policyType->id,
            'owner_id' => $this->director->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ]);

        // List view
        $listResp = $this->actingAs($this->director)->get(route('director.documents.index', [
            'company_id' => $this->company->id,
            'branch_id' => $this->cabangBranch->id,
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
            'search' => 'Sample',
            'view_mode' => 'grid',
        ]));
        $gridResp->assertOk();
        $gridResp->assertSee('Sample View Document');
    }
}
