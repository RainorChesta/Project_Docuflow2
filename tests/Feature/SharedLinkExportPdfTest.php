<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentAccessLink;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\PdfExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedLinkExportPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $division = Division::create(['code' => '01', 'name' => 'JBM']);
        $type = DocumentType::create(['code' => 'S.ED', 'name' => 'Surat Edaran']);
        $this->owner = User::factory()->create(['division_id' => $division->id]);
        $this->document = Document::create([
            'document_number' => 'TST/003',
            'title' => 'Surat Edaran Shared',
            'visibility' => 'division',
            'division_id' => $division->id,
            'document_type_id' => $type->id,
            'owner_id' => $this->owner->id,
        ]);
    }

    private function makeLink(string $role = 'viewer', bool $expired = false): DocumentAccessLink
    {
        return DocumentAccessLink::create([
            'document_id' => $this->document->id,
            'token' => 'tok_' . $role . '_' . uniqid(),
            'role' => $role,
            'expires_at' => $expired ? now()->subDay() : null,
            'created_by' => $this->owner->id,
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
    public function viewer_can_export_via_share_link(): void
    {
        $this->addVersion('<h1>Konten</h1><p>Isi dokumen.</p>');
        $link = $this->makeLink('viewer');

        $response = $this->actingAs($this->owner)
            ->post(route('shared.documents.export-pdf', $link->token));

        $response->assertRedirect();
        $response->assertSessionHas('pdf_export');

        Storage::disk('local')->assertExists('exports/' . session('pdf_export')['filename']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'document.exported',
            'target_type' => 'document',
            'target_id' => $this->document->id,
        ]);
    }

    #[Test]
    public function editor_can_export_via_share_link(): void
    {
        $this->addVersion('<p>Konten</p>');
        $link = $this->makeLink('editor');

        $response = $this->actingAs($this->owner)
            ->post(route('shared.documents.export-pdf', $link->token));

        $response->assertRedirect();
        $response->assertSessionHas('pdf_export');
    }

    #[Test]
    public function expired_link_cannot_export(): void
    {
        $this->addVersion('<p>Konten</p>');
        $link = $this->makeLink('viewer', expired: true);

        $response = $this->actingAs($this->owner)
            ->post(route('shared.documents.export-pdf', $link->token));

        $response->assertNotFound();
        $this->assertDatabaseMissing('audit_logs', ['action' => 'document.exported']);
    }

    #[Test]
    public function export_with_no_content_returns_error(): void
    {
        $link = $this->makeLink('viewer');

        $response = $this->actingAs($this->owner)
            ->post(route('shared.documents.export-pdf', $link->token));

        $response->assertRedirect();
        $response->assertSessionHasErrors('export');
    }

    #[Test]
    public function export_failure_is_reported(): void
    {
        $this->addVersion('<p>Konten</p>');
        $link = $this->makeLink('viewer');

        $mock = \Mockery::mock(PdfExportService::class);
        $mock->shouldReceive('export')->andThrow(new \RuntimeException('boom'));
        $this->app->instance(PdfExportService::class, $mock);

        $response = $this->actingAs($this->owner)
            ->post(route('shared.documents.export-pdf', $link->token));

        $response->assertRedirect();
        $response->assertSessionHasErrors('export');
        $this->assertStringContainsString('PDF generation failed', session('errors')->first('export'));
    }
}
