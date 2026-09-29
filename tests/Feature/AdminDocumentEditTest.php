<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Signature;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDocumentEditTest extends TestCase
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

    public function test_admin_can_view_document_edit_page(): void
    {
        $admin = $this->createAdmin();

        $company = Company::create(['name' => 'PT Nusantara Jaya', 'code' => 'NJ']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Pusat', 'code' => 'PST']);
        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'SE', 'category' => 'naskah_dinas']);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Keuangan', 'kode_unit_kerja' => 'KEU']);

        $doc = Document::create([
            'title' => 'Surat Edaran Awal',
            'document_number' => 'SE-001/NJ/2026',
            'format_choice' => 'lama',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $unitKerja->id,
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.documents.edit', $doc));

        $response->assertStatus(200);
        $response->assertSee('Formulir Edit Dokumen');
        $response->assertSee('Surat Edaran Awal');
        $response->assertSee('SE-001/NJ/2026');
        $response->assertSee('PT NUSANTARA JAYA');
        $response->assertSee('Keuangan');
    }

    public function test_admin_can_update_document_directly_without_approval(): void
    {
        $admin = $this->createAdmin();

        $company = Company::create(['name' => 'PT Nusantara Jaya', 'code' => 'NJ']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Cabang Pusat', 'code' => 'PST']);
        $docType1 = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'SE', 'category' => 'naskah_dinas']);
        $docType2 = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK', 'category' => 'naskah_dinas']);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Keuangan', 'kode_unit_kerja' => 'KEU']);

        $doc = Document::create([
            'title' => 'Judul Lama Dokumen',
            'document_number' => 'OLD-001',
            'format_choice' => 'lama',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $unitKerja->id,
            'document_type_id' => $docType1->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.documents.update', $doc), [
            'title' => 'Judul Baru Yang Sudah Diedit Admin',
            'document_number' => 'NEW-999/SK/2026',
            'format_choice' => 'baru',
            'document_type_id' => $docType2->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $unitKerja->id,
            'visibility' => 'unit_kerja',
            'expiration_date' => '2028-12-31',
            'paper_size' => 'A4',
        ]);

        $response->assertRedirect(route('admin.documents.index'));
        $response->assertSessionHas('success');

        $doc->refresh();
        $this->assertEquals('Judul Baru Yang Sudah Diedit Admin', $doc->title);
        $this->assertEquals('NEW-999/SK/2026', $doc->document_number);
        $this->assertEquals('baru', $doc->format_choice);
        $this->assertEquals($docType2->id, $doc->document_type_id);
        $this->assertEquals('unit_kerja', $doc->visibility);
        $this->assertEquals('2028-12-31', $doc->expiration_date->format('Y-m-d'));
        $this->assertNull($doc->pending_title);
    }

    public function test_non_admin_cannot_edit_or_update_document_via_admin_routes(): void
    {
        $staff = User::factory()->create([
            'system_role' => 'staff',
            'is_active' => true,
        ]);

        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);
        $doc = Document::create([
            'title' => 'Dokumen Staff',
            'document_number' => 'STF-001',
            'document_type_id' => $docType->id,
            'owner_id' => $staff->id,
            'visibility' => 'general',
        ]);

        $editResponse = $this->actingAs($staff)->get(route('admin.documents.edit', $doc));
        $editResponse->assertStatus(403);

        $updateResponse = $this->actingAs($staff)->put(route('admin.documents.update', $doc), [
            'title' => 'Hacked Title',
            'format_choice' => 'baru',
            'document_type_id' => $docType->id,
            'visibility' => 'general',
        ]);
        $updateResponse->assertStatus(403);
    }
}
