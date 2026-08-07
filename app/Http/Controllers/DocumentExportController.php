<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessLogicException;
use App\Models\Document;
use App\Services\AuditService;
use App\Services\PdfExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class DocumentExportController extends Controller
{
    public function __construct(
        protected PdfExportService $pdfService,
        protected AuditService $auditService,
    ) {}

    public function export(Document $document): RedirectResponse
    {
        $this->authorize('view', $document);

        try {
            $result = $this->pdfService->export($document, auth()->user());

            $this->auditService->log(auth()->user(), 'document.exported', 'document', $document->id, [
                'document_id' => $document->id,
                'filename' => $result['filename'],
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
