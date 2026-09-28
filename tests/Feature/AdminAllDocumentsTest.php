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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAllDocumentsTest extends TestCase
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

    public function test_admin_can_view_all_documents_flat_list(): void
    {
        $admin = $this->createAdmin();

        $company1 = Company::create(['name' => 'PT Alpha', 'code' => 'ALP']);
        $company2 = Company::create(['name' => 'PT Beta', 'code' => 'BET']);

        $branch1 = Branch::create(['company_id' => $company1->id, 'name' => 'Cabang Jakarta', 'code' => 'JKT']);
        $branch2 = Branch::create(['company_id' => $company2->id, 'name' => 'Cabang Surabaya', 'code' => 'SBY']);

        $docType = DocumentType::create(['name' => 'SOP Operasional', 'code' => 'SOP']);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'IT Department', 'kode_unit_kerja' => 'IT']);

        $doc1 = Document::create([
            'title' => 'SOP Keamanan Jaringan',
            'document_number' => 'SOP-001/IT/2026',
            'company_id' => $company1->id,
            'branch_id' => $branch1->id,
            'unit_kerja_id' => $unitKerja->id,
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $doc2 = Document::create([
            'title' => 'SOP Pengadaan Server',
            'document_number' => 'SOP-002/IT/2026',
            'company_id' => $company2->id,
            'branch_id' => $branch2->id,
            'unit_kerja_id' => $unitKerja->id,
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.documents.index'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Semua Dokumen');
        $response->assertSee('SOP Keamanan Jaringan');
        $response->assertSee('SOP Pengadaan Server');
        $response->assertSee('SOP-001/IT/2026');
        $response->assertSee('SOP-002/IT/2026');
    }

    public function test_admin_can_search_and_filter_documents(): void
    {
        $admin = $this->createAdmin();

        $company = Company::create(['name' => 'PT Nusantara', 'code' => 'NUS']);
        $branch = Branch::create(['company_id' => $company->id, 'name' => 'Pusat', 'code' => 'PST']);
        $docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);
        $unitKerja = UnitKerja::create(['nama_unit_kerja' => 'HRD', 'kode_unit_kerja' => 'HRD']);

        Document::create([
            'title' => 'SK Pengangkatan Karyawan Tetap',
            'document_number' => 'SK-100/HRD/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $unitKerja->id,
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        Document::create([
            'title' => 'Formulir Cuti Tahunan',
            'document_number' => 'FRM-050/HRD/2026',
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'unit_kerja_id' => $unitKerja->id,
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        // Search for 'Pengangkatan'
        $searchResponse = $this->actingAs($admin)->get(route('admin.documents.index', ['search' => 'Pengangkatan']));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('SK Pengangkatan Karyawan Tetap');
        $searchResponse->assertDontSee('Formulir Cuti Tahunan');
    }

    public function test_admin_can_bulk_download_documents_as_zip(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);

        // Put fake document files in storage
        Storage::disk('local')->put('documents/test1.docx', 'Test Content 1');
        Storage::disk('local')->put('documents/test2.docx', 'Test Content 2');

        $doc1 = Document::create([
            'title' => 'Dokumen Pertama',
            'document_number' => 'DOC-001',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);
        $ver1 = DocumentVersion::create([
            'document_id' => $doc1->id,
            'version_number' => 1,
            'content' => 'Test Content 1',
            'file_path' => 'documents/test1.docx',
            'file_original_name' => 'Dokumen Pertama.docx',
            'status' => 'active',
            'author_id' => $admin->id,
            'author_name' => $admin->name,
        ]);
        $doc1->update(['current_version_id' => $ver1->id]);

        $doc2 = Document::create([
            'title' => 'Dokumen Kedua',
            'document_number' => 'DOC-002',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);
        $ver2 = DocumentVersion::create([
            'document_id' => $doc2->id,
            'version_number' => 1,
            'content' => 'Test Content 2',
            'file_path' => 'documents/test2.docx',
            'file_original_name' => 'Dokumen Kedua.docx',
            'status' => 'active',
            'author_id' => $admin->id,
            'author_name' => $admin->name,
        ]);
        $doc2->update(['current_version_id' => $ver2->id]);

        $response = $this->actingAs($admin)->post(route('admin.documents.bulk-download'), [
            'ids' => [$doc1->id, $doc2->id],
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }

    public function test_admin_can_bulk_delete_documents_to_trash(): void
    {
        $admin = $this->createAdmin();
        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);

        $doc1 = Document::create([
            'title' => 'Dokumen Hapus 1',
            'document_number' => 'DEL-001',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);
        $doc2 = Document::create([
            'title' => 'Dokumen Hapus 2',
            'document_number' => 'DEL-002',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.documents.bulk-delete'), [
            'ids' => [$doc1->id, $doc2->id],
        ]);

        $response->assertRedirect(route('admin.documents.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('documents', ['id' => $doc1->id]);
        $this->assertSoftDeleted('documents', ['id' => $doc2->id]);
    }

    public function test_admin_can_bulk_force_delete_documents(): void
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $docType = DocumentType::create(['name' => 'Dokumen Umum', 'code' => 'UMUM']);

        $doc = Document::create([
            'title' => 'Dokumen Permanen',
            'document_number' => 'PERM-001',
            'document_type_id' => $docType->id,
            'owner_id' => $admin->id,
            'visibility' => 'general',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.documents.bulk-delete'), [
            'ids' => [$doc->id],
            'force' => 1,
        ]);

        $response->assertRedirect(route('admin.documents.index'));
        $this->assertDatabaseMissing('documents', ['id' => $doc->id]);
    }

    public function test_non_admin_cannot_access_admin_documents(): void
    {
        $staff = User::factory()->create([
            'system_role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this->actingAs($staff)->get(route('admin.documents.index'));
        $response->assertStatus(403);
    }
}
