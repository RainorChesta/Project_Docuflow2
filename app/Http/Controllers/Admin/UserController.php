<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('admin');
        $users = User::with(['division', 'companies', 'branches'])->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        $divisions = Division::all();
        $companies = Company::with('branches')->get();
        return view('admin.users.create', compact('divisions', 'companies'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('admin');
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'nip' => 'nullable|string|max:50|unique:users,nip',
            'phone_number' => 'nullable|string|max:50',
            'password' => 'required|string|min:8|confirmed',
            'division_id' => 'nullable|exists:divisions,id',
            'system_role' => 'required|in:admin,direktur,head,user',
            'is_active' => 'boolean',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        unset($validated['password_confirmation']);
        $companyIds = $validated['company_ids'] ?? [];
        $branchIds = $validated['branch_ids'] ?? [];
        unset($validated['company_ids'], $validated['branch_ids']);

        if ($validated['system_role'] === 'admin') {
            $companyIds = Company::pluck('id')->all();
            $branchIds = Branch::pluck('id')->all();
        } elseif ($validated['system_role'] === 'direktur') {
            $validated['nip'] = null;
            $validated['division_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user = User::create($validated);

        if (!empty($companyIds)) {
            $user->companies()->sync($companyIds);
        }
        if (!empty($branchIds)) {
            $user->branches()->sync($branchIds);
        }

        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user): View
    {
        $this->authorize('admin');
        $divisions = Division::all();
        $companies = Company::with('branches')->get();
        $user->load(['companies', 'branches']);
        return view('admin.users.edit', compact('user', 'divisions', 'companies'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('admin');

        // Role direktur tidak dapat ditambahkan saat edit user non-direktur
        $allowedRoles = $user->isDirector() ? 'admin,direktur,head,user' : 'admin,head,user';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'nip' => 'nullable|string|max:50|unique:users,nip,' . $user->id,
            'phone_number' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
            'division_id' => 'nullable|exists:divisions,id',
            'system_role' => 'required|in:' . $allowedRoles,
            'is_active' => 'boolean',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        unset($validated['password_confirmation']);
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $companyIds = $validated['company_ids'] ?? [];
        $branchIds = $validated['branch_ids'] ?? [];
        unset($validated['company_ids'], $validated['branch_ids']);

        if ($validated['system_role'] === 'admin') {
            $companyIds = Company::pluck('id')->all();
            $branchIds = Branch::pluck('id')->all();
        } elseif ($validated['system_role'] === 'direktur') {
            $validated['nip'] = null;
            $validated['division_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        $user->companies()->sync($companyIds);
        $user->branches()->sync($branchIds);

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('admin');
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Cannot delete yourself.']);
        }
        $user->update(['is_active' => false]);
        return redirect()->route('admin.users.index')->with('success', 'User deactivated.');
    }
}
