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

    public function test_uploading_image_format_is_rejected(): void
    {
        $jpegFile = UploadedFile::fake()->image('kop_surat.jpg', 800, 600);

        $response = $this->actingAs($this->admin)->post(route('admin.corporate-soft-files.store'), [
            'title' => 'Kop Surat Gambar',
            'file' => $jpegFile,
            'is_all_companies' => '1',
            'is_all_branches' => '1',
        ]);

        $response->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('corporate_soft_files', [
            'title' => 'Kop Surat Gambar',
        ]);
    }

    public function test_admin_can_update_soft_file_metadata_with_locked_file(): void
    {
        $softFile = CorporateSoftFile::create([
            'title' => 'Kop Surat Lama',
            'description' => 'Deskripsi lama',
            'file_path' => 'corporate_soft_files/original_file.docx',
            'file_original_name' => 'original_file.docx',
            'file_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'file_size' => 10240,
            'status' => 'active',
            'is_all_companies' => true,
            'is_all_branches' => true,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.corporate-soft-files.update', $softFile), [
            'title' => 'Kop Surat Diperbarui',
            'description' => 'Deskripsi baru',
            'is_all_companies' => '0',
            'company_ids' => [$this->companyA->id],
            'is_all_branches' => '0',
            'branch_ids' => [$this->branchA->id],
            'allowed_roles' => ['staff', 'direktur'],
        ]);

        $response->assertRedirect(route('admin.corporate-soft-files.index'));
        $response->assertSessionHas('success');

        $softFile->refresh();
        $this->assertEquals('Kop Surat Diperbarui', $softFile->title);
        $this->assertEquals('Deskripsi baru', $softFile->description);
        // Original file must remain unchanged / locked
        $this->assertEquals('corporate_soft_files/original_file.docx', $softFile->file_path);
        $this->assertEquals('original_file.docx', $softFile->file_original_name);
        $this->assertEquals(['staff', 'direktur'], $softFile->allowed_roles);
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

        // 1. Create a master kop DOCX with header
        $phpWordKop = new \PhpOffice\PhpWord\PhpWord();
        $sectionKop = $phpWordKop->addSection();
        $header = $sectionKop->addHeader();
        $header->addText("PT JBM MASTER KOP HEADER");

        $tmpKop = tempnam(sys_get_temp_dir(), 'test_mkop_') . '.docx';
        $writerKop = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordKop, 'Word2007');
        $writerKop->save($tmpKop);
        $kopDocx = file_get_contents($tmpKop);
        @unlink($tmpKop);

        $softFilePath = 'corporate_soft_files/master_template.docx';
        $disk->put($softFilePath, $kopDocx);

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

        $phpWordDoc = new \PhpOffice\PhpWord\PhpWord();
        $sectionDoc = $phpWordDoc->addSection();
        $sectionDoc->addText("Isi Dokumen Asli Alpha");

        $tmpDoc = tempnam(sys_get_temp_dir(), 'test_mdoc_') . '.docx';
        $writerDoc = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordDoc, 'Word2007');
        $writerDoc->save($tmpDoc);
        $userDocx = file_get_contents($tmpDoc);
        @unlink($tmpDoc);

        $disk->put($docPath, $userDocx);

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

        // Verify file content in storage has the header and preserved user body
        $appliedDocx = $disk->get($docPath);
        $this->assertNotEmpty($appliedDocx);
        $this->assertStringStartsWith('PK', $appliedDocx);

        $tmpCheck = tempnam(sys_get_temp_dir(), 'chk_app_');
        file_put_contents($tmpCheck, $appliedDocx);
        $zip = new \ZipArchive();
        $zip->open($tmpCheck);
        $docXml = $zip->getFromName('word/document.xml');
        $hdrXml = $zip->getFromName('word/header1.xml');
        $zip->close();
        @unlink($tmpCheck);

        $this->assertNotEmpty($hdrXml, 'Header XML should exist');
        $this->assertStringContainsString('PT JBM MASTER KOP HEADER', $hdrXml);
        $this->assertStringContainsString('Isi Dokumen Asli Alpha', $docXml);

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

    public function test_apply_corporate_soft_file_preserves_explicit_headers_and_footers(): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        // Create a DOCX with explicit header, explicit footer, and custom style
        $phpWordKop = new \PhpOffice\PhpWord\PhpWord();
        $sectionKop = $phpWordKop->addSection([
            'marginTop' => 1200,
            'marginBottom' => 1300,
            'marginLeft' => 1400,
            'marginRight' => 1400,
            'headerHeight' => 600,
            'footerHeight' => 600,
        ]);
        $header = $sectionKop->addHeader();
        $header->addText("EXPLICIT HEADER PT DOCUFLOW");
        $footer = $sectionKop->addFooter();
        $footer->addText("EXPLICIT FOOTER GEDUNG DOCUFLOW LT 5");

        $tmpKop = tempnam(sys_get_temp_dir(), 'test_exp_') . '.docx';
        $writerKop = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordKop, 'Word2007');
        $writerKop->save($tmpKop);
        $kopDocx = file_get_contents($tmpKop);
        @unlink($tmpKop);

        $softFilePath = 'corporate_soft_files/kop_explicit_hf_test.docx';
        $disk->put($softFilePath, $kopDocx);

        $softFile = CorporateSoftFile::create([
            'title' => 'Kop Explicit HF Test',
            'file_path' => $softFilePath,
            'file_original_name' => 'kop_explicit_hf_test.docx',
            'status' => 'active',
            'is_all_companies' => true,
            'is_all_branches' => true,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);

        $doc = Document::create([
            'title' => 'Surat Explicit HF Test',
            'document_number' => '101/EXPHF/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
        ]);
        $docPath = 'documents/' . $doc->id . '/v1.docx';

        $phpWordDoc = new \PhpOffice\PhpWord\PhpWord();
        $sectionDoc = $phpWordDoc->addSection();
        $sectionDoc->addText("Teks Asli Dokumen User Explicit HF");

        $tmpDoc = tempnam(sys_get_temp_dir(), 'test_expdoc_') . '.docx';
        $writerDoc = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordDoc, 'Word2007');
        $writerDoc->save($tmpDoc);
        $userDocx = file_get_contents($tmpDoc);
        @unlink($tmpDoc);

        $disk->put($docPath, $userDocx);

        $version = $doc->versions()->create([
            'version_number' => 1,
            'file_path' => $docPath,
            'file_original_name' => 'Surat Explicit HF Test.docx',
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

        // Verify that header1.xml and footer1.xml both exist and contain the explicit text
        $appliedDocx = $disk->get($docPath);
        $tmpCheck = tempnam(sys_get_temp_dir(), 'chk_exphf_');
        file_put_contents($tmpCheck, $appliedDocx);
        $zip = new \ZipArchive();
        $zip->open($tmpCheck);
        $hdrXml = $zip->getFromName('word/header1.xml');
        $ftrXml = $zip->getFromName('word/footer1.xml');
        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmpCheck);

        $this->assertNotEmpty($hdrXml, 'Header1 should exist');
        $this->assertStringContainsString('EXPLICIT HEADER PT DOCUFLOW', $hdrXml);
        $this->assertNotEmpty($ftrXml, 'Footer1 should exist');
        $this->assertStringContainsString('EXPLICIT FOOTER GEDUNG DOCUFLOW LT 5', $ftrXml);
        $this->assertStringContainsString('Teks Asli Dokumen User Explicit HF', $docXml);
        $this->assertStringContainsString('w:footerReference', $docXml);
    }

    public function test_admin_can_upload_a4_docx_successfully(): void
    {
        // Generate an A4 DOCX (11906 x 16838 twips)
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
        ]);
        $header = $section->addHeader();
        $header->addText("Kop Surat A4 Cabang Jakarta");

        $tmp = tempnam(sys_get_temp_dir(), 'test_a4_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmp);

        $file = new UploadedFile($tmp, 'kop_a4.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($this->admin)->post(route('admin.corporate-soft-files.store'), [
            'title' => 'Kop Surat A4 Jakarta',
            'paper_size' => 'a4',
            'file' => $file,
            'is_all_companies' => '1',
            'is_all_branches' => '1',
        ]);

        @unlink($tmp);

        $response->assertRedirect(route('admin.corporate-soft-files.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('corporate_soft_files', [
            'title' => 'Kop Surat A4 Jakarta',
            'paper_size' => 'a4',
        ]);
    }

    public function test_admin_uploading_non_a4_docx_with_target_a4_is_accepted_and_converted_to_a4(): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        // Generate an F4 DOCX (11906 x 18709 twips)
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 18709,
        ]);
        $header = $section->addHeader();
        $header->addText("Kop Surat Asli F4 yang akan dikonversi ke A4");

        $tmp = tempnam(sys_get_temp_dir(), 'test_f4_as_a4_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmp);

        $file = new UploadedFile($tmp, 'kop_f4.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);

        $response = $this->actingAs($this->admin)->post(route('admin.corporate-soft-files.store'), [
            'title' => 'Kop Surat Otomatis Konversi A4',
            'paper_size' => 'a4',
            'file' => $file,
            'is_all_companies' => '1',
            'is_all_branches' => '1',
        ]);

        @unlink($tmp);

        $response->assertRedirect(route('admin.corporate-soft-files.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('corporate_soft_files', [
            'title' => 'Kop Surat Otomatis Konversi A4',
            'paper_size' => 'a4',
        ]);

        $softFile = CorporateSoftFile::where('title', 'Kop Surat Otomatis Konversi A4')->first();
        $storedContent = $disk->get($softFile->file_path);

        $tmpCheck = tempnam(sys_get_temp_dir(), 'chk_conv_a4_');
        file_put_contents($tmpCheck, $storedContent);
        $zip = new \ZipArchive();
        $zip->open($tmpCheck);
        $docXml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($tmpCheck);

        // Stored DOCX must be converted to A4 (16838 twips height)
        $this->assertMatchesRegularExpression('/<w:pgSz\b[^>]*w:h="16838"[^>]*\/>|<w:pgSz\b[^>]*w:w="11906"\s+w:h="16838"/i', $docXml);
    }

    public function test_apply_a4_corporate_soft_file_locks_document_to_a4(): void
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        // 1. Create A4 master kop
        $phpWordKop = new \PhpOffice\PhpWord\PhpWord();
        $sectionKop = $phpWordKop->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 16838,
        ]);
        $header = $sectionKop->addHeader();
        $header->addText("HEADER RESMI A4 PT DOCUFLOW");
        $footer = $sectionKop->addFooter();
        $footer->addText("FOOTER RESMI A4 PT DOCUFLOW");

        $tmpKop = tempnam(sys_get_temp_dir(), 'test_a4kop_') . '.docx';
        $writerKop = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordKop, 'Word2007');
        $writerKop->save($tmpKop);
        $kopDocx = file_get_contents($tmpKop);
        @unlink($tmpKop);

        $softFilePath = 'corporate_soft_files/master_a4.docx';
        $disk->put($softFilePath, $kopDocx);

        $softFile = CorporateSoftFile::create([
            'title' => 'Master Kop A4 Jakarta',
            'paper_size' => 'a4',
            'file_path' => $softFilePath,
            'file_original_name' => 'master_a4.docx',
            'status' => 'active',
            'is_all_companies' => true,
            'is_all_branches' => true,
            'allowed_roles' => ['staff'],
            'created_by' => $this->admin->id,
        ]);

        // 2. User creates a document in F4
        $doc = Document::create([
            'title' => 'Surat A4 Lock Test',
            'document_number' => '202/A4LOCK/IX/2026',
            'document_type_id' => $this->docType->id,
            'owner_id' => $this->staffA->id,
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'unit_kerja_id' => $this->unitKerjaA->id,
            'status' => 'draft',
        ]);
        $docPath = 'documents/' . $doc->id . '/v1.docx';

        $phpWordDoc = new \PhpOffice\PhpWord\PhpWord();
        $sectionDoc = $phpWordDoc->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 18709,
        ]);
        $sectionDoc->addText("Isi Dokumen A4 Lock Test");

        $tmpDoc = tempnam(sys_get_temp_dir(), 'test_a4doc_') . '.docx';
        $writerDoc = \PhpOffice\PhpWord\IOFactory::createWriter($phpWordDoc, 'Word2007');
        $writerDoc->save($tmpDoc);
        $userDocx = file_get_contents($tmpDoc);
        @unlink($tmpDoc);

        $disk->put($docPath, $userDocx);

        $version = $doc->versions()->create([
            'version_number' => 1,
            'file_path' => $docPath,
            'file_original_name' => 'Surat A4 Lock Test.docx',
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

        // 3. Inspect the applied document: must be A4 (w:w="11906" w:h="16838")
        $appliedDocx = $disk->get($docPath);
        $tmpCheck = tempnam(sys_get_temp_dir(), 'chk_a4lock_');
        file_put_contents($tmpCheck, $appliedDocx);
        $zip = new \ZipArchive();
        $zip->open($tmpCheck);
        $docXml = $zip->getFromName('word/document.xml');
        $hdrXml = $zip->getFromName('word/header1.xml');
        $ftrXml = $zip->getFromName('word/footer1.xml');
        $zip->close();
        @unlink($tmpCheck);

        $this->assertNotEmpty($hdrXml);
        $this->assertStringContainsString('HEADER RESMI A4 PT DOCUFLOW', $hdrXml);
        $this->assertNotEmpty($ftrXml);
        $this->assertStringContainsString('FOOTER RESMI A4 PT DOCUFLOW', $ftrXml);
        $this->assertStringContainsString('Isi Dokumen A4 Lock Test', $docXml);

        // Page size in document.xml must be A4 (16838 twips height)
        $this->assertMatchesRegularExpression('/<w:pgSz\b[^>]*w:h="16838"[^>]*\/>|<w:pgSz\b[^>]*w:w="11906"\s+w:h="16838"/i', $docXml);
    }
}

