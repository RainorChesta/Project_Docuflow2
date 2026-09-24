<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentUploadWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private DocumentType $docType;
    private Branch $branch;
    private UnitKerja $unitKerja;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $company = Company::create(['name' => 'CMH Group', 'code' => 'CMH']);
        $this->branch = Branch::create(['company_id' => $company->id, 'name' => 'Klinik Utama', 'code' => 'KLU', 'is_pusat' => false]);
        $this->unitKerja = UnitKerja::create(['nama_unit_kerja' => 'Rekam Medis', 'kode_unit_kerja' => 'RM']);

        $this->user = User::factory()->create([
            'unit_kerja_id' => $this->unitKerja->id,
            'name' => 'Staff Uploader',
            'system_role' => 'staff',
        ]);
        $this->user->companies()->attach($company->id);
        $this->user->branches()->attach($this->branch->id);

        $this->docType = DocumentType::create([
            'name' => 'Standard Operating Procedure',
            'code' => 'SOP',
            'category' => 'akreditasi',
        ]);
    }

    public function test_docx_upload_creates_draft_and_redirects_to_edit_page(): void
    {
        $file = UploadedFile::fake()->create('sop_panduan.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'SOP Rekam Medis 2026',
            'document_type_id' => $this->docType->id,
            'is_upload' => '1',
            'file' => $file,
            'document_number' => '001/SOP-RM/KLU/IX/2026',
            'branch_id' => $this->branch->id,
            'branch_ids' => [$this->branch->id],
            'unit_kerja_id' => $this->unitKerja->id,
        ]);

        $document = Document::where('document_number', '001/SOP-RM/KLU/IX/2026')->first();
        $this->assertNotNull($document);

        // Must redirect to edit page
        $response->assertRedirect(route('documents.edit', $document));

        // Initial version must be in 'draft' status so it does not lock immediately
        $version = $document->versions()->first();
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals('draft', $version->status);
        $this->assertFalse($document->isLockedForEditing());

        // Accessing edit page should succeed (200) and not redirect to show (locked)
        $editResponse = $this->actingAs($this->user)->get(route('documents.edit', $document));
        $editResponse->assertOk();
        $editResponse->assertViewIs('documents.edit');
    }

    public function test_pdf_upload_creates_draft_and_redirects_to_edit_page_for_signing(): void
    {
        $file = UploadedFile::fake()->create('dokumen_kebijakan.pdf', 300, 'application/pdf');

        $response = $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'Kebijakan Pelayanan Pasien',
            'document_type_id' => $this->docType->id,
            'is_upload' => '1',
            'file' => $file,
            'document_number' => '002/SOP-RM/KLU/IX/2026',
            'branch_id' => $this->branch->id,
            'branch_ids' => [$this->branch->id],
            'unit_kerja_id' => $this->unitKerja->id,
        ]);

        $document = Document::where('document_number', '002/SOP-RM/KLU/IX/2026')->first();
        $this->assertNotNull($document);

        // Must redirect to edit page
        $response->assertRedirect(route('documents.edit', $document));

        // Initial version must be in 'draft' status
        $version = $document->versions()->first();
        $this->assertNotNull($version);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals('draft', $version->status);
        $this->assertFalse($document->isLockedForEditing());

        // Accessing edit page should succeed (200) for PDF
        $editResponse = $this->actingAs($this->user)->get(route('documents.edit', $document));
        $editResponse->assertOk();
        $editResponse->assertViewIs('documents.edit');
    }

    public function test_finishing_edit_submits_document_to_approval_stage(): void
    {
        $file = UploadedFile::fake()->create('pedoman.docx', 150, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($this->user)->post(route('documents.store'), [
            'title' => 'Pedoman Mutu',
            'document_type_id' => $this->docType->id,
            'is_upload' => '1',
            'file' => $file,
            'document_number' => '003/SOP-RM/KLU/IX/2026',
            'branch_id' => $this->branch->id,
            'branch_ids' => [$this->branch->id],
            'unit_kerja_id' => $this->unitKerja->id,
        ]);

        $document = Document::where('document_number', '003/SOP-RM/KLU/IX/2026')->first();
        $version = $document->versions()->first();
        $this->assertEquals('draft', $version->status);

        // User finishes editing
        $finishResponse = $this->actingAs($this->user)->post(route('documents.finish-editing', $document));
        $finishResponse->assertRedirect(route('documents.show', ['document' => $document, 'saving' => 1]));

        $version->refresh();
        $this->assertEquals('pending', $version->status);
        $this->assertTrue($document->fresh()->isLockedForEditing());
    }
}
