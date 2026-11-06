<?php

namespace App\Http\Controllers;

use App\Models\ArchiveClassification;
use App\Models\ArchiveEntry;
use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use App\Services\ArchiveAuditService;
use App\Services\ArchiveRegistryService;
use App\Services\ArchiveSnapshotService;
use App\Services\CompanyContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    public function __construct(
        protected ArchiveRegistryService $registryService,
        protected ArchiveSnapshotService $snapshotService,
        protected ArchiveAuditService $auditService,
        protected CompanyContextService $contextService
    ) {}

    /**
     * Archive Hub & Register list.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ArchiveEntry::class);

        $user = $request->user();
        $accessibleCompanyIds = $user->allCompanyIds();

        $activeCompanyId = $this->contextService->getActiveCompanyId();
        $companyId = $request->input('company_id', $activeCompanyId);

        $query = ArchiveEntry::query()
            ->with(['classification', 'branch', 'unitKerja', 'registeredBy', 'snapshot']);

        // Tenant scoping
        if ($companyId && in_array((int)$companyId, array_map('intval', $accessibleCompanyIds), true)) {
            $query->where('company_id', (int)$companyId);
        } else {
            $query->whereIn('company_id', $accessibleCompanyIds);
        }

        // Branch / Unit scoping for non-admin/directors
        if (!$user->isAdmin() && !$user->isDirector()) {
            if ($user->isPicKlinik()) {
                $query->where(function ($q) use ($user) {
                    $q->whereIn('branch_id', $user->allBranchIds())
                        ->orWhereNull('branch_id');
                });
            } else {
                // Head or Staff
                $query->where(function ($q) use ($user) {
                    $q->where('sensitivity_level', 'public')
                        ->orWhere(function ($sub) use ($user) {
                            $sub->whereIn('unit_kerja_id', $user->allUnitKerjaIds())
                                ->whereIn('sensitivity_level', ['internal', 'confidential']);
                        })
                        ->orWhere('owner_id', $user->id)
                        ->orWhere('registered_by', $user->id);
                });
            }
        }

        // Filters
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('archive_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('original_document_number', 'like', "%{$search}%")
                    ->orWhere('classification_code_snapshot', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($classId = $request->input('classification_id')) {
            $query->where('classification_id', $classId);
        }

        if ($sensitivity = $request->input('sensitivity_level')) {
            $query->where('sensitivity_level', $sensitivity);
        }

        if ($request->boolean('legal_hold_only')) {
            $query->where('is_under_legal_hold', true);
        }

        $entries = $query->orderByDesc('registered_at')->paginate(20)->withQueryString();

        // Summary stats for KPI cards
        $statsBase = ArchiveEntry::whereIn('company_id', $accessibleCompanyIds);
        if ($companyId) {
            $statsBase->where('company_id', $companyId);
        }

        $stats = [
            'total' => (clone $statsBase)->where('status', '!=', ArchiveEntry::STATUS_VOIDED)->count(),
            'holds' => (clone $statsBase)->where('is_under_legal_hold', true)->count(),
            'review' => (clone $statsBase)->whereIn('status', [ArchiveEntry::STATUS_UNDER_REVIEW, ArchiveEntry::STATUS_ELIGIBLE_FOR_DISPOSITION])->count(),
            'permanent' => (clone $statsBase)->where('status', ArchiveEntry::STATUS_PERMANENT)->count(),
        ];

        $classifications = ArchiveClassification::where('is_active', true)->orderBy('code')->get();
        $companies = Company::whereIn('id', $accessibleCompanyIds)->get();

        return view('archives.index', compact('entries', 'stats', 'classifications', 'companies', 'companyId'));
    }

    /**
     * Archive Detail Page.
     */
    public function show(Request $request, ArchiveEntry $archive): View
    {
        $this->authorize('view', $archive);

        $archive->load([
            'company',
            'branch',
            'unitKerja',
            'classification',
            'retentionPolicy',
            'registeredBy',
            'approvedBy',
            'owner',
            'snapshot',
            'activeLegalHolds.appliedBy',
            'retentionAssignments.assigner',
            'relationships.toEntry',
            'relationships.relatedDocument',
            'incomingRelationships.fromEntry',
            'events.actor',
        ]);

        $canCorrect = $request->user()->can('correctMetadata', $archive);
        $canReactivate = $request->user()->can('reactivate', $archive);
        $canPlaceHold = $request->user()->can('placeHold', $archive);
        $canRequestDisposition = $request->user()->can('requestDisposition', $archive);
        $canVoid = $request->user()->can('void', $archive);

        return view('archives.show', compact(
            'archive',
            'canCorrect',
            'canReactivate',
            'canPlaceHold',
            'canRequestDisposition',
            'canVoid'
        ));
    }

    /**
     * Download original immutable snapshot file.
     */
    public function download(Request $request, ArchiveEntry $archive): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $archive);

        if ($archive->isDisposed()) {
            return back()->with('error', __('Berkas arsip fisik telah dimusnahkan secara sah berdasarkan Berita Acara Pemusnahan.'));
        }

        $snapshot = $archive->snapshot;
        if (!$snapshot || !$snapshot->object_key) {
            return back()->with('error', __('Berkas snapshot arsip tidak ditemukan.'));
        }

        $disk = Storage::disk($snapshot->storage_disk);
        if (!$disk->exists($snapshot->object_key)) {
            return back()->with('error', __('Objek snapshot fisik tidak ditemukan pada media penyimpanan.'));
        }

        $filename = $snapshot->original_filename ?: ($archive->archive_number . '.docx');

        return response()->streamDownload(function () use ($disk, $snapshot) {
            echo $disk->get($snapshot->object_key);
        }, $filename, [
            'Content-Type' => $snapshot->mime_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * Preview archive snapshot in browser.
     */
    public function preview(Request $request, ArchiveEntry $archive): Response|StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $archive);

        if ($archive->isDisposed()) {
            return back()->with('error', __('Berkas arsip fisik telah dimusnahkan.'));
        }

        $snapshot = $archive->snapshot;
        if (!$snapshot) {
            return back()->with('error', __('Snapshot arsip tidak ditemukan.'));
        }

        if ($snapshot->is_html_snapshot) {
            return response($snapshot->html_content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        $disk = Storage::disk($snapshot->storage_disk);
        if (!$snapshot->object_key || !$disk->exists($snapshot->object_key)) {
            return back()->with('error', __('Berkas fisik tidak ditemukan di penyimpanan.'));
        }

        return response()->streamDownload(function () use ($disk, $snapshot) {
            echo $disk->get($snapshot->object_key);
        }, $snapshot->original_filename ?: 'archive.docx', [
            'Content-Type' => $snapshot->mime_type ?: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'inline; filename="' . ($snapshot->original_filename ?: 'archive.docx') . '"',
        ]);
    }

    /**
     * Trigger on-demand cryptographic integrity verification.
     */
    public function verifyIntegrity(Request $request, ArchiveEntry $archive): RedirectResponse
    {
        $this->authorize('view', $archive);

        $result = $this->snapshotService->verifyIntegrity($archive, $request->user());

        if ($result['passed']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    /**
     * Export archive registry to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', ArchiveEntry::class);

        $user = $request->user();
        $accessibleCompanyIds = $user->allCompanyIds();

        $query = ArchiveEntry::whereIn('company_id', $accessibleCompanyIds)
            ->with(['classification', 'branch', 'unitKerja']);

        $filename = 'daftar-arsip-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Nomor Arsip',
                'Judul',
                'Klasifikasi',
                'Kode Dokumen Asli',
                'Perusahaan',
                'Cabang',
                'Unit Kerja',
                'Tanggal Registrasi',
                'Status',
                'Tingkat Akses',
                'Jatuh Tempo Retensi',
                'Legal Hold',
            ]);

            $query->chunk(200, function ($entries) use ($handle) {
                foreach ($entries as $e) {
                    fputcsv($handle, [
                        $e->archive_number,
                        $e->title,
                        $e->classification_code_snapshot,
                        $e->original_document_number,
                        $e->company_code_snapshot,
                        $e->branch_code_snapshot,
                        $e->unit_code_snapshot,
                        $e->registered_at?->format('Y-m-d H:i'),
                        $e->status,
                        $e->sensitivity_level,
                        $e->retention_due_at?->format('Y-m-d') ?: ($e->isPermanent() ? 'Permanen' : '-'),
                        $e->is_under_legal_hold ? 'YA' : 'TIDAK',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Correct metadata (descriptive fields only) with mandatory audit reason.
     */
    public function correctMetadata(Request $request, ArchiveEntry $archive): RedirectResponse
    {
        $this->authorize('correctMetadata', $archive);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'archive_reason' => 'nullable|string',
            'sensitivity_level' => 'required|in:public,internal,confidential,restricted',
            'storage_location' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'record_date' => 'nullable|date',
            'effective_date' => 'nullable|date',
            'correction_reason' => 'required|string|min:5|max:1000',
        ]);

        $reason = $validated['correction_reason'];
        unset($validated['correction_reason']);

        $this->registryService->correctMetadata($archive, $validated, $reason, $request->user());

        return back()->with('success', __('Metadata arsip berhasil diperbarui dan dicatat dalam audit trail.'));
    }

    /**
     * Reactivate an archive record as a new working document copy.
     */
    public function reactivate(Request $request, ArchiveEntry $archive): RedirectResponse
    {
        $this->authorize('reactivate', $archive);

        $validated = $request->validate([
            'reactivation_reason' => 'required|string|min:5|max:1000',
        ]);

        $workingDoc = $this->registryService->reactivateAsWorkingCopy(
            entry: $archive,
            actor: $request->user(),
            reason: $validated['reactivation_reason']
        );

        return redirect()->route('documents.show', $workingDoc->id)
            ->with('success', __('Salinan kerja baru berhasil dibuat dari Arsip :num.', ['num' => $archive->archive_number]));
    }

    /**
     * Mark an archive entry as void.
     */
    public function void(Request $request, ArchiveEntry $archive): RedirectResponse
    {
        $this->authorize('void', $archive);

        $validated = $request->validate([
            'void_reason' => 'required|string|min:5|max:1000',
        ]);

        $this->registryService->voidArchiveEntry($archive, $validated['void_reason'], $request->user());

        return back()->with('success', __('Arsip :num telah dibatalkan (void). Nomor arsip tidak dapat digunakan kembali.', ['num' => $archive->archive_number]));
    }

    /**
     * Public or authenticated QR verification endpoint.
     */
    public function verifyByToken(Request $request, string $token): View
    {
        $entry = ArchiveEntry::where('qr_token', $token)->firstOrFail();

        return view('archives.verify', compact('entry'));
    }
}
