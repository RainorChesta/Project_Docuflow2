<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\UnitKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitKerjaController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('admin');

        $search = trim((string) $request->get('search', ''));
        $cabangId = $request->get('cabang_id');
        $companyId = $request->get('company_id');

        $allCompanies = \App\Models\Company::orderBy('name')->get();
        $allBranches = Branch::with('company')->orderBy('name')->get();

        $companiesQuery = \App\Models\Company::with(['branches' => function ($bq) use ($search, $cabangId) {
            if (!empty($cabangId)) {
                $bq->where('id', $cabangId);
            }
            $bq->with(['unitKerjas' => function ($uq) use ($search) {
                $uq->withCount('documents')->orderBy('kode_unit_kerja');
                if (!empty($search)) {
                    $uq->where(function ($sub) use ($search) {
                        $sub->where('kode_unit_kerja', 'like', "%{$search}%")
                            ->orWhere('nama_unit_kerja', 'like', "%{$search}%");
                    });
                }
            }])
            ->withCount(['unitKerjas' => function ($uq) use ($search) {
                if (!empty($search)) {
                    $uq->where(function ($sub) use ($search) {
                        $sub->where('kode_unit_kerja', 'like', "%{$search}%")
                            ->orWhere('nama_unit_kerja', 'like', "%{$search}%");
                    });
                }
            }])
            ->orderBy('name');
        }]);

        if (!empty($companyId)) {
            $companiesQuery->where('id', $companyId);
        }

        if (!empty($cabangId)) {
            $companiesQuery->whereHas('branches', fn($bq) => $bq->where('id', $cabangId));
        }

        if (!empty($search)) {
            $companiesQuery->where(function ($cq) use ($search) {
                $cq->whereHas('branches.unitKerjas', function ($uq) use ($search) {
                    $uq->where('kode_unit_kerja', 'like', "%{$search}%")
                       ->orWhere('nama_unit_kerja', 'like', "%{$search}%");
                })->orWhereHas('branches', function ($bq) use ($search) {
                    $bq->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%");
                });
            });
        }

        $companies = $companiesQuery->orderBy('name')->get();

        // Also fetch orphan branches if any (branch without company)
        $orphanBranchesQuery = Branch::whereNull('company_id')
            ->with(['unitKerjas' => function ($uq) use ($search) {
                $uq->withCount('documents')->orderBy('kode_unit_kerja');
                if (!empty($search)) {
                    $uq->where(function ($sub) use ($search) {
                        $sub->where('kode_unit_kerja', 'like', "%{$search}%")
                            ->orWhere('nama_unit_kerja', 'like', "%{$search}%");
                    });
                }
            }])
            ->withCount('unitKerjas');

        if (!empty($cabangId)) {
            $orphanBranchesQuery->where('id', $cabangId);
        }
        $orphanBranches = $orphanBranchesQuery->get();

        $totalUnitKerjas = UnitKerja::count();

        // Determine which branch accordions should be open by default
        $initialOpenBranchIds = [];
        $allBranchIds = [];

        foreach ($companies as $comp) {
            foreach ($comp->branches as $branch) {
                $allBranchIds[] = (string) $branch->id;
                if (!empty($search) || !empty($cabangId) || $branch->unit_kerjas_count > 0) {
                    $initialOpenBranchIds[] = (string) $branch->id;
                }
            }
        }
        foreach ($orphanBranches as $branch) {
            $allBranchIds[] = (string) $branch->id;
            if (!empty($search) || !empty($cabangId) || $branch->unit_kerjas_count > 0) {
                $initialOpenBranchIds[] = (string) $branch->id;
            }
        }

        if (empty($initialOpenBranchIds) && !empty($allBranchIds)) {
            $initialOpenBranchIds[] = $allBranchIds[0];
        }

        return view('admin.unit_kerja.index', compact(
            'companies',
            'orphanBranches',
            'allCompanies',
            'allBranches',
            'cabangId',
            'companyId',
            'search',
            'totalUnitKerjas',
            'initialOpenBranchIds',
            'allBranchIds'
        ));
    }

    public function create(Request $request): View
    {
        $this->authorize('admin');
        $companies = \App\Models\Company::with(['branches' => fn($q) => $q->orderBy('name')])->orderBy('name')->get();
        $branches = Branch::with('company')->orderBy('name')->get();
        $selectedCabangId = $request->get('cabang_id');
        $selectedCompanyId = null;

        if ($selectedCabangId) {
            $branch = Branch::find($selectedCabangId);
            $selectedCompanyId = $branch?->company_id;
        }

        return view('admin.unit_kerja.create', compact('companies', 'branches', 'selectedCabangId', 'selectedCompanyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'cabang_id' => 'required|exists:branches,id',
            'kode_unit_kerja' => [
                'required',
                'string',
                'max:50',
                Rule::unique('unit_kerjas')->where(fn($q) => $q->where('cabang_id', $request->cabang_id)),
            ],
            'nama_unit_kerja' => 'required|string|max:255',
        ], [
            'cabang_id.required' => __('Cabang wajib dipilih.'),
            'kode_unit_kerja.unique' => __('Kode unit kerja sudah digunakan di cabang ini.'),
        ]);

        $validated['kode_unit_kerja'] = strtoupper(trim($validated['kode_unit_kerja']));

        UnitKerja::create($validated);

        return redirect()->route('admin.unit-kerja.index', ['cabang_id' => $validated['cabang_id']])
            ->with('success', __('Unit Kerja berhasil dibuat.'));
    }

    public function edit(UnitKerja $unitKerja): View
    {
        $this->authorize('admin');
        $companies = \App\Models\Company::with(['branches' => fn($q) => $q->orderBy('name')])->orderBy('name')->get();
        $branches = Branch::with('company')->orderBy('name')->get();
        $selectedCabangId = $unitKerja->cabang_id;
        $selectedCompanyId = $unitKerja->cabang?->company_id;

        return view('admin.unit_kerja.edit', compact('unitKerja', 'companies', 'branches', 'selectedCabangId', 'selectedCompanyId'));
    }

    public function update(Request $request, UnitKerja $unitKerja): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'cabang_id' => 'required|exists:branches,id',
            'kode_unit_kerja' => [
                'required',
                'string',
                'max:50',
                Rule::unique('unit_kerjas')
                    ->where(fn($q) => $q->where('cabang_id', $request->cabang_id))
                    ->ignore($unitKerja->id),
            ],
            'nama_unit_kerja' => 'required|string|max:255',
        ], [
            'kode_unit_kerja.unique' => __('Kode unit kerja sudah digunakan di cabang ini.'),
        ]);

        $validated['kode_unit_kerja'] = strtoupper(trim($validated['kode_unit_kerja']));

        $unitKerja->update($validated);

        return redirect()->route('admin.unit-kerja.index', ['cabang_id' => $unitKerja->cabang_id])
            ->with('success', __('Unit Kerja berhasil diperbarui.'));
    }

    public function destroy(UnitKerja $unitKerja): RedirectResponse
    {
        $this->authorize('admin');

        if ($unitKerja->documents()->exists()) {
            return back()->withErrors(['error' => __('Tidak dapat menghapus unit kerja yang memiliki dokumen terkait.')]);
        }

        $cabangId = $unitKerja->cabang_id;
        $unitKerja->delete();

        return redirect()->route('admin.unit-kerja.index', ['cabang_id' => $cabangId])
            ->with('success', __('Unit Kerja berhasil dihapus.'));
    }
}
