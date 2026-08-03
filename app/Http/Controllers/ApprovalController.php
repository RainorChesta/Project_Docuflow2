<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditService;
use App\Services\VersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(
        protected VersionService $versionService,
        protected AuditService $auditService,
    ) {}

    public function index(): \Illuminate\View\View
    {
        $user = auth()->user();
        $pendingVersions = DocumentVersion::where('status', 'pending')
            ->whereNull('discarded_at')
            ->whereHas('document', fn($q) => $q->where('division_id', $user->division_id))
            ->with('document', 'author')
            ->latest()
            ->get();

        return view('approvals.index', compact('pendingVersions'));
    }

    public function approve(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('approve', $document);

        $this->versionService->approve($version, auth()->user(), $request->input('notes'));

        $this->auditService->log(auth()->user(), 'version.approved', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        return redirect()->route('approvals.index')->with('success', 'Version approved and activated.');
    }

    public function reject(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('approve', $document);

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);

        $this->versionService->reject($version, auth()->user(), $validated['notes'] ?? null);

        $this->auditService->log(auth()->user(), 'version.rejected', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        return redirect()->route('approvals.index')->with('success', 'Version rejected.');
    }

    public function rollback(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('update', $document);

        $newVersion = $this->versionService->rollback($document, $version, auth()->user());

        $this->auditService->log(auth()->user(), 'version.rollback', 'document_version', $newVersion->id, [
            'document_id' => $document->id,
            'source_version' => $version->version_number,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Rollback submitted. Pending approval.');
    }
}
