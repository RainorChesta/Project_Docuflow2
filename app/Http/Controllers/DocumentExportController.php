<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentExportController extends Controller
{
    public function __construct(
        protected PdfExportService $pdfService,
        protected AuditService $auditService,
    ) {}

    public function export(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('view', $document);

        // paper_size dikirim dari modal export/print di halaman show/preview.
        // Default cetak/ekspor adalah F4 (21 x 33 cm).
        // Override ukuran kertas HANYA untuk job ekspor/cetak kali ini —
        // TIDAK PERNAH mengubah paper_size yang tersimpan di dokumen.
        $validated = $request->validate([
            'paper_size' => 'nullable|string|in:F4,A4,A5,A3,Letter,Legal,Custom',
            'custom_width' => 'nullable|numeric|gt:0|required_if:paper_size,Custom',
            'custom_height' => 'nullable|numeric|gt:0|required_if:paper_size,Custom',
            'custom_unit' => 'nullable|string|in:cm,mm',
        ], [
            'custom_width.required_if' => __('Lebar kertas wajib diisi untuk ukuran custom.'),
            'custom_width.gt' => __('Lebar kertas harus bernilai lebih dari 0.'),
            'custom_width.numeric' => __('Lebar kertas harus berupa angka valid.'),
            'custom_height.required_if' => __('Tinggi kertas wajib diisi untuk ukuran custom.'),
            'custom_height.gt' => __('Tinggi kertas harus bernilai lebih dari 0.'),
            'custom_height.numeric' => __('Tinggi kertas harus berupa angka valid.'),
        ]);

        $paperSize = $validated['paper_size'] ?? 'F4';
        $customDimensions = null;
        if ($paperSize === 'Custom') {
            $customDimensions = [
                'width' => (float) $validated['custom_width'],
                'height' => (float) $validated['custom_height'],
                'unit' => $validated['custom_unit'] ?? 'cm',
            ];
        }

        try {
            $result = $this->pdfService->export($document, auth()->user(), $paperSize, $customDimensions);

            $this->auditService->log(auth()->user(), 'document.exported', 'document', $document->id, [
                'document_id' => $document->id,
                'filename' => $result['filename'],
                'paper_size' => $paperSize === 'Custom'
                    ? "Custom ({$customDimensions['width']}x{$customDimensions['height']} {$customDimensions['unit']})"
                    : $paperSize,
            ]);

            return back()->with('pdf_export', [
                'filename' => $result['filename'],
                'url' => Storage::disk('local')->temporaryUrl($result['path'], now()->addMinutes(5), [
                    'filename' => $result['filename'],
                ]),
            ]);
        } catch (BusinessLogicException $e) {
            return back()->withErrors(['export' => $e->getMessage()]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors(['export' => 'PDF generation failed. Please try again.']);
        }
    }
}