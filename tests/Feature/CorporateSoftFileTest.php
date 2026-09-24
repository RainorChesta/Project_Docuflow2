<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CorporateSoftFile;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CorporateSoftFileTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $staffA;
    protected User $staffB;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected UnitKerja $unitKerjaA;
    protected DocumentType $docType;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('onlyoffice.storage_disk', 'local'));

        $this->companyA = Company::create(['name' => 'PT ALPHA', 'code' => 'ALP']);
        $this->companyB = Company::create(['name' => 'PT BETA', 'code' => 'BET']);

        $this->branchA = Branch::create(['name' => 'CABANG JAKARTA', 'code' => 'JKT', 'company_id' => $this->companyA->id]);
        $this->branchB = Branch::create(['name' => 'CABANG SURABAYA', 'code' => 'SBY', 'company_id' => $this->companyB->id]);

        $this->unitKerjaA = UnitKerja::create(['nama_unit_kerja' => 'DIVISI IT', 'kode_unit_kerja' => 'IT']);

        $this->docType = DocumentType::create(['name' => 'Surat Keputusan', 'code' => 'SK']);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'system_role' => 'admin',
        ]);

        $this->staffA = User::factory()->create([
            'name' => 'Staff Jakarta',
            'email' => 'staff.jkt@example.com',
            'system_role' => 'staff',
            'unit_kerja_id' => $this->unitKerjaA->id,
        ]);
        $this->staffA->companies()->attach($this->companyA->id);
        $this->staffA->branches()->attach($this->branchA->id);

        $this->staffB = User::factory()->create([
            'name' => 'Staff Surabaya',
            'email' => 'staff.sby@example.com',
            'system_role' => 'staff',
            'unit_kerja_id' => $this->unitKerjaA->id,
        ]);
        $this->staffB->companies()->attach($this->companyB->id);
        $this->staffB->branches()->attach($this->branchB->id);
    }

    public function test_admin_can_view_and_create_corporate_soft_file(): void
    {
        $file = UploadedFile::fake()->create('kop_surat_alpha.docx', 100);

        $response = $this->actingAs($this->admin)->post(route('admin.corporate-soft-files.store'), [
            'title' => 'Kop Surat Alpha Pusat',
            'description' => 'Kop surat resmi PT Alpha',
            'file' => $file,
            'is_all_companies' => '0',
            'company_ids' => [$this->companyA->id],
            'is_all_branches' => '0',
            'branch_ids' => [$this->branchA->id],
            'allowed_roles' => ['staff', 'head'],
        ]);

        $response->assertRedirect(route('admin.corporate-soft-files.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('corporate_soft_files', [
            'title' => 'Kop Surat Alpha Pusat',
            'is_all_companies' => false,
            'is_all_branches' => false,
            'status' => 'active',
        ]);

        $softFile = CorporateSoftFile::where('title', 'Kop Surat Alpha Pusat')->first();
        $this->assertTrue($softFile->companies->contains($this->companyA->id));
        $this->assertTrue($softFile->branches->contains($this->branchA->id));
    }

    public function test_non_admin_cannot_access_admin_corporate_soft_file_management(): void
    {
        $response = $this->actingAs($this->staffA)->get(route('admin.corporate-soft-files.index'));
        $response->assertForbidden();
    }

    public function test_staff_can_only_access_soft_files_scoped_to_their_company_and_branch(): void
    {
        $softFileA = CorporateSoftFile::create([
            'title' => 'Soft File Cabang Jakarta',
            'file_path' => 'corporate_soft_files/dummy_a.docx',
            'file_original_name' => 'dummy_a.docx',
            'status' => 'active',
            'is_all_companies' => false,
            'is_all_branches' => false,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);
        $softFileA->companies()->attach($this->companyA->id);
        $softFileA->branches()->attach($this->branchA->id);

        $softFileB = CorporateSoftFile::create([
            'title' => 'Soft File Cabang Surabaya',
            'file_path' => 'corporate_soft_files/dummy_b.docx',
            'file_original_name' => 'dummy_b.docx',
            'status' => 'active',
            'is_all_companies' => false,
            'is_all_branches' => false,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);
        $softFileB->companies()->attach($this->companyB->id);
        $softFileB->branches()->attach($this->branchB->id);

        // Document owned by Staff A in Company A & Branch A
        $docA = Document::create([
            'title' => 'Dokumen Internal Jakarta',
            'document_number' => '001/SK/JKT/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
        ]);
        $versionA = $docA->versions()->create([
            'version_number' => 1,
            'file_path' => 'documents/' . $docA->id . '/v1.docx',
            'file_original_name' => 'Dokumen Internal Jakarta.docx',
            'content' => '',
            'author_name' => $this->staffA->name,
            'status' => 'pending',
            'author_id' => $this->staffA->id,
        ]);
        $docA->update(['current_version_id' => $versionA->id]);

        // Query accessible soft files for Staff A on Doc A
        $accessibleFilesForA = CorporateSoftFile::query()
            ->accessibleBy($this->staffA, $docA->company_id, $docA->branch_id)
            ->get();

        $this->assertTrue($accessibleFilesForA->contains('id', $softFileA->id));
        $this->assertFalse($accessibleFilesForA->contains('id', $softFileB->id));

        // Query accessible soft files for Staff B on Doc A
        $accessibleFilesForB = CorporateSoftFile::query()
            ->accessibleBy($this->staffB, $this->companyB->id, $this->branchB->id)
            ->get();

        $this->assertFalse($accessibleFilesForB->contains('id', $softFileA->id));
        $this->assertTrue($accessibleFilesForB->contains('id', $softFileB->id));
    }

    public function test_apply_corporate_soft_file_to_document_version(): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $softFilePath = 'corporate_soft_files/master_template.docx';
        $disk->put($softFilePath, 'MASTER_CONTENT_XYZ');

        $softFile = CorporateSoftFile::create([
            'title' => 'Master Template Alpha',
            'file_path' => $softFilePath,
            'file_original_name' => 'master_template.docx',
            'status' => 'active',
            'is_all_companies' => true,
            'is_all_branches' => true,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);

        $doc = Document::create([
            'title' => 'Surat Tugas Alpha',
            'document_number' => '002/SK/JKT/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
        ]);
        $docPath = 'documents/' . $doc->id . '/v1.docx';
        $disk->put($docPath, 'OLD_CONTENT');

        $version = $doc->versions()->create([
            'version_number' => 1,
            'file_path' => $docPath,
            'file_original_name' => 'Surat Tugas Alpha.docx',
            'content' => '',
            'author_name' => $this->staffA->name,
            'status' => 'pending',
            'author_id' => $this->staffA->id,
        ]);
        $doc->update(['current_version_id' => $version->id]);

        $response = $this->actingAs($this->staffA)->postJson(
            route('documents.corporate-soft-files.apply', [$doc, $softFile])
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        // Verify file content in storage is updated with master soft file
        $this->assertEquals('MASTER_CONTENT_XYZ', $disk->get($docPath));

        // Verify document tracks which corporate soft file was applied
        $this->assertEquals($softFile->id, $doc->fresh()->corporate_soft_file_id);
    }

    public function test_admin_cannot_upload_image_corporate_soft_file(): void
    {
        $imageFile = UploadedFile::fake()->image('kop_surat_resmi.png', 800, 200);

        $response = $this->actingAs($this->admin)->post(route('admin.corporate-soft-files.store'), [
            'title' => 'Kop Surat PNG Gambar',
            'description' => 'Kop surat berupa gambar PNG',
            'file' => $imageFile,
            'is_all_companies' => '1',
            'is_all_branches' => '1',
        ]);

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseMissing('corporate_soft_files', [
            'title' => 'Kop Surat PNG Gambar',
        ]);
    }

    public function test_admin_can_upload_and_apply_pdf_corporate_soft_file(): void
    {
        $storageDisk = config('onlyoffice.storage_disk', 'local');
        $pdfFile = UploadedFile::fake()->create('kop_surat_resmi.pdf', 200, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.corporate-soft-files.store'), [
            'title' => 'Kop Surat PDF Resmi',
            'description' => 'Kop surat berupa dokumen PDF',
            'file' => $pdfFile,
            'is_all_companies' => '1',
            'is_all_branches' => '1',
        ]);

        $response->assertRedirect(route('admin.corporate-soft-files.index'));
        $response->assertSessionHas('success');

        $softFile = CorporateSoftFile::where('title', 'Kop Surat PDF Resmi')->first();
        $this->assertNotNull($softFile);
        $this->assertTrue($softFile->isPdf());
        $this->assertEquals('PDF', $softFile->file_type);

        // Test applying PDF kop surat to document
        $doc = Document::create([
            'title' => 'Surat PDF Kop',
            'document_number' => '004/SK/JKT/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
        ]);
        $disk = Storage::disk($storageDisk);
        $docPath = 'documents/' . $doc->id . '/v1.docx';
        $disk->put($docPath, 'INITIAL_CONTENT');

        $version = $doc->versions()->create([
            'version_number' => 1,
            'file_path' => $docPath,
            'file_original_name' => 'Surat PDF Kop.docx',
            'content' => '',
            'author_name' => $this->staffA->name,
            'status' => 'pending',
            'author_id' => $this->staffA->id,
        ]);
        $doc->update(['current_version_id' => $version->id]);

        $applyRes = $this->actingAs($this->staffA)->postJson(
            route('documents.corporate-soft-files.apply', [$doc, $softFile])
        );

        $applyRes->assertOk();
        $applyRes->assertJson(['success' => true]);
        $this->assertEquals($softFile->id, $doc->fresh()->corporate_soft_file_id);
    }

    public function test_admin_can_preview_corporate_soft_file(): void
    {
        $storageDisk = config('onlyoffice.storage_disk', 'local');
        $file = UploadedFile::fake()->create('kop_pt.docx', 100);
        $softFile = CorporateSoftFile::create([
            'title' => 'Kop Preview DOCX',
            'file_path' => $file->store('corporate_soft_files', $storageDisk),
            'file_original_name' => 'kop_pt.docx',
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 102400,
            'is_all_companies' => true,
            'is_all_branches' => true,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $res = $this->actingAs($this->admin)->get(route('admin.corporate-soft-files.preview', $softFile));
        $res->assertOk();
        $res->assertViewIs('admin.corporate_soft_files.preview');
        $res->assertViewHas('corporateSoftFile');
        $res->assertViewHas('onlyOfficeConfig');

        $contentRes = $this->actingAs($this->admin)->get(route('admin.corporate-soft-files.preview-content', $softFile));
        $contentRes->assertOk();

        $configRes = $this->actingAs($this->admin)->getJson(route('admin.corporate-soft-files.preview-config', $softFile));
        $configRes->assertOk();
        $configRes->assertJsonStructure(['documentType', 'document', 'editorConfig']);
    }

    public function test_can_remove_corporate_soft_file_from_document_and_template(): void
    {
        $softFile = CorporateSoftFile::create([
            'title' => 'Kop Batalkan Test',
            'file_path' => 'corporate_soft_files/kop_cancel.docx',
            'file_original_name' => 'kop_cancel.docx',
            'status' => 'active',
            'is_all_companies' => true,
            'is_all_branches' => true,
            'created_by' => $this->admin->id,
        ]);

        $doc = Document::create([
            'title' => 'Surat Batal Kop',
            'document_number' => '005/SK/JKT/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
            'corporate_soft_file_id' => $softFile->id,
        ]);

        // Remove from document
        $resDoc = $this->actingAs($this->staffA)->postJson(
            route('documents.corporate-soft-files.remove', $doc)
        );
        $resDoc->assertOk();
        $resDoc->assertJson(['success' => true]);
        $this->assertNull($doc->fresh()->corporate_soft_file_id);

        // Template remove test
        $template = \App\Models\DocumentTemplate::create([
            'title' => 'Template Batal Kop',
            'document_type_id' => $this->docType->id,
            'file_path' => 'templates/template_test.docx',
            'file_original_name' => 'template_test.docx',
            'is_active' => true,
            'corporate_soft_file_id' => $softFile->id,
            'created_by' => $this->admin->id,
        ]);

        $resTmpl = $this->actingAs($this->admin)->postJson(
            route('admin.templates.corporate-soft-files.remove', $template)
        );
        $resTmpl->assertOk();
        $resTmpl->assertJson(['success' => true]);
        $this->assertNull($template->fresh()->corporate_soft_file_id);
    }

    public function test_apply_corporate_soft_file_preserves_existing_user_content(): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        // 1. Create a dummy docx with user paragraphs
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection();
        $section->addText("SURAT KEPUTUSAN DIREKSI NOMOR 999");
        $section->addText("Menimbang: Kepentingan operasional.");

        $tmpDocx = tempnam(sys_get_temp_dir(), 'test_usr_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpDocx);
        $userDocxContent = file_get_contents($tmpDocx);
        @unlink($tmpDocx);

        // 2. Create a corporate soft file docx with a header
        $phpWordKop = new \PhpOffice\PhpWord\PhpWord();
        $sectionKop = $phpWordKop->addSection();
        $header = $sectionKop->addHeader();
        $header->addText("PT JBM KORPORAT RESMI");

        $tmpKop = tempnam(sys_get_temp_dir(), 'test_kop_') . '.docx';
        $writerKop = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordKop, 'Word2007');
        $writerKop->save($tmpKop);
        $kopDocxContent = file_get_contents($tmpKop);
        @unlink($tmpKop);

        $softFilePath = 'corporate_soft_files/kop_preserve_test.docx';
        $disk->put($softFilePath, $kopDocxContent);

        $softFile = CorporateSoftFile::create([
            'title' => 'Kop Preserve Test',
            'file_path' => $softFilePath,
            'file_original_name' => 'kop_preserve_test.docx',
            'status' => 'active',
            'is_all_companies' => true,
            'is_all_branches' => true,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);

        $doc = Document::create([
            'title' => 'Surat Direksi',
            'document_number' => '999/DIR/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
        ]);
        $docPath = 'documents/' . $doc->id . '/v1.docx';
        $disk->put($docPath, $userDocxContent);

        $version = $doc->versions()->create([
            'version_number' => 1,
            'file_path' => $docPath,
            'file_original_name' => 'Surat Direksi.docx',
            'content' => '',
            'author_name' => $this->staffA->name,
            'status' => 'pending',
            'author_id' => $this->staffA->id,
        ]);
        $doc->update(['current_version_id' => $version->id]);

        // Apply kop
        $res = $this->actingAs($this->staffA)->postJson(
            route('documents.corporate-soft-files.apply', [$doc, $softFile])
        );
        $res->assertOk();

        // Verify the resulting docx
        $updatedDocx = $disk->get($docPath);
        $tmpCheck = tempnam(sys_get_temp_dir(), 'chk_pres_');
        file_put_contents($tmpCheck, $updatedDocx);
        $zip = new \ZipArchive();
        $zip->open($tmpCheck);
        $docXml = $zip->getFromName('word/document.xml');
        $hasHeader = $zip->getFromName('word/header1.xml') !== false;
        $zip->close();
        @unlink($tmpCheck);

        $this->assertTrue($hasHeader, 'Header1 should exist');
        $this->assertStringContainsString('SURAT KEPUTUSAN DIREKSI NOMOR 999', $docXml, 'User content should be preserved');
        $this->assertStringContainsString('Menimbang: Kepentingan operasional.', $docXml, 'User content should be preserved');
    }
}

