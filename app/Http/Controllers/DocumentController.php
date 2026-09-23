<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\DocumentUnitKerjaShare;
use App\Models\DocumentVersion;
use App\Models\SignatureRequest;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\ApprovalRoutingService;
use App\Services\AuditService;
use App\Services\CompanyContextService;
use App\Services\DocumentProcessorService;
use App\Services\DocumentService;
use App\Services\OnlyOfficeService;
use App\Services\QrCodeService;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $documentService,
        protected VersionService $versionService,
        protected AuditService $auditService,
        protected QrCodeService $qrCodeService,
        protected OnlyOfficeService $onlyOfficeService,
        protected ApprovalRoutingService $approvalRoutingService,
    ) {}

    private function getApprovedSignatures(Document $document): array
    {
        return SignatureRequest::where('document_id', $document->id)
            ->where('status', 'approved')
            ->where('is_used', false)
            ->with(['targetUser.signatures', 'requestedSignature.company'])
            ->get()
            ->map(function ($req) {
                $targetUser = $req->targetUser;
                if (!$targetUser || !$targetUser->hasSignature()) {
                    return null;
                }
                $sig = $req->requestedSignature 
                    ?? $targetUser->signatures()->where('type', 'original')->first() 
                    ?? $targetUser->signatures()->first();
                if (!$sig) {
                    return null;
                }
                return [
                    'request_id' => $req->id,
                    'url' => $this->onlyOfficeService->getSignatureFileUrlForSignature($sig),
                    'target_user_name' => $targetUser->name,
                    'type' => $sig->type,
                    'company_name' => $sig->company?->name,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * Automatically apply any approved signature requests to the DOCX file and rotate OnlyOffice cache key.
     */
    private function autoApplyApprovedSignatures(Document $document, ?DocumentVersion $version = null): void
    {
        $version = $version ?? $document->displayVersion();
        if (!$version || !$version->file_path || !str_ends_with(strtolower($version->file_path), '.docx')) {
            return;
        }

        $approvedRequests = SignatureRequest::where('document_id', $document->id)
            ->where('status', 'approved')
            ->where('is_used', false)
            ->latest('id')
            ->with(['targetUser.signatures', 'requestedSignature'])
            ->get();

        if ($approvedRequests->isEmpty()) {
            return;
        }

        $processor = app(DocumentProcessorService::class);
        $appliedAny = false;

        foreach ($approvedRequests as $req) {
            $sig = null;
            if ($req->isStamp() && $req->requestedSignature) {
                $sig = $req->requestedSignature;
            } elseif ($req->requestedSignature) {
                $sig = $req->requestedSignature;
            } else {
                $sig = $req->targetUser?->signatures()->where('type', 'original')->first() 
                    ?? $req->targetUser?->signatures()->first();
            }

            $signaturePath = null;
            if ($sig && $sig->file_path) {
                if (Storage::disk('public')->exists($sig->file_path)) {
                    $signaturePath = Storage::disk('public')->path($sig->file_path);
                } elseif (file_exists(storage_path('app/public/' . ltrim($sig->file_path, '/')))) {
                    $signaturePath = storage_path('app/public/' . ltrim($sig->file_path, '/'));
                } elseif (file_exists(public_path('storage/' . ltrim($sig->file_path, '/')))) {
                    $signaturePath = public_path('storage/' . ltrim($sig->file_path, '/'));
                } elseif (file_exists($sig->file_path)) {
                    $signaturePath = $sig->file_path;
                }
            }

            if ($signaturePath && file_exists($signaturePath)) {
                $success = $processor->processSignature($document, $version, $req->id, $signaturePath, $req);
                if ($success) {
                    $appliedAny = true;
                }
            }
        }

        if ($appliedAny) {
            $this->onlyOfficeService?->rotateDocumentKey($document, $version);
        }
    }

    public function index(Request $request): View
    {
        $user = auth()->user();

        // If director accesses index without type specified or generally, redirect to director browsing view
        if ($user->isDirector() && !$request->has('type')) {
            return redirect()->route('director.documents.index');
        }

        // Tab selection: general | mine | unit_kerja (or legacy 'division') | shared
        $type = $request->get('type', 'general');
        if ($type === 'division') {
            $type = 'unit_kerja';
        }

        $query = Document::with('owner', 'unitKerja', 'company', 'branch', 'currentVersion', 'versions');

        $contextService = app(CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);
        $userBranchIds = $user->allBranchIds();
        $userCompanyIds = $user->allCompanyIds();

        $folder = $request->get('folder');
        $virtualFolders = [];
        $breadcrumbs = [];

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
            if ($user->isAdmin()) {
                if ($activeBranchId) {
                    $query->where(function ($q) use ($activeBranchId, $activeCompanyId) {
                        $q->where(function ($gq) use ($activeBranchId, $activeCompanyId) {
                            $gq->where('visibility', Document::VISIBILITY_GENERAL)
                               ->where(function ($bq) use ($activeBranchId, $activeCompanyId) {
                                   $bq->where('branch_id', $activeBranchId)
                                      ->orWhere(function ($sub) use ($activeCompanyId) {
                                          $sub->whereNull('branch_id')
                                              ->where('company_id', $activeCompanyId);
                                      });
                               });
                        })
                        ->orWhereHas('distributions', fn($dq) => $dq->where('target_branch_id', $activeBranchId));
                    });
                } elseif ($activeCompanyId) {
                    $query->where(function ($q) use ($activeCompanyId) {
                        $q->where(function ($gq) use ($activeCompanyId) {
                            $gq->where('visibility', Document::VISIBILITY_GENERAL)
                               ->where(function ($sub) use ($activeCompanyId) {
                                   $sub->where('company_id', $activeCompanyId)
                                       ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId));
                               });
                        })
                        ->orWhereHas('distributions', fn($dq) => $dq->whereHas('targetBranch', fn($b) => $b->where('company_id', $activeCompanyId)));
                    });
                } else {
                    $query->where(function ($q) {
                        $q->where('visibility', Document::VISIBILITY_GENERAL)
                          ->orWhereHas('distributions');
                    });
                }
            } else {
                $query->where(function ($q) use ($activeBranchId, $activeCompanyId, $user) {
                    if ($activeBranchId) {
                        $q->where(function ($gq) use ($activeBranchId, $activeCompanyId) {
                            $gq->where('visibility', Document::VISIBILITY_GENERAL)
                               ->where(function ($bq) use ($activeBranchId, $activeCompanyId) {
                                   $bq->where('branch_id', $activeBranchId)
                                      ->orWhere(function ($sub) use ($activeCompanyId) {
                                          $sub->whereNull('branch_id')
                                              ->where('company_id', $activeCompanyId);
                                      });
                               });
                        })
                        ->orWhereHas('distributions', fn($dq) => $dq->where('target_branch_id', $activeBranchId));
                    } elseif ($activeCompanyId) {
                        $q->where(function ($gq) use ($activeCompanyId) {
                            $gq->where('visibility', Document::VISIBILITY_GENERAL)
                               ->where(function ($sub) use ($activeCompanyId) {
                                   $sub->where('company_id', $activeCompanyId)
                                       ->orWhereHas('branch', fn($b) => $b->where('company_id', $activeCompanyId));
                               });
                        })
                        ->orWhereHas('distributions', fn($dq) => $dq->whereHas('targetBranch', fn($b) => $b->where('company_id', $activeCompanyId)));
                    } else {
                        $userBranchIds = $user->allBranchIds();
                        $userCompanyIds = $user->allCompanyIds();
                        $q->where(function ($gq) use ($userBranchIds, $userCompanyIds) {
                            $gq->where('visibility', Document::VISIBILITY_GENERAL)
                               ->where(function ($uq) use ($userBranchIds, $userCompanyIds) {
                                   $uq->whereIn('branch_id', $userBranchIds)
                                      ->orWhereIn('company_id', $userCompanyIds);
                                });
                        })
                        ->orWhereHas('distributions', fn($dq) => $dq->whereIn('target_branch_id', $userBranchIds));
                    }
                });
            }

            $query->whereHas('versions', fn($q) => $q->where('status', 'active'));
            
            $user->unreadNotifications()
                ->where('data->type', 'document_cross_branch_received')
                ->update(['read_at' => now()]);
        } elseif ($type === 'mine') {
            $query->ownedBy($user);
        } elseif ($type === 'shared') {
            $unitKerjaIds = $user->allUnitKerjaIds();
            $query->where('owner_id', '!=', $user->id)
                  ->where(function ($q) use ($user, $unitKerjaIds) {
                      $q->whereHas('shares', fn($sq) => $sq->where('user_id', $user->id));
                      if (!empty($unitKerjaIds)) {
                          $q->orWhereHas('unitKerjaShares', fn($dq) => $dq->whereIn('unit_kerja_id', $unitKerjaIds));
                      }
                  })
                  ->with([
                      'shares' => fn($q) => $q->where('user_id', $user->id),
                      'unitKerjaShares' => fn($q) => !empty($unitKerjaIds) ? $q->whereIn('unit_kerja_id', $unitKerjaIds) : $q,
                  ]);

            $user->unreadNotifications()
                ->where('data->type', 'document_shared')
                ->update(['read_at' => now()]);
        } else {
            // Work Unit tab
            $query->unitKerja($user);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        if ($unitKerjaId = $request->get('unit_kerja_id')) {
            $query->where('unit_kerja_id', $unitKerjaId);
        }

        if ($documentTypeId = $request->get('document_type_id')) {
            $query->where('document_type_id', $documentTypeId);
        }

        if ($year = $request->get('year')) {
            $query->whereYear('created_at', (int) $year);
        }

        if ($formatChoice = $request->get('format_choice')) {
            if (in_array($formatChoice, ['baru', 'lama'], true)) {
                $query->where('format_choice', $formatChoice);
            }
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
            $query->where('is_expired', false);
        }

        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $documents = $query->latest()->paginate($perPage)->withQueryString();
        $documentTypes = DocumentType::orderBy('category')->orderBy('name')->get();
        $unitKerjas = $contextService->getAvailableUnitKerjas($user, $activeBranchId);

        $view = match ($type) {
            'mine' => 'documents.mine',
            'unit_kerja', 'division' => 'documents.unit_kerja',
            'shared' => 'documents.shared_index',
            default => 'documents.general',
        };

        $showDocuments = true;

        return view($view, compact('documents', 'documentTypes', 'unitKerjas', 'type', 'showDocuments', 'virtualFolders', 'breadcrumbs', 'folder'));
    }

    public function choose(): View
    {
        $templates = DocumentTemplate::active()->with('documentType')->orderBy('title')->get();
        $documentTypes = DocumentType::orderBy('category')->orderBy('name')->get();
        
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
        $contextService = app(CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeBranch = $activeBranchId ? Branch::with('company')->find($activeBranchId) : null;
        $availableBranches = $contextService->getAvailableBranches($user);

        $documentTypes = DocumentType::orderBy('category')->orderBy('name')->get();

        $activeUnitKerjaId = $contextService->getActiveUnitKerjaId($user);
        $activeUnitKerja = $contextService->getActiveUnitKerja($user);

        $companies = ($user->isAdmin() || $user->isDirector())
            ? ($user->isAdmin()
                ? Company::with('branches')->orderBy('name')->get()
                : $user->companies()->with('branches')->orderBy('name')->get())
            : collect();

        if ($user->isDirector() && $companies->isEmpty()) {
            $companies = Company::with('branches')->orderBy('name')->get();
        }

        $unitKerjas = ($user->isAdmin() || $user->isDirector())
            ? UnitKerja::orderBy('kode_unit_kerja')->get()
            : $contextService->getAvailableUnitKerjas($user, $activeBranchId);
        $userUnitKerjaId = $activeUnitKerjaId ?? $user->unit_kerja_id;

        $selectedTemplate = null;
        $initialDocumentNumber = null;
        if ($templateId = $request->query('template_id')) {
            $selectedTemplate = DocumentTemplate::active()->with('documentType')->find($templateId);
            if ($selectedTemplate && $selectedTemplate->documentType) {
                $userUnitKerja = $activeUnitKerja ?? $unitKerjas->first();
                $initialDocumentNumber = $this->documentService->previewNumber(
                    $selectedTemplate->documentType,
                    $activeBranch,
                    $userUnitKerja
                );
            }
        }

        return view('documents.create', compact(
            'unitKerjas', 'documentTypes', 'activeBranch', 'availableBranches', 'companies', 'selectedTemplate', 'initialDocumentNumber', 'activeUnitKerja', 'activeUnitKerjaId', 'userUnitKerjaId'
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
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
        ]);

        $user = auth()->user();
        $contextService = app(CompanyContextService::class);
        $branchId = $validated['branch_id'] ?? $contextService->getActiveBranchId($user);
        $branch = $branchId ? Branch::with('company')->find($branchId) : null;

        $unitKerjaId = $validated['unit_kerja_id'] ?? $contextService->getActiveUnitKerjaId($user) ?? $user->unit_kerja_id;
        $unitKerja = $unitKerjaId ? UnitKerja::find($unitKerjaId) : null;

        $documentType = DocumentType::findOrFail($validated['document_type_id']);

        return response()->json([
            'number' => $this->documentService->previewNumber($documentType, $branch, $unitKerja),
        ]);
    }

    /**
     * Check if a document number is already used in the database.
     */
    public function checkNumber(Request $request): JsonResponse
    {
        $number = trim((string) $request->input('document_number'));

        if (empty($number) || $number === __('Pilih tipe dokumen dahulu...')) {
            return response()->json([
                'checked' => false,
                'message' => 'Nomor dokumen kosong.',
            ]);
        }

        $firstSegment = explode('/', $number)[0] ?? '';
        if (ctype_digit($firstSegment) && strlen($firstSegment) < 3) {
            return response()->json([
                'checked' => false,
                'message' => 'Nomor urut dokumen belum lengkap (minimal 3 digit).',
            ]);
        }

        $existingDoc = Document::withTrashed()
            ->where('document_number', $number)
            ->with(['owner', 'documentType'])
            ->first();

        if ($existingDoc) {
            $deletedNotice = $existingDoc->trashed() ? ' (' . __('di Tempat Sampah') . ')' : '';
            return response()->json([
                'checked' => true,
                'exists' => true,
                'message' => __('Nomor dokumen ":number" SUDAH DIGUNAKAN:deleted pada dokumen ":title" (:owner).', [
                    'number' => $number,
                    'deleted' => $deletedNotice,
                    'title' => $existingDoc->title,
                    'owner' => $existingDoc->owner?->name ?? 'User',
                ]),
                'document' => [
                    'id' => $existingDoc->id,
                    'title' => $existingDoc->title,
                    'document_number' => $existingDoc->document_number,
                    'is_trashed' => $existingDoc->trashed(),
                    'owner' => $existingDoc->owner?->name,
                    'created_at' => $existingDoc->created_at?->format('d/m/Y'),
                ],
            ]);
        }

        return response()->json([
            'checked' => true,
            'exists' => false,
            'message' => __('Nomor dokumen ":number" BELUM DIGUNAKAN pada dokumen manapun (Tersedia).', [
                'number' => $number,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $user = auth()->user();
        $isUpload = $request->boolean('is_upload');
        $isManualNumber = $request->boolean('is_manual_number') || ($isUpload && $request->filled('document_number'));
        $templateId = $request->input('template_id');

        $contextService = app(CompanyContextService::class);
        $branchIds = (array) $request->input('branch_ids', []);
        $branchId = $request->input('branch_id')
            ?? (!empty($branchIds) ? $branchIds[0] : null)
            ?? $contextService->getActiveBranchId($user);

        $targetBranch = $branchId ? Branch::find($branchId) : null;
        $documentType = DocumentType::findOrFail($request->input('document_type_id'));

        $rules = [
            'title' => 'required|string|max:255',
            'document_type_id' => 'required|exists:document_types,id',
            'branch_id' => 'nullable|exists:branches,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'company_ids' => 'nullable|array',
            'expiration_date' => 'nullable|date',
            'template_id' => 'nullable|exists:document_templates,id',
            'corporate_soft_file_id' => 'nullable|exists:corporate_soft_files,id',
        ];

        // Dokumen Akreditasi requires unit_kerja_id
        if ($documentType->isAkreditasi()) {
            $rules['unit_kerja_id'] = 'required|exists:unit_kerjas,id';
        } else {
            $rules['unit_kerja_id'] = 'nullable|exists:unit_kerjas,id';
        }

        if ($isUpload) {
            $rules['file'] = 'required|file|mimes:pdf,docx|max:10240';
            $rules['document_number'] = 'required|string|max:100|unique:documents,document_number';
        } elseif ($isManualNumber && $request->filled('document_number')) {
            $rules['document_number'] = 'required|string|max:100|unique:documents,document_number';
        }

        $validated = $request->validate($rules, [
            'document_number.unique' => __('Nomor dokumen sudah digunakan.'),
            'document_number.required' => __('Nomor dokumen wajib diisi saat mode manual/unggah.'),
            'unit_kerja_id.required' => __('Unit kerja wajib dipilih untuk Dokumen Akreditasi.'),
        ]);

        if (empty($validated['unit_kerja_id'])) {
            $activeUkId = $contextService->getActiveUnitKerjaId($user);
            $validated['unit_kerja_id'] = $activeUkId ?? $user->unit_kerja_id ?? ($user->allUnitKerjaIds()[0] ?? null);
        }

        $validated['visibility'] = Document::VISIBILITY_UNIT_KERJA;
        $validated['expiration_date'] = $validated['expiration_date'] ?? null;

        if ($branchId) {
            $validated['branch_id'] = $branchId;
            $validated['company_id'] = $targetBranch?->company_id;
        }

        if (($user->isAdmin() || $user->isDirector()) && empty($validated['branch_id'])) {
            return back()->withInput()->withErrors(['branch_ids' => __('Harap pilih minimal satu cabang.')]);
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

        if (!empty($branchIds) && count($branchIds) > 1 && !empty($validated['branch_id'])) {
            $sourceBranchId = $validated['branch_id'];
            foreach ($branchIds as $targetBranchId) {
                if ((int) $targetBranchId !== (int) $sourceBranchId) {
                    \App\Models\DocumentDistribution::firstOrCreate([
                        'document_id' => $doc->id,
                        'source_branch_id' => $sourceBranchId,
                        'target_branch_id' => $targetBranchId,
                    ], [
                        'status' => 'unread',
                        'sent_at' => now(),
                        'created_by' => $user->id,
                    ]);
                }
            }
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

        if ($document->owner_id && $currentUser && $currentUser->id !== $document->owner_id) {
            $hasGrantedAccess = DocumentShare::where('document_id', $document->id)
                ->where('user_id', $currentUser->id)
                ->exists();

            if (!$hasGrantedAccess && !empty($currentUser->allUnitKerjaIds())) {
                $hasGrantedAccess = \App\Models\DocumentUnitKerjaShare::where('document_id', $document->id)
                    ->whereIn('unit_kerja_id', $currentUser->allUnitKerjaIds())
                    ->exists();
            }

            if ($hasGrantedAccess) {
                $currentUser->unreadNotifications()
                    ->where('data->type', 'document_shared')
                    ->where('data->document_id', $document->id)
                    ->update(['read_at' => now()]);

                $throttleKey = 'notif_doc_opened_' . $document->id . '_' . $currentUser->id;
                if (Cache::add($throttleKey, true, now()->addMinutes(15))) {
                    $document->owner?->notify(new \App\Notifications\DocumentOpenedByGrantedUser($document, $currentUser->name));
                }
            }
        }

        $document->load('owner', 'unitKerja', 'documentType', 'currentVersion', 'corporateSoftFile', 'versions.author', 'shares.user', 'unitKerjaShares.unitKerja');

        $unitKerjas = auth()->user()->isAdmin()
            ? UnitKerja::orderBy('kode_unit_kerja')->get()
            : UnitKerja::whereIn('id', auth()->user()->allUnitKerjaIds())->get();

        $version = $document->displayVersion();
        $onlyOfficeConfig = null;
        if ($version) {
            $this->autoApplyApprovedSignatures($document, $version);
            $onlyOfficeConfig = $this->onlyOfficeService->generateEditorConfig(
                $document,
                $version,
                auth()->user(),
                'view'
            );
        }
        $companies = \App\Models\Company::with('branches')->get();

        $approvedSignatures = $this->getApprovedSignatures($document);

        $pendingVersion = $document->versions()->where('status', 'pending')->whereNull('discarded_at')->latest('id')->first();
        if ($pendingVersion) {
            $author = $document->owner ?? $currentUser;
            if ($pendingVersion->approvalSteps()->count() === 0) {
                $this->approvalRoutingService->compileWorkflowFromSignatures($document, $pendingVersion, $author);
            }
        }

        return view('documents.show', compact('document', 'unitKerjas', 'onlyOfficeConfig', 'version', 'approvedSignatures', 'companies'));
    }

    public function summarize(Request $request, Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $force = $request->boolean('force');
        $percentage = (int) $request->input('percentage', 30);
        $model = $request->input('model', 'auto');
        $locale = app()->getLocale();

        if ($document->isSummaryCompleted() && !$force) {
            return response()->json([
                'status' => Document::SUMMARY_COMPLETED,
                'summary' => $document->summary,
            ]);
        }

        if (!app()->environment('testing')) {
            $hasKey = match ($model) {
                'groq' => !empty(config('services.groq.key')),
                'deepseek' => !empty(config('services.deepseek.key')),
                'ollama' => !empty(config('services.ollama.url')),
                default => !empty(config('services.groq.key')) || !empty(config('services.deepseek.key')) || !empty(config('services.ollama.url')),
            };

            if (!$hasKey) {
                $msg = match ($model) {
                    'groq' => 'GROQ_API_KEY belum diisi di file .env. Silakan tambahkan GROQ_API_KEY (dari console.groq.com) untuk menggunakan Groq.',
                    'deepseek' => 'DEEPSEEK_API_KEY belum diisi di file .env. Silakan tambahkan DEEPSEEK_API_KEY untuk menggunakan DeepSeek.',
                    'ollama' => 'OLLAMA_URL belum diisi di file .env.',
                    default => 'Konfigurasi AI belum disetel di file .env. Silakan isi GROQ_API_KEY atau DEEPSEEK_API_KEY di file .env.',
                };

                $document->update([
                    'summary_status' => Document::SUMMARY_FAILED,
                    'summary_error' => $msg,
                ]);

                return response()->json([
                    'status' => Document::SUMMARY_FAILED,
                    'error' => $msg,
                ], 422);
            }
        }

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

    public function summaryStatus(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        if ($document->summary_status === Document::SUMMARY_PROCESSING && $document->summary_started_at) {
            if ($document->summary_started_at->diffInSeconds(now()) >= 120) {
                $document->update([
                    'summary_status' => Document::SUMMARY_FAILED,
                    'summary_error' => 'Proses ringkasan memakan waktu terlalu lama atau antrean belum dijalankan.',
                ]);
            }
        }

        return response()->json([
            'status' => $document->summary_status,
            'summary' => $document->summary,
            'error' => $document->summary_error,
        ]);
    }

    public function onlyofficeStatus(Document $document): JsonResponse
    {
        $this->authorize('view', $document);
        $version = $document->displayVersion();

        return response()->json([
            'active' => Cache::has('onlyoffice_active_' . $document->id),
            'updated_at' => $version?->updated_at?->timestamp,
        ]);
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        $document->load('currentVersion', 'versions', 'corporateSoftFile');

        $version = $document->displayVersion();

        if (!$version) {
            abort(404, 'Document version not found.');
        }

        $this->autoApplyApprovedSignatures($document, $version);

        $onlyOfficeConfig = $this->onlyOfficeService->generateEditorConfig(
            $document,
            $version,
            auth()->user(),
            'edit'
        );

        $currentUser = auth()->user();
        $userSignatureUrl = $this->onlyOfficeService->getSignatureFileUrl($currentUser);
        $userSignatureToken = $userSignatureUrl ? $this->onlyOfficeService->generateInsertImageToken($userSignatureUrl) : null;
        $userSignatureClientUrl = ($currentUser->hasSignature() && $currentUser->signature?->file_path)
            ? asset('storage/' . $currentUser->signature->file_path)
            : null;
        $userSignatureDataUri = null;
        if ($currentUser->hasSignature() && $currentUser->signature?->file_path && Storage::disk('public')->exists($currentUser->signature->file_path)) {
            $rawBytes = Storage::disk('public')->get($currentUser->signature->file_path);
            $trimmedBytes = $this->onlyOfficeService->trimSignatureImage($rawBytes);
            $userSignatureDataUri = 'data:image/png;base64,' . base64_encode($trimmedBytes);
        }

        $qrCodeUrl = $this->onlyOfficeService->getQrCodeFileUrl($document);
        $qrCodeToken = $this->onlyOfficeService->generateInsertImageToken($qrCodeUrl);
        $qrCodeDataUri = $this->qrCodeService->dataUri($this->qrCodeService->qrcodeUrl($document));

        $approvedSignatures = $this->getApprovedSignatures($document);

        $canAccessSoftFiles = $currentUser->canAccessCorporateSoftFiles();
        $corporateSoftFiles = collect();
        if ($canAccessSoftFiles) {
            $corporateSoftFiles = \App\Models\CorporateSoftFile::query()
                ->accessibleBy($currentUser, $document->company_id, $document->branch_id)
                ->latest()
                ->get();
        }

        return view('documents.edit', compact(
            'document',
            'version',
            'onlyOfficeConfig',
            'qrCodeUrl',
            'qrCodeToken',
            'qrCodeDataUri',
            'userSignatureUrl',
            'userSignatureToken',
            'userSignatureClientUrl',
            'userSignatureDataUri',
            'approvedSignatures',
            'canAccessSoftFiles',
            'corporateSoftFiles'
        ));
    }

    /**
     * Get accessible corporate soft files for a document in JSON format.
     */
    public function corporateSoftFiles(Document $document): JsonResponse
    {
        $this->authorize('update', $document);
        $user = auth()->user();

        if (!$user->canAccessCorporateSoftFiles()) {
            return response()->json(['corporate_soft_files' => []]);
        }

        $files = \App\Models\CorporateSoftFile::query()
            ->accessibleBy($user, $document->company_id, $document->branch_id)
            ->latest()
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'title' => $file->title,
                    'description' => $file->description,
                    'original_name' => $file->file_original_name,
                    'file_url' => $this->onlyOfficeService->getCorporateSoftFileUrl($file),
                ];
            });

        return response()->json(['corporate_soft_files' => $files]);
    }

    /**
     * Apply a corporate soft file to current document version.
     */
    public function applyCorporateSoftFile(Request $request, Document $document, \App\Models\CorporateSoftFile $corporateSoftFile): JsonResponse
    {
        $this->authorize('update', $document);
        $user = auth()->user();

        if (!$user->canAccessCorporateSoftFiles($corporateSoftFile)) {
            return response()->json(['error' => __('Anda tidak memiliki hak akses untuk soft file ini.')], 403);
        }

        $version = $document->displayVersion();
        if (!$version) {
            return response()->json(['error' => __('Versi dokumen tidak ditemukan.')], 404);
        }

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        if (!$disk->exists($corporateSoftFile->file_path)) {
            return response()->json(['error' => __('Berkas soft file korporat tidak ditemukan di storage.')], 404);
        }

        // Flush any active in-memory edits from ONLYOFFICE to server storage first
        $this->onlyOfficeService->forceSaveDocument($document, $version);
        usleep(300000);
        $version->refresh();

        // Apply corporate soft file (Header & Footer) to current version's DOCX while preserving existing content
        $existingDocx = ($version->file_path && $disk->exists($version->file_path))
            ? $disk->get($version->file_path)
            : '';

        if (empty($existingDocx)) {
            $existingDocx = app(\App\Services\DocumentService::class)->createBlankDocx($document->id, $version->version_number);
            $existingDocx = $disk->get($version->file_path);
        }

        $mergedDocx = $this->onlyOfficeService->applyCorporateSoftFileToDocx($existingDocx, $corporateSoftFile);
        $disk->put($version->file_path, $mergedDocx);
        $version->touch();

        // Update document reference to indicate which corporate soft file was applied
        $document->update([
            'corporate_soft_file_id' => $corporateSoftFile->id,
            'format_choice' => 'F4',
            'paper_size' => 'F4',
        ]);

        // Invalidate ONLYOFFICE cached keys so editor reloads new content
        $this->onlyOfficeService->rotateDocumentKey($document, $version);

        return response()->json([
            'success' => true,
            'message' => __('Soft File Korporat ":title" berhasil diterapkan ke dokumen.', ['title' => $corporateSoftFile->title]),
            'redirect_url' => route('documents.edit', $document),
        ]);
    }

    /**
     * Remove / cancel the corporate soft file applied to document.
     */
    public function removeCorporateSoftFile(Request $request, Document $document): JsonResponse
    {
        $this->authorize('update', $document);

        $document->update([
            'corporate_soft_file_id' => null,
            'format_choice' => 'A4',
            'paper_size' => 'A4',
        ]);

        $version = $document->displayVersion();
        if ($version) {
            // Flush any active in-memory edits from ONLYOFFICE to server storage first
            $this->onlyOfficeService->forceSaveDocument($document, $version);
            usleep(300000);
            $version->refresh();

            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            if ($version->file_path && $disk->exists($version->file_path)) {
                $rawDocx = $disk->get($version->file_path);
                $cleanedDocx = $this->onlyOfficeService->removeHeaderAndFooterFromDocx($rawDocx);
                $disk->put($version->file_path, $cleanedDocx);
            }
            $version->touch();
            $this->onlyOfficeService->rotateDocumentKey($document, $version);
        }

        return response()->json([
            'success' => true,
            'message' => __('Pilihan kop surat korporat berhasil dibatalkan dari dokumen.'),
            'redirect_url' => route('documents.edit', $document),
        ]);
    }

    public function preview(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load('owner', 'unitKerja', 'documentType', 'currentVersion', 'corporateSoftFile');

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
            'paper_margin' => 'nullable|string',
        ]);

        $margin = $this->decodePaperMargin($validated['paper_margin'] ?? null);

        $document->update([
            'paper_size' => $validated['paper_size'] ?? 'A4',
            'paper_margin' => $margin,
        ]);

        $user = auth()->user();
        $version = $this->versionService->savePending($document, $validated['content'], $user);

        $this->auditService->log($user, 'version.created', 'document_version', $version->id, [
            'document_id' => $document->id,
            'version_number' => $version->version_number,
        ]);

        $unnotifiedSigRequests = SignatureRequest::where('document_id', $document->id)
            ->where('status', 'pending')
            ->whereNull('notified_at')
            ->get();

        foreach ($unnotifiedSigRequests as $sigReq) {
            $sigReq->sendNotification();
        }

        $resolution = $this->approvalRoutingService->resolveApprover($document, $user);
        $this->approvalRoutingService->applyToDocument($document, $resolution);

        foreach ($resolution['approvers'] as $approver) {
            $approver->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $user->name));
        }

        if ($resolution['role'] !== null) {
            $user->notify(new \App\Notifications\ApprovalRouteResolved(
                $document,
                $resolution['role'],
                $resolution['approvers']->pluck('name')->join(', '),
                $resolution['message'],
                $resolution['isFallback'],
            ));
        }

        $message = $version->wasRecentlyCreated
            ? __('Perubahan disimpan. Menunggu persetujuan.')
            : __('Versi v:version diperbarui (tetap menunggu persetujuan).', ['version' => $version->version_number]);

        return redirect()->route('documents.show', $document)->with('success', $message);
    }

    public function discard(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $document);

        Cache::put('ignore_onlyoffice_save_' . $document->id, true, now()->addSeconds(30));

        SignatureRequest::where('document_id', $document->id)
            ->where('status', 'pending')
            ->whereNull('notified_at')
            ->delete();

        $isPendingV1 = (!$document->current_version_id && !$document->currentVersion)
            && ($document->versions()->where('status', 'pending')->where('version_number', 1)->exists()
                || $document->versions()->count() <= 1);

        $isLeaveGuard = $request->boolean('is_leave_guard');

        if ($isPendingV1 && !$isLeaveGuard) {
            $document->delete();

            $this->auditService->log(auth()->user(), 'document.trashed', 'document', $document->id, [
                'document_id' => $document->id,
                'reason' => 'pending_v1_discarded',
            ]);

            $message = __('Dokumen telah dipindahkan ke trash.');

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'discarded' => true,
                    'trashed' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('documents.index', ['type' => 'mine'])->with('success', $message);
        }

        $discarded = $this->versionService->discardPending($document);

        $this->onlyOfficeService->rotateDocumentKey($document);
        $document->currentVersion?->touch();
        $document->touch();

        if ($discarded) {
            $this->auditService->log(auth()->user(), 'version.discarded', 'document_version', $discarded->id, [
                'document_id' => $document->id,
                'version_number' => $discarded->version_number,
            ]);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'discarded' => (bool) $discarded,
                'message' => $discarded
                    ? __('Perubahan versi pending v:version dibuang.', ['version' => $discarded->version_number])
                    : __('Tidak ada perubahan untuk dibuang.')
            ]);
        }

        return redirect()->route('documents.show', $document)->with('success', $discarded
            ? __('Perubahan pada dokumen berhasil dibuang.')
            : __('Tidak ada perubahan untuk dibuang.'));
    }

    public function finishEditing(Request $request, Document $document): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $document);

        $user = auth()->user();
        $version = $document->displayVersion();

        $unnotifiedSigRequests = SignatureRequest::where('document_id', $document->id)
            ->where('status', 'pending')
            ->whereNull('notified_at')
            ->get();

        foreach ($unnotifiedSigRequests as $sigReq) {
            $sigReq->sendNotification();
        }

        $routingMessage = null;
        if ($version) {
            if ($version->status === 'draft') {
                $version->update(['status' => 'pending']);
            }

            if ($version->status === 'pending') {
                $notifKey = 'approval_notified_' . $document->id . '_v' . $version->id;

                Cache::forget('onlyoffice_pending_notif_' . $document->id);

                if (!Cache::has($notifKey)) {
                    Cache::put($notifKey, true, now()->addMinutes(10));

                    $resolution = $this->approvalRoutingService->resolveApprover($document, $user);
                    $this->approvalRoutingService->applyToDocument($document, $resolution);

                    foreach ($resolution['approvers'] as $approver) {
                        $approver->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $user->name));
                    }

                    if ($resolution['role'] !== null) {
                        $user->notify(new \App\Notifications\ApprovalRouteResolved(
                            $document,
                            $resolution['role'],
                            $resolution['approvers']->pluck('name')->join(', '),
                            $resolution['message'],
                            $resolution['isFallback'],
                        ));
                        $routingMessage = $resolution['message'];
                    }
                }
            }
        }

        $successMessage = $routingMessage ?? __('Perubahan disimpan. Menunggu persetujuan.');

        session()->flash('success', $successMessage);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'redirect_url' => route('documents.show', $document),
            ]);
        }

        return redirect()->route('documents.show', $document)->with('success', $successMessage);
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

        $document->update(array_filter([
            'paper_size' => $validated['paper_size'] ?? null,
            'paper_margin' => $margin,
        ], fn($v) => $v !== null));

        $this->versionService->saveDraft($document, $validated['content'], auth()->user());

        return redirect()->route('documents.show', $document)->with('success', __('Draf berhasil disimpan.'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('success', __('Dokumen telah dipindahkan ke trash.'));
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

        $resolution = $this->approvalRoutingService->resolveApprover($document, $user);
        $this->approvalRoutingService->applyToDocument($document, $resolution);

        foreach ($resolution['approvers'] as $approver) {
            $approver->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $user->name));
        }

        if ($resolution['role'] !== null) {
            $user->notify(new \App\Notifications\ApprovalRouteResolved(
                $document,
                $resolution['role'],
                $resolution['approvers']->pluck('name')->join(', '),
                $resolution['message'],
                $resolution['isFallback'],
            ));
        }

        return redirect()->route('documents.show', $document)->with('success', __('Versi baru diunggah. Menunggu persetujuan.'));
    }

    public function download(Request $request, Document $document)
    {
        $this->authorize('view', $document);

        $version = $request->filled('version_id')
            ? $document->versions()->where('id', $request->input('version_id'))->first()
            : $document->displayVersion();

        abort_unless($version && $version->file_path, 404, 'File not found');

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        abort_unless($disk->exists($version->file_path), 404, 'Physical file not found');

        $downloadName = $version->file_original_name ?? $document->title;
        if (!str_ends_with(strtolower($downloadName), '.docx') && !str_ends_with(strtolower($downloadName), '.pdf')) {
            $downloadName .= '.docx';
        }

        return $disk->download($version->file_path, $downloadName);
    }

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

    public function qrCode(Document $document)
    {
        $this->authorize('view', $document);

        $png = $this->qrCodeService->pngBytes($this->qrCodeService->qrcodeUrl($document));

        return response($png, 200, ['Content-Type' => 'image/png']);
    }

    public function viewByHash(string $token)
    {
        try {
            $id = Crypt::decryptString(base64_decode(strtr($token, '-_', '+/')));
        } catch (\Throwable) {
            abort(404);
        }

        $document = Document::with(['owner', 'unitKerja', 'documentType', 'currentVersion'])->findOrFail($id);

        return view('documents.verified', compact('document', 'token'));
    }

    public function previewByHash(string $token)
    {
        try {
            $id = Crypt::decryptString(base64_decode(strtr($token, '-_', '+/')));
        } catch (\Throwable) {
            abort(404);
        }

        $document = Document::with(['owner', 'unitKerja', 'documentType', 'currentVersion'])->findOrFail($id);

        if (!auth()->check()) {
            return redirect()->guest(route('login'));
        }

        if (!auth()->user()->can('view', $document)) {
            abort(403, __('Anda tidak memiliki izin untuk melihat dokumen ini.'));
        }

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

    public function updateVisibility(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('manageScope', $document);

        $validated = $request->validate([
            'visibility' => 'required|in:general,unit_kerja,division,personal',
            'target_branch_ids' => 'nullable|array',
            'target_branch_ids.*' => 'exists:branches,id'
        ]);

        $vis = $validated['visibility'] === 'division' ? Document::VISIBILITY_UNIT_KERJA : $validated['visibility'];

        $unitKerjaId = $document->unit_kerja_id
            ?? app(CompanyContextService::class)->getActiveUnitKerjaId(auth()->user())
            ?? auth()->user()->unit_kerja_id
            ?? (auth()->user()->allUnitKerjaIds()[0] ?? null);

        $document->update([
            'visibility' => $vis,
            'unit_kerja_id' => $unitKerjaId,
            'is_public' => $vis === Document::VISIBILITY_GENERAL,
        ]);
        
        if ($vis === Document::VISIBILITY_GENERAL) {
            $targetBranchIds = $request->input('target_branch_ids', []);
            \App\Models\DocumentDistribution::where('document_id', $document->id)->delete();
            
            if (is_array($targetBranchIds) && count($targetBranchIds) > 0) {
                $distributions = [];
                $targetBranches = Branch::whereIn('id', $targetBranchIds)->get()->keyBy('id');
                
                $sourceBranchId = $document->branch_id ?? (auth()->user()->allBranchIds()[0] ?? null);
                foreach ($targetBranchIds as $targetBranchId) {
                    $distributions[] = [
                        'document_id' => $document->id,
                        'source_branch_id' => $sourceBranchId,
                        'target_branch_id' => $targetBranchId,
                        'sent_at' => now(),
                        'status' => 'unread',
                        'created_by' => auth()->id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    if (isset($targetBranches[$targetBranchId])) {
                        $branchUsers = User::whereHas('branches', fn($q) => $q->where('branches.id', $targetBranchId))
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
            \App\Models\DocumentDistribution::where('document_id', $document->id)->delete();
        }

        $this->auditService->log(auth()->user(), 'document.visibility_changed', 'document', $document->id, [
            'visibility' => $vis,
            'unit_kerja_id' => $document->unit_kerja_id,
        ]);

        return back()->with('success', __('Visibilitas dokumen berhasil diperbarui.'));
    }

    private function decodePaperMargin(?string $raw): ?array
    {
        if (!$raw || trim($raw) === '' || trim($raw) === 'null') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

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

    public function rename(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('rename', $document);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $oldTitle = $document->title;
        $newTitle = trim($validated['title']);

        if ($oldTitle === $newTitle) {
            return back()->with('info', __('Nama dokumen tidak berubah.'));
        }

        $user = auth()->user();
        $version = $this->versionService->renameDocument($document, $newTitle, $user);

        $this->auditService->log($user, 'document.renamed', 'document', $document->id, [
            'old_title' => $oldTitle,
            'new_title' => $newTitle,
            'version_number' => $version?->version_number,
        ]);

        if ($version && $version->status === 'pending') {
            if ($version->wasRecentlyCreated) {
                $resolution = $this->approvalRoutingService->resolveApprover($document, $user);
                $this->approvalRoutingService->applyToDocument($document, $resolution);

                foreach ($resolution['approvers'] as $approver) {
                    $approver->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $user->name));
                }

                if ($resolution['role'] !== null) {
                    $user->notify(new \App\Notifications\ApprovalRouteResolved(
                        $document,
                        $resolution['role'],
                        $resolution['approvers']->pluck('name')->join(', '),
                        $resolution['message'],
                        $resolution['isFallback'],
                    ));
                }

                return back()->with('success', __('Nama dokumen diperbarui dan versi v:version diajukan untuk persetujuan.', [
                    'version' => $version->version_number,
                ]));
            } else {
                $authorName = $version->author_name ?? $user->name;
                $origTitle = $version->old_title ?? $oldTitle;
                $notifTitle = $version->isRename()
                    ? __('Permintaan Persetujuan Perubahan Nama Dokumen')
                    : __('Permintaan Persetujuan Dokumen');

                $notifMessage = $version->isRename()
                    ? __(':author mengajukan perubahan nama dokumen dari ":old" menjadi ":doc" (v:ver)', [
                        'author' => $authorName,
                        'old' => $origTitle,
                        'doc' => $newTitle,
                        'ver' => $version->version_number,
                    ])
                    : __(':author mengajukan persetujuan untuk dokumen ":doc" (v:ver)', [
                        'author' => $authorName,
                        'doc' => $newTitle,
                        'ver' => $version->version_number,
                    ]);

                $existingNotifs = DB::table('notifications')
                    ->where(function ($q) use ($document, $version) {
                        $q->where('data->document_id', $document->id)
                          ->orWhere('data->version_id', $version->id);
                    })
                    ->where('data->type', 'approval_request')
                    ->get();

                foreach ($existingNotifs as $row) {
                    $payload = json_decode($row->data, true) ?: [];
                    $payload['title'] = $notifTitle;
                    $payload['message'] = $notifMessage;
                    $payload['document_title'] = $newTitle;
                    if ($version->isRename()) {
                        $payload['old_title'] = $origTitle;
                        $payload['is_rename'] = true;
                    }
                    DB::table('notifications')
                        ->where('id', $row->id)
                        ->update(['data' => json_encode($payload)]);
                }
            }
        }

        return back()->with('success', __('Nama dokumen berhasil diubah.'));
    }

    public function requestRename(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('requestRename', $document);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $newTitle = trim($validated['title']);

        if ($newTitle === $document->title) {
            return back()->with('error', __('Nama baru tidak boleh sama dengan nama dokumen saat ini.'));
        }

        if ($document->hasPendingRename()) {
            return back()->with('error', __('Sudah ada permintaan perubahan nama yang menunggu persetujuan.'));
        }

        $user = auth()->user();

        $document->update([
            'pending_title' => $newTitle,
            'rename_requested_by_id' => $user->id,
            'rename_requested_at' => now(),
            'rename_request_notes' => $validated['notes'] ?? null,
        ]);

        $this->auditService->log($user, 'document.rename_requested', 'document', $document->id, [
            'current_title' => $document->title,
            'requested_title' => $newTitle,
            'notes' => $validated['notes'] ?? null,
        ]);

        $heads = collect();
        if ($document->unit_kerja_id) {
            $heads = User::where(function ($q) use ($document) {
                    $q->where('unit_kerja_id', $document->unit_kerja_id)
                      ->orWhereHas('unitKerjas', fn($uq) => $uq->where('unit_kerjas.id', $document->unit_kerja_id));
                })
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
                $head->notify(new \App\Notifications\DocumentRenameRequested($document, $newTitle, $user->name, $validated['notes'] ?? null));
            }
        }

        if ($heads->isEmpty()) {
            $approvers = User::whereIn('system_role', ['admin', 'direktur'])
                ->where('id', '!=', $user->id)
                ->get();
            foreach ($approvers as $approver) {
                $approver->notify(new \App\Notifications\DocumentRenameRequested($document, $newTitle, $user->name, $validated['notes'] ?? null));
            }
        }

        return back()->with('success', __('Permintaan perubahan nama diajukan. Menunggu persetujuan.'));
    }

    public function cancelRenameRequest(Document $document): RedirectResponse
    {
        $user = auth()->user();

        if (!$document->hasPendingRename()) {
            return back()->with('error', __('Tidak ada permintaan perubahan nama yang menunggu.'));
        }

        $isRequester = $document->rename_requested_by_id === $user->id;
        $isOwner = $document->owner_id === $user->id;
        if (!$isRequester && !$isOwner && !$user->isAdmin() && !$user->isDirector()) {
            abort(403, __('Anda tidak berhak membatalkan permintaan ini.'));
        }

        $document->update([
            'pending_title' => null,
            'rename_requested_by_id' => null,
            'rename_requested_at' => null,
            'rename_request_notes' => null,
        ]);

        $this->auditService->log($user, 'document.rename_cancelled', 'document', $document->id, [
            'title' => $document->title,
        ]);

        return back()->with('success', __('Permintaan perubahan nama dokumen berhasil dibatalkan.'));
    }
}