<?php

namespace App\Services;

use App\AI\Contracts\AIClientInterface;
use App\AI\Prompts\DocumentSummaryPrompt;
use App\Models\Document;
use RuntimeException;

/**
 * Ringkasan dokumen berbasis AI. Alur:
 *
 *   extract (PDF/DOCX) → clean → chunk → ringkas per chunk → gabung
 *
 * Teks dan chunk hanya bersifat sementara — tidak pernah disimpan ke DB.
 * Dokumen dengan konten kecil langsung diringkas dalam satu panggilan.
 */
class DocumentSummarizer
{
    private const CHUNK_SIZE = 12000;
    private const CHUNK_OVERLAP = 200;

    public function __construct(
        private readonly AIClientInterface $aiClient,
        private readonly PdfTextExtractor $pdfTextExtractor,
    ) {}

    /**
     * Ringkas dokumen dan simpan hasilnya. Dipanggil dari queue job.
     * Meng-update status dokumen: processing → completed | failed.
     */
    public function summarize(Document $document, int $percentage = 30): void
    {
        $this->markProcessing($document);

        try {
            $source = $this->extractContent($document);
            $summary = $this->summarizeText($source, $percentage);

            Document::whereKey($document->id)->update([
                'summary' => $summary,
                'summary_status' => Document::SUMMARY_COMPLETED,
                'summary_error' => null,
                'summary_completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Document::whereKey($document->id)->update([
                'summary_status' => Document::SUMMARY_FAILED,
                'summary_error' => $this->friendlyMessage($e),
            ]);

            report($e);
        }
    }

    public function summarizeText(string $text, int $percentage = 30): string
    {
        $text = $this->clean($text);

        if ($text === '') {
            throw new RuntimeException('Dokumen tidak memiliki konten untuk diringkas.');
        }

        $chunkSize = (int) config('dokuflow.ai_summary.chunk_size', self::CHUNK_SIZE);

        // Satu chunk → satu panggilan langsung, hemat panggilan API.
        if (mb_strlen($text) <= $chunkSize) {
            return $this->aiClient->chat(
                DocumentSummaryPrompt::chunkSystem($percentage),
                DocumentSummaryPrompt::chunkContent($text),
            );
        }

        $chunks = $this->chunk($text);
        $payloads = [];

        foreach ($chunks as $chunk) {
            $payloads[] = [
                'system' => DocumentSummaryPrompt::chunkSystem($percentage),
                'content' => DocumentSummaryPrompt::chunkContent($chunk),
            ];
        }

        // Proses API paralel (maksimal 5 request sekaligus untuk menghindari Rate Limit)
        $summaries = $this->aiClient->chatBatch($payloads, 5);
        
        // Urutkan ulang berdasarkan index (karena eksekusi paralel bisa selesai tidak berurutan)
        ksort($summaries);
        
        // Reset key array
        $summaries = array_values($summaries);

        // Satu chunk summary → langsung jadi final, tidak perlu gabung lagi.
        if (count($summaries) === 1) {
            return $summaries[0];
        }

        return $this->aiClient->chat(
            DocumentSummaryPrompt::combineSystem($percentage),
            DocumentSummaryPrompt::combineContent($summaries),
        );
    }

    /**
     * Ambil konten sumber dokumen: versi berkas (PDF/DOCX) diekstrak dari
     * disk via PdfTextExtractor, versi editor memakai konten versi aktif.
     */
    private function extractContent(Document $document): string
    {
        $version = $document->displayVersion();

        if (!$version) {
            throw new RuntimeException('Dokumen belum memiliki konten.');
        }

        if ($version->file_path) {
            return $this->pdfTextExtractor->extract($version->file_path, $version->file_mime);
        }

        $content = trim(strip_tags($version->content ?? ''));

        if ($content === '') {
            throw new RuntimeException('Dokumen tidak memiliki konten untuk diringkas.');
        }

        return $content;
    }

    private function clean(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        return trim($text);
    }

    /**
     * Pecah teks menjadi chunk dengan ukuran karakter konfigurable dan
     * overlap kecil supaya kalimat yang terpotong di batas chunk tetap
     * punya konteks.
     */
    private function chunk(string $text): array
    {
        $size = (int) config('dokuflow.ai_summary.chunk_size', self::CHUNK_SIZE);
        $overlap = min((int) config('dokuflow.ai_summary.chunk_overlap', self::CHUNK_OVERLAP), (int) ($size / 2));

        $chunks = [];
        $length = mb_strlen($text);
        $start = 0;

        while ($start < $length) {
            $end = min($start + $size, $length);

            if ($end < $length) {
                // Potong di spasi terakhir sebelum batas agar tidak
                // membelah kata/sentences secara kasar.
                $cut = mb_strrpos(mb_substr($text, $start, $size), ' ', 0, 'UTF-8');
                if ($cut !== false && $cut > $size / 2) {
                    $end = $start + $cut;
                }
            }

            $chunks[] = mb_substr($text, $start, $end - $start, 'UTF-8');

            if ($end >= $length) {
                break;
            }

            $start = $end - $overlap;
        }

        return $chunks;
    }

    private function markProcessing(Document $document): void
    {
        Document::whereKey($document->id)->update([
            'summary_status' => Document::SUMMARY_PROCESSING,
            'summary_started_at' => now(),
            'summary_error' => null,
        ]);
    }

    /**
     * Pesan error ramah pengguna — jangan pernah bocorkan detail internal,
     * API key, atau respons mentah Groq.
     */
    private function friendlyMessage(\Throwable $e): string
    {
        if ($e instanceof RuntimeException && stripos($e->getMessage(), 'groq') === false && stripos($e->getMessage(), 'http') === false) {
            return $e->getMessage();
        }

        return 'Ringkasan gagal dibuat. Silakan coba lagi.';
    }
}