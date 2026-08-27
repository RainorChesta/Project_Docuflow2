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

        if ($user->isAdmin() || $user->isDirector()) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            $pendingVersionsQuery = DocumentVersion::where('status', 'pending')
                ->whereNull('discarded_at');
            $pendingRollbacksQuery = Document::whereNotNull('pending_rollback_version_id');

            if (!$user->isAdmin() && !empty($companyIds)) {
                $pendingVersionsQuery->whereHas('document', fn($q) => $q->whereIn('company_id', $companyIds));
                $pendingRollbacksQuery->whereIn('company_id', $companyIds);
            }

            $pendingVersions = $pendingVersionsQuery->with('document', 'author')->latest()->get();
            $pendingRollbacks = $pendingRollbacksQuery->with('pendingRollbackVersion', 'rollbackRequestedBy')->latest()->get();

            return view('approvals.index', compact('pendingVersions', 'pendingRollbacks'));
        }
        $divisionIds = $user->allDivisionIds();

        $pendingVersions = DocumentVersion::where('status', 'pending')
            ->whereNull('discarded_at')
            ->whereHas('document', fn($q) => $q->whereIn('division_id', $divisionIds)->visibleTo($user))
            ->with('document', 'author')
            ->latest()
            ->get();

        $pendingRollbacks = Document::whereIn('division_id', $divisionIds)
            ->visibleTo($user)
            ->whereNotNull('pending_rollback_version_id')
            ->with('pendingRollbackVersion', 'rollbackRequestedBy')
            ->latest()
            ->get();

        return view('approvals.index', compact('pendingVersions', 'pendingRollbacks'));
    }

    public function approve(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('approve', $document);

        $reviewer = auth()->user();
        $this->versionService->approve($version, $reviewer, $request->input('notes'));

        $this->auditService->log($reviewer, 'version.approved', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        // Notify document author
        if ($version->author_id && $version->author_id !== $reviewer->id) {
            $version->author?->notify(new \App\Notifications\DocumentApprovalResult(
                $document,
                $version,
                'approved',
                $reviewer->name,
                $request->input('notes')
            ));
        }

        return redirect()->route('approvals.index')->with('success', 'Version approved and activated.');
    }

    public function reject(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('approve', $document);

        $validated = $request->validate(['notes' => 'nullable|string|max:500']);
        $reviewer = auth()->user();

        $this->versionService->reject($version, $reviewer, $validated['notes'] ?? null);

        $this->auditService->log($reviewer, 'version.rejected', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        // Notify document author
        if ($version->author_id && $version->author_id !== $reviewer->id) {
            $version->author?->notify(new \App\Notifications\DocumentApprovalResult(
                $document,
                $version,
                'rejected',
                $reviewer->name,
                $validated['notes'] ?? null
            ));
        }

        return redirect()->route('approvals.index')->with('success', 'Version rejected.');
    }

    /**
     * Ajukan permintaan rollback (belum eksekusi apa pun) — menunggu
     * approval kepala divisi lewat approveRollback()/rejectRollback().
     */
    public function rollback(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->authorize('update', $document);

        $user = auth()->user();

        try {
            $this->versionService->requestRollback($document, $version, $user);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->log($user, 'rollback.requested', 'document', $document->id, [
            'target_version' => $version->version_number,
        ]);

        // Notify Division Heads about rollback request
        if ($document->division_id) {
            $heads = \App\Models\User::where('division_id', $document->division_id)
                ->where('system_role', 'head')
                ->where('id', '!=', $user->id)
                ->where(function ($q) use ($document) {
                    if ($document->branch_id) {
                        $q->whereHas('branches', fn($bq) => $bq->where('branches.id', $document->branch_id));
                    } elseif ($document->company_id) {
                        $q->whereHas('companies', fn($cq) => $cq->where('companies.id', $document->company_id));
                    }
                })
                ->get();

            foreach ($heads as $head) {
                $head->notify(new \App\Notifications\DocumentRollbackRequested($document, $version, $user->name));
            }
        }

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
