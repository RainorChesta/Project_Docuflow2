<?php

namespace App\Http\Controllers;

use App\Models\ArchiveClassification;
use App\Models\ArchiveEntry;
use App\Models\ArchiveRequest;
use App\Models\ArchiveRetentionPolicy;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\ArchiveEligibilityService;
use App\Services\ArchiveNumberingService;
use App\Services\ArchiveRegistryService;
use App\Services\CompanyContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ArchiveRequestController extends Controller
{
    public function __construct(
        protected ArchiveRegistryService $registryService,
        protected ArchiveEligibilityService $eligibilityService,
        protected ArchiveNumberingService $numberingService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * Archive Requests & Approvals Queue.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ArchiveRequest::class);

        $user = $request->user();
        $tab = $request->input('tab', 'my_requests'); // 'my_requests' or 'approval_queue'

        $accessibleCompanyIds = $user->allCompanyIds();

        $myRequests = ArchiveRequest::where('requested_by', $user->id)
            ->with(['document', 'classification', 'archiveEntry'])
            ->latest('id')
            ->paginate(15, ['*'], 'my_page');

        // Approval Queue query
        $approvalQuery = ArchiveRequest::whereIn('company_id', $accessibleCompanyIds)
            ->where('state', ArchiveRequest::STATE_PENDING_APPROVAL)
            ->with(['document', 'requester', 'classification', 'branch', 'unitKerja']);

        if (!$user->isAdmin() && !$user->isDirector()) {
            if ($user->isPicKlinik()) {
                $approvalQuery->whereIn('branch_id', $user->allBranchIds());
            } elseif ($user->isHead()) {
                $approvalQuery->whereIn('unit_kerja_id', $user->allUnitKerjaIds());
            } else {
                $approvalQuery->whereRaw('1 = 0');
            }
        }

        $pendingApprovals = $approvalQuery->latest('id')->paginate(15, ['*'], 'queue_page');

        return view('archives.requests', compact('myRequests', 'pendingApprovals', 'tab'));
    }

    /**
     * Intake Wizard to archive an existing document.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', ArchiveRequest::class);

        $documentId = $request->input('document_id');
        if (!$documentId) {
            return redirect()->route('documents.index')
                ->with('error', __('Pilih dokumen yang ingin didaftarkan ke arsip.'));
        }

        $document = Document::with(['documentType', 'versions', 'owner', 'company', 'branch', 'unitKerja'])->findOrFail($documentId);
        $version = $document->displayVersion();

        // Check preflight eligibility
        $eligibility = $this->eligibilityService->checkEligibility($document, $version);
        if (!$eligibility['eligible']) {
            return redirect()->route('documents.show', $document->id)
                ->with('error', __('Dokumen belum memenuhi syarat arsip: :reasons', [
                    'reasons' => implode(', ', $eligibility['reasons']),
                ]));
        }

        // Auto-seed standard master classifications if none exist
        if (ArchiveClassification::where('is_active', true)->count() === 0) {
            (new \Database\Seeders\ArchiveMasterDataSeeder())->run();
        }

        $classifications = ArchiveClassification::where('is_active', true)
            ->where(function ($q) use ($document) {
                $q->whereNull('company_id')->orWhere('company_id', $document->company_id);
            })
            ->orderBy('code')
            ->get();

        $retentionPolicies = ArchiveRetentionPolicy::where('is_active', true)
            ->where(function ($q) use ($document) {
                $q->whereNull('company_id')->orWhere('company_id', $document->company_id);
            })
            ->orderBy('code')
            ->get();

        // Intelligent auto-selection based on document's document_type
        $selectedClassification = null;
        if ($document->documentType) {
            $docTypeCode = strtoupper(trim($document->documentType->code));
            $docTypeName = mb_strtolower(trim($document->documentType->name));

            // 1. Exact code match (e.g. S.ED, IM, ST, PKS, PKWT, I/S.KEL, E/S.KEL, BA, etc.)
            $selectedClassification = $classifications->first(function ($c) use ($docTypeCode) {
                return strtoupper($c->code) === $docTypeCode;
            });

            // 2. Prefix / variant match (e.g. SP-1, SP-2, SP-3, SP-L -> SP)
            if (!$selectedClassification) {
                $prefixCode = strtok($docTypeCode, '-_');
                if ($prefixCode && strlen($prefixCode) >= 2) {
                    $selectedClassification = $classifications->first(function ($c) use ($prefixCode) {
                        return strtoupper($c->code) === $prefixCode;
                    });
                }
            }

            // 3. Name similarity match (e.g. 'Surat Edaran', 'Perjanjian Kerjasama')
            if (!$selectedClassification) {
                $selectedClassification = $classifications->first(function ($c) use ($docTypeName) {
                    $cName = mb_strtolower(trim($c->name));
                    return $cName === $docTypeName || str_contains($cName, $docTypeName) || str_contains($docTypeName, $cName);
                });
            }
        }

        // Fallback to first available classification if no specific match
        $selectedClassification = $selectedClassification ?: $classifications->first();
        $defaultClassificationId = $selectedClassification?->id;

        // Non-reserving format preview for UI guidance using matched classification
        $preview = $this->numberingService->previewNumber(
            company: $document->company,
            branch: $document->branch,
            unit: $document->unitKerja,
            classification: $selectedClassification
        );

        return view('archives.create', compact(
            'document',
            'version',
            'eligibility',
            'classifications',
            'retentionPolicies',
            'preview',
            'selectedClassification',
            'defaultClassificationId'
        ));
    }

    /**
     * Store a new archive request.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ArchiveRequest::class);

        $validated = $request->validate([
            'document_id' => 'required|exists:documents,id',
            'requested_version_id' => 'nullable|exists:document_versions,id',
            'classification_id' => 'required|exists:archive_classifications,id',
            'retention_policy_id' => 'nullable|exists:archive_retention_policies,id',
            'archive_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'archive_reason' => 'nullable|string',
            'record_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'sensitivity_level' => 'required|in:public,internal,confidential,restricted',
            'tags' => 'nullable|array',
            'submit_now' => 'nullable|boolean',
        ]);

        $document = Document::findOrFail($validated['document_id']);
        $classification = ArchiveClassification::findOrFail($validated['classification_id']);

        $archiveRequest = $this->registryService->createRequest(
            document: $document,
            classification: $classification,
            requester: $request->user(),
            attributes: $validated
        );

        if ($request->boolean('submit_now')) {
            $this->registryService->submitRequest($archiveRequest, $request->user());
            return redirect()->route('archive-requests.show', $archiveRequest->id)
                ->with('success', __('Permohonan pengarsipan berhasil diajukan untuk ditinjau.'));
        }

        return redirect()->route('archive-requests.show', $archiveRequest->id)
            ->with('success', __('Draf permohonan pengarsipan berhasil disimpan.'));
    }

    /**
     * Show details of an archive request.
     */
    public function show(Request $request, ArchiveRequest $archiveRequest): View
    {
        $this->authorize('view', $archiveRequest);

        $archiveRequest->load([
            'document',
            'requestedVersion',
            'requester',
            'approver',
            'approvedBy',
            'classification',
            'retentionPolicy',
            'archiveEntry',
            'company',
            'branch',
            'unitKerja',
        ]);

        $canSubmit = $request->user()->can('submit', $archiveRequest);
        $canApprove = $request->user()->can('approve', $archiveRequest);
        $canCancel = $request->user()->can('cancel', $archiveRequest);
        $canFinalize = $request->user()->can('finalize', $archiveRequest);

        $preview = $this->numberingService->previewNumber(
            company: $archiveRequest->company,
            branch: $archiveRequest->branch,
            unit: $archiveRequest->unitKerja,
            classification: $archiveRequest->classification
        );

        return view('archives.request-show', compact(
            'archiveRequest',
            'canSubmit',
            'canApprove',
            'canCancel',
            'canFinalize',
            'preview'
        ));
    }

    /**
     * Submit draft request for review.
     */
    public function submit(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('submit', $archiveRequest);

        $this->registryService->submitRequest($archiveRequest, $request->user());

        return back()->with('success', __('Permohonan pengarsipan telah diajukan.'));
    }

    /**
     * Approve pending request.
     */
    public function approve(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('approve', $archiveRequest);

        $notes = $request->input('notes');
        $this->registryService->approveRequest($archiveRequest, $request->user(), $notes);

        return back()->with('success', __('Permohonan pengarsipan disetujui. Silakan lakukan finalisasi pendaftaran.'));
    }

    /**
     * Reject pending request.
     */
    public function reject(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('reject', $archiveRequest);

        $validated = $request->validate([
            'notes' => 'required|string|min:5|max:1000',
        ]);

        $this->registryService->rejectRequest($archiveRequest, $request->user(), $validated['notes']);

        return back()->with('success', __('Permohonan pengarsipan telah ditolak.'));
    }

    /**
     * Return pending request for correction.
     */
    public function returnForCorrection(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('returnForCorrection', $archiveRequest);

        $validated = $request->validate([
            'notes' => 'required|string|min:5|max:1000',
        ]);

        $this->registryService->returnForCorrection($archiveRequest, $request->user(), $validated['notes']);

        return back()->with('success', __('Permohonan pengarsipan dikembalikan ke pemohon untuk perbaikan.'));
    }

    /**
     * Cancel an archive request.
     */
    public function cancel(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('cancel', $archiveRequest);

        $reason = $request->input('reason');
        $this->registryService->cancelRequest($archiveRequest, $request->user(), $reason);

        return redirect()->route('archive-requests.index')
            ->with('success', __('Permohonan pengarsipan telah dibatalkan.'));
    }

    /**
     * Permanently delete a canceled, rejected, failed, or draft archive request.
     */
    public function destroy(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('delete', $archiveRequest);

        $this->registryService->deleteRequest($archiveRequest, $request->user());

        return redirect()->route('archive-requests.index')
            ->with('success', __('Permohonan pengarsipan telah dihapus.'));
    }

    /**
     * Finalize an approved request into an official ArchiveEntry.
     */
    public function finalize(Request $request, ArchiveRequest $archiveRequest): RedirectResponse
    {
        $this->authorize('finalize', $archiveRequest);

        $entry = $this->registryService->finalizeRegistration($archiveRequest, $request->user());

        return redirect()->route('archives.show', $entry->id)
            ->with('success', __('Dokumen resmi terdaftar dalam arsip dengan nomor :num.', ['num' => $entry->archive_number]));
    }

    /**
     * AJAX endpoint: non-reserving number format preview.
     */
    public function previewNumber(Request $request): JsonResponse
    {
        $request->validate([
            'classification_id' => 'required|exists:archive_classifications,id',
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
        ]);

        $classification = ArchiveClassification::findOrFail($request->input('classification_id'));
        $company = $request->input('company_id') ? \App\Models\Company::find($request->input('company_id')) : null;
        $branch = $request->input('branch_id') ? \App\Models\Branch::find($request->input('branch_id')) : null;
        $unitKerja = $request->input('unit_kerja_id') ? \App\Models\UnitKerja::find($request->input('unit_kerja_id')) : null;

        $preview = $this->numberingService->previewNumber(
            company: $company,
            branch: $branch,
            unit: $unitKerja,
            classification: $classification
        );

        return response()->json($preview);
    }
}
