<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAndBranchTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_company_automatically_creates_pusat_branch(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/companies', [
            'name' => 'PT Jaya Bersama',
            'code' => 'JBM',
        ]);

        $response->assertRedirect('/admin/companies');
        $this->assertDatabaseHas('companies', [
            'name' => 'PT Jaya Bersama',
            'code' => 'JBM',
        ]);

        $company = Company::where('code', 'JBM')->first();
        $this->assertNotNull($company);
        $this->assertDatabaseHas('branches', [
            'company_id' => $company->id,
            'name' => 'Pusat',
            'is_pusat' => 1,
            'code' => null,
        ]);
    }

    public function test_branch_effective_code_inherits_company_code_for_pusat(): void
    {
        $company = Company::create(['name' => 'PT Makmur', 'code' => 'MKM']);
        $pusat = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $surabaya = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Surabaya', 'is_pusat' => false, 'code' => 'SBY']);

        $this->assertSame('MKM', $pusat->effective_code);
        $this->assertSame('SBY', $surabaya->effective_code);
    }

    public function test_document_numbering_incorporates_branch_code(): void
    {
        $company = Company::create(['name' => 'PT Jaya', 'code' => 'JBM']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Surabaya', 'is_pusat' => false, 'code' => 'SBY']);
        $division = Division::create(['code' => 'IT', 'name' => 'Information Tech']);
        $docType = DocumentType::create(['code' => 'S.KEL', 'name' => 'Surat Keluar']);

        $service = app(DocumentService::class);
        $number = $service->generateId($division, $docType, $branch);

        $this->assertStringContainsString('/IT/SBY/', $number);
    }

    public function test_director_role_has_read_only_access(): void
    {
        $director = User::factory()->create(['system_role' => 'direktur']);
        $company = Company::create(['name' => 'PT Jaya', 'code' => 'JBM']);

        // Director can browse
        $this->actingAs($director)->get('/director/documents')->assertOk();

        // Director cannot create master data
        $this->actingAs($director)->post('/admin/companies', [
            'name' => 'New PT',
            'code' => 'NPT',
        ])->assertSessionHas('error');
    }
}
