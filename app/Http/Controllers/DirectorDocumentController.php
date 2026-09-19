<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\Request;
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
     * Mark a released document as acknowledged/read by Director.
     */
    public function acknowledgeRead(Request $request, Document $document): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        if (!$user->isDirector() && !$user->isAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $document->update([
            'director_read_at' => now(),
            'director_acknowledged_by_id' => $user->id,
        ]);

        return back()->with('success', __('Dokumen telah ditandai telah dibaca dan diketahui oleh Direktur.'));
    }
}
