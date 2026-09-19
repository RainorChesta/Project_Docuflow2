<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DocumentController extends Controller
{
    /**
     * Semua dokumen dari semua unit kerja/user, termasuk yang masih pending
     * atau draft (belum di-approve). Admin bisa melihat & menghapusnya.
     */
    public function index(Request $request): View
    {
        $this->authorize('admin');
        $user = auth()->user();

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
            if (!$user->isAdmin()) {
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
            if (!$user->isAdmin()) {
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
                'active' => $selectedBranchId && !$selectedUnitKerjaId,
            ];
        }

        if ($currentUnitKerja) {
            $breadcrumbs[] = [
                'name' => $currentUnitKerja->name,
                'url' => route('admin.documents.index', array_filter([
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
        // Level 2: Inside Branch -> Show Unit Kerja folders
        elseif ($selectedBranchId && !$selectedUnitKerjaId) {
            $branchDocUnits = Document::where('branch_id', $selectedBranchId)
                ->whereNotNull('unit_kerja_id')
                ->select('unit_kerja_id')
                ->selectRaw('count(*) as count')
                ->groupBy('unit_kerja_id')
                ->pluck('count', 'unit_kerja_id');

            $allUnitKerjas = UnitKerja::orderBy('code');
            if (!empty($search)) {
                $allUnitKerjas->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }
            $allUnitKerjas = $allUnitKerjas->get();
            
            $folders = $allUnitKerjas->map(function ($uk) use ($branchDocUnits, $selectedCompanyId, $selectedBranchId, $viewMode) {
                $count = $branchDocUnits[$uk->id] ?? 0;
                return [
                    'id' => $uk->id,
                    'type' => 'unit_kerja',
                    'name' => $uk->name,
                    'code' => strtoupper($uk->code),
                    'doc_count' => $count,
                    'url' => route('admin.documents.index', array_filter([
                        'company_id' => $selectedCompanyId,
                        'branch_id' => $selectedBranchId,
                        'unit_kerja_id' => $uk->id,
                        'view_mode' => $viewMode,
                    ])),
                ];
            });
        }

        // Fetch Documents for current branch & filters
        $documents = collect();
        $availableUnitKerjas = collect();
        $availableDocumentTypes = collect();
        $availableCreators = collect();

        $hasSearchOrFilter = $selectedUnitKerjaId 
            || ($search !== null && trim($search) !== '') 
            || $selectedDocTypeId 
            || $selectedOwnerId
            || $selectedFormatChoice;

        // Documents are only queried inside a selected unit kerja
        if ($selectedUnitKerjaId) {
            // Populate filter options dynamically from documents in this selected unit kerja and branch
            $branchDocTypes = DocumentType::whereHas('documents', fn($q) => $q->where('unit_kerja_id', $selectedUnitKerjaId)->where('branch_id', $selectedBranchId))->orderBy('name')->get();
            $availableDocumentTypes = $branchDocTypes->isNotEmpty() ? $branchDocTypes : DocumentType::orderBy('name')->get();

            $availableCreators = User::whereHas('documents', fn($q) => $q->where('unit_kerja_id', $selectedUnitKerjaId)->where('branch_id', $selectedBranchId))->orderBy('name')->get(['id', 'name']);

            $docQuery = Document::where('unit_kerja_id', $selectedUnitKerjaId)
                ->where('branch_id', $selectedBranchId)
                ->with(['owner', 'unitKerja', 'documentType', 'currentVersion', 'versions', 'branch.company']);

            // Apply search filter for documents inside unit kerja
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
            'currentUnitKerja',
            'selectedCompanyId',
            'selectedBranchId',
            'selectedUnitKerjaId',
            'selectedDocTypeId',
            'selectedOwnerId',
            'selectedFormatChoice',
            'search',
            'viewMode',
            'availableUnitKerjas',
            'availableDocumentTypes',
            'availableCreators'
        ));
    }
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