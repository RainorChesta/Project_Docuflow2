<?php

namespace App\Services;

use App\AI\Contracts\AIClientInterface;
use App\AI\Prompts\DocumentSummaryPrompt;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Ringkasan dokumen berbasis AI. Alur:
 *
 *   extract (PDF/DOCX/Editor) → clean → cek kosong/singkat → chunk (bila panjang) → ringkas → sanitize
 *
 * Teks dan chunk hanya bersifat sementara — tidak pernah disimpan ke DB.
 * Dokumen kosong atau kurang dari 1 paragraf ditangani secara khusus dan profesional.
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
    public function summarize(Document $document, int $percentage = 30, string $locale = 'id'): void
    {
        $this->markProcessing($document);

        try {
            $source = $this->extractContent($document);
            $summary = $this->summarizeText($source, $percentage, $locale);

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

    /**
     * Ringkas teks dokumen dengan penanganan cerdas:
     * 1. Dokumen kosong: kembalikan pemberitahuan profesional terstruktur
     * 2. Dokumen < 1 paragraf: ringkas secara profesional dengan prompt khusus
     * 3. Dokumen normal/panjang: ringkas dengan chunking + density
     */
    public function summarizeText(string $text, int $percentage = 30, string $locale = 'id'): string
    {
        $text = $this->clean($text);

        // Kasus 1: Dokumen Kosong
        if ($text === '') {
            return self::emptyDocumentMessage($locale);
        }

        // Kasus 2: Dokumen Kurang Dari 1 Paragraf (1 kalimat atau teks ringkas)
        if ($this->isShortContent($text)) {
            $rawSummary = $this->aiClient->chat(
                DocumentSummaryPrompt::shortContentSystem($locale),
                DocumentSummaryPrompt::shortContentUser($text, $locale),
            );

            return $this->sanitizeSummary($rawSummary, $text, $locale);
        }

        // Kasus 3: Dokumen Normal / Panjang
        $chunkSize = (int) config('dokuflow.ai_summary.chunk_size', self::CHUNK_SIZE);

        // Satu chunk → satu panggilan langsung, hemat panggilan API.
        if (mb_strlen($text) <= $chunkSize) {
            $rawSummary = $this->aiClient->chat(
                DocumentSummaryPrompt::chunkSystem($percentage, $locale),
                DocumentSummaryPrompt::chunkContent($text, $locale),
            );

            return $this->sanitizeSummary($rawSummary, $text, $locale);
        }

        $chunks = $this->chunk($text);
        $payloads = [];

        foreach ($chunks as $chunk) {
            $payloads[] = [
                'system' => DocumentSummaryPrompt::chunkSystem($percentage, $locale),
                'content' => DocumentSummaryPrompt::chunkContent($chunk, $locale),
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
            return $this->sanitizeSummary($summaries[0], $text, $locale);
        }

        $rawFinal = $this->aiClient->chat(
            DocumentSummaryPrompt::combineSystem($percentage, $locale),
            DocumentSummaryPrompt::combineContent($summaries, $locale),
        );

        return $this->sanitizeSummary($rawFinal, $text, $locale);
    }

    /**
     * Pesan ringkasan resmi & profesional bila dokumen belum memiliki isi.
     */
    public static function emptyDocumentMessage(string $locale = 'id'): string
    {
        return match ($locale) {
            'en' => 'This document does not contain any written text content to summarize. Please ensure the document content is filled in before generating a summary.',
            default => 'Dokumen ini belum memuat isi atau teks tertulis untuk diringkas. Silakan lengkapi isi dokumen terlebih dahulu agar ringkasan dapat dihasilkan secara optimal.',
        };
    }

    /**
     * Deteksi apakah teks dokumen tergolong sangat pendek (kurang dari 1 paragraf / 1-2 kalimat).
     */
    public function isShortContent(string $text): bool
    {
        $clean = trim($text);
        if ($clean === '') {
            return true;
        }

        $wordCount = str_word_count(strip_tags($clean));
        $charCount = mb_strlen($clean);

        // Kurang dari 45 kata atau kurang dari 250 karakter terhitung kurang dari 1 paragraf standar
        return $wordCount < 45 || $charCount < 250;
    }

    /**
     * Sanitasi output ringkasan dari AI:
     * 1. Hapus pembungkus markdown block atau tanda kutip
     * 2. Deteksi dan tangani bila terjadi kebocoran aturan prompt (prompt leakage)
     */
    public function sanitizeSummary(string $summary, string $sourceText, string $locale = 'id'): string
    {
        $summary = trim($summary);

        // Hapus pembungkus markdown block ```markdown ... ``` atau ``` ... ```
        if (preg_match('/^```(?:markdown|text)?\s*([\s\S]*?)\s*```$/i', $summary, $matches)) {
            $summary = trim($matches[1]);
        }

        // Hapus tanda kutip pembungkus jika ada
        if ((str_starts_with($summary, '"') && str_ends_with($summary, '"')) ||
            (str_starts_with($summary, "'") && str_ends_with($summary, "'"))) {
            $summary = trim(substr($summary, 1, -1));
        }

        // Pola deteksi kebocoran aturan prompt
        $leakagePatterns = [
            '/aturan-aturan berikut/i',
            '/tidak menggunakan list/i',
            '/berbagi hanya paragraf/i',
            '/summaris akan mencakup/i',
            '/tidak menggunakan kata-kata introver/i',
            '/tidak ada filler words/i',
            '/ringkasan tidak memiliki halusinasi/i',
            '/absolute rules/i',
            '/strictly forbidden/i',
            '/only paragraphs/i',
            '/no filler words/i',
            '/no hallucination/i',
            '/output only the summary/i',
        ];

        $hasLeakage = false;
        foreach ($leakagePatterns as $pattern) {
            if (preg_match($pattern, $summary)) {
                $hasLeakage = true;
                break;
            }
        }

        if ($hasLeakage) {
            Log::warning('DocumentSummarizer: AI output contained prompt leakage. Falling back to sanitized executive summary.');
            return $this->generateFallbackProfessionalSummary($sourceText, $locale);
        }

        return $summary;
    }

    /**
     * Ringkasan profesional darurat bila AI menghasilkan respon tidak wajar / bocoran prompt.
     */
    private function generateFallbackProfessionalSummary(string $sourceText, string $locale = 'id'): string
    {
        $clean = trim($sourceText);
        if ($clean === '') {
            return self::emptyDocumentMessage($locale);
        }

        // Bersihkan tanda baca di akhir
        $clean = rtrim($clean, " \t\n\r\0\x0B.");

        if ($locale === 'en') {
            return "Executive Summary: " . ucfirst($clean) . ".";
        }

        return "Intisari Dokumen: " . ucfirst($clean) . ".";
    }

    /**
     * Ambil konten sumber dokumen: versi berkas (PDF/DOCX) diekstrak dari
     * disk via PdfTextExtractor, versi editor memakai konten versi aktif.
     */
    private function extractContent(Document $document): string
    {
        $version = $document->displayVersion();

        if (!$version) {
            return '';
        }

        if ($version->file_path) {
            try {
                return $this->pdfTextExtractor->extract($version->file_path, $version->file_mime);
            } catch (\Throwable $e) {
                // Berkas tidak dapat diekstrak atau kosong
                return '';
            }
        }

        $rawContent = $version->content ?? '';
        return $this->clean(strip_tags($rawContent));
    }

    private function clean(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\xc2\xa0", "\u{00a0}"], ' ', $text);
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
        if (config('app.env') === 'local') {
            return $e->getMessage();
        }

        if ($e instanceof RuntimeException) {
            if ($e->getMessage() === 'AIFallbackManager requires at least one AI client.') {
                return 'API Key belum dikonfigurasi. Silakan isi konfigurasi AI di file .env.';
            }

            if (stripos($e->getMessage(), 'groq') === false && stripos($e->getMessage(), 'http') === false) {
                return $e->getMessage();
            }
        }

        return 'Ringkasan gagal dibuat. Silakan coba lagi.';
    }
}