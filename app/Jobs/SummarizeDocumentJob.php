<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentSummarizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SummarizeDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Backoff bertahap untuk rate-limit (429) — jangan langsung memukul
     * API berulang kali setelah kena rate limit.
     */
    public function backoff(): array
    {
        return [5, 15, 30];
    }

    public function __construct(
        public int $documentId,
        public int $percentage = 30,
        public string $model = 'auto',
        public string $locale = 'id',
    ) {}

    public function handle(\App\Services\PdfTextExtractor $pdfExtractor): void
    {
        $document = Document::find($this->documentId);

        if (!$document) {
            return;
        }

        // Sudah selesai (mis. job duplikat) → jangan proses ulang.
        if ($document->isSummaryCompleted()) {
            return;
        }

        // Kunci per dokumen: cegah dua job memproses dokumen yang sama
        // secara bersamaan. Kunci dilepas otomatis setelah job selesai.
        $lock = Cache::lock('summarize:' . $this->documentId, 600);

        if (!$lock->get()) {
            return;
        }

        try {
            if ($this->model === 'auto') {
                $client = app(\App\AI\Contracts\AIClientInterface::class);
            } else {
                $client = match ($this->model) {
                    'groq' => app(\App\AI\GroqClient::class),
                    'deepseek' => app(\App\AI\DeepseekClient::class),
                    'ollama' => app(\App\AI\OllamaClient::class),
                    default => null,
                };

                if (!$client) {
                    throw new \Exception("Model AI '{$this->model}' belum dikonfigurasi (Konfigurasi / API Key tidak ditemukan).");
                }
            }

            $summarizer = new DocumentSummarizer($client, $pdfExtractor);
            $summarizer->summarize($document, $this->percentage, $this->locale);
        } finally {
            $lock->release();
        }
    }

    public function failed(\Throwable $exception): void
    {
        $document = Document::find($this->documentId);

        if ($document) {
            $document->update([
                'summary_status' => Document::SUMMARY_FAILED,
                'summary_error' => 'Gagal meringkas: ' . $exception->getMessage(),
            ]);
        }
    }
}