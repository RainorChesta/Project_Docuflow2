<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(): View
    {
        $this->authorize('admin');
        $companies = Company::withCount('branches', 'users')->paginate(15);
        return view('admin.companies.index', compact('companies'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        return view('admin.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:companies,code',
        ]);

        $validated['name'] = mb_strtoupper(trim($validated['name']));
        $validated['code'] = strtoupper(trim($validated['code']));

        $company = Company::create($validated);

        // Auto-create default "Pusat" branch
        $company->branches()->create([
            'name' => 'PUSAT',
            'is_pusat' => true,
            'code' => null,
        ]);

        return redirect()->route('admin.companies.index')->with('success', __('Perusahaan berhasil dibuat dengan cabang Pusat default.'));
    }

    public function edit(Company $company): View
    {
        $this->authorize('admin');
        return view('admin.companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:companies,code,' . $company->id,
        ]);

        $validated['name'] = mb_strtoupper(trim($validated['name']));
        $validated['code'] = strtoupper(trim($validated['code']));
        $company->update($validated);

        return redirect()->route('admin.companies.index')->with('success', __('Perusahaan berhasil diperbarui.'));
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('admin');
        if ($company->documents()->withTrashed()->exists()) {
            return back()->with('error', __('Tidak dapat menghapus perusahaan yang memiliki dokumen terkait.'));
        }

        $company->delete();
        return redirect()->route('admin.companies.index')->with('success', __('Perusahaan berhasil dihapus.'));
    }
}
