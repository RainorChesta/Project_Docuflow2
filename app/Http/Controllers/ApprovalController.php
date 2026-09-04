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

    public function index(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $tab = $request->query('tab');
        if ($tab === 'renames') {
            return $this->renames($request);
        }
        if ($tab === 'rollbacks') {
            return $this->rollbacks($request);
        }

        return $this->versions($request);
    }

    public function versions(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $versionsQuery = $this->getPendingVersionsQuery($user, $search);

        $counts = [
            'versions' => (clone $versionsQuery)->count(),
            'renames' => $this->getPendingRenamesQuery($user)->count(),
            'rollbacks' => $this->getPendingRollbacksQuery($user)->count(),
        ];
        $counts['total'] = $counts['versions'] + $counts['renames'] + $counts['rollbacks'];

        $pendingVersions = $versionsQuery
            ->with(['document.branch', 'document.company', 'document.division', 'author'])
            ->latest('id')
            ->paginate($perPage, ['*'], 'version_page')
            ->withQueryString();

        return view('approvals.versions', compact(
            'pendingVersions',
            'counts',
            'search',
            'perPage'
        ));
    }

    public function renames(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $renamesQuery = $this->getPendingRenamesQuery($user, $search);

        $counts = [
            'versions' => $this->getPendingVersionsQuery($user)->count(),
            'renames' => (clone $renamesQuery)->count(),
            'rollbacks' => $this->getPendingRollbacksQuery($user)->count(),
        ];
        $counts['total'] = $counts['versions'] + $counts['renames'] + $counts['rollbacks'];

        $pendingRenames = $renamesQuery
            ->with(['renameRequestedBy', 'division', 'branch', 'company'])
            ->latest('rename_requested_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'rename_page')
            ->withQueryString();

        return view('approvals.renames', compact(
            'pendingRenames',
            'counts',
            'search',
            'perPage'
        ));
    }

    public function rollbacks(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $rollbacksQuery = $this->getPendingRollbacksQuery($user, $search);

        $counts = [
            'versions' => $this->getPendingVersionsQuery($user)->count(),
            'renames' => $this->getPendingRenamesQuery($user)->count(),
            'rollbacks' => (clone $rollbacksQuery)->count(),
        ];
        $counts['total'] = $counts['versions'] + $counts['renames'] + $counts['rollbacks'];

        $pendingRollbacks = $rollbacksQuery
            ->with(['rollbackRequestedBy', 'pendingRollbackVersion', 'division', 'branch', 'company', 'currentVersion'])
            ->latest('rollback_requested_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'rollback_page')
            ->withQueryString();

        return view('approvals.rollbacks', compact(
            'pendingRollbacks',
            'counts',
            'search',
            'perPage'
        ));
    }

    protected function getPendingVersionsQuery($user, string $search = '')
    {
        $roleFilter = function ($q) use ($user) {
            if ($user->isAdmin() || $user->isDirector()) {
                $q->where('approver_role', $user->system_role)
                  ->orWhereNull('approver_role');
            } else {
                $q->where('approver_role', 'head')
                  ->orWhereNull('approver_role');
            }
        };

        if ($user->isAdmin() || $user->isDirector()) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            $pendingVersionsQuery = DocumentVersion::where('status', 'pending')
                ->whereNull('discarded_at')
                ->whereHas('document', $roleFilter);

            if (!$user->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $pendingVersionsQuery->whereHas('document', $companyFilter);
            }
        } else {
            $divisionIds = $user->allDivisionIds();

            $pendingVersionsQuery = DocumentVersion::where('status', 'pending')
                ->whereNull('discarded_at')
                ->whereHas('document', fn($q) => $q->whereIn('division_id', $divisionIds)->visibleTo($user)->where($roleFilter));
        }

        if ($search !== '') {
            $pendingVersionsQuery->where(function ($vq) use ($search) {
                // 1. Direct author_name column on document_versions
                $vq->where('author_name', 'like', "%{$search}%");

                // 2. Version number if numeric or formatted like v1, v2
                $trimmedVersion = ltrim(strtolower($search), 'v. ');
                if (is_numeric($trimmedVersion)) {
                    $vq->orWhere('version_number', (int) $trimmedVersion);
                }

                // 3. Original file name
                $vq->orWhere('file_original_name', 'like', "%{$search}%");

                // 4. Related document (title, document number, division, branch)
                $vq->orWhereHas('document', function ($dq) use ($search) {
                    $dq->where('title', 'like', "%{$search}%")
                       ->orWhere('document_number', 'like', "%{$search}%")
                       ->orWhereHas('division', fn($divQ) => $divQ->where('name', 'like', "%{$search}%"))
                       ->orWhereHas('branch', fn($brQ) => $brQ->where('name', 'like', "%{$search}%"));
                });

                // 5. Author user account
                $vq->orWhereHas('author', function ($aq) use ($search) {
                    $aq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        return $pendingVersionsQuery;
    }

    protected function getPendingRenamesQuery($user, string $search = '')
    {
        $roleFilter = function ($q) use ($user) {
            if ($user->isAdmin() || $user->isDirector()) {
                $q->where('approver_role', $user->system_role)
                  ->orWhereNull('approver_role');
            } else {
                $q->where('approver_role', 'head')
                  ->orWhereNull('approver_role');
            }
        };

        if ($user->isAdmin() || $user->isDirector()) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            $pendingRenamesQuery = Document::whereNotNull('pending_title')
                ->where('pending_title', '!=', '')
                ->where($roleFilter);

            if (!$user->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $pendingRenamesQuery->where($companyFilter);
            }
        } else {
            $divisionIds = $user->allDivisionIds();

            $pendingRenamesQuery = Document::whereIn('division_id', $divisionIds)
                ->visibleTo($user)
                ->whereNotNull('pending_title')
                ->where('pending_title', '!=', '')
                ->where($roleFilter);
        }

        if ($search !== '') {
            $pendingRenamesQuery->where(function ($rq) use ($search) {
                $rq->where('title', 'like', "%{$search}%")
                   ->orWhere('pending_title', 'like', "%{$search}%")
                   ->orWhere('document_number', 'like', "%{$search}%")
                   ->orWhereHas('renameRequestedBy', function ($uq) use ($search) {
                       $uq->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                   })
                   ->orWhereHas('division', fn($divQ) => $divQ->where('name', 'like', "%{$search}%"))
                   ->orWhereHas('branch', fn($brQ) => $brQ->where('name', 'like', "%{$search}%"));
            });
        }

        return $pendingRenamesQuery;
    }

    protected function getPendingRollbacksQuery($user, string $search = '')
    {
        $roleFilter = function ($q) use ($user) {
            if ($user->isAdmin() || $user->isDirector()) {
                $q->where('approver_role', $user->system_role)
                  ->orWhereNull('approver_role');
            } else {
                $q->where('approver_role', 'head')
                  ->orWhereNull('approver_role');
            }
        };

        if ($user->isAdmin() || $user->isDirector()) {
            $companyIds = $user->companies()->pluck('companies.id')->all();

            $pendingRollbacksQuery = Document::whereNotNull('pending_rollback_version_id')
                ->where($roleFilter);

            if (!$user->isAdmin() && !empty($companyIds)) {
                $companyFilter = function ($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds)
                      ->orWhereHas('branch', fn($bq) => $bq->whereIn('company_id', $companyIds));
                };
                $pendingRollbacksQuery->where($companyFilter);
            }
        } else {
            $divisionIds = $user->allDivisionIds();

            $pendingRollbacksQuery = Document::whereIn('division_id', $divisionIds)
                ->visibleTo($user)
                ->whereNotNull('pending_rollback_version_id')
                ->where($roleFilter);
        }

        if ($search !== '') {
            $pendingRollbacksQuery->where(function ($rq) use ($search) {
                $rq->where('title', 'like', "%{$search}%")
                   ->orWhere('document_number', 'like', "%{$search}%")
                   ->orWhereHas('rollbackRequestedBy', function ($uq) use ($search) {
                       $uq->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                   })
                   ->orWhereHas('pendingRollbackVersion', function ($vq) use ($search) {
                       $trimmedVersion = ltrim(strtolower($search), 'v. ');
                       if (is_numeric($trimmedVersion)) {
                           $vq->where('version_number', (int) $trimmedVersion);
                       }
                   })
                   ->orWhereHas('division', fn($divQ) => $divQ->where('name', 'like', "%{$search}%"))
                   ->orWhereHas('branch', fn($brQ) => $brQ->where('name', 'like', "%{$search}%"));
            });
        }

        return $pendingRollbacksQuery;
    }

    /**
     * Bulk approve selected document versions.
     */
    public function bulkApproveVersions(Request $request): RedirectResponse
    {
        $reviewer = auth()->user();
        $versionIds = $request->input('version_ids', []);

        if (is_string($versionIds)) {
            $versionIds = explode(',', $versionIds);
        }

        $versionIds = array_filter(array_map('intval', (array) $versionIds));

        if (empty($versionIds)) {
            return back()->with('error', __('Pilih setidaknya satu versi dokumen untuk disetujui.'));
        }

        $versions = DocumentVersion::with('document', 'author')
            ->whereIn('id', $versionIds)
            ->where('status', 'pending')
            ->get();

        if ($versions->isEmpty()) {
            return back()->with('error', __('Tidak ada versi dokumen pending yang valid untuk disetujui.'));
        }

        $count = 0;
        foreach ($versions as $version) {
            $document = $version->document;
            if (!$document || !$reviewer->can('approve', $document)) {
                continue;
            }

            $this->versionService->approve($version, $reviewer);

            $this->auditService->log($reviewer, 'version.approved', 'document_version', $version->id, [
                'document_id' => $document->id,
                'version_number' => $version->version_number,
                'bulk' => true,
            ]);

            // Notify document author
            if ($version->author_id && $version->author_id !== $reviewer->id) {
                $version->author?->notify(new \App\Notifications\DocumentApprovalResult(
                    $document,
                    $version,
                    'approved',
                    $reviewer->name,
                ));
            }

            $count++;
        }

        return back()->with('success', __(':count versi dokumen berhasil disetujui sekaligus.', ['count' => $count]));
    }

    /**
     * Bulk reject selected document versions.
     */
    public function bulkRejectVersions(Request $request): RedirectResponse
    {
        $reviewer = auth()->user();
        $versionIds = $request->input('version_ids', []);
        $notes = $request->input('notes', __('Ditolak secara massal oleh reviewer.'));

        if (is_string($versionIds)) {
            $versionIds = explode(',', $versionIds);
        }

        $versionIds = array_filter(array_map('intval', (array) $versionIds));

        if (empty($versionIds)) {
            return back()->with('error', __('Pilih setidaknya satu versi dokumen untuk ditolak.'));
        }

        $versions = DocumentVersion::with('document', 'author')
            ->whereIn('id', $versionIds)
            ->where('status', 'pending')
            ->get();

        if ($versions->isEmpty()) {
            return back()->with('error', __('Tidak ada versi dokumen pending yang valid untuk ditolak.'));
        }

        $count = 0;
        foreach ($versions as $version) {
            $document = $version->document;
            if (!$document || !$reviewer->can('approve', $document)) {
                continue;
            }

            $this->versionService->reject($version, $reviewer, $notes);

            $this->auditService->log($reviewer, 'version.rejected', 'document_version', $version->id, [
                'document_id' => $document->id,
                'version_number' => $version->version_number,
                'notes' => $notes,
                'bulk' => true,
            ]);

            // Notify document author
            if ($version->author_id && $version->author_id !== $reviewer->id) {
                $version->author?->notify(new \App\Notifications\DocumentApprovalResult(
                    $document,
                    $version,
                    'rejected',
                    $reviewer->name,
                    $notes,
                ));
            }

            $count++;
        }

        return back()->with('success', __(':count versi dokumen berhasil ditolak.', ['count' => $count]));
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

        return redirect()->to($request->header('referer') ?: route('approvals.index'))->with('success', __('Versi disetujui dan diaktifkan.'));
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

        return redirect()->to($request->header('referer') ?: route('approvals.index'))->with('success', __('Versi ditolak.'));
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

    public function approveRename(Request $request, Document $document): RedirectResponse
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

        return redirect()->to($request->header('referer') ?: route('approvals.index'))->with(
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

        return redirect()->to($request->header('referer') ?: route('approvals.index'))->with('success', __('Permintaan perubahan nama dokumen ditolak.'));
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
