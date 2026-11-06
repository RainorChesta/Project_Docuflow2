<?php

namespace App\Http\Controllers;

use App\Models\ArchiveDispositionRequest;
use App\Models\ArchiveEntry;
use App\Models\ArchiveLegalHold;
use App\Services\ArchiveDispositionService;
use App\Services\ArchiveHoldService;
use App\Services\ArchiveRetentionService;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveGovernanceController extends Controller
{
    public function __construct(
        protected ArchiveHoldService $holdService,
        protected ArchiveRetentionService $retentionService,
        protected ArchiveDispositionService $dispositionService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * Governance Hub: Retention Reviews, Legal Holds & Disposition Queue.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isDirector() && !$user->isPicKlinik()) {
            abort(403, __('Unauthorized to view Archive Governance.'));
        }

        $accessibleCompanyIds = $user->allCompanyIds();
        $tab = $request->input('tab', 'retention'); // retention, holds, disposition

        // 1. Retention reviews / due for disposition
        $retentionEntries = ArchiveEntry::whereIn('company_id', $accessibleCompanyIds)
            ->whereIn('status', [ArchiveEntry::STATUS_UNDER_REVIEW, ArchiveEntry::STATUS_ELIGIBLE_FOR_DISPOSITION])
            ->with(['classification', 'company', 'branch', 'unitKerja'])
            ->orderBy('retention_due_at')
            ->paginate(15, ['*'], 'ret_page');

        // 2. Active & released legal holds
        $legalHolds = ArchiveLegalHold::whereIn('company_id', $accessibleCompanyIds)
            ->with(['archiveEntry', 'appliedBy', 'releasedBy'])
            ->latest('id')
            ->paginate(15, ['*'], 'hold_page');

        // 3. Disposition requests
        $dispositionRequests = ArchiveDispositionRequest::whereIn('company_id', $accessibleCompanyIds)
            ->with(['archiveEntry', 'requester', 'reviewer', 'executor'])
            ->latest('id')
            ->paginate(15, ['*'], 'disp_page');

        return view('archives.governance', compact('retentionEntries', 'legalHolds', 'dispositionRequests', 'tab'));
    }

    /**
     * Place a legal hold on an archive entry.
     */
    public function storeHold(Request $request, ArchiveEntry $archive): RedirectResponse
    {
        $this->authorize('placeHold', $archive);

        $validated = $request->validate([
            'case_reference' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'reason' => 'required|string|min:5|max:1000',
        ]);

        $this->holdService->applyHold(
            entry: $archive,
            caseReference: $validated['case_reference'],
            title: $validated['title'],
            reason: $validated['reason'],
            actor: $request->user()
        );

        return back()->with('success', __('Legal Hold berhasil diberlakukan pada arsip :num.', ['num' => $archive->archive_number]));
    }

    /**
     * Release a legal hold.
     */
    public function releaseHold(Request $request, ArchiveLegalHold $hold): RedirectResponse
    {
        $archive = $hold->archiveEntry;
        $this->authorize('releaseHold', [$archive, $hold]);

        $validated = $request->validate([
            'release_reason' => 'required|string|min:5|max:1000',
        ]);

        $this->holdService->releaseHold(
            hold: $hold,
            releaseReason: $validated['release_reason'],
            actor: $request->user()
        );

        return back()->with('success', __('Legal Hold untuk kasus :case berhasil dicabut.', ['case' => $hold->case_reference]));
    }

    /**
     * Request disposition (destroy, permanent, extend).
     */
    public function storeDisposition(Request $request, ArchiveEntry $archive): RedirectResponse
    {
        $this->authorize('requestDisposition', $archive);

        $validated = $request->validate([
            'action_type' => 'required|in:destroy,permanent,extend,transfer',
            'justification' => 'required|string|min:5|max:1000',
            'extension_days' => 'nullable|integer|min:30|max:3650',
        ]);

        $this->dispositionService->requestDisposition(
            entry: $archive,
            actionType: $validated['action_type'],
            justification: $validated['justification'],
            actor: $request->user(),
            extensionDays: $validated['extension_days'] ?? null
        );

        return back()->with('success', __('Permohonan penyusutan arsip berhasil diajukan untuk disetujui.'));
    }

    /**
     * Approve and execute disposition request.
     */
    public function approveDisposition(Request $request, ArchiveDispositionRequest $disposition): RedirectResponse
    {
        $archive = $disposition->archiveEntry;
        $this->authorize('approveDisposition', $archive);

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $this->dispositionService->executeDisposition(
            request: $disposition,
            actor: $request->user(),
            reviewNotes: $validated['review_notes'] ?? null
        );

        return back()->with('success', __('Penyusutan arsip berhasil dieksekusi. Berita acara pemusnahan/penyusutan telah diterbitkan.'));
    }

    /**
     * Reject disposition request.
     */
    public function rejectDisposition(Request $request, ArchiveDispositionRequest $disposition): RedirectResponse
    {
        $archive = $disposition->archiveEntry;
        $this->authorize('approveDisposition', $archive);

        $validated = $request->validate([
            'notes' => 'required|string|min:5|max:1000',
        ]);

        $this->dispositionService->rejectDisposition(
            request: $disposition,
            notes: $validated['notes'],
            actor: $request->user()
        );

        return back()->with('success', __('Permohonan penyusutan arsip telah ditolak.'));
    }

    /**
     * Display printable official disposition certificate (Berita Acara).
     */
    public function certificate(Request $request, ArchiveDispositionRequest $disposition): View
    {
        if (!$disposition->isExecuted()) {
            abort(404, __('Certificate is only available for executed dispositions.'));
        }

        $archive = $disposition->archiveEntry;
        $this->authorize('view', $archive);

        return view('archives.certificate', compact('disposition', 'archive'));
    }
}
