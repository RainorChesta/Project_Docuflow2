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

    public function test_director_cannot_create_master_data(): void
    {
        $director = User::factory()->create(['system_role' => 'direktur']);
        $company = Company::create(['name' => 'PT Jaya', 'code' => 'JBM']);
        $director->companies()->sync([$company->id]);

        // Director can browse
        $this->actingAs($director)->get('/director/documents')->assertOk();

        // Director cannot create master data (admin only)
        $this->actingAs($director)->post('/admin/companies', [
            'name' => 'New PT',
            'code' => 'NPT',
        ])->assertForbidden();
    }

    public function test_director_can_create_edit_and_approve_document(): void
    {
        $company = Company::create(['name' => 'PT Jaya', 'code' => 'JBM']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['code' => 'IT', 'name' => 'Information Technology']);
        $docType = DocumentType::create(['code' => 'S.KEL', 'name' => 'Surat Keluar']);

        $director = User::factory()->create([
            'system_role' => 'direktur',
            'nip' => null,
            'division_id' => null,
        ]);
        $director->companies()->sync([$company->id]);
        $director->branches()->sync([$branch->id]);

        // 1. Director can create document
        $response = $this->actingAs($director)->post('/documents', [
            'title' => 'Director Document',
            'document_type_id' => $docType->id,
            'division_id' => $division->id,
            'branch_id' => $branch->id,
        ]);

        $document = Document::where('title', 'Director Document')->first();
        $this->assertNotNull($document);
        $response->assertRedirect(route('documents.edit', $document));
        $response->assertRedirect(route('documents.edit', $document));

        // 2. Director can edit document
        $editResponse = $this->actingAs($director)->put("/documents/{$document->id}/save", [
            'content' => 'Updated content by director',
        ]);
        $editResponse->assertRedirect(route('documents.show', $document));

        $pendingVersion = $document->versions()->where('status', 'pending')->first();
        $this->assertNotNull($pendingVersion);

        // 3. Director can approve pending document version
        $approveResponse = $this->actingAs($director)->post("/documents/{$document->id}/versions/{$pendingVersion->id}/approve", [
            'notes' => 'Approved by director',
        ]);
        $approveResponse->assertRedirect(route('approvals.index'));

        $pendingVersion->refresh();
        $this->assertSame('active', $pendingVersion->status);
    }

    public function test_admin_can_create_director_with_company_assignments_and_null_nip_division(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $branchA1 = Branch::create(['company_id' => $companyA->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $companyB = Company::create(['name' => 'PT Beta', 'code' => 'BET']);
        $division = Division::create(['code' => 'HR', 'name' => 'Human Resources']);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Pak Direktur',
            'email' => 'direktur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nip' => '123456789', // Should be cleared to null
            'division_id' => $division->id, // Should be cleared to null
            'system_role' => 'direktur',
            'is_active' => '1',
            'company_ids' => [$companyA->id],
            'branch_ids' => [$branchA1->id],
        ]);

        $response->assertRedirect('/admin/users');
        
        $director = User::where('email', 'direktur@example.com')->first();
        $this->assertNotNull($director);
        $this->assertSame('direktur', $director->system_role);
        $this->assertNull($director->nip);
        $this->assertNull($director->division_id);
        $this->assertTrue($director->companies->contains($companyA->id));
        $this->assertFalse($director->companies->contains($companyB->id));
        $this->assertTrue($director->branches->contains($branchA1->id));
    }

    public function test_admin_can_update_director_company_assignments(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $division = Division::create(['code' => 'ACC', 'name' => 'Accounting']);
        $director = User::factory()->create([
            'system_role' => 'direktur',
            'nip' => null,
            'division_id' => null,
        ]);

        $companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $companyB = Company::create(['name' => 'PT Beta', 'code' => 'BET']);
        $branchB1 = Branch::create(['company_id' => $companyB->id, 'name' => 'Pusat', 'is_pusat' => true]);

        $response = $this->actingAs($admin)->put("/admin/users/{$director->id}", [
            'name' => 'Pak Direktur Updated',
            'email' => $director->email,
            'nip' => '999999', // Should be ignored/null
            'division_id' => $division->id, // Should be ignored/null for direktur
            'system_role' => 'direktur',
            'is_active' => '1',
            'company_ids' => [$companyB->id],
            'branch_ids' => [$branchB1->id],
        ]);

        $response->assertRedirect('/admin/users');

        $director->refresh();
        $this->assertSame('Pak Direktur Updated', $director->name);
        $this->assertNull($director->nip);
        $this->assertNull($director->division_id);
        $this->assertFalse($director->companies->contains($companyA->id));
        $this->assertTrue($director->companies->contains($companyB->id));
        $this->assertTrue($director->branches->contains($branchB1->id));
    }

    public function test_admin_user_creation_automatically_assigns_all_companies_and_branches(): void
    {
        $superAdmin = User::factory()->create(['system_role' => 'admin']);
        $companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $companyB = Company::create(['name' => 'PT Beta', 'code' => 'BET']);
        $branchB = Branch::create(['company_id' => $companyB->id, 'name' => 'Cabang', 'is_pusat' => false]);

        $response = $this->actingAs($superAdmin)->post('/admin/users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'system_role' => 'admin',
            'is_active' => '1',
            // No company_ids or branch_ids supplied
        ]);

        $response->assertRedirect('/admin/users');

        $newAdmin = User::where('email', 'newadmin@example.com')->first();
        $this->assertNotNull($newAdmin);
        $this->assertSame('admin', $newAdmin->system_role);
        $this->assertTrue($newAdmin->companies->contains($companyA->id));
        $this->assertTrue($newAdmin->companies->contains($companyB->id));
        $this->assertTrue($newAdmin->branches->contains($branchA->id));
        $this->assertTrue($newAdmin->branches->contains($branchB->id));
    }

    public function test_admin_can_access_documents_from_all_companies_and_branches(): void
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        $companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $divisionA = Division::create(['code' => 'HR', 'name' => 'HR']);
        $docType = DocumentType::create(['code' => 'S.KEL', 'name' => 'Surat Keluar']);

        $userA = User::factory()->create(['division_id' => $divisionA->id]);
        $userA->companies()->sync([$companyA->id]);
        $userA->branches()->sync([$branchA->id]);

        $documentService = app(\App\Services\DocumentService::class);
        $doc = $documentService->create([
            'title' => 'PT Alfa Internal Doc',
            'document_type_id' => $docType->id,
            'division_id' => $divisionA->id,
            'branch_id' => $branchA->id,
            'company_id' => $companyA->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ], $userA->id);

        $doc->versions()->first()->update(['status' => 'active']);

        // Admin can view document show page
        $this->actingAs($admin)->get(route('documents.show', $doc))->assertOk();

        // Admin can view Dokumen Divisi tab and see docs from all divisions
        $response = $this->actingAs($admin)->get(route('documents.index', ['type' => 'division']))->assertOk();
        $response->assertSee('PT Alfa Internal Doc');

        // Admin can view director accordion with all companies
        $dirResponse = $this->actingAs($admin)->get(route('director.documents.index'))->assertOk();
        $dirResponse->assertSee('PT Alfa');
    }

    public function test_user_cannot_access_documents_from_other_branch_or_company(): void
    {
        $companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $branchA = Branch::create(['company_id' => $companyA->id, 'name' => 'Branch Alfa', 'is_pusat' => true]);
        $companyB = Company::create(['name' => 'PT Beta', 'code' => 'BET']);
        $branchB = Branch::create(['company_id' => $companyB->id, 'name' => 'Branch Beta', 'is_pusat' => true]);

        $division = Division::create(['code' => 'HR', 'name' => 'Human Resources']);
        $docType = DocumentType::create(['code' => 'S.KEL', 'name' => 'Surat Keluar']);

        $userAlfa = User::factory()->create(['division_id' => $division->id]);
        $userAlfa->companies()->sync([$companyA->id]);
        $userAlfa->branches()->sync([$branchA->id]);

        $userBeta = User::factory()->create(['division_id' => $division->id]);
        $userBeta->companies()->sync([$companyB->id]);
        $userBeta->branches()->sync([$branchB->id]);

        $documentService = app(\App\Services\DocumentService::class);

        // Alfa doc
        $docAlfa = $documentService->create([
            'title' => 'Dokumen Khusus Alfa',
            'document_type_id' => $docType->id,
            'division_id' => $division->id,
            'branch_id' => $branchA->id,
            'company_id' => $companyA->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ], $userAlfa->id);
        $docAlfa->versions()->first()->update(['status' => 'active']);

        // Beta doc
        $docBeta = $documentService->create([
            'title' => 'Dokumen Rahasia Beta',
            'document_type_id' => $docType->id,
            'division_id' => $division->id,
            'branch_id' => $branchB->id,
            'company_id' => $companyB->id,
            'visibility' => Document::VISIBILITY_DIVISION,
        ], $userBeta->id);
        $docBeta->versions()->first()->update(['status' => 'active']);

        // 1. User Alfa cannot view or edit Beta doc directly
        $this->actingAs($userAlfa)->get(route('documents.show', $docBeta))->assertForbidden();
        $this->actingAs($userAlfa)->get(route('documents.edit', $docBeta))->assertForbidden();

        // 2. User Alfa can view own doc in Alfa
        $this->actingAs($userAlfa)->get(route('documents.show', $docAlfa))->assertOk();

        // 3. User Alfa dashboard recent & division list does NOT include Beta doc
        $dashResponse = $this->actingAs($userAlfa)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('Dokumen Khusus Alfa');
        $dashResponse->assertDontSee('Dokumen Rahasia Beta');

        // 4. User Alfa document list does not contain Beta doc
        $docListResponse = $this->actingAs($userAlfa)->get(route('documents.index', ['type' => 'division']));
        $docListResponse->assertOk();
        $docListResponse->assertSee('Dokumen Khusus Alfa');
        $docListResponse->assertDontSee('Dokumen Rahasia Beta');
    }

    public function test_director_documents_page_hides_branch_switcher_dropdown_for_director(): void
    {
        $company = Company::create(['name' => 'PT Jaya Harmoni', 'code' => 'JHM']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        
        $director = User::factory()->create([
            'system_role' => 'direktur',
            'nip' => null,
            'division_id' => null,
        ]);
        $director->companies()->sync([$company->id]);
        $director->branches()->sync([$branch->id]);

        $admin = User::factory()->create(['system_role' => 'admin']);
        $admin->companies()->sync([$company->id]);
        $admin->branches()->sync([$branch->id]);

        // Director on director.documents.index: company & branch switcher is hidden
        $directorResp = $this->actingAs($director)->get(route('director.documents.index'));
        $directorResp->assertOk();
        $directorResp->assertDontSee('name="company_id"', false);
        $directorResp->assertDontSee('name="branch_id"', false);

        // Admin on director.documents.index: switcher is visible
        $adminResp = $this->actingAs($admin)->get(route('director.documents.index'));
        $adminResp->assertOk();
        $adminResp->assertSee('name="company_id"', false);
        $adminResp->assertSee('name="branch_id"', false);
    }
}
