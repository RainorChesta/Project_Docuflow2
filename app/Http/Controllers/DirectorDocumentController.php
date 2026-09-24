<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class DirectorDocumentController extends Controller
{
    /**
     * Display Google Drive-style folder navigation of Companies, Branches, Unit Kerja, and Documents for Director.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        if (!$user->isDirector() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $search = $request->get('search');
        $selectedCompanyId = $request->get('company_id');
        $selectedBranchId = $request->get('branch_id');
        $selectedUnitKerjaId = $request->get('unit_kerja_id') ?? $request->get('division_id');
        $selectedDocTypeId = $request->get('document_type_id');
        $selectedOwnerId = $request->get('owner_id');
        $selectedFormatChoice = $request->get('format_choice');
        $viewMode = $request->get('view_mode', 'grid');

        // Verify company access if specified
        $currentCompany = null;
        if ($selectedCompanyId) {
            $compQuery = Company::where('id', $selectedCompanyId);
            if (!$user->isAdmin() && !$user->isDirector()) {
                $compQuery->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
            $currentCompany = $compQuery->first();
            if (!$currentCompany) {
                $selectedCompanyId = null;
                $selectedBranchId = null;
                $selectedUnitKerjaId = null;
            }
        }

        // Verify branch access if specified
        $currentBranch = null;
        if ($selectedBranchId && $selectedCompanyId) {
            $branchQuery = Branch::where('id', $selectedBranchId)->where('company_id', $selectedCompanyId);
            if (!$user->isAdmin() && !$user->isDirector()) {
                $branchQuery->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
            $currentBranch = $branchQuery->first();
            if (!$currentBranch) {
                $selectedBranchId = null;
                $selectedUnitKerjaId = null;
            }
        }

        // Verify unit kerja if specified
        $currentUnitKerja = null;
        if ($selectedUnitKerjaId) {
            $currentUnitKerja = UnitKerja::find($selectedUnitKerjaId);
        }

        // Build Breadcrumbs trail
        $breadcrumbs = [
            [
                'name' => __('Semua Perusahaan'),
                'url' => route('director.documents.index', array_filter(['view_mode' => $viewMode])),
                'icon' => 'home',
                'active' => !$selectedCompanyId,
            ]
        ];

        if ($currentCompany) {
            $breadcrumbs[] = [
                'name' => $currentCompany->name,
                'url' => route('director.documents.index', array_filter([
                    'company_id' => $currentCompany->id,
                    'view_mode' => $viewMode,
                ])),
                'icon' => 'company',
                'active' => $selectedCompanyId && !$selectedBranchId,
            ];
        }

        if ($currentBranch) {
            $breadcrumbs[] = [
                'name' => $currentBranch->name . ($currentBranch->is_pusat ? ' (Pusat)' : ''),
                'url' => route('director.documents.index', array_filter([
                    'company_id' => $currentCompany->id,
                    'branch_id' => $currentBranch->id,
                    'view_mode' => $viewMode,
                ])),
                'icon' => 'branch',
                'active' => $selectedBranchId && !$selectedUnitKerjaId,
            ];
        }

        if ($currentUnitKerja) {
            $breadcrumbs[] = [
                'name' => $currentUnitKerja->name,
                'url' => route('director.documents.index', array_filter([
                    'company_id' => $currentCompany?->id,
                    'branch_id' => $currentBranch?->id,
                    'unit_kerja_id' => $currentUnitKerja->id,
                    'view_mode' => $viewMode,
                ])),
                'icon' => 'unit_kerja',
                'active' => true,
            ];
        }

        // Parent URL for "Up one level"
        $parentUrl = null;
        if ($selectedUnitKerjaId) {
            $parentUrl = route('director.documents.index', array_filter([
                'company_id' => $selectedCompanyId,
                'branch_id' => $selectedBranchId,
                'view_mode' => $viewMode,
            ]));
        } elseif ($selectedBranchId) {
            $parentUrl = route('director.documents.index', array_filter([
                'company_id' => $selectedCompanyId,
                'view_mode' => $viewMode,
            ]));
        } elseif ($selectedCompanyId) {
            $parentUrl = route('director.documents.index', array_filter([
                'view_mode' => $viewMode,
            ]));
        }

        // Fetch available filter options
        $availableCompanies = Company::query();
        if (!$user->isAdmin()) {
            $availableCompanies->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
        }
        $availableCompanies = $availableCompanies->orderBy('name')->get(['id', 'name', 'code']);

        $availableBranches = collect();
        if ($selectedCompanyId) {
            $brQuery = Branch::where('company_id', $selectedCompanyId);
            if (!$user->isAdmin() && !$user->isDirector()) {
                $brQuery->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
            }
            $availableBranches = $brQuery->orderByDesc('is_pusat')->orderBy('name')->get(['id', 'name', 'code', 'company_id']);
        }

        $availableUnitKerjas = UnitKerja::orderBy('kode_unit_kerja')->get();
        $availableDocumentTypes = DocumentType::orderBy('name')->get(['id', 'name', 'code', 'category']);
        $availableCreators = User::whereHas('documents', function ($q) use ($user, $selectedCompanyId, $selectedBranchId) {
            if ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            } elseif ($selectedCompanyId) {
                $q->where('company_id', $selectedCompanyId);
            }
        })->orderBy('name')->get(['id', 'name']);

        $selectedStatus = $request->get('status');

        $hasSearchOrFilter = ($search !== null && trim($search) !== '') 
            || ($selectedDocTypeId !== null && $selectedDocTypeId !== '') 
            || ($selectedOwnerId !== null && $selectedOwnerId !== '')
            || ($selectedFormatChoice !== null && $selectedFormatChoice !== '')
            || ($selectedStatus !== null && $selectedStatus !== '');

        // Sub-Folders collection for current level
        $folders = collect();
        $documents = collect();

        // When NO search/filter is active: Browse Google Drive folder structure
        if (!$hasSearchOrFilter && !$selectedUnitKerjaId) {
            // Level 0: Root -> Show Company folders
            if (!$selectedCompanyId) {
                $companiesQuery = Company::query();
                if (!$user->isAdmin()) {
                    $companiesQuery->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
                }
                $companies = $companiesQuery->withCount(['branches' => function ($bq) use ($user) {
                    if (!$user->isAdmin()) {
                        $bq->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
                    }
                }])->withCount(['documents'])->orderBy('name')->get();

                $folders = $companies->map(function ($comp) use ($viewMode) {
                    return [
                        'id' => $comp->id,
                        'type' => 'company',
                        'name' => $comp->name,
                        'code' => $comp->code,
                        'sub_count' => $comp->branches_count,
                        'sub_label' => 'Cabang',
                        'doc_count' => $comp->documents_count,
                        'url' => route('director.documents.index', array_filter([
                            'company_id' => $comp->id,
                            'view_mode' => $viewMode,
                        ])),
                    ];
                });
            }
            // Level 1: Inside Company -> Show Branch folders
            elseif ($selectedCompanyId && !$selectedBranchId) {
                $branchesQuery = Branch::where('company_id', $selectedCompanyId);
                if (!$user->isAdmin() && !$user->isDirector()) {
                    $branchesQuery->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
                }
                $branches = $branchesQuery->withCount(['documents'])->orderByDesc('is_pusat')->orderBy('name')->get();

                $folders = $branches->map(function ($br) use ($selectedCompanyId, $viewMode) {
                    return [
                        'id' => $br->id,
                        'type' => 'branch',
                        'name' => $br->name,
                        'code' => $br->effective_code,
                        'is_pusat' => (bool) $br->is_pusat,
                        'doc_count' => $br->documents_count,
                        'url' => route('director.documents.index', array_filter([
                            'company_id' => $selectedCompanyId,
                            'branch_id' => $br->id,
                            'view_mode' => $viewMode,
                        ])),
                    ];
                });
            }
            // Level 2: Inside Branch -> Show Unit Kerja folders
            elseif ($selectedBranchId && !$selectedUnitKerjaId) {
                $branchDocUnits = Document::where('branch_id', $selectedBranchId)
                    ->whereNotNull('unit_kerja_id')
                    ->select('unit_kerja_id')
                    ->selectRaw('count(*) as count')
                    ->groupBy('unit_kerja_id')
                    ->pluck('count', 'unit_kerja_id');

                $allUnitKerjas = UnitKerja::orderBy('code')->get();
                
                $folders = $allUnitKerjas->map(function ($uk) use ($branchDocUnits, $selectedCompanyId, $selectedBranchId, $viewMode) {
                    $count = $branchDocUnits[$uk->id] ?? 0;
                    return [
                        'id' => $uk->id,
                        'type' => 'unit_kerja',
                        'name' => $uk->name,
                        'code' => strtoupper($uk->code),
                        'doc_count' => $count,
                        'url' => route('director.documents.index', array_filter([
                            'company_id' => $selectedCompanyId,
                            'branch_id' => $selectedBranchId,
                            'unit_kerja_id' => $uk->id,
                            'view_mode' => $viewMode,
                        ])),
                    ];
                });
            }
        }

        // When searching, filtering, OR inside a Unit Kerja -> Query Documents
        if ($hasSearchOrFilter || $selectedUnitKerjaId) {
            $docQuery = Document::visibleTo($user)
                ->with(['owner', 'unitKerja', 'documentType', 'currentVersion', 'versions', 'branch.company', 'company']);

            if ($selectedCompanyId) {
                $docQuery->where(function ($q) use ($selectedCompanyId) {
                    $q->where('company_id', $selectedCompanyId)
                      ->orWhereHas('branch', fn($b) => $b->where('company_id', $selectedCompanyId));
                });
            }

            if ($selectedBranchId) {
                $docQuery->where('branch_id', $selectedBranchId);
            }

            if ($selectedUnitKerjaId) {
                $docQuery->where('unit_kerja_id', $selectedUnitKerjaId);
            }

            // Keyword Search (Title, Document Number, Pending Title)
            if (!empty($search)) {
                $docQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%")
                      ->orWhere('pending_title', 'like', "%{$search}%");
                });
            }

            // Document Type Filter
            if ($selectedDocTypeId) {
                $docQuery->where('document_type_id', $selectedDocTypeId);
            }

            // Creator Filter
            if ($selectedOwnerId) {
                $docQuery->where('owner_id', $selectedOwnerId);
            }

            // Format Choice Filter
            if ($selectedFormatChoice && in_array($selectedFormatChoice, ['baru', 'lama'], true)) {
                $docQuery->where('format_choice', $selectedFormatChoice);
            }

            // Status Filter
            if ($selectedStatus) {
                if ($selectedStatus === 'active') {
                    $docQuery->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
                             ->where('is_expired', false);
                } elseif ($selectedStatus === 'pending') {
                    $docQuery->whereHas('versions', fn($q) => $q->where('status', 'pending'));
                } elseif ($selectedStatus === 'expired') {
                    $docQuery->where('is_expired', true);
                }
            }

            // Tembusan Filter
            $tab = $request->get('tab', 'all');
            if ($tab === 'tembusan') {
                $docQuery->whereNotNull('director_notified_at');
            }

            $documents = $docQuery->latest()->paginate(16)->withQueryString();
        }

        $tab = $request->get('tab', 'all');
        $unreadTembusanCount = Document::whereNotNull('director_notified_at')
            ->whereNull('director_read_at')
            ->count();

        return view('director.documents.index', compact(
            'breadcrumbs',
            'parentUrl',
            'folders',
            'documents',
            'hasSearchOrFilter',
            'currentCompany',
            'currentBranch',
            'currentUnitKerja',
            'selectedCompanyId',
            'selectedBranchId',
            'selectedUnitKerjaId',
            'selectedDocTypeId',
            'selectedOwnerId',
            'selectedFormatChoice',
            'selectedStatus',
            'search',
            'viewMode',
            'tab',
            'unreadTembusanCount',
            'availableCompanies',
            'availableBranches',
            'availableUnitKerjas',
            'availableDocumentTypes',
            'availableCreators'
        ));
    }

    /**
     * Display all active and approved documents across all companies and branches for Director review.
     */
    public function activeDocuments(Request $request): View
    {
        $user = auth()->user();
        if (!$user->isDirector() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $tab = $request->get('tab', 'all'); // 'all', 'unseen', 'seen'
        $search = $request->get('search');
        $selectedCompanyId = $request->get('company_id');
        $selectedBranchId = $request->get('branch_id');
        $selectedUnitKerjaId = $request->get('unit_kerja_id');
        $selectedDocTypeId = $request->get('document_type_id');
        $selectedOwnerId = $request->get('owner_id');
        $selectedFormatChoice = $request->get('format_choice');
        $sort = $request->get('sort', 'latest');
        $viewMode = $request->get('view_mode', 'list');

        // Base query for active documents across ALL companies and branches
        $baseActiveQuery = Document::query()
            ->whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
            ->where('is_expired', false);

        // Compute tab counts (global or scoped to active)
        $totalActiveCount = (clone $baseActiveQuery)->count();
        $unseenActiveCount = (clone $baseActiveQuery)->whereNull('director_read_at')->count();
        $seenActiveCount = (clone $baseActiveQuery)->whereNotNull('director_read_at')->count();

        // Filter by tab
        $docQuery = (clone $baseActiveQuery)
            ->with(['owner', 'unitKerja', 'documentType', 'currentVersion', 'versions', 'branch.company', 'company', 'directorAcknowledgedBy']);

        if ($tab === 'unseen') {
            $docQuery->whereNull('director_read_at');
        } elseif ($tab === 'seen') {
            $docQuery->whereNotNull('director_read_at');
        }

        // Search Filter
        if (!empty($search)) {
            $docQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('pending_title', 'like', "%{$search}%");
            });
        }

        // Company Filter
        if ($selectedCompanyId) {
            $docQuery->where(function ($q) use ($selectedCompanyId) {
                $q->where('company_id', $selectedCompanyId)
                  ->orWhereHas('branch', fn($b) => $b->where('company_id', $selectedCompanyId));
            });
        }

        // Branch Filter
        if ($selectedBranchId) {
            $docQuery->where('branch_id', $selectedBranchId);
        }

        // Unit Kerja Filter
        if ($selectedUnitKerjaId) {
            $docQuery->where('unit_kerja_id', $selectedUnitKerjaId);
        }

        // Document Type Filter
        if ($selectedDocTypeId) {
            $docQuery->where('document_type_id', $selectedDocTypeId);
        }

        // Creator Filter
        if ($selectedOwnerId) {
            $docQuery->where('owner_id', $selectedOwnerId);
        }

        // Format Choice Filter
        if ($selectedFormatChoice && in_array($selectedFormatChoice, ['baru', 'lama'], true)) {
            $docQuery->where('format_choice', $selectedFormatChoice);
        }

        // Date-based filtering & grouping
        $selectedActiveDate = $request->get('active_date');
        $isSpecificDateFilter = false;
        $selectedActiveDateFormatted = null;

        if (!empty($selectedActiveDate)) {
            try {
                $parsedDate = \Carbon\Carbon::parse($selectedActiveDate);
                $targetStart = $parsedDate->copy()->startOfDay();
                $targetEnd = $parsedDate->copy()->endOfDay();
                $isSpecificDateFilter = true;
                $selectedActiveDateFormatted = $parsedDate->translatedFormat('d F Y');
                $selectedActiveDate = $parsedDate->format('Y-m-d');
            } catch (\Exception $e) {
                $selectedActiveDate = null;
            }
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        // 1. Query for Documents becoming active Today
        $todayDocQuery = (clone $docQuery)->where(function ($q) use ($todayStart, $todayEnd) {
            $q->whereHas('currentVersion', function ($vq) use ($todayStart, $todayEnd) {
                $vq->where('status', 'active')
                   ->where(function ($sub) use ($todayStart, $todayEnd) {
                       $sub->whereBetween('reviewed_at', [$todayStart, $todayEnd])
                           ->orWhere(function ($s2) use ($todayStart, $todayEnd) {
                               $s2->whereNull('reviewed_at')
                                  ->whereBetween('created_at', [$todayStart, $todayEnd]);
                           });
                   });
            })->orWhere(function ($dq) use ($todayStart, $todayEnd) {
                $dq->whereDoesntHave('currentVersion', fn($vq) => $vq->whereNotNull('reviewed_at'))
                   ->whereBetween('created_at', [$todayStart, $todayEnd]);
            });
        });

        // 2. Query for Documents activated Before Today
        $previousDocQuery = (clone $docQuery)->where(function ($q) use ($todayStart) {
            $q->whereHas('currentVersion', function ($vq) use ($todayStart) {
                $vq->where('status', 'active')
                   ->where(function ($sub) use ($todayStart) {
                       $sub->where('reviewed_at', '<', $todayStart)
                           ->orWhere(function ($s2) use ($todayStart) {
                               $s2->whereNull('reviewed_at')
                                  ->where('created_at', '<', $todayStart);
                           });
                   });
            })->orWhere(function ($dq) use ($todayStart) {
                $dq->whereDoesntHave('currentVersion', fn($vq) => $vq->whereNotNull('reviewed_at'))
                   ->where('created_at', '<', $todayStart);
            });
        });

        $todayCount = (clone $todayDocQuery)->count();
        $previousCount = (clone $previousDocQuery)->count();
        $perPage = 16;
        $page = max(1, (int) $request->get('page', 1));

        if ($isSpecificDateFilter) {
            // Specific Date Filter is active
            $filteredDateQuery = (clone $docQuery)->where(function ($q) use ($targetStart, $targetEnd) {
                $q->whereHas('currentVersion', function ($vq) use ($targetStart, $targetEnd) {
                    $vq->where('status', 'active')
                       ->where(function ($sub) use ($targetStart, $targetEnd) {
                           $sub->whereBetween('reviewed_at', [$targetStart, $targetEnd])
                               ->orWhere(function ($s2) use ($targetStart, $targetEnd) {
                                   $s2->whereNull('reviewed_at')
                                      ->whereBetween('created_at', [$targetStart, $targetEnd]);
                               });
                       });
                })->orWhere(function ($dq) use ($targetStart, $targetEnd) {
                    $dq->whereDoesntHave('currentVersion', fn($vq) => $vq->whereNotNull('reviewed_at'))
                       ->whereBetween('created_at', [$targetStart, $targetEnd]);
                });
            });

            $totalCount = (clone $filteredDateQuery)->count();
            $items = $filteredDateQuery->latest('updated_at')->forPage($page, $perPage)->get()
                ->sortByDesc(fn($doc) => $doc->activated_at->getTimestamp());

            $documents = new LengthAwarePaginator(
                $items,
                $totalCount,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            $groupedDocuments = $items->groupBy(fn($doc) => $doc->activated_at->format('Y-m-d'));
            $isTodayPage = ($selectedActiveDate === now()->format('Y-m-d'));
        } else {
            // Default pagination: Page 1 = Today's documents, Page 2+ = Previous documents
            $previousPages = (int) ceil($previousCount / $perPage);
            $totalPages = max(1, 1 + $previousPages);

            if ($page > $totalPages && $totalPages > 0) {
                $page = $totalPages;
            }

            $isTodayPage = ($page === 1);

            if ($isTodayPage) {
                $items = $todayDocQuery->latest('updated_at')->get()
                    ->sortByDesc(fn($doc) => $doc->activated_at->getTimestamp());
            } else {
                $offset = ($page - 2) * $perPage;
                $items = $previousDocQuery->latest('updated_at')->skip($offset)->take($perPage)->get()
                    ->sortByDesc(fn($doc) => $doc->activated_at->getTimestamp());
            }

            $virtualTotal = $todayCount + $previousCount;
            $paginatorTotal = max($virtualTotal, $totalPages * $perPage);

            $documents = new LengthAwarePaginator(
                $items,
                $paginatorTotal,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            $groupedDocuments = $items->groupBy(fn($doc) => $doc->activated_at->format('Y-m-d'));
        }

        // Fetch filter dropdown options across system
        $availableCompanies = Company::orderBy('name')->get(['id', 'name', 'code']);
        $availableBranches = Branch::orderByDesc('is_pusat')->orderBy('name')->get(['id', 'name', 'code', 'company_id', 'is_pusat']);
        $availableUnitKerjas = UnitKerja::orderBy('kode_unit_kerja')->get();
        $availableDocumentTypes = DocumentType::orderBy('name')->get(['id', 'name', 'code', 'category']);
        $availableCreators = User::whereHas('documents', function ($q) {
            $q->whereHas('currentVersion', fn($qv) => $qv->where('status', 'active'));
        })->orderBy('name')->get(['id', 'name']);

        // Fetch distinct available active dates for quick dropdown filter
        $availableActiveDates = Document::whereHas('currentVersion', fn($q) => $q->where('status', 'active'))
            ->where('is_expired', false)
            ->with('currentVersion')
            ->get(['id', 'current_version_id', 'created_at'])
            ->map(function ($doc) {
                return $doc->activated_at->format('Y-m-d');
            })
            ->unique()
            ->sortDesc()
            ->values();

        return view('director.active_documents.index', compact(
            'documents',
            'groupedDocuments',
            'isTodayPage',
            'isSpecificDateFilter',
            'selectedActiveDate',
            'selectedActiveDateFormatted',
            'todayCount',
            'previousCount',
            'tab',
            'totalActiveCount',
            'unseenActiveCount',
            'seenActiveCount',
            'search',
            'selectedCompanyId',
            'selectedBranchId',
            'selectedUnitKerjaId',
            'selectedDocTypeId',
            'selectedOwnerId',
            'selectedFormatChoice',
            'viewMode',
            'availableCompanies',
            'availableBranches',
            'availableUnitKerjas',
            'availableDocumentTypes',
            'availableCreators',
            'availableActiveDates'
        ));
    }

    /**
     * Mark a released/active document as seen/acknowledged by Director.
     */
    public function acknowledgeRead(Request $request, Document $document): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isDirector() && !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $action = $request->input('action', 'seen'); // 'seen', 'unseen', 'toggle'

        if ($action === 'unseen' || ($action === 'toggle' && $document->isDirectorRead())) {
            $document->update([
                'director_read_at' => null,
                'director_acknowledged_by_id' => null,
            ]);
            $isSeen = false;
            $message = __('Status tinjauan Direktur berhasil dibatalkan.');
        } else {
            $document->update([
                'director_read_at' => now(),
                'director_acknowledged_by_id' => $user->id,
            ]);
            $isSeen = true;
            $message = __('Dokumen berhasil ditandai telah ditinjau oleh Direktur.');
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_seen' => $isSeen,
                'director_read_at' => $document->director_read_at?->diffForHumans(),
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk mark multiple documents as seen/unseen by Director.
     */
    public function bulkAcknowledge(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isDirector() && !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'document_ids' => 'required|array|min:1',
            'document_ids.*' => 'required|integer|exists:documents,id',
            'action' => 'required|in:seen,unseen',
        ]);

        $docIds = $validated['document_ids'];
        $action = $validated['action'];

        if ($action === 'seen') {
            Document::whereIn('id', $docIds)->update([
                'director_read_at' => now(),
                'director_acknowledged_by_id' => $user->id,
            ]);
            $message = __(':count dokumen berhasil ditandai telah ditinjau.', ['count' => count($docIds)]);
        } else {
            Document::whereIn('id', $docIds)->update([
                'director_read_at' => null,
                'director_acknowledged_by_id' => null,
            ]);
            $message = __('Status tinjauan pada :count dokumen berhasil dibatalkan.', ['count' => count($docIds)]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count' => count($docIds),
                'action' => $action,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
