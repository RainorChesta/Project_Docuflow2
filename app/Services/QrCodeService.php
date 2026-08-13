<?php

namespace App\Services;

use App\Models\Document;
use DOMDocument;
use DOMXPath;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Crypt;

class QrCodeService
{
    /**
     * Ukuran gambar QR (px, persegi) — dipakai untuk render server-side
     * (data URI) maupun endpoint gambar untuk print client-side.
     */
    private const SIZE = 160;

    /**
     * URL QR code dokumen — pakai token TERENKRIPSI (bukan ID mentah),
     * supaya ID dokumen tidak bocor di QR. Token = base64url dari
     * Crypt::encryptString(id) — URL-friendly (tanpa +/=/ dll).
     * Resolver-nya di DocumentController::viewByHash (route documents.hash).
     */
    public function qrcodeUrl(Document $document): string
    {
        $token = rtrim(strtr(base64_encode(Crypt::encryptString((string) $document->id)), '+/', '-_'), '=');

        return route('documents.hash', ['token' => $token]);
    }

    /**
     * Generate QR code sebagai PNG mentah (bytes). Dipakai oleh route
     * documents.qrcode (lihat DocumentController::qrCode) — endpoint ini
     * yang di-load client-side oleh tombol "print" di toolbar Jodit
     * (lihat getCleanValue({forPrint:true}) di resources/js/jodit.js).
     */
    public function pngBytes(string $url): string
    {
        return $this->build($url)->getString();
    }

    /**
     * Generate QR code sebagai data URI base64 — langsung dipakai sebagai
     * src <img> tanpa request HTTP tambahan. Dipakai untuk render
     * server-side: halaman show/preview/preview-version (lihat
     * injectPlaceholder di bawah) & PdfExportService.
     */
    public function dataUri(string $url): string
    {
        return $this->build($url)->getDataUri();
    }

    private function build(string $url)
    {
return (new Builder(
        writer: new PngWriter(),
        data: $url,
        size: self::SIZE,
        margin: 4,
    ))->build();
    }

    /**
     * Ganti semua placeholder QR di dalam $html jadi <img> QR code ASLI.
     *
     * Placeholder dicari berdasarkan TEKS "[QR CODE DOKUMEN <size>px]",
     * BUKAN atribut data-qr-placeholder — karena Jodit membuang semua
     * atribut data-* saat konten di-clean/save di client (lihat catatan di
     * resources/js/jodit.js, fungsi getCleanValue), jadi atribut itu SUDAH
     * TIDAK ADA LAGI di HTML yang tersimpan ke DB. Teks tidak pernah kena
     * strip Jodit, jadi pencarian berbasis teks ini yang bisa diandalkan.
     *
     * Placeholder TIDAK pernah diganti saat konten disimpan ke DB — cuma
     * saat DI-RENDER untuk ditampilkan/dicetak (show/preview/
     * preview-version/PDF export). Ini kenapa QR selalu "hidup" mengikuti
     * URL dokumen yang sama, bukan gambar beku hasil generate satu kali.
     *
     * DOM-based (bukan str_replace) — konsisten dengan pola yang sudah
     * dipakai PdfExportService::normalizeContentFonts().
     */
    public function injectPlaceholder(?string $html, Document $document): string
    {
        if (!$html || trim($html) === '' || !preg_match('/\[QR CODE DOKUMEN\s*(\d+)px\]/i', $html)) {
            return $html ?? '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="utf-8"?><div id="__qr_root__">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        if (!$loaded) {
            return $html;
        }

        $dataUri = $this->dataUri($this->qrcodeUrl($document));
        $xpath = new DOMXPath($dom);

        // //*[not(*)] = elemen "daun" (tidak punya elemen anak) — placeholder
        // selalu berupa <span> daun berisi teks marker. Snapshot ke array dulu
        // (iterator_to_array) karena kita mengganti node saat iterasi —
        // NodeList live akan kacau kalau dimodifikasi sambil di-loop.
        foreach (iterator_to_array($xpath->query('//*[not(*)]')) as $el) {
            /** @var \DOMElement $el */
            if (!preg_match('/\[QR CODE DOKUMEN\s*(\d+)px\]/i', $el->textContent, $m)) {
                continue;
            }

            $size = max(40, min(400, (int) $m[1]));
            $img = $dom->createElement('img');
            $img->setAttribute('src', $dataUri);
            $img->setAttribute('alt', 'QR Code Dokumen');
            $img->setAttribute('style', "width:{$size}px;height:{$size}px;vertical-align:middle;");
            $el->parentNode->replaceChild($img, $el);
        }

        $root = $dom->getElementById('__qr_root__');
        if (!$root) {
            return $html;
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }
}