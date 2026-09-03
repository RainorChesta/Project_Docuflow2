<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\ApprovalRoutingService;
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
        protected ApprovalRoutingService $approvalRoutingService,
    ) {}

    public function index(): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() || $user->isDirector()) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            $pendingVersionsQuery = DocumentVersion::where('status', 'pending')
                ->whereNull('discarded_at')
                ->whereHas('document');
            $pendingRollbacksQuery = Document::whereNotNull('pending_rollback_version_id');
            $pendingRenamesQuery = Document::whereNotNull('pending_title')
                ->where('pending_title', '!=', '');

            if (!$user->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $pendingVersionsQuery->whereHas('document', $companyFilter);
                $pendingRollbacksQuery->where($companyFilter);
                $pendingRenamesQuery->where($companyFilter);
            }

            $pendingVersions = $pendingVersionsQuery->with('document', 'author')->latest()->get();
            $pendingRollbacks = $pendingRollbacksQuery->with('pendingRollbackVersion', 'rollbackRequestedBy')->latest()->get();
            $pendingRenames = $pendingRenamesQuery->with('renameRequestedBy', 'division')->latest('rename_requested_at')->get();

            return view('approvals.index', compact('pendingVersions', 'pendingRollbacks', 'pendingRenames'));
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

        $pendingRenames = Document::whereIn('division_id', $divisionIds)
            ->visibleTo($user)
            ->whereNotNull('pending_title')
            ->where('pending_title', '!=', '')
            ->with('renameRequestedBy', 'division')
            ->latest('rename_requested_at')
            ->get();

        return view('approvals.index', compact('pendingVersions', 'pendingRollbacks', 'pendingRenames'));
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

        // Notify sibling approvers (other Admins/Direkturs) that approval is done
        $this->notifySiblingApprovers($document, $reviewer, 'approved');

        return redirect()->route('approvals.index')->with('success', __('Versi disetujui dan diaktifkan.'));
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

        // Notify sibling approvers (other Admins/Direkturs) that rejection is done
        $this->notifySiblingApprovers($document, $reviewer, 'rejected');

        return redirect()->route('approvals.index')->with('success', __('Versi ditolak.'));
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

        // Dynamic approval routing for rollback notifications
        $resolution = $this->approvalRoutingService->resolveApprover($document, $user);

        foreach ($resolution['approvers'] as $approver) {
            $approver->notify(new \App\Notifications\DocumentRollbackRequested($document, $version, $user->name));
        }

        return redirect()->route('documents.show', $document)->with('success', __('Permintaan rollback diajukan. Menunggu persetujuan kepala divisi.'));
    }

    public function approveRollback(Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        $requester = $document->rollbackRequestedBy;
        $targetVersion = $document->pendingRollbackVersion;
        $reviewer = auth()->user();

        try {
            $restored = $this->versionService->approveRollbackRequest($document, $reviewer);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->log($reviewer, 'rollback.approved', 'document', $document->id, [
            'restored_version' => $restored->version_number,
        ]);

        if ($requester && $requester->id !== $reviewer->id) {
            $requester->notify(new \App\Notifications\DocumentRollbackResult(
                $document,
                $targetVersion,
                'approved',
                $reviewer->name
            ));
        }

        return redirect()->route('documents.show', $document)->with(
            'success',
            __('Rollback disetujui. Dokumen kembali ke versi v:version.', ['version' => $restored->version_number])
        );
    }

    public function rejectRollback(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approve', $document);

        $requester = $document->rollbackRequestedBy;
        $targetVersion = $document->pendingRollbackVersion;
        $targetVersionNumber = $targetVersion?->version_number;
        $reviewer = auth()->user();
        $notes = $request->input('notes') ?? $request->input('reason');

        try {
            $this->versionService->rejectRollbackRequest($document);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $this->auditService->log($reviewer, 'rollback.rejected', 'document', $document->id, [
            'target_version' => $targetVersionNumber,
            'notes' => $notes,
        ]);

        if ($requester && $requester->id !== $reviewer->id) {
            $requester->notify(new \App\Notifications\DocumentRollbackResult(
                $document,
                $targetVersion,
                'rejected',
                $reviewer->name,
                $notes
            ));
        }

        return redirect()->route('documents.show', $document)->with('success', __('Permintaan rollback ditolak.'));
    }

    public function approveRename(Document $document): RedirectResponse
    {
        $this->authorize('approveRename', $document);

        if (!$document->hasPendingRename()) {
            return back()->with('error', __('Tidak ada permintaan perubahan nama yang menunggu.'));
        }

        $requester = $document->renameRequestedBy;
        $oldTitle = $document->title;
        $newTitle = $document->pending_title;
        $reviewer = auth()->user();

        $document->update([
            'title' => $newTitle,
            'pending_title' => null,
            'rename_requested_by_id' => null,
            'rename_requested_at' => null,
            'rename_request_notes' => null,
        ]);

        $this->auditService->log($reviewer, 'document.rename_approved', 'document', $document->id, [
            'old_title' => $oldTitle,
            'new_title' => $newTitle,
        ]);

        if ($requester && $requester->id !== $reviewer->id) {
            $requester->notify(new \App\Notifications\DocumentRenameResult(
                $document,
                $oldTitle,
                $newTitle,
                'approved',
                $reviewer->name
            ));
        }

        return redirect()->route('approvals.index')->with(
            'success',
            __('Perubahan nama dokumen menjadi ":title" telah disetujui.', ['title' => $newTitle])
        );
    }

    public function rejectRename(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('approveRename', $document);

        if (!$document->hasPendingRename()) {
            return back()->with('error', __('Tidak ada permintaan perubahan nama yang menunggu.'));
        }

        $requester = $document->renameRequestedBy;
        $oldTitle = $document->title;
        $targetTitle = $document->pending_title;
        $reviewer = auth()->user();
        $notes = $request->input('notes') ?? $request->input('reason');

        $document->update([
            'pending_title' => null,
            'rename_requested_by_id' => null,
            'rename_requested_at' => null,
            'rename_request_notes' => null,
        ]);

        $this->auditService->log($reviewer, 'document.rename_rejected', 'document', $document->id, [
            'current_title' => $oldTitle,
            'rejected_title' => $targetTitle,
            'notes' => $notes,
        ]);

        if ($requester && $requester->id !== $reviewer->id) {
            $requester->notify(new \App\Notifications\DocumentRenameResult(
                $document,
                $oldTitle,
                $targetTitle,
                'rejected',
                $reviewer->name,
                $notes
            ));
        }

        return redirect()->route('approvals.index')->with('success', __('Permintaan perubahan nama dokumen ditolak.'));
    }

    /**
     * Notify other approvers (sibling Admins/Direkturs) that the document
     * has already been handled, so they don't need to take action.
     */
    private function notifySiblingApprovers(Document $document, $reviewer, string $action): void
    {
        // Only relevant when the approver_role is 'admin' (multi-approver scenario)
        if ($document->approver_role !== 'admin') {
            return;
        }

        $companyId = $document->company_id ?? $document->branch?->company_id;
        if (!$companyId) {
            return;
        }

        $siblingAdmins = \App\Models\User::where('system_role', 'admin')
            ->where('is_active', true)
            ->where('id', '!=', $reviewer->id)
            ->whereHas('companies', fn($cq) => $cq->where('companies.id', $companyId))
            ->get();

        foreach ($siblingAdmins as $admin) {
            $admin->notify(new \App\Notifications\ApprovalAlreadyHandled(
                $document,
                $reviewer->name,
                $action,
            ));
        }
    }
}
