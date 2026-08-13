<?php

namespace Tests\Feature;

use App\AI\GroqClient;

use App\Jobs\SummarizeDocumentJob;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\DocumentSummarizer;
use App\Services\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentSummarizeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();

        $division = Division::create(['code' => 'HRD', 'name' => 'Human Resources']);
        $docType = DocumentType::create(['name' => 'Surat Edaran', 'code' => 'S.ED']);
        $this->user = User::factory()->create(['division_id' => $division->id]);

        $this->document = Document::create([
            'document_number' => '001/S.ED/HRD/JBM/VIII/2026',
            'title' => 'Dokumen Uji Ringkasan AI',
            'visibility' => Document::VISIBILITY_DIVISION,
            'division_id' => $division->id,
            'owner_id' => $this->user->id,
            'document_type_id' => $docType->id,
        ]);

        DocumentVersion::create([
            'document_id' => $this->document->id,
            'version_number' => 1,
            'content' => '<p>Ini adalah konten utama dokumen kebijakan internal yang akan diringkas oleh AI Groq.</p>',
            'author_id' => $this->user->id,
            'author_name' => $this->user->name,
            'status' => 'active',
        ]);

        $this->document->update(['current_version_id' => 1]);
    }

    #[Test]
    public function user_can_dispatch_summarize_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson(route('documents.summarize', $this->document));

        $response->assertOk()
            ->assertJson([
                'status' => Document::SUMMARY_PROCESSING,
                'document_id' => $this->document->id,
            ]);

        Queue::assertPushed(SummarizeDocumentJob::class, fn($job) => $job->documentId === $this->document->id);
    }

    #[Test]
    public function summary_status_endpoint_returns_current_state(): void
    {
        $this->document->update([
            'summary_status' => Document::SUMMARY_COMPLETED,
            'summary' => 'Ringkasan berhasil dibuat.',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('documents.summary-status', $this->document));

        $response->assertOk()
            ->assertJson([
                'status' => Document::SUMMARY_COMPLETED,
                'summary' => 'Ringkasan berhasil dibuat.',
                'error' => null,
            ]);
    }

    #[Test]
    public function document_summarizer_successfully_summarizes_text(): void
    {
        $groqMock = $this->createMock(GroqClient::class);
        $groqMock->expects($this->once())
            ->method('chat')
            ->willReturn('Ringkasan AI: Dokumen berisi kebijakan internal.');

        $extractor = new PdfTextExtractor();
        $summarizer = new DocumentSummarizer($groqMock, $extractor);

        $summarizer->summarize($this->document);

        $this->document->refresh();

        $this->assertEquals(Document::SUMMARY_COMPLETED, $this->document->summary_status);
        $this->assertEquals('Ringkasan AI: Dokumen berisi kebijakan internal.', $this->document->summary);
        $this->assertNull($this->document->summary_error);
    }

    #[Test]
    public function document_summarizer_handles_error_gracefully(): void
    {
        $groqMock = $this->createMock(GroqClient::class);
        $groqMock->expects($this->once())
            ->method('chat')
            ->willThrowException(new \RuntimeException('Groq API Error'));

        $extractor = new PdfTextExtractor();
        $summarizer = new DocumentSummarizer($groqMock, $extractor);

        $summarizer->summarize($this->document);

        $this->document->refresh();

        $this->assertEquals(Document::SUMMARY_FAILED, $this->document->summary_status);
        $this->assertEquals('Ringkasan gagal dibuat. Silakan coba lagi.', $this->document->summary_error);
    }
}
