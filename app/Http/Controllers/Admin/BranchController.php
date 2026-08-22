<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('admin');
        $companyId = $request->get('company_id');

        $query = Branch::with('company')->withCount('users', 'documents');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $branches = $query->orderBy('company_id')->orderBy('is_pusat', 'desc')->paginate(15);
        $companies = Company::orderBy('name')->get();

        return view('admin.branches.index', compact('branches', 'companies', 'companyId'));
    }

    public function create(Request $request): View
    {
        $this->authorize('admin');
        $companies = Company::orderBy('name')->get();
        $selectedCompanyId = $request->get('company_id');

        return view('admin.branches.create', compact('companies', 'selectedCompanyId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');
        $isPusat = $request->boolean('is_pusat');

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'is_pusat' => 'boolean',
        ];

        if ($isPusat) {
            // Only 1 Pusat per company
            $rules['company_id'] = [
                'required',
                'exists:companies,id',
                Rule::unique('branches')->where(fn($q) => $q->where('company_id', $request->company_id)->where('is_pusat', true)),
            ];
        } else {
            $rules['code'] = [
                'required',
                'string',
                'max:20',
                Rule::unique('branches')->where(fn($q) => $q->where('company_id', $request->company_id)),
            ];
        }

        $validated = $request->validate($rules, [
            'company_id.unique' => 'This company already has a Pusat branch.',
            'code.unique' => 'Branch code already exists in this company.',
        ]);

        $validated['is_pusat'] = $isPusat;
        $validated['code'] = $isPusat ? null : strtoupper(trim($validated['code'] ?? ''));

        Branch::create($validated);

        return redirect()->route('admin.branches.index', ['company_id' => $validated['company_id']])
            ->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('admin');
        $companies = Company::orderBy('name')->get();
        return view('admin.branches.edit', compact('branch', 'companies'));
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->authorize('admin');
        $isPusat = $request->boolean('is_pusat');

        $rules = [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'is_pusat' => 'boolean',
        ];

        if ($isPusat) {
            // Only 1 Pusat per company
            $rules['company_id'] = [
                'required',
                'exists:companies,id',
                Rule::unique('branches')
                    ->where(fn($q) => $q->where('company_id', $request->company_id)->where('is_pusat', true))
                    ->ignore($branch->id),
            ];
        } else {
            $rules['code'] = [
                'required',
                'string',
                'max:20',
                Rule::unique('branches')
                    ->where(fn($q) => $q->where('company_id', $request->company_id))
                    ->ignore($branch->id),
            ];
        }

        $validated = $request->validate($rules, [
            'company_id.unique' => 'This company already has a Pusat branch.',
            'code.unique' => 'Branch code already exists in this company.',
        ]);

        $validated['is_pusat'] = $isPusat;
        $validated['code'] = $isPusat ? null : strtoupper(trim($validated['code'] ?? ''));

        $branch->update($validated);

        return redirect()->route('admin.branches.index', ['company_id' => $branch->company_id])
            ->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('admin');
        if ($branch->documents()->exists()) {
            return back()->with('error', 'Cannot delete branch with associated documents.');
        }

        $companyId = $branch->company_id;
        $branch->delete();

        return redirect()->route('admin.branches.index', ['company_id' => $companyId])
            ->with('success', 'Branch deleted successfully.');
    }
}
