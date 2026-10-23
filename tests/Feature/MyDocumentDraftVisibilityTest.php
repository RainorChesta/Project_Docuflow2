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

class MyDocumentDraftVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_created_and_saved_as_draft_appears_on_my_documents_page(): void
    {
        $company = Company::create(['name' => 'PT Test Vis', 'code' => 'TTV']);
        $branch = Branch::create([
            'company_id' => $company->id,
            'name' => 'PUSAT',
            'is_pusat' => true,
            'code' => 'PST',
        ]);
        $unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'Unit Medis',
            'kode_unit_kerja' => 'UM',
        ]);

        $user = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $unitKerja->id,
        ]);
        $user->companies()->attach($company->id);
        $user->unitKerjas()->attach($unitKerja->id);

        $docType = DocumentType::create([
            'name' => 'Memo Internal',
            'code' => 'MEMO',
            'category' => 'naskah_dinas',
        ]);

        // 1. Submit create document with active company context
        $response = $this->actingAs($user)
            ->withSession([
                'active_company_id' => $company->id,
                'locale' => 'id',
            ])
            ->post(route('documents.store'), [
                'title' => 'Dokumen Draft Saya Test',
                'document_type_id' => $docType->id,
                'unit_kerja_id' => $unitKerja->id,
            ]);

        $response->assertRedirect();
        $doc = Document::where('title', 'Dokumen Draft Saya Test')->first();
        $this->assertNotNull($doc);
        $this->assertEquals($user->id, $doc->owner_id);
        $this->assertEquals($company->id, $doc->company_id);
        $this->assertEquals($branch->id, $doc->branch_id);

        // 2. Simulate Save as Draft from Editor
        $draftResponse = $this->actingAs($user)
            ->withSession([
                'active_company_id' => $company->id,
                'locale' => 'id',
            ])
            ->postJson(route('documents.save-draft-editor', $doc));

        $draftResponse->assertOk();
        $draftResponse->assertJson(['success' => true]);

        // 3. Visit Dokumen Saya (type=mine)
        $mineResponse = $this->actingAs($user)
            ->withSession([
                'active_company_id' => $company->id,
                'locale' => 'id',
            ])
            ->get(route('documents.index', ['type' => 'mine']));

        $mineResponse->assertOk();
        $mineResponse->assertSee('Dokumen Draft Saya Test');
        $mineResponse->assertSee('Draf');

        // 4. Visit Dokumen Saya with status=draft filter
        $filterResponse = $this->actingAs($user)
            ->withSession([
                'active_company_id' => $company->id,
                'locale' => 'id',
            ])
            ->get(route('documents.index', ['type' => 'mine', 'status' => 'draft']));

        $filterResponse->assertOk();
        $filterResponse->assertSee('Dokumen Draft Saya Test');
    }

    public function test_unassigned_branch_draft_document_appears_on_my_documents_page(): void
    {
        $company = Company::create(['name' => 'PT Test Unassigned', 'code' => 'TTU']);
        $unitKerja = UnitKerja::create([
            'nama_unit_kerja' => 'Unit Umum',
            'kode_unit_kerja' => 'UU',
        ]);

        $user = User::factory()->create([
            'system_role' => 'user',
            'unit_kerja_id' => $unitKerja->id,
        ]);
        $user->companies()->attach($company->id);

        $docType = DocumentType::create([
            'name' => 'Nota Dinas',
            'code' => 'NOT',
            'category' => 'naskah_dinas',
        ]);

        // Manually create document with null company_id and null branch_id (legacy or unassigned draft)
        $doc = Document::create([
            'title' => 'Dokumen Tanpa Cabang Draft',
            'document_number' => '999/NOT/PST/X/2026',
            'owner_id' => $user->id,
            'document_type_id' => $docType->id,
            'unit_kerja_id' => $unitKerja->id,
            'company_id' => null,
            'branch_id' => null,
            'is_expired' => false,
        ]);

        $doc->versions()->create([
            'version_number' => 1,
            'content' => '',
            'author_id' => $user->id,
            'author_name' => $user->name,
            'status' => 'draft',
        ]);

        // Visit Dokumen Saya (type=mine)
        $mineResponse = $this->actingAs($user)
            ->withSession([
                'active_company_id' => $company->id,
                'locale' => 'id',
            ])
            ->get(route('documents.index', ['type' => 'mine']));

        $mineResponse->assertOk();
        $mineResponse->assertSee('Dokumen Tanpa Cabang Draft');
        $mineResponse->assertSee('Draf');
    }
}
