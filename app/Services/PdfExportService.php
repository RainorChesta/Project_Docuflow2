<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Models\User;
use DOMDocument;
use DOMXPath;

class PdfExportService
{
    public function __construct(
        protected QrCodeService $qrCodeService,
    ) {}

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
     * Pemetaan font-family Jodit (FONT_LIST di jodit.js) ke font SISTEM
     * (Windows/Linux) — browser render pakai font asli, tanpa perlu
     * download Google Fonts async, jadi layout langsung stabil begitu DOM
     * ter-parse (script pagination tidak perlu menunggu font network).
     *
     * PENTING: dipanggil via normalizeContentFonts() di export() — TANPA
     * pemanggilan ini, teks yang di-set user pakai font custom (Roboto,
     * Poppins, dst di toolbar) akan JATUH KE FONT DEFAULT BROWSER di PDF,
     * padahal di editor & preview font itu ke-render asli lewat Google
     * Fonts @import. Beda font = beda lebar karakter = beda titik wrap
     * paragraf = JUMLAH BARIS PER HALAMAN BEDA dari yang dihitung script
     * pagination di editor. Ini penyebab paling umum "selisih baris" PDF.
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

    private const VIRTUAL_TIME_BUDGET_MS = 8000;

    /**
     * Ruang tulis minimum per halaman (px) — SAMA PERSIS dengan
     * MIN_PAGE_CONTENT_PX di resources/js/jodit.js.
     */
    private const MIN_PAGE_CONTENT_PX = 60;

    /**
     * Ukuran kertas (px @96dpi) — SAMA PERSIS dengan PAPER_SIZES di
     * resources/js/jodit.js.
     */
    private const PAPER_SIZES_PX = [
        'A4' => ['width' => 794, 'height' => 1123],
        'A5' => ['width' => 559, 'height' => 794],
        'A3' => ['width' => 1123, 'height' => 1587],
        'Letter' => ['width' => 816, 'height' => 1056],
        'Legal' => ['width' => 816, 'height' => 1344],
    ];

    /**
     * Cap absolut lebar gambar (px) — jaring pengaman terakhir kalau
     * suatu saat contentWidth dihitung salah / sangat besar (bukan
     * batas utama; batas utama sekarang mengikuti $contentWidth kertas
     * yang aktif, lihat resolveImagePaths()).
     */
    private const ABSOLUTE_MAX_IMG_WIDTH_PX = 2000;

