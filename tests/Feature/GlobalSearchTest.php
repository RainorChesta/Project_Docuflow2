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
}
