<?php

namespace Tests\Feature;

use App\Models\DocumentType;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDocumentTypeTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = User::factory()->create(['system_role' => 'admin']);
        Signature::create([
            'user_id' => $admin->id,
            'file_path' => 'signatures/admin.png',
            'type' => 'original',
            'created_via' => 'canvas',
        ]);
        return $admin;
    }

    public function test_admin_can_view_and_search_document_types(): void
    {
        $admin = $this->createAdmin();

        DocumentType::create(['code' => 'SOP', 'name' => 'Standard Operating Procedure', 'category' => 'akreditasi']);
        DocumentType::create(['code' => 'SK', 'name' => 'Surat Keputusan', 'category' => 'akreditasi']);
        DocumentType::create(['code' => 'ND', 'name' => 'Nota Dinas', 'category' => 'naskah_dinas']);

        // 1. View all
        $response = $this->actingAs($admin)->get(route('admin.document-types.index'));
        $response->assertStatus(200);
        $types = $response->viewData('documentTypes');
        $this->assertTrue($types->contains('code', 'SOP'));
        $this->assertTrue($types->contains('code', 'SK'));
        $this->assertTrue($types->contains('code', 'ND'));

        // 2. Search by code
        $searchCodeResp = $this->actingAs($admin)->get(route('admin.document-types.index', ['search' => 'SK']));
        $searchCodeResp->assertStatus(200);
        $searchCodeTypes = $searchCodeResp->viewData('documentTypes');
        $this->assertCount(1, $searchCodeTypes);
        $this->assertSame('SK', $searchCodeTypes->first()->code);

        // 3. Search by name keyword
        $searchNameResp = $this->actingAs($admin)->get(route('admin.document-types.index', ['search' => 'Operating']));
        $searchNameResp->assertStatus(200);
        $searchNameTypes = $searchNameResp->viewData('documentTypes');
        $this->assertCount(1, $searchNameTypes);
        $this->assertSame('SOP', $searchNameTypes->first()->code);
    }

    public function test_admin_can_filter_document_types_by_category(): void
    {
        $admin = $this->createAdmin();

        DocumentType::create(['code' => 'SOP', 'name' => 'Standard Operating Procedure', 'category' => 'akreditasi']);
        DocumentType::create(['code' => 'SK', 'name' => 'Keputusan', 'category' => 'akreditasi']);
        DocumentType::create(['code' => 'S.ED', 'name' => 'Surat Edaran', 'category' => 'naskah_dinas']);
        DocumentType::create(['code' => 'ST', 'name' => 'Surat Tugas', 'category' => 'naskah_dinas']);

        // Filter by naskah_dinas
        $respNaskah = $this->actingAs($admin)->get(route('admin.document-types.index', ['category' => 'naskah_dinas']));
        $respNaskah->assertStatus(200);
        $naskahTypes = $respNaskah->viewData('documentTypes');
        $this->assertCount(2, $naskahTypes);
        $this->assertTrue($naskahTypes->contains('code', 'S.ED'));
        $this->assertTrue($naskahTypes->contains('code', 'ST'));
        $this->assertFalse($naskahTypes->contains('code', 'SOP'));

        // Filter by akreditasi
        $respAkreditasi = $this->actingAs($admin)->get(route('admin.document-types.index', ['category' => 'akreditasi']));
        $respAkreditasi->assertStatus(200);
        $akreditasiTypes = $respAkreditasi->viewData('documentTypes');
        $this->assertCount(2, $akreditasiTypes);
        $this->assertTrue($akreditasiTypes->contains('code', 'SOP'));
        $this->assertTrue($akreditasiTypes->contains('code', 'SK'));
        $this->assertFalse($akreditasiTypes->contains('code', 'S.ED'));
    }

    public function test_admin_can_render_create_and_edit_forms_with_category(): void
    {
        $admin = $this->createAdmin();
        $type = DocumentType::create(['code' => 'SOP', 'name' => 'Standard Operating Procedure', 'category' => 'akreditasi']);

        // Create form
        $createResp = $this->actingAs($admin)->get(route('admin.document-types.create'));
        $createResp->assertStatus(200);
        $createResp->assertSee('name="category"', false);
        $createResp->assertSee('naskah_dinas');
        $createResp->assertSee('akreditasi');

        // Edit form
        $editResp = $this->actingAs($admin)->get(route('admin.document-types.edit', $type));
        $editResp->assertStatus(200);
        $editResp->assertSee('name="category"', false);
        $editResp->assertSee('value="akreditasi" selected', false);
    }

    public function test_admin_can_create_document_type_with_category(): void
    {
        $admin = $this->createAdmin();

        // 1. Create Naskah Dinas
        $response1 = $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'code' => 's.memo',
            'name' => 'Surat Memo Internal',
            'category' => 'naskah_dinas',
        ]);
        $response1->assertRedirect(route('admin.document-types.index'));
        $this->assertDatabaseHas('document_types', [
            'code' => 'S.MEMO',
            'name' => 'Surat Memo Internal',
            'category' => 'naskah_dinas',
        ]);

        // 2. Create Dokumen Akreditasi
        $response2 = $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'code' => 'ped',
            'name' => 'Pedoman Teknis',
            'category' => 'akreditasi',
        ]);
        $response2->assertRedirect(route('admin.document-types.index'));
        $this->assertDatabaseHas('document_types', [
            'code' => 'PED',
            'name' => 'Pedoman Teknis',
            'category' => 'akreditasi',
        ]);
    }

    public function test_admin_can_update_document_type_category(): void
    {
        $admin = $this->createAdmin();
        $type = DocumentType::create(['code' => 'DOC1', 'name' => 'General Doc', 'category' => 'naskah_dinas']);

        $response = $this->actingAs($admin)->put(route('admin.document-types.update', $type), [
            'code' => 'DOC1',
            'name' => 'General Doc Updated',
            'category' => 'akreditasi',
        ]);

        $response->assertRedirect(route('admin.document-types.index'));
        $this->assertDatabaseHas('document_types', [
            'id' => $type->id,
            'category' => 'akreditasi',
            'name' => 'General Doc Updated',
        ]);
    }

    public function test_admin_cannot_create_document_type_with_invalid_category(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.document-types.store'), [
            'code' => 'TEST',
            'name' => 'Test Doc',
            'category' => 'invalid_category',
        ]);

        $response->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('document_types', ['code' => 'TEST']);
    }

    public function test_admin_can_adjust_per_page_pagination(): void
    {
        $admin = $this->createAdmin();

        for ($i = 1; $i <= 30; $i++) {
            DocumentType::create([
                'code' => sprintf('T%02d', $i),
                'name' => sprintf('Type Number %02d', $i),
                'category' => $i % 2 === 0 ? 'akreditasi' : 'naskah_dinas',
            ]);
        }

        // Default 10 per page
        $defaultResp = $this->actingAs($admin)->get(route('admin.document-types.index'));
        $defaultResp->assertStatus(200);
        $this->assertCount(10, $defaultResp->viewData('documentTypes'));

        // Adjust to 25 per page
        $page25Resp = $this->actingAs($admin)->get(route('admin.document-types.index', ['per_page' => 25]));
        $page25Resp->assertStatus(200);
        $this->assertCount(25, $page25Resp->viewData('documentTypes'));

        // Adjust to 50 per page (should display all 30)
        $page50Resp = $this->actingAs($admin)->get(route('admin.document-types.index', ['per_page' => 50]));
        $page50Resp->assertStatus(200);
        $this->assertCount(30, $page50Resp->viewData('documentTypes'));
    }

    public function test_non_admin_cannot_access_document_types_management(): void
    {
        $regularUser = User::factory()->create(['system_role' => 'staff']);

        $response = $this->actingAs($regularUser)->get(route('admin.document-types.index'));
        $response->assertStatus(403);
    }
}