    /**
     * Build a PDF for a document's display content via headless Chrome
     * print-to-pdf.
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

        // Hitung metrik halaman (ukuran kertas, margin ter-clamp, content
        // width/height) SEKALI di sini — dipakai bareng untuk resize
        // gambar (resolveImagePaths) DAN untuk membangun HTML (buildHtml),
        // supaya keduanya selalu pakai angka yang identik, tidak dihitung
        // dua kali secara terpisah yang berisiko drift.
        $metrics = $this->resolvePageMetrics($document, $paperSizeOverride);

        $content = app(SignatureResolverService::class)->resolve($display->content, $document, $user, true);
        $content = $this->qrCodeService->injectPlaceholder($content, $document);

        // WAJIB dipanggil SEBELUM buildHtml() — tanpa ini font custom user
        // (Roboto/Poppins/dst) tidak ter-render sama sekali di PDF.
        $content = $this->normalizeContentFonts($content);

        // Skala gambar mengikuti CONTENT WIDTH KERTAS AKTIF (bukan angka
        // hardcode) — supaya gambar di PDF proporsinya sama dengan yang
        // terlihat user di editor/preview, apa pun ukuran kertas yang
        // dipilih (A3 lebih lebar, A5 lebih sempit, dst).
        $content = $this->resolveImagePaths($content, $metrics['contentWidth']);

        $html = $this->buildHtml($content, $metrics);

        $filename = $this->filename($document);
        $path = 'exports/' . $filename;

        $htmlPath = storage_path('app/private/exports/tmp_' . uniqid() . '.html');
        $pdfPath = storage_path('app/private/' . $path);

        try {
            if (!is_dir(dirname($htmlPath))) {
                @mkdir(dirname($htmlPath), 0755, true);
            }
            file_put_contents($htmlPath, $html);

            $cmd = sprintf(
                '%s --headless=new --disable-gpu --no-pdf-header-footer --virtual-time-budget=%d --print-to-pdf=%s %s 2>&1',
                escapeshellarg($chrome),
                self::VIRTUAL_TIME_BUDGET_MS,
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

    private function normalizeFontFamilyValue(string $value): string
    {
        $value = str_replace(['&#39;', '&#039;', '&quot;', '"', "'"], '', $value);
        $parts = array_map('trim', explode(',', $value));

        return implode(',', $parts);
    }

    /**
     * Ganti semua src gambar lokal (relatif /storage/...) menjadi URL
     * file:/// absolut supaya headless Chrome bisa memuatnya. URL
     * eksternal/data URI/anchor dibiarkan.
     *
     * $contentWidth: lebar area konten KERTAS AKTIF (sudah dihitung dari
     * ukuran kertas + margin ter-clamp di resolvePageMetrics()) — gambar
     * di-skala supaya tidak melebihi lebar ini, SAMA PERSIS seperti
     * `max-width:100%` yang berlaku di editor/preview terhadap paper
     * yang sedang aktif. Sebelumnya nilai ini hardcode 690px (asumsi A4),
     * sehingga di kertas A3 gambar terlihat lebih kecil dari preview, dan
     * di kertas A5 gambar bisa overflow keluar margin.
     */
    private function resolveImagePaths(string $content, int $contentWidth): string
    {
        $maxWidth = min($contentWidth, self::ABSOLUTE_MAX_IMG_WIDTH_PX);

        return preg_replace_callback(
            '/<img\b[^>]*>/i',
            function (array $m) use ($maxWidth): string {
                $tag = $m[0];

                if (!preg_match('/src=["\']([^"\']+)["\']/i', $tag, $srcM)) {
                    return $tag;
                }
                $src = $srcM[1];

                if (
                    preg_match('#^(https?:)?//#i', $src)
                    || str_starts_with($src, 'data:')
                    || str_starts_with($src, '#')
                    || str_starts_with($src, 'file://')
                ) {
                    return $tag;
                }

                $path = public_path(ltrim($src, '/'));
                if (!is_file($path)) {
                    return $tag;
                }

                $fileUrl = 'file:///' . str_replace('\\', '/', $path);
                $tag = str_replace($src, $fileUrl, $tag);

                $size = @getimagesize($path);
                if ($size === false) {
                    return $tag;
                }
                [$nativeW, $nativeH] = $size;

                $targetW = preg_match('/\bwidth=["\']?(\d+)["\']?/i', $tag, $wm)
                    ? min((int) $wm[1], $maxWidth)
                    : min($nativeW, $maxWidth);

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

    private function filename(Document $document): string
    {
        $title = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $document->title) ?: 'document';
        $division = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $document->division?->code ?? 'no_division') ?: 'no_division';
        $date = now()->format('Y-m-d');

        return "{$title}_{$division}_{$date}.pdf";
    }

    private function pxToIn(float $px): float
    {
        return round($px / 96, 4);
    }

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

    /**
     * Hitung SEMUA metrik halaman (ukuran kertas, margin ter-clamp,
     * content width, content-per-page) dalam SATU tempat — dipakai
     * bareng oleh export() (untuk resize gambar) dan buildHtml() (untuk
     * @page CSS + script pagination), supaya kedua konsumen ini TIDAK
     * PERNAH menghitung angka yang berbeda satu sama lain.
     */
    private function resolvePageMetrics(Document $document, ?string $paperSizeOverride = null): array
    {
        $margin = $this->resolveMargin($document);
        $paperSize = $paperSizeOverride ?? $document->paper_size ?? 'A4';
        $page = self::PAPER_SIZES_PX[$paperSize] ?? self::PAPER_SIZES_PX['A4'];

        if ($margin['top'] + $margin['bottom'] > $page['height'] - self::MIN_PAGE_CONTENT_PX) {
            $margin['top'] = max(0, $page['height'] - self::MIN_PAGE_CONTENT_PX - $margin['bottom']);
        }
        if ($margin['left'] + $margin['right'] > $page['width'] - self::MIN_PAGE_CONTENT_PX) {
            $margin['left'] = max(0, $page['width'] - self::MIN_PAGE_CONTENT_PX - $margin['right']);
        }

        return [
            'paperSize' => $paperSize,
            'page' => $page,
            'margin' => $margin,
            'contentWidth' => $page['width'] - $margin['left'] - $margin['right'],
            'contentPerPage' => max($page['height'] - $margin['top'] - $margin['bottom'], 1),
        ];
    }



    /**
     * Bangun HTML lengkap untuk headless Chrome print-to-pdf.
     *
     * PENTING: TIDAK ADA script pagination JavaScript di sini.
     * Chrome menangani page-break secara native lewat @page CSS.
     * Sebelumnya ada buildPaginationScript() yang menyisipkan
     * `break-before: page` via JS — ini BENTROK dengan mekanisme
     * @page Chrome sehingga elemen yang sudah natural di halaman
     * berikutnya dipaksa break lagi → muncul halaman kosong.
     */
    private function buildHtml(string $content, array $metrics): string
    {
        $margin = $metrics['margin'];
        $paperSize = $metrics['paperSize'];
        $contentWidth = $metrics['contentWidth'];

        $topIn = $this->pxToIn($margin['top']);
        $rightIn = $this->pxToIn($margin['right']);
        $bottomIn = $this->pxToIn($margin['bottom']);
        $leftIn = $this->pxToIn($margin['left']);

        $sharedCss = $this->sharedTypographyCss();

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                *, ::before, ::after { box-sizing: border-box; }
                @page {
                    size: {$paperSize} portrait;
                    margin: {$topIn}in {$rightIn}in {$bottomIn}in {$leftIn}in;
                }
                html, body { margin: 0; padding: 0; }
                body { orphans: 2; widows: 2; }
                .paper {
                    width: {$contentWidth}px;
                    box-sizing: content-box;
                }
                {$sharedCss}
            </style>
        </head>
        <body>
            <div class="paper doku-content">{$content}</div>
        </body>
        </html>
        HTML;
    }

    private function sharedTypographyCss(): string
    {
        return <<<CSS
            .doku-content, .doku-paper { font-family: Arial, sans-serif; font-size: 16px; line-height: 1.5; color: #000; word-wrap: break-word; text-align: left; }
            :is(.doku-content, .doku-paper) p { margin-top: 0; margin-bottom: 1em; }
            :is(.doku-content, .doku-paper) ul, :is(.doku-content, .doku-paper) ol { margin-top: 0; margin-bottom: 1em; padding-left: 40px !important; }
            :is(.doku-content, .doku-paper) ul { list-style-type: disc !important; }
            :is(.doku-content, .doku-paper) ul ul { list-style-type: circle !important; margin-bottom: 0; }
            :is(.doku-content, .doku-paper) ul ul ul { list-style-type: square !important; }
            :is(.doku-content, .doku-paper) ol { list-style-type: decimal !important; }
            :is(.doku-content, .doku-paper) ol ol { list-style-type: lower-alpha !important; margin-bottom: 0; }
            :is(.doku-content, .doku-paper) ol ol ol { list-style-type: lower-roman !important; }
            :is(.doku-content, .doku-paper) li { margin-bottom: 4px; display: list-item !important; text-align: match-parent; }
            :is(.doku-content, .doku-paper) li > ul, :is(.doku-content, .doku-paper) li > ol { margin-bottom: 0; }
            :is(.doku-content, .doku-paper) h1, :is(.doku-content, .doku-paper) h2, :is(.doku-content, .doku-paper) h3, :is(.doku-content, .doku-paper) h4, :is(.doku-content, .doku-paper) h5, :is(.doku-content, .doku-paper) h6 { margin-top: 1.2em; margin-bottom: 0.5em; font-weight: bold !important; line-height: 1.2; }
            :is(.doku-content, .doku-paper) h1 { font-size: 2em !important; }
            :is(.doku-content, .doku-paper) h2 { font-size: 1.5em !important; }
            :is(.doku-content, .doku-paper) h3 { font-size: 1.17em !important; }
            :is(.doku-content, .doku-paper) h4 { font-size: 1em !important; }
            :is(.doku-content, .doku-paper) h5 { font-size: 0.83em !important; }
            :is(.doku-content, .doku-paper) h6 { font-size: 0.67em !important; }
            :is(.doku-content, .doku-paper) table { border-collapse: collapse; width: 100%; margin-bottom: 1em; }
            :is(.doku-content, .doku-paper) th, :is(.doku-content, .doku-paper) td { border: 1px solid #ccc; padding: 8px; text-align: left; }
            :is(.doku-content, .doku-paper) th { font-weight: bold; background-color: #f9fafb; }
            :is(.doku-content, .doku-paper) blockquote { margin: 1em 40px; border-left: 4px solid #ccc; padding-left: 1em; color: #666; }
            :is(.doku-content, .doku-paper) pre { background: #f4f4f4; padding: 1em; overflow-x: auto; font-family: monospace; }
            :is(.doku-content, .doku-paper) b, :is(.doku-content, .doku-paper) strong { font-weight: bold !important; }
            :is(.doku-content, .doku-paper) i, :is(.doku-content, .doku-paper) em { font-style: italic !important; }
            :is(.doku-content, .doku-paper) u { text-decoration: underline !important; }
            :is(.doku-content, .doku-paper) img { display: inline; max-width: 100%; height: auto; }
            :is(.doku-content, .doku-paper) a { color: #1a0dab; text-decoration: underline; }
            :is(.doku-content, .doku-paper) hr { margin: 1em 0; border: none; border-top: 1px solid #ccc; }
            :is(.doku-content, .doku-paper) sub { vertical-align: sub; font-size: smaller; }
            :is(.doku-content, .doku-paper) sup { vertical-align: super; font-size: smaller; }
        CSS;
    }
}