<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentExportPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $admin;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $division = Division::create(['code' => '01', 'name' => 'JBM']);
        $docType = DocumentType::create(['name' => 'Surat Edaran Test Type', 'code' => 'S.ED.TEST']);
        $this->owner = User::factory()->create(['division_id' => $division->id]);
        $this->admin = User::factory()->create(['system_role' => 'admin', 'is_active' => true]);
        $this->document = Document::create([
            'document_number' => 'TST/002',
            'title' => 'Surat Edaran Test',
            'visibility' => 'division',
            'division_id' => $division->id,
            'owner_id' => $this->owner->id,
            'document_type_id' => $docType->id,
            'paper_size' => 'A4',
        ]);
    }

    private function addVersion(string $content, string $status = 'active'): DocumentVersion
    {
        $v = DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_number' => 1,
            'content' => $content,
            'author_id' => $this->owner->id,
            'author_name' => $this->owner->name,
            'status' => $status,
        ]);
        if ($status === 'active') {
            $this->document->update(['current_version_id' => $v->id]);
        }

        return $v;
    }

    #[Test]
    public function successful_export_creates_pdf_and_audit_log(): void
    {
        $this->addVersion('<h1>Konten</h1><p>Isi dokumen.</p>');

        $response = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document));

        $response->assertRedirect();
        $response->assertSessionHas('pdf_export');

        $this->assertFileExists(storage_path('app/private/exports/' . session('pdf_export')['filename']));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.exported',
            'target_type' => 'document',
            'target_id' => $this->document->id,
        ]);
    }

    #[Test]
    public function export_defaults_to_f4_and_preserves_document_original_paper_size(): void
    {
        $this->addVersion('<h1>Konten Dokumen</h1><p>Uji coba default cetak F4.</p>');
        $this->assertEquals('A4', $this->document->paper_size);

        $response = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document), []);

        $response->assertRedirect();
        $response->assertSessionHas('pdf_export');

        // Audit log shows F4 was used for the print/export job
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.exported',
            'target_type' => 'document',
            'target_id' => $this->document->id,
            'details->paper_size' => 'F4',
        ]);

        // Document's stored paper_size in database MUST remain unchanged
        $this->assertEquals('A4', $this->document->fresh()->paper_size);
    }

    #[Test]
    public function export_with_preset_size_changes_print_output_and_preserves_document(): void
    {
        $this->addVersion('<h1>Legal Print</h1><p>Uji coba export Legal.</p>');

        $response = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document), [
                'paper_size' => 'Legal',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('pdf_export');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.exported',
            'target_type' => 'document',
            'target_id' => $this->document->id,
            'details->paper_size' => 'Legal',
        ]);

        // Original document unchanged
        $this->assertEquals('A4', $this->document->fresh()->paper_size);
    }

    #[Test]
    public function export_with_custom_size_succeeds_and_logs_dimensions(): void
    {
        $this->addVersion('<h1>Custom Size</h1><p>Uji coba custom size.</p>');

        $response = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document), [
                'paper_size' => 'Custom',
                'custom_width' => 21.5,
                'custom_height' => 33.5,
                'custom_unit' => 'cm',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('pdf_export');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.exported',
            'target_type' => 'document',
            'target_id' => $this->document->id,
            'details->paper_size' => 'Custom (21.5x33.5 cm)',
        ]);

        // Original document unchanged
        $this->assertEquals('A4', $this->document->fresh()->paper_size);
    }

    #[Test]
    public function export_with_invalid_custom_size_is_rejected(): void
    {
        $this->addVersion('<h1>Invalid Custom</h1><p>Test.</p>');

        // 1. Missing width and height
        $response1 = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document), [
                'paper_size' => 'Custom',
            ]);
        $response1->assertRedirect();
        $response1->assertSessionHasErrors(['custom_width', 'custom_height']);

        // 2. Zero or negative values
        $response2 = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document), [
                'paper_size' => 'Custom',
                'custom_width' => 0,
                'custom_height' => -10,
            ]);
        $response2->assertRedirect();
        $response2->assertSessionHasErrors(['custom_width', 'custom_height']);

        // 3. Non-numeric values
        $response3 = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document), [
                'paper_size' => 'Custom',
                'custom_width' => 'abc',
                'custom_height' => 'xyz',
            ]);
        $response3->assertRedirect();
        $response3->assertSessionHasErrors(['custom_width', 'custom_height']);
    }

    #[Test]
    public function user_without_view_permission_cannot_export(): void
    {
        $this->addVersion('<p>Konten</p>');

        $outsider = User::factory()->create(['division_id' => null, 'system_role' => 'staff']);

        $response = $this->actingAs($outsider)
            ->post(route('documents.export-pdf', $this->document));

        $response->assertForbidden();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'document.exported']);
    }

    #[Test]
    public function export_with_no_content_returns_error(): void
    {
        // Document with no versions at all.
        $response = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document));

        $response->assertRedirect();
        $response->assertSessionHasErrors('export');
        $this->assertStringContainsString('No content available to export', session('errors')->first('export'));
    }

    #[Test]
    public function export_failure_is_reported_and_retryable(): void
    {
        $this->addVersion('<p>Konten</p>');

        $mock = \Mockery::mock(PdfExportService::class);
        $mock->shouldReceive('export')->andThrow(new \RuntimeException('boom'));
        $this->app->instance(PdfExportService::class, $mock);

        $response = $this->actingAs($this->admin)
            ->post(route('documents.export-pdf', $this->document));

        $response->assertRedirect();
        $response->assertSessionHasErrors('export');
        $this->assertStringContainsString('PDF generation failed', session('errors')->first('export'));
    }

    #[Test]
    public function export_respects_personal_visibility(): void
    {
        $this->addVersion('<p>Rahasia</p>');
        $this->document->update(['visibility' => 'personal']);

        $other = User::factory()->create(['division_id' => 1]);

        $response = $this->actingAs($other)
            ->post(route('documents.export-pdf', $this->document));

        $response->assertForbidden();
    }
}
