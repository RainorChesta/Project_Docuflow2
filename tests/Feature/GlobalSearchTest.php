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

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_search_documents_by_title_and_number(): void
    {
        $company = Company::create(['name' => 'PT Makmur', 'code' => 'MKM']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'IT Dept', 'code' => 'IT']);
        $docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);

        $user = User::factory()->create(['division_id' => $division->id]);
        $user->companies()->sync([$company->id]);
        $user->branches()->sync([$branch->id]);

        $doc1 = Document::create([
            'title' => 'Panduan Keamanan Sistem',
            'document_number' => '001/SK/IT/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        $doc2 = Document::create([
            'title' => 'Laporan Keuangan Tahunan',
            'document_number' => '002/SK/FIN/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);

        // Search by title
        $response = $this->actingAs($user)->getJson(route('search', ['q' => 'Keamanan']));
        $response->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonFragment(['title' => 'Panduan Keamanan Sistem']);

        // Search by document number
        $responseNumber = $this->actingAs($user)->getJson(route('search', ['q' => '002/SK']));
        $responseNumber->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonFragment(['title' => 'Laporan Keuangan Tahunan']);
    }

    public function test_user_can_filter_search_by_visibility_and_document_type(): void
    {
        $company = Company::create(['name' => 'PT Makmur', 'code' => 'MKM']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'is_pusat' => true]);
        $division = Division::create(['name' => 'IT Dept', 'code' => 'IT']);
        $docTypeSK = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);
        $docTypeSOP = DocumentType::create(['name' => 'Standard Operating Procedure', 'code' => 'SOP']);

        $user = User::factory()->create(['division_id' => $division->id]);
        $user->companies()->sync([$company->id]);
        $user->branches()->sync([$branch->id]);

        Document::create([
            'title' => 'SOP Backup Database',
            'document_number' => '001/SOP/IT/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'document_type_id' => $docTypeSOP->id,
            'owner_id' => $user->id,
            'visibility' => 'division',
        ]);

        Document::create([
            'title' => 'SK Kebijakan Umum',
            'document_number' => '002/SK/IT/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'document_type_id' => $docTypeSK->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);

        // Filter by visibility 'general'
        $responseGeneral = $this->actingAs($user)->getJson(route('search', ['visibility' => 'general']));
        $responseGeneral->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonFragment(['title' => 'SK Kebijakan Umum']);

        // Filter by document type
        $responseSOP = $this->actingAs($user)->getJson(route('search', ['document_type_id' => $docTypeSOP->id]));
        $responseSOP->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonFragment(['title' => 'SOP Backup Database']);
    }

    public function test_user_can_find_all_documents_from_accessible_company_and_branches(): void
    {
        $companyA = Company::create(['name' => 'PT Alfa', 'code' => 'ALF']);
        $branchA1 = Branch::create(['company_id' => $companyA->id, 'name' => 'Alfa Jakarta', 'is_pusat' => true]);
        $branchA2 = Branch::create(['company_id' => $companyA->id, 'name' => 'Alfa Bandung', 'is_pusat' => false]);

        $companyB = Company::create(['name' => 'PT Beta', 'code' => 'BET']);
        $branchB1 = Branch::create(['company_id' => $companyB->id, 'name' => 'Beta Surabaya', 'is_pusat' => true]);

        $division = Division::create(['name' => 'Finance', 'code' => 'FIN']);
        $docType = DocumentType::create(['name' => 'Regulasi Internal', 'code' => 'REG']);

        $user = User::factory()->create(['division_id' => $division->id]);
        // User assigned to Company A (and Branch A1)
        $user->companies()->sync([$companyA->id]);
        $user->branches()->sync([$branchA1->id]);

        // Doc in branch A1
        Document::create([
            'title' => 'Dokumen Alfa Jakarta',
            'document_number' => '001/ALF/JKT',
            'company_id' => $companyA->id,
            'branch_id' => $branchA1->id,
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);

        // Doc in branch A2 (same company, user has company access)
        $docA2 = Document::create([
            'title' => 'Dokumen Alfa Bandung',
            'document_number' => '002/ALF/BDG',
            'company_id' => $companyA->id,
            'branch_id' => $branchA2->id,
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);

        // Doc in Company B (unassigned)
        $otherUser = User::factory()->create(['division_id' => $division->id]);
        Document::create([
            'title' => 'Dokumen Beta Surabaya',
            'document_number' => '003/BET/SBY',
            'company_id' => $companyB->id,
            'branch_id' => $branchB1->id,
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'owner_id' => $otherUser->id,
            'visibility' => 'general',
        ]);

        // User should be able to find documents from Company A across branches
        $responseAlfa = $this->actingAs($user)->getJson(route('search', ['q' => 'Alfa']));
        $responseAlfa->assertOk()
            ->assertJsonCount(2, 'results')
            ->assertJsonFragment(['title' => 'Dokumen Alfa Jakarta'])
            ->assertJsonFragment(['title' => 'Dokumen Alfa Bandung']);

        // User should NOT see documents from Company B
        $responseBeta = $this->actingAs($user)->getJson(route('search', ['q' => 'Surabaya']));
        $responseBeta->assertOk()
            ->assertJsonCount(0, 'results');
    }

    public function test_user_can_search_by_branch_and_company_name(): void
    {
        $company = Company::create(['name' => 'PT Nusantara Sentosa', 'code' => 'NTS']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Yogyakarta', 'is_pusat' => true]);
        $division = Division::create(['name' => 'Legal', 'code' => 'LEG']);
        $docType = DocumentType::create(['name' => 'Nota Dinas', 'code' => 'ND']);

        $user = User::factory()->create(['division_id' => $division->id]);
        $user->companies()->sync([$company->id]);
        $user->branches()->sync([$branch->id]);

        Document::create([
            'title' => 'Pengumuman Libur Nasional',
            'document_number' => '010/ND/LEG/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'division_id' => $division->id,
            'document_type_id' => $docType->id,
            'owner_id' => $user->id,
            'visibility' => 'general',
        ]);

        // Search by branch name
        $responseBranch = $this->actingAs($user)->getJson(route('search', ['q' => 'Yogyakarta']));
        $responseBranch->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonFragment(['title' => 'Pengumuman Libur Nasional']);

        // Search by company name
        $responseCompany = $this->actingAs($user)->getJson(route('search', ['q' => 'Nusantara']));
        $responseCompany->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonFragment(['title' => 'Pengumuman Libur Nasional']);
    }
}
