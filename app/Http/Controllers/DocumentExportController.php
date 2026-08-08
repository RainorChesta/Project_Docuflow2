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

        // paper_size opsional, dikirim dari modal export di halaman show
        // (lihat _export-pdf-modal di documents/show.blade.php). Override
        // ukuran kertas HANYA untuk export kali ini — TIDAK mengubah
        // paper_size yang tersimpan di dokumen. Margin tetap ikut margin
        // dokumen; PdfExportService yang akan meng-clamp margin itu ke
        // ukuran kertas override (kalau perlu) lewat clampMarginToPage()-nya
        // sendiri, konsisten dengan clamp yang sama di resources/js/jodit.js.
        $validated = $request->validate([
            'paper_size' => 'nullable|string|in:A4,A5,A3,Letter,Legal',
        ]);

        try {
            $result = $this->pdfService->export($document, auth()->user(), $validated['paper_size'] ?? null);

            $this->auditService->log(auth()->user(), 'document.exported', 'document', $document->id, [
                'document_id' => $document->id,
                'filename' => $result['filename'],
                'paper_size' => $validated['paper_size'] ?? $document->paper_size ?? 'A4',
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