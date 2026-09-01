<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\Division;
use App\Models\DocumentVersion;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\QrCodeService;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected VersionService $versionService,
        protected AuditService $auditService,
        protected QrCodeService $qrCodeService,
        protected \App\Services\OnlyOfficeService $onlyOfficeService,
    ) {}

    private function getApprovedSignatures(Document $document): array
    {
        return \App\Models\SignatureRequest::where('document_id', $document->id)
            ->where('status', 'approved')
            ->where('is_used', false)
            ->with('targetUser.signature')
            ->get()
            ->map(function ($req) {
                if (!$req->targetUser || !$req->targetUser->hasSignature()) {
                    return null;
                }
                return [
                    'request_id' => $req->id,
                    'url' => $this->onlyOfficeService->getSignatureFileUrl($req->targetUser),
                    'target_user_name' => $req->targetUser->name,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    public function index(Request $request): View
    {
        $user = auth()->user();

        // If director accesses index without type specified or generally, redirect to director browsing view
        if ($user->isDirector() && !$request->has('type')) {
            return redirect()->route('director.documents.index');
        }

        // Tab selection: general | mine | division
        $type = $request->get('type', 'general');

        $query = Document::with('owner', 'division', 'company', 'branch', 'currentVersion', 'versions');

        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);
        $userBranchIds = $user->allBranchIds();
        $userCompanyIds = $user->allCompanyIds();

        $folder = $request->get('folder');
        $virtualFolders = [];
        $breadcrumbs = [];

        // Base query - only apply strict active branch filtering if NOT in 'general' type,
        // or if in 'general' type, we will apply specific branch filters based on the active folder.
        if (!$user->isAdmin()) {
            if ($type !== 'general' && $type !== 'shared') {
                if ($activeBranchId && !$user->isDirector()) {
                    $query->where('branch_id', $activeBranchId);
                } elseif ($activeCompanyId && !$user->isDirector()) {
                    $query->where(function ($q) use ($activeCompanyId) {
                        $q->where('company_id', $activeCompanyId)
                          ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId));
                    });
                } elseif (!empty($userBranchIds)) {
                    $query->where(function ($q) use ($userBranchIds, $userCompanyIds) {
                        $q->whereIn('branch_id', $userBranchIds)
                          ->orWhere(function ($sub) use ($userCompanyIds) {
                              $sub->whereNull('branch_id')
                                  ->whereIn('company_id', $userCompanyIds);
                          });
                    });
                } elseif (!empty($userCompanyIds)) {
                    $query->whereIn('company_id', $userCompanyIds);
                }
            }
        }

        if ($type === 'general') {
            $query->general()->where(function ($q) use ($activeBranchId, $activeCompanyId, $user) {
                if ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId)
                       ->orWhere(function ($sub) use ($activeCompanyId) {
                           $sub->whereNull('branch_id')
                               ->where('company_id', $activeCompanyId);
                       })
                       ->orWhereHas('distributions', fn($dq) => $dq->where('target_branch_id', $activeBranchId));
                } elseif ($activeCompanyId) {
                    $q->where(function ($sub) use ($activeCompanyId) {
                        $sub->where('company_id', $activeCompanyId)
                            ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId));
                    })
                    ->orWhereHas('distributions', fn($dq) => $dq->whereHas('targetBranch', fn($b) => $b->where('company_id', $activeCompanyId)));
                } else {
                    $q->whereIn('branch_id', $user->allBranchIds())
                       ->orWhereIn('company_id', $user->allCompanyIds())
                       ->orWhereHas('distributions', fn($dq) => $dq->whereIn('target_branch_id', $user->allBranchIds()));
                }
            });
            
            // Clear notifications
            $user->unreadNotifications()
                ->where('data->type', 'document_cross_branch_received')
                ->update(['read_at' => now()]);
        } elseif ($type === 'mine') {
            // My Documents — semua dokumen milik user, apapun scopenya.
            $query->ownedBy($user);
        } elseif ($type === 'shared') {
            // Shared Documents — shared directly with user or user's division, excluding owned docs.
            $divisionIds = $user->allDivisionIds();
            $query->where('owner_id', '!=', $user->id)
                  ->where(function ($q) use ($user, $divisionIds) {
                      $q->whereHas('shares', fn($sq) => $sq->where('user_id', $user->id));
                      if (!empty($divisionIds)) {
                          $q->orWhereHas('divisionShares', fn($dq) => $dq->whereIn('division_id', $divisionIds));
                      }
                  })
                  ->with([
                      'shares' => fn($q) => $q->where('user_id', $user->id),
                      'divisionShares' => fn($q) => !empty($divisionIds) ? $q->whereIn('division_id', $divisionIds) : $q,
                  ]);

            // Clear unread shared document notifications when viewing the shared list
            $user->unreadNotifications()
                ->where('data->type', 'document_shared')
                ->update(['read_at' => now()]);
        } else {
            // Division-scoped documents of divisions the user belongs to.
            $query->division($user);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($divisionId = $request->get('division_id')) {
            $query->where('division_id', $divisionId);
        }

        if ($documentTypeId = $request->get('document_type_id')) {
            $query->where('document_type_id', $documentTypeId);
        }

        if ($year = $request->get('year')) {
            $query->whereYear('created_at', (int) $year);
        }

        if ($status = $request->get('status')) {
            if ($status === 'active') {
                $query->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
                      ->where('is_expired', false);
            } elseif ($status === 'pending') {
                $query->whereDoesntHave('currentVersion')
                    ->orWhereHas('versions', fn($q) => $q->where('status', 'pending'));
            } elseif ($status === 'draft') {
                $query->whereDoesntHave('versions');
            } elseif ($status === 'expired') {
                $query->where('is_expired', true);
            }
        } else {
            // Sembunyikan dokumen kedaluwarsa secara default kecuali diminta
            $query->where('is_expired', false);
        }

        $documents = $query->latest()->paginate(15)->withQueryString();
        $documentTypes = DocumentType::orderBy('name')->get();

        $view = match ($type) {
            'mine' => 'documents.mine',
            'division' => 'documents.division',
            'shared' => 'documents.shared_index',
            default => 'documents.general',
        };

        // General documents always shows the filter toolbar.
        // Other types already include _search unconditionally in their views.
        $showDocuments = true;

        return view($view, compact('documents', 'documentTypes', 'type', 'showDocuments', 'virtualFolders', 'breadcrumbs', 'folder'));
    }

    /**
     * MS Word-style chooser page: blank document or pick a template.
     */
    public function choose(): View
    {
        $templates = DocumentTemplate::active()->with('documentType')->orderBy('title')->get();
        $documentTypes = DocumentType::all();
        
        $frequentTemplates = DocumentTemplate::active()
            ->withCount('documents')
            ->orderByDesc('documents_count')
            ->limit(5)
            ->get()
            ->filter(fn($t) => $t->documents_count > 0);

        return view('documents.choose', compact('templates', 'documentTypes', 'frequentTemplates'));
    }

    public function create(Request $request): View
    {
        $user = auth()->user();
        $divisions = ($user->isAdmin() || $user->isDirector())
            ? Division::all()
            : Division::whereIn('id', $user->allDivisionIds())->get();
        $documentTypes = DocumentType::all();

        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeBranch = $activeBranchId ? \App\Models\Branch::with('company')->find($activeBranchId) : null;
        $availableBranches = $contextService->getAvailableBranches($user);

        // If a template_id is passed via URL, load the template for auto-fill
        $selectedTemplate = null;
        $initialDocumentNumber = null;
        if ($templateId = $request->query('template_id')) {
            $selectedTemplate = DocumentTemplate::active()->with('documentType')->find($templateId);
            if ($selectedTemplate && $selectedTemplate->documentType) {
                $userDivision = ($user->isAdmin() || $user->isDirector())
                    ? ($request->filled('division_id') ? Division::find($request->input('division_id')) : null)
                    : $user->division;
                $initialDocumentNumber = $this->documentService->previewNumber($userDivision, $selectedTemplate->documentType, $activeBranch);
            }
        }

        return view('documents.create', compact(
            'divisions', 'documentTypes', 'activeBranch', 'availableBranches', 'selectedTemplate', 'initialDocumentNumber'
        ));
    }

    /**
     * Preview nomor dokumen berikutnya untuk tipe dokumen tertentu.
     */
    public function nextNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type_id' => 'required|exists:document_types,id',
            'branch_id' => 'nullable|exists:branches,id',
            'division_id' => 'nullable|exists:divisions,id',
        ]);

        $user = auth()->user();
        $divisionId = ($user->isAdmin() || $user->isDirector())
            ? ($validated['division_id'] ?? $user->division_id)
            : $user->division_id;
        $division = $divisionId ? Division::find($divisionId) : null;
        $documentType = DocumentType::findOrFail($validated['document_type_id']);

        $contextService = app(\App\Services\CompanyContextService::class);
        $branchId = $validated['branch_id'] ?? $contextService->getActiveBranchId($user);
        $branch = $branchId ? \App\Models\Branch::with('company')->find($branchId) : null;

        return response()->json([
            'number' => $this->documentService->previewNumber($division, $documentType, $branch),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $user = auth()->user();
        $isUpload = $request->boolean('is_upload');
        $templateId = $request->input('template_id');

        $rules = [
            'title' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'division_id' => ($user->isAdmin() || $user->isDirector()) ? 'required|exists:divisions,id' : 'nullable',
            'branch_id' => 'nullable|exists:branches,id',
            'expiration_date' => 'nullable|date',
            'template_id' => 'nullable|exists:document_templates,id',
        ];

        if ($isUpload) {
            $rules['file'] = 'required|file|mimes:pdf,docx|max:10240';
            $rules['document_number'] = [
                'required',
                'string',
                'max:100',
                'unique:documents,document_number',
                // Format resmi: seq/tipe/divisi/pusat/bulan-romawi/tahun
                'regex:/^\d{3}\/[A-Z0-9.\-]+\/[A-Z0-9.\-]+\/[A-Z0-9.\-]+\/(I|II|III|IV|V|VI|VII|VIII|IX|X|XI|XII)\/\d{4}$/',
            ];
        }

        $validated = $request->validate($rules, [
            'document_number.regex' => 'Format nomor tidak sesuai. Contoh: 029/S.ED/HRD/JBM/VIII/2026',
        ]);

        $validated['division_id'] = ($user->isAdmin() || $user->isDirector())
            ? ($validated['division_id'] ?? null)
            : $user->division_id;
        $validated['visibility'] = Document::VISIBILITY_DIVISION;
        $validated['expiration_date'] = $validated['expiration_date'] ?? null;

        $contextService = app(\App\Services\CompanyContextService::class);
        $branchId = $validated['branch_id'] ?? $contextService->getActiveBranchId($user);
        if ($branchId) {
            $branch = \App\Models\Branch::find($branchId);
            $validated['branch_id'] = $branchId;
            $validated['company_id'] = $branch?->company_id;
        }

        if ($isUpload) {
            $doc = $this->documentService->createFromUpload($validated, $user->id, $request->file('file'));
            $message = 'Dokumen berhasil diunggah. Silakan edit dokumen di editor.';
        } elseif ($templateId) {
            $template = DocumentTemplate::findOrFail($templateId);
            $doc = $this->documentService->createFromTemplate($validated, $user->id, $template);
            $message = 'Dokumen berhasil dibuat dari template. Silakan edit di editor.';
        } else {
            $doc = $this->documentService->create($validated, $user->id);
            $message = 'Document created. Fill in the content.';
        }

        $this->auditService->log($user, 'document.created', 'document', $doc->id, [
            'title' => $doc->title,
            'document_number' => $doc->document_number,
            'visibility' => $doc->visibility,
            'via_upload' => $isUpload,
            'from_template' => $templateId ? true : false,
        ]);

        return redirect()->route('documents.edit', $doc)->with('success', $message);
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $currentUser = auth()->user();

        // If the viewer is not the document owner, and has granted access to this document, notify the owner
        if ($document->owner_id && $currentUser && $currentUser->id !== $document->owner_id) {
            $hasGrantedAccess = \App\Models\DocumentShare::where('document_id', $document->id)
                ->where('user_id', $currentUser->id)
                ->exists();

            if (!$hasGrantedAccess && !empty($currentUser->allDivisionIds())) {
                $hasGrantedAccess = \App\Models\DocumentDivisionShare::where('document_id', $document->id)
                    ->whereIn('division_id', $currentUser->allDivisionIds())
                    ->exists();
            }

            if ($hasGrantedAccess) {
                // Mark unread share notification for this document as read
                $currentUser->unreadNotifications()
                    ->where('data->type', 'document_shared')
                    ->where('data->document_id', $document->id)
                    ->update(['read_at' => now()]);

                // Throttle notification per viewer & document (15 minutes) to prevent notification spam on page reload
                $throttleKey = 'notif_doc_opened_' . $document->id . '_' . $currentUser->id;
                if (\Illuminate\Support\Facades\Cache::add($throttleKey, true, now()->addMinutes(15))) {
                    $document->owner?->notify(new \App\Notifications\DocumentOpenedByGrantedUser($document, $currentUser->name));
                }
            }
        }

        $document->load('owner', 'division', 'documentType', 'currentVersion', 'versions.author', 'shares.user', 'divisionShares.division');

        $divisions = auth()->user()->isAdmin()
            ? Division::all()
            : Division::whereIn('id', auth()->user()->allDivisionIds())->get();

        $version = $document->displayVersion();
        $onlyOfficeConfig = null;
        if ($version) {
            $onlyOfficeConfig = $this->onlyOfficeService->generateEditorConfig(
                $document,
                $version,
                auth()->user(),
                'view'
            );
        }
        $companies = \App\Models\Company::with('branches')->get();

        $approvedSignatures = $this->getApprovedSignatures($document);

        return view('documents.show', compact('document', 'divisions', 'onlyOfficeConfig', 'version', 'approvedSignatures', 'companies'));
    }

    /**
     * Mulai ringkasan AI secara asinkron. Request langsung balas dengan
     * status processing — Groq dipanggil di queue job, bukan di sini.
     */
    public function summarize(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $force = $request->boolean('force');
        $percentage = (int) $request->input('percentage', 30);
        $model = $request->input('model', 'auto');
        $locale = app()->getLocale();

        // Sudah selesai & tidak dipaksa ringkas ulang → kirim hasil yang tersimpan.
        if ($document->isSummaryCompleted() && !$force) {
            return response()->json([
                'status' => Document::SUMMARY_COMPLETED,
                'summary' => $document->summary,
            ]);
        }

        // Jika force ringkas ulang atau status sebelumnya failed → reset status
        if ($force || $document->summary_status === Document::SUMMARY_FAILED) {
            $document->update([
                'summary_status' => Document::SUMMARY_PENDING,
                'summary' => null,
                'summary_error' => null,
            ]);
        }

        $this->documentService->dispatchSummary($document, $percentage, $model, $locale);

        $document->refresh();

        return response()->json([
            'status' => $document->summary_status,
            'summary' => $document->summary,
            'error' => $document->summary_error,
            'document_id' => $document->id,
        ]);
    }

    /**
     * Status ringkasan untuk polling frontend.
     */
    public function summaryStatus(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        return response()->json([
            'status' => $document->summary_status,
            'summary' => $document->summary,
            'error' => $document->summary_error,
        ]);
    }

    /**
     * Poll ONLYOFFICE editor state (active session or compilation state)
     */
    public function onlyofficeStatus(Document $document): JsonResponse
    {
        $this->authorize('view', $document);
        $version = $document->displayVersion();

        return response()->json([
            'active' => \Illuminate\Support\Facades\Cache::has('onlyoffice_active_' . $document->id),
            'updated_at' => $version?->updated_at?->timestamp,
        ]);
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        $document->load('currentVersion', 'versions');

        $version = $document->displayVersion();

        if (!$version) {
            abort(404, 'Document version not found.');
        }

        $onlyOfficeConfig = $this->onlyOfficeService->generateEditorConfig(
            $document,
            $version,
            auth()->user(),
            'edit'
        );

        $currentUser = auth()->user();
        $userSignatureUrl = $this->onlyOfficeService->getSignatureFileUrl($currentUser);
        $userSignatureToken = $userSignatureUrl ? $this->onlyOfficeService->generateInsertImageToken($userSignatureUrl) : null;
        $userSignatureDataUri = ($currentUser->hasSignature() && $currentUser->signature?->file_path && Storage::disk('public')->exists($currentUser->signature->file_path))
            ? 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($currentUser->signature->file_path))
            : null;

        $qrCodeUrl = $this->onlyOfficeService->getQrCodeFileUrl($document);
        $qrCodeToken = $this->onlyOfficeService->generateInsertImageToken($qrCodeUrl);
        $qrCodeDataUri = $this->qrCodeService->dataUri($this->qrCodeService->qrcodeUrl($document));

        $approvedSignatures = $this->getApprovedSignatures($document);

        // Build server-side banner data for approved signature requests
        $pendingApprovalBanner = \App\Models\SignatureRequest::where('document_id', $document->id)
            ->where('requester_id', $currentUser->id)
            ->where('status', 'approved')
            ->where('is_used', false)
            ->with('targetUser')
            ->get()
            ->map(fn($r) => $r->targetUser?->name)
            ->filter()
            ->values()
            ->toArray();

        return view('documents.edit', compact(
            'document',
            'version',
            'onlyOfficeConfig',
            'qrCodeUrl',
            'qrCodeToken',
            'qrCodeDataUri',
            'userSignatureUrl',
            'userSignatureToken',
            'userSignatureDataUri',
            'approvedSignatures',
            'pendingApprovalBanner'
        ));
    }

    public function preview(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('owner', 'division', 'documentType', 'currentVersion');

        $version = $document->displayVersion();
        $onlyOfficeConfig = null;
        if ($version) {
            $onlyOfficeConfig = $this->onlyOfficeService->generateEditorConfig(
                $document,
                $version,
                auth()->user(),
                'view'
            );
        }

        $approvedSignatures = $this->getApprovedSignatures($document);

        return view('documents.preview', compact('document', 'onlyOfficeConfig', 'approvedSignatures'));
    }

    public function previewContent(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('currentVersion');

        return view('documents.preview-content', compact('document'));
    }

    public function previewVersion(Document $document, DocumentVersion $version): View
    {
        $this->authorize('view', $document);

        abort_unless($version->document_id === $document->id, 404);

        $onlyOfficeConfig = $this->onlyOfficeService->generateEditorConfig(
            $document,
            $version,
            auth()->user(),
            'view'
        );

        $approvedSignatures = $this->getApprovedSignatures($document);

        return view('documents.preview-version', compact('document', 'version', 'onlyOfficeConfig', 'approvedSignatures'));
    }

    public function save(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string',
            'paper_size' => 'nullable|string|in:A4,A5,A3,Letter,Legal',
            // paper_margin dikirim sebagai JSON string dari hidden input
            // (lihat insert.blade.php) — decode manual ke array.
            'paper_margin' => 'nullable|string',
        ]);

        $margin = $this->decodePaperMargin($validated['paper_margin'] ?? null);

        // Simpan pengaturan kertas ke dokumen (dipakai preview/show).
        $document->update([
            'paper_size' => $validated['paper_size'] ?? 'A4',
            'paper_margin' => $margin,
        ]);

        // savePending updates the existing pending version in place — no new version.
        $user = auth()->user();
        $version = $this->versionService->savePending($document, $validated['content'], $user);

        $this->auditService->log($user, 'version.created', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        // Notify Division Heads about pending approval
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
                $head->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $user->name));
            }
        }

        $message = $version->wasRecentlyCreated
            ? 'Edit saved. Pending approval.'
            : 'Versi v' . $version->version_number . ' diperbarui (tetap menunggu approval).';

        return redirect()->route('documents.show', $document)->with('success', $message);
    }

    public function discard(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $discarded = $this->versionService->discardPending($document);

        if ($discarded) {
            $this->auditService->log(auth()->user(), 'version.discarded', 'document_version', $discarded->id, [
                'document_id' => $document->id,
                'version_number' => $discarded->version_number,
            ]);
        }

        return redirect()->route('documents.index', ['type' => 'mine'])->with('success', $discarded
            ? 'Versi pending v' . $discarded->version_number . ' di-discard.'
            : 'Tidak ada versi pending untuk di-discard.');
    }

    public function saveDraft(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'content' => 'required|string',
            'paper_size' => 'nullable|string|in:A4,A5,A3,Letter,Legal',
            'paper_margin' => 'nullable|string',
        ]);

        $margin = $this->decodePaperMargin($validated['paper_margin'] ?? null);

        // Simpan pengaturan kertas ke dokumen (dipakai preview/show).
        // Hanya update yang benar-benar dikirim form — jangan menimpa
        // pengaturan yang sudah ada dengan null kalau form lama tidak
        // mengirim hidden input paper (dulu margin hilang setelah save draft).
        $document->update(array_filter([
            'paper_size' => $validated['paper_size'] ?? null,
            'paper_margin' => $margin,
        ], fn($v) => $v !== null));

        $this->versionService->saveDraft($document, $validated['content'], auth()->user());

        return redirect()->route('documents.show', $document)->with('success', 'Draft saved.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document discarded.');
    }

    public function uploadVersion(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf,docx|max:10240',
        ]);

        $user = auth()->user();
        $version = $this->versionService->savePendingFile($document, $request->file('file'), $user);

        $this->auditService->log($user, 'version.uploaded', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        // Notify Division Heads about pending approval
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
                $head->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $user->name));
            }
        }

        return redirect()->route('documents.show', $document)->with('success', 'Versi baru diunggah. Menunggu approval.');
    }

    /**
     * Download the latest version of the document as DOCX.
     */
    public function download(Document $document)
    {
        $this->authorize('view', $document);

        $version = $document->displayVersion();
        abort_unless($version && $version->file_path, 404, 'File not found');

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        abort_unless($disk->exists($version->file_path), 404, 'Physical file not found');

        $downloadName = $document->title;
        if (!str_ends_with(strtolower($downloadName), '.docx') && !str_ends_with(strtolower($downloadName), '.pdf')) {
            $downloadName .= '.docx';
        }

        return $disk->download($version->file_path, $downloadName);
    }

    /**
     * Stream berkas unggahan secara privat. Akses tetap tunduk pada
     * policy 'view' dokumen — tidak pernah lewat disk publik.
     */
    public function file(Document $document, DocumentVersion $version)
    {
        $this->authorize('view', $document);

        abort_unless($version->document_id === $document->id, 404);
        abort_unless($version->file_path, 404);

        return Storage::disk(config('onlyoffice.storage_disk', 'local'))->response(
            $version->file_path,
            $version->file_original_name ?? ($document->title . '.docx'),
            ['Content-Disposition' => 'inline; filename="' . ($version->file_original_name ?? ($document->title . '.docx')) . '"']
        );
    }

    /**
     * Stream gambar QR code (PNG) yang mengarah ke halaman show dokumen ini.
     * Dipakai oleh tombol "print" di toolbar Jodit (client-side) untuk
     * mengganti placeholder QR jadi gambar asli sebelum window.print()
     * dipanggil — lihat getCleanValue({forPrint:true}) di resources/js/jodit.js.
     */
    public function qrCode(Document $document)
    {
        $this->authorize('view', $document);

        $png = $this->qrCodeService->pngBytes($this->qrCodeService->qrcodeUrl($document));

        return response($png, 200, ['Content-Type' => 'image/png']);
    }

    /**
     * Resolver QR: token terenkripsi (lihat QrCodeService::qrcodeUrl) →
     * dokumen → redirect ke halaman show. QR di dokumen fisik menunjuk
     * ke sini, bukan ke URL dengan ID mentah.
     */
    public function viewByHash(string $token)
    {
        try {
            $id = Crypt::decryptString(base64_decode(strtr($token, '-_', '+/')));
        } catch (\Throwable) {
            abort(404);
        }

        $document = Document::findOrFail($id);

        if (!auth()->user()->can('view', $document)) {
            abort(403, 'Anda tidak punya akses ke dalam dokumen ini.');
        }

        return view('documents.verified', compact('document'));
    }

    /**
     * Change a document's visibility scope (general / division / personal).
     * Division is not selectable here — the document keeps its original
     * division (division_id is NOT NULL at DB level).
     */
    public function updateVisibility(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'visibility' => 'required|in:general,division,personal',
            'target_branch_ids' => 'nullable|array',
            'target_branch_ids.*' => 'exists:branches,id'
        ]);

        // Division scope keeps the document's original division; fall back to
        // the current user's division if the document has none.
        $divisionId = $document->division_id ?? auth()->user()->division_id;

        $document->update([
            'visibility' => $validated['visibility'],
            'division_id' => $divisionId,
            // Legacy derived flag stays in sync with the scope.
            'is_public' => $validated['visibility'] === Document::VISIBILITY_GENERAL,
        ]);
        
        // Sync document distributions for general visibility
        if ($validated['visibility'] === Document::VISIBILITY_GENERAL) {
            $targetBranchIds = $request->input('target_branch_ids', []);
            \App\Models\DocumentDistribution::where('document_id', $document->id)->delete();
            
            if (is_array($targetBranchIds) && count($targetBranchIds) > 0) {
                $distributions = [];
                $targetBranches = \App\Models\Branch::whereIn('id', $targetBranchIds)->get()->keyBy('id');
                
                foreach ($targetBranchIds as $targetBranchId) {
                    $distributions[] = [
                        'document_id' => $document->id,
                        'source_branch_id' => $document->branch_id,
                        'target_branch_id' => $targetBranchId,
                        'sent_at' => now(),
                        'status' => 'unread',
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    // Notify users in the target branch
                    if (isset($targetBranches[$targetBranchId])) {
                        $branchUsers = \App\Models\User::whereHas('branches', fn($q) => $q->where('branches.id', $targetBranchId))
                            ->where('is_active', true)
                            ->where('id', '!=', auth()->id())
                            ->get();
                            
                        $targetBranchName = $targetBranches[$targetBranchId]->name;
                        $senderName = auth()->user()->name;
                        
                        foreach ($branchUsers as $branchUser) {
                            $branchUser->notify(new \App\Notifications\CrossBranchDocumentReceived($document, $targetBranchName, $senderName));
                        }
                    }
                }
                \App\Models\DocumentDistribution::insert($distributions);
            }
        } else {
            // Remove all distributions if not general
            \App\Models\DocumentDistribution::where('document_id', $document->id)->delete();
        }

        $this->auditService->log(auth()->user(), 'document.visibility_changed', 'document', $document->id, [
            'visibility' => $validated['visibility'],
            'division_id' => $document->division_id,
        ]);

        return back()->with('success', 'Document visibility updated.');
    }

    /**
     * Decode paper_margin yang dikirim sebagai JSON string dari hidden input
     * (lihat insert.blade.php). Kembalikan array {top,right,bottom,left} atau
     * null kalau kosong/tidak valid.
     */
    private function decodePaperMargin(?string $raw): ?array
    {
        if (!$raw || trim($raw) === '' || trim($raw) === 'null') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        // Hanya ambil 4 sisi margin, pastikan angka ≥ 0.
        $margin = [];
        foreach (['top', 'right', 'bottom', 'left'] as $side) {
            $v = $decoded[$side] ?? null;
            if (!is_numeric($v) || (int) $v < 0) {
                return null;
            }
            $margin[$side] = (int) $v;
        }

        return $margin;
    }

    /**
     * View a document template using ONLYOFFICE.
     */
    public function previewTemplate(DocumentTemplate $template): View
    {
        $user = auth()->user();
        
        $config = $this->onlyOfficeService->generateTemplateEditorConfig(
            $template, 
            $user, 
            'view'
        );

        return view('templates.preview', compact('template', 'config'));
    }
}