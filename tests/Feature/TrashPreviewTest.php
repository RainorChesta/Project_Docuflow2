<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrashPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'system_role' => 'admin',
            'is_active' => true,
        ]);

        Signature::create([
            'user_id' => $admin->id,
            'file_path' => 'signatures/admin.png',
            'type' => 'original',
            'created_via' => 'canvas',
        ]);

        return $admin;
    }

    public function test_trashed_document_shows_clickable_title_and_preview_button(): void
    {
        $admin = $this->createAdmin();
        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);

        $doc = Document::create([
            'title' => 'Dokumen Terhapus Uji Coba',
            'document_number' => 'TRASH-001',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $ver = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Konten Sampah</p>',
            'status' => 'active',
            'author_id' => $admin->id,
            'author_name' => $admin->name,
        ]);
        $doc->update(['current_version_id' => $ver->id]);

        // Soft delete to trash
        $doc->delete();

        $response = $this->actingAs($admin)->get(route('trash.index'));
        $response->assertStatus(200);
        $response->assertSee('Dokumen Terhapus Uji Coba');
        $response->assertSee(route('documents.preview', ['document' => $doc->id, 'from' => 'trash']));
    }

    public function test_user_can_preview_trashed_document_via_preview_route(): void
    {
        $admin = $this->createAdmin();
        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);

        $doc = Document::create([
            'title' => 'Dokumen Rahasia Sampah',
            'document_number' => 'TRASH-002',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $ver = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Isi Dokumen Sampah</p>',
            'status' => 'active',
            'author_id' => $admin->id,
            'author_name' => $admin->name,
        ]);
        $doc->update(['current_version_id' => $ver->id]);

        $doc->delete();

        $response = $this->actingAs($admin)->get(route('documents.preview', ['document' => $doc->id, 'from' => 'trash']));
        $response->assertStatus(200);
    }

    public function test_user_can_preview_trashed_document_via_trash_preview_route(): void
    {
        $admin = $this->createAdmin();
        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);

        $doc = Document::create([
            'title' => 'Dokumen Rahasia Sampah 2',
            'document_number' => 'TRASH-003',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $ver = DocumentVersion::create([
            'document_id' => $doc->id,
            'version_number' => 1,
            'content' => '<p>Isi Dokumen Sampah</p>',
            'status' => 'active',
            'author_id' => $admin->id,
            'author_name' => $admin->name,
        ]);
        $doc->update(['current_version_id' => $ver->id]);

        $doc->delete();

        $trashPreviewResponse = $this->actingAs($admin)->get(route('trash.preview', $doc->id));
        $trashPreviewResponse->assertRedirect(route('documents.preview', ['document' => $doc->id, 'from' => 'trash']));
    }
}
