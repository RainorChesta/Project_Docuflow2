<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Smalot\PdfParser\Parser as SmalotPdfParser;

/**
 * Service khusus untuk ekstraksi teks dari berkas dokumen (PDF & DOCX).
 * Berkas dibaca dari disk secara aman tanpa memuat seluruh berkas ke memori DB.
 */
class PdfTextExtractor
{
    /**
     * Ekstrak teks dari berkas berdasarkan path penyimpanan di disk.
     */
    public function extract(string $path, ?string $mime = null): string
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($path)) {
            throw new RuntimeException("Berkas tidak ditemukan pada path: {$path}");
        }

        $fullPath = $disk->path($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isPdf = $ext === 'pdf' || str_contains($mime ?? '', 'pdf');
        $isDocx = $ext === 'docx' || str_contains($mime ?? '', 'word')
            || str_contains($mime ?? '', 'officedocument');

        if ($isPdf) {
            $text = $this->extractPdfText($fullPath);
        } elseif ($isDocx) {
            $text = $this->extractDocxText($fullPath);
        } else {
            throw new RuntimeException('Format berkas tidak didukung untuk ekstraksi teks (hanya PDF atau DOCX).');
        }

        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('Unable to extract text from the PDF/document (mungkin berupa PDF hasil scan tanpa lapisan teks).');
        }

        return $text;
    }

    /**
     * Ekstrak teks dari berkas PDF menggunakan Smalot PDFParser dengan fallback stream parser.
     */
    public function extractPdfText(string $filePath): string
    {
        // 1. Coba ekstrak menggunakan Smalot PdfParser bila tersedia
        if (class_exists(SmalotPdfParser::class)) {
            try {
                $parser = new SmalotPdfParser();
                $pdf = $parser->parseFile($filePath);
                $text = trim($pdf->getText());

                if ($text !== '') {
                    return $text;
                }
            } catch (\Throwable $e) {
                // Lanjut ke fallback manual jika parser eksternal gagal
                report($e);
            }
        }

        // 2. Fallback: Parse objek stream + FlateDecode (gzuncompress) & raw text operators
        return $this->extractPdfTextFallback($filePath);
    }

    private function extractPdfTextFallback(string $filePath): string
    {
        $raw = @file_get_contents($filePath);
        if ($raw === false) {
            return '';
        }

        $texts = [];

        // Stream terkompresi FlateDecode: "stream ... endstream"
        if (preg_match_all('/stream\s*(.*?)\s*endstream/s', $raw, $m)) {
            foreach ($m[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded === false) {
                    continue;
                }
                if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)\s*Tj|\[(?:[^\[\]]*)\]\s*TJ/s', $decoded, $tm)) {
                    foreach ($tm[0] as $op) {
                        if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $op, $sm)) {
                            foreach ($sm[0] as $s) {
                                $texts[] = $this->unescapePdfString($s);
                            }
                        }
                    }
                }
            }
        }

        // Stream mentah tanpa kompresi
        if (empty($texts)) {
            if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)\s*Tj|\[(?:[^\[\]]*)\]\s*TJ/s', $raw, $tm)) {
                foreach ($tm[0] as $op) {
                    if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/s', $op, $sm)) {
                        foreach ($sm[0] as $s) {
                            $texts[] = $this->unescapePdfString($s);
                        }
                    }
                }
            }
        }

        return trim(implode(' ', $texts));
    }

    private function unescapePdfString(string $s): string
    {
        $s = trim($s, '()');
        $s = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $s);
        return preg_replace_callback('/\\\\([0-7]{1,3})/', fn($m) => chr(octdec($m[1])), $s);
    }

    /**
     * Ekstrak teks dari berkas DOCX dengan membaca word/document.xml.
     */
    public function extractDocxText(string $filePath): string
    {
        if (!class_exists(\ZipArchive::class)) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        $xml = preg_replace('/<\/w:p>|<\/w:tr>|<w:br\s*\/>/i', "\n", $xml);
        $text = preg_replace('/<[^>]+>/', '', $xml);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[ \t]+/', ' ', $text));
    }
}
