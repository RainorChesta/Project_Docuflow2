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
    public function index(Request $request): View
    {
        $this->authorize('admin');
        $query = User::with(['divisions', 'companies', 'branches']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('division')) {
            $query->whereHas('divisions', function($q) use ($request) {
                $q->where('divisions.id', $request->division);
            });
        }

        if ($request->filled('role')) {
            $query->where('system_role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $users = $query->latest('id')->paginate(20)->appends($request->query());
        $divisions = Division::all();

        return view('admin.users.index', compact('users', 'divisions'));
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
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
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
        $divisionIds = $validated['division_ids'] ?? [];
        unset($validated['company_ids'], $validated['branch_ids'], $validated['division_ids']);

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
        if (!empty($divisionIds)) {
            $user->divisions()->sync($divisionIds);
        }

        return redirect()->route('admin.users.index')->with('success', __('Pengguna berhasil dibuat.'));
    }

    public function edit(User $user): View
    {
        $this->authorize('admin');
        $divisions = Division::all();
        $companies = Company::with('branches')->get();
        $user->load(['companies', 'branches', 'divisions']);
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
            'division_ids' => 'nullable|array',
            'division_ids.*' => 'exists:divisions,id',
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
        $divisionIds = $validated['division_ids'] ?? [];
        unset($validated['company_ids'], $validated['branch_ids'], $validated['division_ids']);

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
        $user->divisions()->sync($divisionIds);

        return redirect()->route('admin.users.index')->with('success', __('Pengguna berhasil diperbarui.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('admin');

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun Anda sendiri.']);
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
                // Hapus file tanda tangan jika ada
                if ($user->signature) {
                    if ($user->signature->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->signature->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($user->signature->file_path);
                    }
                    $user->signature()->delete();
                }

                // Hapus foto profil jika ada
                if ($user->profile_picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_picture)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
                }

                // Detach pivot tables
                $user->companies()->detach();
                $user->branches()->detach();
                $user->divisions()->detach();

                // Bersihkan relasi foreign key yang belum cascade
                // 1. Dokumen yang dimiliki user: hapus atau nullkan owner
                // Jika user memiliki dokumen, kita bisa nullkan atau hapus dokumen sesuai kebutuhan sistem
                // Mengingat owner_id di dokumen:
                \App\Models\DocumentAccessLink::where('created_by', $user->id)->delete();
                \App\Models\DocumentDistribution::where('created_by', $user->id)->orWhere('target_user_id', $user->id)->delete();
                
                // Rollback request references
                \App\Models\Document::where('rollback_requested_by_id', $user->id)->update([
                    'rollback_requested_by_id' => null
                ]);

                // Version author / reviewer nullOnDelete
                \App\Models\DocumentVersion::where('author_id', $user->id)->update(['author_id' => null]);
                \App\Models\DocumentVersion::where('reviewer_id', $user->id)->update(['reviewer_id' => null]);

                // Signature requests
                \App\Models\SignatureRequest::where('requester_id', $user->id)->orWhere('target_user_id', $user->id)->delete();

                // Document shares
                \App\Models\DocumentShare::where('user_id', $user->id)->orWhere('invited_by', $user->id)->delete();
                \App\Models\DocumentDivisionShare::where('invited_by', $user->id)->delete();

                // Dokumen milik user: jika ada, kita hapus dokumen beserta versinya
                $ownedDocuments = \App\Models\Document::where('owner_id', $user->id)->get();
                foreach ($ownedDocuments as $doc) {
                    foreach ($doc->versions as $version) {
                        if ($version->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($version->file_path)) {
                            \Illuminate\Support\Facades\Storage::disk('local')->delete($version->file_path);
                        }
                    }
                    $doc->versions()->delete();
                    $doc->forceDelete();
                }

                // Document templates created by user
                $ownedTemplates = \App\Models\DocumentTemplate::where('created_by', $user->id)->get();
                foreach ($ownedTemplates as $tmpl) {
                    if ($tmpl->file_path && \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'))->exists($tmpl->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'))->delete($tmpl->file_path);
                    }
                    $tmpl->delete();
                }

                // Audit logs
                \App\Models\AuditLog::where('user_id', $user->id)->update(['user_id' => null]);

                // Hard delete user
                $user->delete();
            });

            return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus secara permanen.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Gagal menghapus user: ' . $e->getMessage()]);
        }
    }
}
