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
            'signature_type' => 'canvas',
        ]);
        return $admin;
    }

    public function test_admin_can_view_and_search_document_types(): void
    {
        $admin = $this->createAdmin();

        DocumentType::create(['code' => 'SOP', 'name' => 'Standard Operating Procedure']);
        DocumentType::create(['code' => 'SK', 'name' => 'Surat Keputusan']);
        DocumentType::create(['code' => 'ND', 'name' => 'Nota Dinas']);

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

    public function test_admin_can_adjust_per_page_pagination(): void
    {
        $admin = $this->createAdmin();

        for ($i = 1; $i <= 30; $i++) {
            DocumentType::create([
                'code' => sprintf('T%02d', $i),
                'name' => sprintf('Type Number %02d', $i),
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
