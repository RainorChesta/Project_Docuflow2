<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Semua dokumen dari semua divisi/user, termasuk yang masih pending
     * atau draft (belum di-approve). Admin bisa melihat & menghapusnya.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');
        $user = auth()->user();

        $search = $request->get('search');
        $selectedCompanyId = $request->get('company_id');
        $selectedBranchId = $request->get('branch_id');
        $selectedDivisionId = $request->get('division_id');
        $selectedDocTypeId = $request->get('document_type_id');
        $selectedOwnerId = $request->get('owner_id');
        $selectedFormatChoice = $request->get('format_choice');
        $viewMode = $request->get('view_mode', 'grid');

        // Verify company access if specified
        $currentCompany = null;
        if ($selectedCompanyId) {
            $compQuery = Company::where('id', $selectedCompanyId);
            if (!$user->isAdmin()) {
                $compQuery->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
            $currentCompany = $compQuery->first();
            if (!$currentCompany) {
                $selectedCompanyId = null;
                $selectedBranchId = null;
                $selectedDivisionId = null;
            }
        }

        // Verify branch access if specified
        $currentBranch = null;
        if ($selectedBranchId && $selectedCompanyId) {
            $branchQuery = Branch::where('id', $selectedBranchId)->where('company_id', $selectedCompanyId);
            if (!$user->isAdmin()) {
                $branchQuery->whereHas('users', fn($q) => $q->where('users.id', $user->id));
            }
            $currentBranch = $branchQuery->first();
            if (!$currentBranch) {
                $selectedBranchId = null;
                $selectedDivisionId = null;
            }
        }

        // Verify division if specified
        $currentDivision = null;
        if ($selectedDivisionId) {
            $currentDivision = Division::find($selectedDivisionId);
        }

        // Build Breadcrumbs trail
        $breadcrumbs = [
            [
                'name' => __('Semua Perusahaan'),
                'url' => route('admin.documents.index', array_filter(['view_mode' => $viewMode])),
                'icon' => 'home',
                'active' => !$selectedCompanyId,
            ]
        ];

        if ($currentCompany) {
            $breadcrumbs[] = [
                'name' => $currentCompany->name,
                'url' => route('admin.documents.index', array_filter([
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
                'url' => route('admin.documents.index', array_filter([
                    'company_id' => $currentCompany->id,
                    'branch_id' => $currentBranch->id,
                    'view_mode' => $viewMode,
                ])),
                'icon' => 'branch',
                'active' => $selectedBranchId && !$selectedDivisionId,
            ];
        }

        if ($currentDivision) {
            $breadcrumbs[] = [
                'name' => $currentDivision->name,
                'url' => route('admin.documents.index', array_filter([
                    'company_id' => $currentCompany?->id,
                    'branch_id' => $currentBranch?->id,
                    'division_id' => $currentDivision->id,
                    'view_mode' => $viewMode,
                ])),
                'icon' => 'division',
                'active' => true,
            ];
        }

        // Parent URL for "Up one level"
        $parentUrl = null;
        if ($selectedDivisionId) {
            $parentUrl = route('admin.documents.index', array_filter([
                'company_id' => $selectedCompanyId,
                'branch_id' => $selectedBranchId,
                'view_mode' => $viewMode,
            ]));
        } elseif ($selectedBranchId) {
            $parentUrl = route('admin.documents.index', array_filter([
                'company_id' => $selectedCompanyId,
                'view_mode' => $viewMode,
            ]));
        } elseif ($selectedCompanyId) {
            $parentUrl = route('admin.documents.index', array_filter([
                'view_mode' => $viewMode,
            ]));
        }

        // Sub-Folders collection for current level
        $folders = collect();

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
                    'url' => route('admin.documents.index', array_filter([
                        'company_id' => $comp->id,
                        'view_mode' => $viewMode,
                    ])),
                ];
            });
        }
        // Level 1: Inside Company -> Show Branch folders
        elseif ($selectedCompanyId && !$selectedBranchId) {
            $branchesQuery = Branch::where('company_id', $selectedCompanyId);
            if (!$user->isAdmin()) {
                $branchesQuery->whereHas('users', fn($uq) => $uq->where('users.id', $user->id));
            }
            
            if (!empty($search)) {
                $branchesQuery->where('name', 'like', "%{$search}%");
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
                    'url' => route('admin.documents.index', array_filter([
                        'company_id' => $selectedCompanyId,
                        'branch_id' => $br->id,
                        'view_mode' => $viewMode,
                    ])),
                ];
            });
        }
        // Level 2: Inside Branch -> Show Division folders
        elseif ($selectedBranchId && !$selectedDivisionId) {
            $branchDocDivisions = Document::where('branch_id', $selectedBranchId)
                ->whereNotNull('division_id')
                ->select('division_id')
                ->selectRaw('count(*) as count')
                ->groupBy('division_id')
                ->pluck('count', 'division_id');

            $allDivisions = Division::orderBy('name');
            if (!empty($search)) {
                $allDivisions->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }
            $allDivisions = $allDivisions->get();
            
            $folders = $allDivisions->map(function ($div) use ($branchDocDivisions, $selectedCompanyId, $selectedBranchId, $viewMode) {
                $count = $branchDocDivisions[$div->id] ?? 0;
                return [
                    'id' => $div->id,
                    'type' => 'division',
                    'name' => $div->name,
                    'code' => strtoupper($div->code),
                    'doc_count' => $count,
                    'url' => route('admin.documents.index', array_filter([
                        'company_id' => $selectedCompanyId,
                        'branch_id' => $selectedBranchId,
                        'division_id' => $div->id,
                        'view_mode' => $viewMode,
                    ])),
                ];
            });
        }

        // Fetch Documents for current branch & filters
        $documents = collect();
        $availableDivisions = collect();
        $availableDocumentTypes = collect();
        $availableCreators = collect();

        $hasSearchOrFilter = $selectedDivisionId 
            || ($search !== null && trim($search) !== '') 
            || $selectedDocTypeId 
            || $selectedOwnerId
            || $selectedFormatChoice;

        // Documents are only queried inside a selected division
        if ($selectedDivisionId) {
            // Populate filter options dynamically from documents in this selected division and branch
            $branchDocTypes = DocumentType::whereHas('documents', fn($q) => $q->where('division_id', $selectedDivisionId)->where('branch_id', $selectedBranchId))->orderBy('name')->get();
            $availableDocumentTypes = $branchDocTypes->isNotEmpty() ? $branchDocTypes : DocumentType::orderBy('name')->get();

            $availableCreators = User::whereHas('documents', fn($q) => $q->where('division_id', $selectedDivisionId)->where('branch_id', $selectedBranchId))->orderBy('name')->get(['id', 'name']);

            $docQuery = Document::where('division_id', $selectedDivisionId)
                ->where('branch_id', $selectedBranchId)
                ->with(['owner', 'division', 'documentType', 'currentVersion', 'versions', 'branch.company']);

            // Apply search filter for documents inside division
            if (!empty($search)) {
                $docQuery->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                });
            }

            // Apply document type filter
            if ($selectedDocTypeId) {
                $docQuery->where('document_type_id', $selectedDocTypeId);
            }

            // Apply creator filter
            if ($selectedOwnerId) {
                $docQuery->where('owner_id', $selectedOwnerId);
            }

            // Apply format choice filter
            if ($selectedFormatChoice && in_array($selectedFormatChoice, ['baru', 'lama'], true)) {
                $docQuery->where('format_choice', $selectedFormatChoice);
            }

            $documents = $docQuery->latest()->paginate(16)->withQueryString();
        }

        return view('admin.documents.index', compact(
            'breadcrumbs',
            'parentUrl',
            'folders',
            'documents',
            'hasSearchOrFilter',
            'currentCompany',
            'currentBranch',
            'currentDivision',
            'selectedCompanyId',
            'selectedBranchId',
            'selectedDivisionId',
            'selectedDocTypeId',
            'selectedOwnerId',
            'selectedFormatChoice',
            'search',
            'viewMode',
            'availableDivisions',
            'availableDocumentTypes',
            'availableCreators'
        ));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('admin');

        // Hapus berkas fisik semua versi sebelum record dihapus.
        foreach ($document->versions as $version) {
            if ($version->file_path) {
                Storage::disk('local')->delete($version->file_path);
            }
        }
        
        $document->versions()->delete();
        $document->forceDelete();

        return redirect()->route('admin.documents.index')->with('success', __('Dokumen dihapus permanen.'));
    }
}