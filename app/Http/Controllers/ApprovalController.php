<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\AuditService;
use App\Services\VersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

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

        $pendingRollbacks = Document::where('division_id', $user->division_id)
            ->whereNotNull('pending_rollback_version_id')
            ->with('pendingRollbackVersion', 'rollbackRequestedBy')
            ->latest()
            ->get();

        return view('approvals.index', compact('pendingVersions', 'pendingRollbacks'));
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

    /**
     * Ajukan permintaan rollback (belum eksekusi apa pun) — menunggu
     * approval kepala divisi lewat approveRollback()/rejectRollback().
     */
    public function rollback(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('update', $document);

        try {
            $this->versionService->requestRollback($document, $version, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->log(auth()->user(), 'rollback.requested', 'document', $document->id, [
            'target_version' => $version->version_number,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Permintaan rollback diajukan. Menunggu approval kepala divisi.');
    }

    public function approveRollback(Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        try {
            $restored = $this->versionService->approveRollbackRequest($document, auth()->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->log(auth()->user(), 'rollback.approved', 'document', $document->id, [
            'restored_version' => $restored->version_number,
        ]);

        return redirect()->route('documents.show', $document)->with(
            'success',
            'Rollback disetujui. Dokumen kembali ke versi v' . $restored->version_number . '.'
        );
    }

    public function rejectRollback(Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        $targetVersionNumber = $document->pendingRollbackVersion?->version_number;

        try {
            $this->versionService->rejectRollbackRequest($document);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->log(auth()->user(), 'rollback.rejected', 'document', $document->id, [
            'target_version' => $targetVersionNumber,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Permintaan rollback ditolak.');
    }
}
