<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    /**
     * Margin default (px), HARUS SAMA PERSIS dengan DEFAULT_MARGIN di
     * resources/js/jodit.js — itu sumber kebenaran untuk margin editor &
     * print. PHP tidak bisa import langsung dari file JS, jadi nilainya
     * disalin manual di sini. Kalau DEFAULT_MARGIN di jodit.js berubah,
     * nilai ini harus ikut disesuaikan.
     */
    private const DEFAULT_MARGIN = [
        'top' => 48,
        'right' => 56,
        'bottom' => 48,
        'left' => 56,
    ];

    /**
     * Path executable Chrome/Edge untuk headless print-to-pdf.
     * Coba beberapa lokasi umum (Windows + Linux).
     */
    private const CHROME_CANDIDATES = [
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        '/usr/bin/google-chrome',
        '/usr/bin/chromium',
        '/usr/bin/chromium-browser',
        '/snap/bin/chromium',
    ];

    /**
     * Pemetaan font-family Jodit (FONT_LIST di jodit.js) ke font yang
     * tersedia sebagai font SISTEM (Windows/Linux) — browser render pakai
     * font asli, jadi metrik karakter identik dengan editor. Key = versi
     * TERNORMALISASI (tanpa kutip, tanpa spasi ekstra).
     *
     * Google Fonts (Roboto/Open Sans/dst) diarahkan ke pengganti metrik
     * terdekat yang umum tersedia sebagai font sistem.
     */
    private const PDF_SAFE_FONT_MAP = [
        'Arial,Helvetica,sans-serif' => 'Arial, sans-serif',
        'Georgia,serif' => 'Georgia, serif',
        'Times New Roman,Times,serif' => 'Times New Roman, serif',
        'Courier New,Courier,monospace' => 'Courier New, monospace',
        'Roboto,sans-serif' => 'Arial, sans-serif',
        'Open Sans,sans-serif' => 'Arial, sans-serif',
        'Merriweather,serif' => 'Georgia, serif',
        'Poppins,sans-serif' => 'Arial, sans-serif',
        'Lora,serif' => 'Georgia, serif',
        'Source Code Pro,monospace' => 'Courier New, monospace',
    ];

    /**
     * Build a PDF for a document's display content via headless Chrome
     * print-to-pdf — engine render SAMA dengan browser, jadi hasilnya
     * konsisten dengan print di editor Jodit (font asli, CSS @page,
     * pagination browser).
     *
     * @param string|null $paperSizeOverride Ukuran kertas opsional (A4/A5/
     *        A3/Letter/Legal) dari form export di halaman show — override
     *        HANYA untuk export ini, tidak mengubah $document->paper_size.
     *        Kalau null, pakai $document->paper_size (fallback 'A4').
     *
     * @throws BusinessLogicException if the document has no exportable content
     */
    public function export(Document $document, User $user, ?string $paperSizeOverride = null): array
    {
        $display = $document->displayVersion();

        if (!$display || !trim(strip_tags($display->content))) {
            throw new BusinessLogicException('No content available to export.');
        }

        $chrome = $this->findChrome();
        if (!$chrome) {
            throw new BusinessLogicException('Chrome/Edge tidak ditemukan di server untuk generate PDF.');
        }

        $content = app(\App\Services\SignatureResolverService::class)->resolve($display->content, $document, $user, true);
        $content = $this->normalizeContentFonts($content);
        $html = $this->buildHtml($document, $this->resolveImagePaths($content), $paperSizeOverride);

        $filename = $this->filename($document);
        $path = 'exports/' . $filename;

        // File HTML temp + output PDF, keduanya di disk private.
        $htmlPath = storage_path('app/private/exports/tmp_' . uniqid() . '.html');
        $pdfPath = storage_path('app/private/' . $path);

        try {
            file_put_contents($htmlPath, $html);

            $cmd = sprintf(
                '%s --headless=new --disable-gpu --no-pdf-header-footer --print-to-pdf=%s %s 2>&1',
                escapeshellarg($chrome),
                escapeshellarg($pdfPath),
                escapeshellarg($htmlPath)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode !== 0 || !is_file($pdfPath) || filesize($pdfPath) === 0) {
                report(new \RuntimeException('Chrome print-to-pdf gagal: ' . implode("\n", $output)));

                throw new BusinessLogicException('PDF generation failed.');
            }
        } finally {
            @unlink($htmlPath);
        }

        return [
            'filename' => $filename,
            'path' => $path,
        ];
    }

    private function findChrome(): ?string
    {
        foreach (self::CHROME_CANDIDATES as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        $which = @exec('which google-chrome chromium chromium-browser 2>/dev/null');

        return $which ?: null;
    }

    /**
     * Normalisasi semua deklarasi font-family di dalam konten ke font
     * sistem (lihat PDF_SAFE_FONT_MAP). Parsing DOM (bukan str_replace
     * di seluruh HTML mentah) supaya perubahan hanya menyentuh atribut
     * style, dan pencocokan kebal variasi kutip/spasi serialisasi browser.
     *
     * Kalau parsing gagal, konten asli dikembalikan.
     */
    private function normalizeContentFonts(string $content): string
    {
        if (trim($content) === '') {
            return $content;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8"?><div id="__pdf_font_root__">' . $content . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        if (!$loaded) {
            return $content;
        }

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//*[@style]') as $el) {
            /** @var \DOMElement $el */
            $original = $el->getAttribute('style');
            $normalized = $this->replaceFontFamilyInStyle($original);

            if ($normalized !== $original) {
                $el->setAttribute('style', $normalized);
            }
        }

        $root = $dom->getElementById('__pdf_font_root__');
        if (!$root) {
            return $content;
        }

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return $html;
    }

    /**
     * Ganti deklarasi "font-family: ..." di dalam SATU string style="...".
     * Bandingkan versi ternormalisasi terhadap PDF_SAFE_FONT_MAP; kalau
     * tidak ada yang cocok, nilai asli dibiarkan.
     */
    private function replaceFontFamilyInStyle(string $style): string
    {
        return preg_replace_callback(
            '/font-family\s*:\s*([^;]+)/i',
            function (array $m): string {
                $raw = trim($m[1]);
                $key = $this->normalizeFontFamilyValue($raw);
                $safe = self::PDF_SAFE_FONT_MAP[$key] ?? null;

                return 'font-family: ' . ($safe ?? $raw);
            },
            $style
        );
    }

    /**
     * Normalisasi nilai font-family untuk pencocokan: buang kutip
     * (literal ' " dan HTML entity), rapikan spasi di sekitar koma.
     */
    private function normalizeFontFamilyValue(string $value): string
    {
        $value = str_replace(['&#39;', '&#039;', '&quot;', '"', "'"], '', $value);
        $parts = array_map('trim', explode(',', $value));

        return implode(',', $parts);
    }

    /**
     * Ganti semua src gambar lokal (relatif /storage/...) menjadi URL
     * file:/// absolut supaya headless Chrome (yang membuka HTML dari
     * disk) bisa memuatnya. URL eksternal/data URI/anchor dibiarkan.
     * Sekaligus skala gambar besar ke lebar konten (preserve aspect ratio).
     */
    private function resolveImagePaths(string $content): string
    {
        return preg_replace_callback(
            '/<img\b[^>]*>/i',
            function (array $m): string {
                $tag = $m[0];

                if (!preg_match('/src=["\']([^"\']+)["\']/i', $tag, $srcM)) {
                    return $tag;
                }
                $src = $srcM[1];

                // URL absolut / data URI / anchor / file — biarkan apa adanya
                if (
                    preg_match('#^(https?:)?//#i', $src)
                    || str_starts_with($src, 'data:')
                    || str_starts_with($src, '#')
                    || str_starts_with($src, 'file://')
                ) {
                    return $tag;
                }

                // Path publik relatif, mis. /storage/jodit-uploads/x.png
                $path = public_path(ltrim($src, '/'));
                if (!is_file($path)) {
                    return $tag;
                }

                // 1) ganti src ke URL file:/// absolut (format Chrome)
                $fileUrl = 'file:///' . str_replace('\\', '/', $path);
                $tag = str_replace($src, $fileUrl, $tag);

                // 2) skala gambar agar tidak melebihi lebar konten
                $size = @getimagesize($path);
                if ($size === false) {
                    return $tag;
                }
                [$nativeW, $nativeH] = $size;

                $targetW = preg_match('/\bwidth=["\']?(\d+)["\']?/i', $tag, $wm)
                    ? min((int) $wm[1], self::MAX_IMG_WIDTH_PX)
                    : min($nativeW, self::MAX_IMG_WIDTH_PX);

                $targetH = (int) round($targetW * $nativeH / $nativeW);

                $tag = preg_replace('/\s(width|height)=["\'][^"\']*["\']/i', '', $tag);
                $tag = preg_replace('/\b(width|height)\s*:\s*[^;"\']+/i', '', $tag);

                return preg_replace(
                    '/\s*\/?>/i',
                    ' width="' . $targetW . '" height="' . $targetH . '" />',
                    $tag,
                    1
                );
            },
            $content
        );
    }

    /**
     * Lebar konten (px) — buffer untuk dokumen A4 portrait dengan margin
     * default kiri+kanan (56+56px).
     */
    private const MAX_IMG_WIDTH_PX = 690;

    /**
     * Ruang tulis minimum per halaman (px) — SAMA PERSIS dengan
     * MIN_PAGE_CONTENT_PX di resources/js/jodit.js. Dipakai untuk mengecek
     * apakah margin dokumen muat di dalam kertas.
     */
    private const MIN_PAGE_CONTENT_PX = 60;

    /**
     * Ukuran kertas (px @96dpi) — SAMA PERSIS dengan PAPER_SIZES di
     * resources/js/jodit.js (sumber kebenaran ukuran kertas editor).
     * Dipakai untuk mengecek apakah margin dokumen muat di dalam kertas.
     */
    private const PAPER_SIZES_PX = [
        'A4' => ['width' => 794, 'height' => 1123],
        'A5' => ['width' => 559, 'height' => 794],
        'A3' => ['width' => 1123, 'height' => 1587],
        'Letter' => ['width' => 816, 'height' => 1056],
        'Legal' => ['width' => 816, 'height' => 1344],
    ];

    private function filename(Document $document): string
    {
        $title = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $document->title) ?: 'document';
        $division = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $document->division?->code ?? 'no_division') ?: 'no_division';
        $date = now()->format('Y-m-d');

        return "{$title}_{$division}_{$date}.pdf";
    }

    /**
     * Konversi px ke in untuk unit @page (96px = 1in) — SAMA PERSIS
     * dengan konversi doPrint() di jodit.js (px/96).
     */
    private function pxToIn(float $px): float
    {
        return round($px / 96, 4);
    }

    /**
     * Ambil margin dokumen (px), fallback ke DEFAULT_MARGIN kalau dokumen
     * belum pernah menyimpan pengaturan margin (48/56/48/56 — sama dengan
     * DEFAULT_MARGIN di jodit.js).
     */
    private function resolveMargin(Document $document): array
    {
        $m = $document->paper_margin ?? [];

        return [
            'top' => $m['top'] ?? self::DEFAULT_MARGIN['top'],
            'right' => $m['right'] ?? self::DEFAULT_MARGIN['right'],
            'bottom' => $m['bottom'] ?? self::DEFAULT_MARGIN['bottom'],
            'left' => $m['left'] ?? self::DEFAULT_MARGIN['left'],
        ];
    }

    private function buildHtml(Document $document, string $content, ?string $paperSizeOverride = null): string
    {
        $margin = $this->resolveMargin($document);
        // FIX: paperSizeOverride (dari form export halaman show) menang
        // atas paper_size tersimpan di dokumen — tapi HANYA untuk
        // rendering export ini, $document->paper_size sendiri tidak diubah.
        $paperSize = $paperSizeOverride ?? $document->paper_size ?? 'A4';

        // Clamp margin ke ukuran kertas: margin total (atas+bawah / kiri+kanan)
        // tidak boleh melebihi ukuran kertas. Tanpa ini, @page margin yang lebih
        // besar dari halaman membuat Chrome/Edge headless JATUH ke ukuran kertas
        // default (mis. Letter) dan margin diabaikan total — konten yang di
        // editor kelihatan di bawah halaman (margin besar) malah muncul di paling
        // atas halaman saat export. Nilai ini SAMA PERSIS dengan
        // clampMarginToPage() di resources/js/jodit.js — termasuk saat paperSize
        // di-override dari request export (bukan cuma dari $document->paper_size),
        // dua-duanya sekarang memakai $paperSize yang sudah final di atas.
        $page = self::PAPER_SIZES_PX[$paperSize] ?? self::PAPER_SIZES_PX['A4'];
        if ($margin['top'] + $margin['bottom'] > $page['height'] - self::MIN_PAGE_CONTENT_PX) {
            $margin['top'] = max(0, $page['height'] - self::MIN_PAGE_CONTENT_PX - $margin['bottom']);
        }
        if ($margin['left'] + $margin['right'] > $page['width'] - self::MIN_PAGE_CONTENT_PX) {
            $margin['left'] = max(0, $page['width'] - self::MIN_PAGE_CONTENT_PX - $margin['right']);
        }

        $topIn = $this->pxToIn($margin['top']);
        $rightIn = $this->pxToIn($margin['right']);
        $bottomIn = $this->pxToIn($margin['bottom']);
        $leftIn = $this->pxToIn($margin['left']);

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                /* Ukuran kertas & margin fisik — SAMA dengan @page yang
                   dibangun doPrint() di jodit.js (in, px/96). Browser
                   (Chrome headless) menerapkan margin ini ke SETIAP halaman,
                   jadi konsisten dengan print editor. */
                @page {
                    size: {$paperSize} portrait;
                    margin: {$topIn}in {$rightIn}in {$bottomIn}in {$leftIn}in;
                }
                /* font-size & line-height sama persis dengan .doku-paper
                   (_paper.blade.php): 16px / normal. */
                body { font-family: 'Times New Roman', Times, serif; font-weight: normal; font-size: 16px; line-height: normal; color: #111; }
                table { border-collapse: collapse; width: 100%; }
                table th, table td { border: 1px solid #ccc; padding: 4px 6px; }
                img { max-width: 100%; height: auto; }
                /* Elemen level atas tidak dipotong di tengah saat pindah
                   halaman (break-inside: avoid) — konsisten dengan aturan
                   doPrint/repaginateEditor di jodit.js. */
                .paper > * { break-inside: avoid; page-break-inside: avoid; }
            </style>
        </head>
        <body>
            <div class="paper">{$content}</div>
        </body>
        </html>
        HTML;
    }
}