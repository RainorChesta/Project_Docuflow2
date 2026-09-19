<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\UnitKerja;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('admin');
        $query = User::with(['companies', 'branches.company', 'unitKerjas', 'unitKerja']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit_kerja')) {
            $query->where(function($q) use ($request) {
                $q->where('unit_kerja_id', $request->unit_kerja)
                  ->orWhereHas('unitKerjas', function($sub) use ($request) {
                      $sub->where('unit_kerjas.id', $request->unit_kerja);
                  });
            });
        }

        if ($request->filled('role')) {
            $query->where('system_role', $request->role);
        }

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('system_role', '!=', 'admin')
                      ->where(function ($q) {
                          $q->where(function ($sub) {
                              $sub->where('system_role', '!=', 'direktur')
                                  ->where(function ($missing) {
                                      $missing->where(function ($noUnit) {
                                          $noUnit->whereNull('unit_kerja_id')
                                                 ->whereDoesntHave('unitKerjas');
                                      })
                                      ->orWhereDoesntHave('companies')
                                      ->orWhereDoesntHave('branches');
                                  });
                          })->orWhere(function ($sub) {
                              $sub->where('system_role', 'direktur')
                                  ->where(function ($missing) {
                                      $missing->whereDoesntHave('companies')
                                              ->orWhereDoesntHave('branches');
                                  });
                          });
                      });
            } elseif ($request->status === '1') {
                $query->where('is_active', true)
                      ->where(function ($q) {
                          $q->where('system_role', 'admin')
                            ->orWhere(function ($sub) {
                                $sub->where('system_role', 'direktur')
                                    ->whereHas('companies')
                                    ->whereHas('branches');
                            })
                            ->orWhere(function ($sub) {
                                $sub->whereNotIn('system_role', ['admin', 'direktur'])
                                    ->where(function ($unitQ) {
                                        $unitQ->whereNotNull('unit_kerja_id')
                                              ->orWhereHas('unitKerjas');
                                    })
                                    ->whereHas('companies')
                                    ->whereHas('branches');
                            });
                      });
            } elseif ($request->status === '0') {
                $query->where('is_active', false);
            }
        }

        $users = $query->latest('id')->paginate(20)->appends($request->query());
        $unitKerjas = UnitKerja::orderBy('kode_unit_kerja')->get();

        return view('admin.users.index', compact('users', 'unitKerjas'));
    }

    public function create(): View
    {
        $this->authorize('admin');
        $companies = Company::with(['branches'])->orderBy('name')->get();
        $unitKerjas = UnitKerja::orderBy('kode_unit_kerja')->get();
        return view('admin.users.create', compact('companies', 'unitKerjas'));
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
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
            'unit_kerja_ids' => 'nullable|array',
            'unit_kerja_ids.*' => 'exists:unit_kerjas,id',
            'branch_unit_kerjas' => 'nullable|array',
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
        $unitKerjaIds = $validated['unit_kerja_ids'] ?? [];
        $branchUnitKerjas = $request->input('branch_unit_kerjas', []);
        unset($validated['company_ids'], $validated['branch_ids'], $validated['unit_kerja_ids'], $validated['branch_unit_kerjas']);

        if ($validated['system_role'] === 'admin') {
            $companyIds = Company::pluck('id')->all();
            $branchIds = Branch::pluck('id')->all();
            $validated['unit_kerja_id'] = null;
            $unitKerjaIds = [];
        } elseif ($validated['system_role'] === 'direktur') {
            $validated['nip'] = null;
            $validated['unit_kerja_id'] = null;
            $unitKerjaIds = [];
        } else {
            if (in_array($validated['system_role'], ['user', 'head'], true)) {
                if (empty($branchIds)) {
                    return back()->withInput()->withErrors(['branch_ids' => __('Pilih minimal satu cabang atau kantor pusat untuk penempatan pengguna.')]);
                }

                $assignedBranches = Branch::whereIn('id', $branchIds)->get();

                // Validasi unit kerja per Cabang/Pusat
                if ($assignedBranches->isNotEmpty()) {
                    foreach ($assignedBranches as $branch) {
                        $uks = $branchUnitKerjas[$branch->id] ?? [];
                        if (empty($uks) && !empty($unitKerjaIds)) {
                            $uks = $unitKerjaIds;
                        }
                        if (empty($uks) && UnitKerja::exists()) {
                            return back()->withInput()->withErrors([
                                'branch_unit_kerjas' => __('Pilih minimal satu unit kerja untuk penugasan di :branch.', ['branch' => $branch->name]),
                                'unit_kerja_ids' => __('Pilih minimal satu unit kerja untuk penugasan di :branch.', ['branch' => $branch->name]),
                            ]);
                        }
                    }
                }
            }

            $resolvedUnitKerjas = $this->collectAllUnitKerjaIds($branchIds, $branchUnitKerjas, $unitKerjaIds);
            $validated['unit_kerja_id'] = !empty($resolvedUnitKerjas) ? $resolvedUnitKerjas[0] : null;
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user = User::create($validated);

        $this->syncUserAssignments(
            $user,
            $validated['system_role'],
            $companyIds,
            $branchIds,
            $branchUnitKerjas,
            $unitKerjaIds
        );

        return redirect()->route('admin.users.index')->with('success', __('Pengguna berhasil dibuat.'));
    }

    public function edit(User $user): View
    {
        $this->authorize('admin');
        $companies = Company::with(['branches'])->orderBy('name')->get();
        $unitKerjas = UnitKerja::orderBy('kode_unit_kerja')->get();
        $user->load(['companies', 'branches', 'unitKerjas', 'unitKerja']);
        $branchUnitKerjasMap = $user->getBranchUnitKerjasMap();
        return view('admin.users.edit', compact('user', 'companies', 'unitKerjas', 'branchUnitKerjasMap'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('admin');

        $allowedRoles = $user->isDirector() ? 'admin,direktur,head,user' : 'admin,head,user';

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'nip' => 'nullable|string|max:50|unique:users,nip,' . $user->id,
            'phone_number' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8|confirmed',
            'unit_kerja_id' => 'nullable|exists:unit_kerjas,id',
            'unit_kerja_ids' => 'nullable|array',
            'unit_kerja_ids.*' => 'exists:unit_kerjas,id',
            'branch_unit_kerjas' => 'nullable|array',
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
        $unitKerjaIds = $validated['unit_kerja_ids'] ?? [];
        $branchUnitKerjas = $request->input('branch_unit_kerjas', []);
        unset($validated['company_ids'], $validated['branch_ids'], $validated['unit_kerja_ids'], $validated['branch_unit_kerjas']);

        if ($validated['system_role'] === 'admin') {
            $companyIds = Company::pluck('id')->all();
            $branchIds = Branch::pluck('id')->all();
            $validated['unit_kerja_id'] = null;
            $unitKerjaIds = [];
        } elseif ($validated['system_role'] === 'direktur') {
            $validated['nip'] = null;
            $validated['unit_kerja_id'] = null;
            $unitKerjaIds = [];
        } else {
            if (in_array($validated['system_role'], ['user', 'head'], true)) {
                if (!empty($branchIds)) {
                    $assignedBranches = Branch::whereIn('id', $branchIds)->get();

                    if ($assignedBranches->isNotEmpty()) {
                        foreach ($assignedBranches as $branch) {
                            $uks = $branchUnitKerjas[$branch->id] ?? [];
                            if (empty($uks) && !empty($unitKerjaIds)) {
                                $uks = $unitKerjaIds;
                            }
                            if (empty($uks) && UnitKerja::exists()) {
                                return back()->withInput()->withErrors([
                                    'branch_unit_kerjas' => __('Pilih minimal satu unit kerja untuk penugasan di :branch.', ['branch' => $branch->name]),
                                    'unit_kerja_ids' => __('Pilih minimal satu unit kerja untuk penugasan di :branch.', ['branch' => $branch->name]),
                                ]);
                            }
                        }
                    }
                }
            }

            $resolvedUnitKerjas = $this->collectAllUnitKerjaIds($branchIds, $branchUnitKerjas, $unitKerjaIds);
            $validated['unit_kerja_id'] = !empty($resolvedUnitKerjas) ? $resolvedUnitKerjas[0] : null;
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        $this->syncUserAssignments(
            $user,
            $validated['system_role'],
            $companyIds,
            $branchIds,
            $branchUnitKerjas,
            $unitKerjaIds
        );

        event(new \App\Events\UserVerificationUpdated($user));

        return redirect()->route('admin.users.index')->with('success', __('Pengguna berhasil diperbarui.'));
    }

    private function collectAllUnitKerjaIds(array $branchIds, array $branchUnitKerjas, array $flatUnitKerjaIds): array
    {
        $collected = [];
        $branches = Branch::whereIn('id', $branchIds)->get();
        foreach ($branches as $c) {
            $uks = $branchUnitKerjas[$c->id] ?? [];
            foreach ($uks as $uId) {
                $collected[] = (int) $uId;
            }
        }
        if (empty($collected) && !empty($flatUnitKerjaIds)) {
            $collected = array_map('intval', $flatUnitKerjaIds);
        }
        return array_values(array_unique($collected));
    }

    private function syncUserAssignments(
        User $user,
        string $systemRole,
        array $companyIds,
        array $branchIds,
        array $branchUnitKerjas,
        array $flatUnitKerjaIds
    ): void {
        if ($systemRole === 'admin') {
            $user->companies()->sync(Company::pluck('id')->all());
            $user->branches()->sync(Branch::pluck('id')->all());
            DB::table('unit_kerja_user')->where('user_id', $user->id)->delete();
            return;
        }

        if ($systemRole === 'direktur') {
            $user->companies()->sync($companyIds);
            $user->branches()->sync($branchIds);
            DB::table('unit_kerja_user')->where('user_id', $user->id)->delete();
            return;
        }

        $user->companies()->sync($companyIds);
        $user->branches()->sync($branchIds);

        $assignedBranches = Branch::whereIn('id', $branchIds)->get();

        // Unit Kerja sync with branch_id
        DB::table('unit_kerja_user')->where('user_id', $user->id)->delete();
        $unitKerjaRows = [];

        if ($assignedBranches->isNotEmpty()) {
            foreach ($assignedBranches as $branch) {
                $uks = $branchUnitKerjas[$branch->id] ?? [];
                if (empty($uks) && !empty($flatUnitKerjaIds)) {
                    $uks = $flatUnitKerjaIds;
                }
                foreach ($uks as $ukId) {
                    $unitKerjaRows[] = [
                        'user_id' => $user->id,
                        'unit_kerja_id' => (int) $ukId,
                        'branch_id' => $branch->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        } elseif (!empty($flatUnitKerjaIds)) {
            foreach ($flatUnitKerjaIds as $ukId) {
                $unitKerjaRows[] = [
                    'user_id' => $user->id,
                    'unit_kerja_id' => (int) $ukId,
                    'branch_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($unitKerjaRows)) {
            DB::table('unit_kerja_user')->insert($unitKerjaRows);
        }
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('admin');

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => __('Tidak dapat menghapus akun Anda sendiri.')]);
        }

        try {
            DB::transaction(function () use ($user) {
                if ($user->signature) {
                    if ($user->signature->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->signature->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($user->signature->file_path);
                    }
                    $user->signature()->delete();
                }

                if ($user->profile_picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_picture)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_picture);
                }

                $user->companies()->detach();
                $user->branches()->detach();
                $user->unitKerjas()->detach();

                \App\Models\DocumentAccessLink::where('created_by', $user->id)->delete();
                \App\Models\DocumentDistribution::where('created_by', $user->id)->orWhere('target_user_id', $user->id)->delete();
                
                \App\Models\Document::where('rollback_requested_by_id', $user->id)->update([
                    'rollback_requested_by_id' => null
                ]);

                \App\Models\DocumentVersion::where('author_id', $user->id)->update(['author_id' => null]);
                \App\Models\DocumentVersion::where('reviewer_id', $user->id)->update(['reviewer_id' => null]);

                \App\Models\SignatureRequest::where('requester_id', $user->id)->orWhere('target_user_id', $user->id)->delete();
                \App\Models\DocumentShare::where('user_id', $user->id)->orWhere('invited_by', $user->id)->delete();
                \App\Models\DocumentUnitKerjaShare::where('invited_by', $user->id)->delete();

                $ownedDocuments = Document::where('owner_id', $user->id)->get();
                foreach ($ownedDocuments as $doc) {
                    foreach ($doc->versions as $version) {
                        if ($version->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($version->file_path)) {
                            \Illuminate\Support\Facades\Storage::disk('local')->delete($version->file_path);
                        }
                    }
                    $doc->versions()->delete();
                    $doc->forceDelete();
                }

                $ownedTemplates = \App\Models\DocumentTemplate::where('created_by', $user->id)->get();
                foreach ($ownedTemplates as $tmpl) {
                    if ($tmpl->file_path && \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'))->exists($tmpl->file_path)) {
                        \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'))->delete($tmpl->file_path);
                    }
                    $tmpl->delete();
                }

                \App\Models\AuditLog::where('user_id', $user->id)->update(['user_id' => null]);

                $user->delete();
            });

            return redirect()->route('admin.users.index')->with('success', __('User berhasil dihapus secara permanen.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => __('Gagal menghapus user: :message', ['message' => $e->getMessage()])]);
        }
    }
}
