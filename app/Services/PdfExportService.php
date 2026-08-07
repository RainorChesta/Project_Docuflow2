<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    /**
     * Build a PDF for a document's display content.
     *
     * @throws BusinessLogicException if the document has no exportable content
     */
    public function export(Document $document, User $user): array
    {
        $display = $document->displayVersion();

        if (!$display || !trim(strip_tags($display->content))) {
            throw new BusinessLogicException('No content available to export.');
        }

        $html = $this->buildHtml($document, $this->resolveImagePaths($display->content));

        // DEBUG: verifikasi src gambar yang masuk ke DomPDF
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $imgMatches);
        \Illuminate\Support\Facades\Log::debug('PDF export images', [
            'document_id' => $document->id,
            'count' => count($imgMatches[1]),
            'srcs' => $imgMatches[1],
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'dejavu sans');
        // Izinkan DomPDF membaca file gambar dari folder public/ (mis. storage uploads).
        // public/storage adalah symlink ke storage/app/public, jadi kedua path di-chroot.
        $options->setChroot(array_merge(
            [public_path(), storage_path('app/public')],
            $options->getChroot()
        ));

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $this->filename($document);

        $path = 'exports/' . $filename;
        Storage::disk('local')->put($path, $dompdf->output());

        return [
            'filename' => $filename,
            'path' => $path,
        ];
    }

    /**
     * Lebar konten PDF dalam px (dompdf 96dpi):
     * A4 portrait = 595.28pt, margin @page kiri+kanan = 96px = 72pt
     * → konten = 523.28pt = 697.7px. 690 = buffer aman.
     */
    private const MAX_IMG_WIDTH_PX = 690;

    /**
     * Ganti semua src gambar lokal (relatif /storage/...) menjadi path file
     * absolut, supaya DomPDF bisa membacanya dari disk. URL eksternal
     * (http/https) dibiarkan — DomPDF akan fetch via isRemoteEnabled.
     *
     * Sekaligus skala gambar besar ke lebar konten PDF (preserve aspect ratio),
     * karena dompdf tidak selalu menghormati CSS max-width:100% pada <img>
     * yang punya atribut width eksplisit / native besar.
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

                // URL absolut / data URI / anchor — biarkan apa adanya
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

                // 1) ganti src ke path absolut
                $tag = str_replace($src, $path, $tag);

                // 2) skala gambar agar tidak melebihi lebar konten PDF
                $size = @getimagesize($path);
                if ($size === false) {
                    return $tag;
                }
                [$nativeW, $nativeH] = $size;

                // lebar target: lebar eksplisit di tag (kalau ada), dibatasi max
                $targetW = preg_match('/\bwidth=["\']?(\d+)["\']?/i', $tag, $wm)
                    ? min((int) $wm[1], self::MAX_IMG_WIDTH_PX)
                    : min($nativeW, self::MAX_IMG_WIDTH_PX);

                $targetH = (int) round($targetW * $nativeH / $nativeW);

                // buang width/height (atribut & di dalam style) biar deterministik
                $tag = preg_replace('/\s(width|height)=["\'][^"\']*["\']/i', '', $tag);
                $tag = preg_replace('/\b(width|height)\s*:\s*[^;"\']+/i', '', $tag);

                // sisipkan width/height baru sebelum tag ditutup
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

    private function buildHtml(Document $document, string $content): string
    {
        // Header/footer via dompdf inline script: title + export date + page numbers.
        $head = <<<'HTML'
        <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('helvetica', 'normal');
            $pdf->page_text(72, 36, $PAGE_NUM . ' / ' . $PAGE_COUNT, $font, 8, array(0.4, 0.4, 0.4));
        }
        </script>
        HTML;

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            {$head}
            <style>
                @page { margin: 72px 48px 60px; }
                body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #111; line-height: 1.5; }
                table { border-collapse: collapse; width: 100%; }
                table th, table td { border: 1px solid #ccc; padding: 4px 6px; }
                img { max-width: 100%; height: auto; }
            </style>
        </head>
        <body>
            <div class="paper">{$content}</div>
        </body>
        </html>
        HTML;
    }
}
