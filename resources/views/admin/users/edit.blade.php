<x-app-layout>
    <x-slot name="header">{{ __('Edit Pengguna') }}</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full px-0">
            @php
                $userCompanyIds = old('company_ids', $user->companies->pluck('id')->toArray());
                $userBranchIds = old('branch_ids', $user->branches->pluck('id')->toArray());
            @endphp
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6"
                 x-data="{
                     role: '{{ old('system_role', $user->system_role) }}',
                     selectedCompanies: {{ json_encode($userCompanyIds) }},
                     toggleCompany(id) {
                         if (this.selectedCompanies.includes(id)) {
                             this.selectedCompanies = this.selectedCompanies.filter(c => c !== id);
                         } else {
                             this.selectedCompanies.push(id);
                         }
                     }
                 }">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf @method('PUT')
                    @if($errors->any())
                        <div class="alert alert-error mb-4">
                            <ul class="text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Lengkap') }}</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control w-full">
                            <label for="email" class="label"><span class="label-text font-medium">{{ __('Email') }}</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="input input-bordered w-full" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="nip" class="label">
                                <span class="label-text font-medium">{{ __('NIP') }}</span>
                                <span class="label-text-alt text-base-content/50" x-show="role === 'direktur'">{{ __('(N/A)') }}</span>
                            </label>
                            <input type="text" name="nip" id="nip" value="{{ old('nip', $user->nip) }}"
                                   :disabled="role === 'direktur'"
                                   :placeholder="role === 'direktur' ? '—' : 'Contoh: 198501152010121001'"
                                   :class="role === 'direktur' ? 'bg-base-200 cursor-not-allowed opacity-60' : ''"
                                   class="input input-bordered w-full">
                        </div>
                        <div class="form-control w-full">
                            <label for="phone_number" class="label"><span class="label-text font-medium">{{ __('Nomor Telepon') }}</span></label>
                            <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" placeholder="Contoh: 081234567890" class="input input-bordered w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="password" class="label"><span class="label-text font-medium">{{ __('Password (kosongkan jika tidak diubah)') }}</span></label>
                            <input type="password" name="password" id="password" class="input input-bordered w-full">
                        </div>
                        <div class="form-control w-full">
                            <label for="password_confirmation" class="label"><span class="label-text font-medium">{{ __('Konfirmasi Password') }}</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="input input-bordered w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="division_id" class="label">
                                <span class="label-text font-medium">{{ __('Divisi Utama') }}</span>
                                <span class="label-text-alt text-base-content/50" x-show="role === 'direktur'">{{ __('(N/A)') }}</span>
                            </label>
                            <select name="division_id" id="division_id"
                                    :disabled="role === 'direktur'"
                                    :class="role === 'direktur' ? 'bg-base-200 cursor-not-allowed opacity-60' : ''"
                                    class="select select-bordered w-full">
                                <option value="">{{ __('Tanpa Divisi') }}</option>
                                @foreach($divisions as $div)
                                    <option value="{{ $div->id }}" {{ old('division_id', $user->division_id) == $div->id ? 'selected' : '' }}>{{ $div->code }} - {{ $div->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control w-full">
                            <label for="system_role" class="label"><span class="label-text font-medium">{{ __('Peran Sistem (Role)') }}</span></label>
                            <select name="system_role" id="system_role" x-model="role" class="select select-bordered w-full" required>
                                <option value="user" {{ old('system_role', $user->system_role) === 'user' ? 'selected' : '' }}>User (Staff)</option>
                                <option value="head" {{ old('system_role', $user->system_role) === 'head' ? 'selected' : '' }}>Division Head (Kepala Divisi)</option>
                                @if($user->system_role === 'direktur')
                                    <option value="direktur" {{ old('system_role', $user->system_role) === 'direktur' ? 'selected' : '' }}>Direktur</option>
                                @endif
                                <option value="admin" {{ old('system_role', $user->system_role) === 'admin' ? 'selected' : '' }}>System Admin</option>
                            </select>
                            @if($user->system_role !== 'direktur')
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Role Direktur hanya dapat ditentukan saat penambahan user baru.') }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Multi-Company & Branch Assignment --}}
                    <div class="border-t border-base-200 pt-4 mt-4 mb-4">
                        <div x-show="role === 'admin'" class="alert alert-info py-2.5 text-xs mb-3 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span>{{ __('Role System Admin secara otomatis memiliki hak akses ke seluruh Perusahaan & Cabang.') }}</span>
                        </div>

                        <div x-show="role !== 'admin'">
                            <h3 class="font-semibold text-sm mb-2">{{ __('Assignment Perusahaan & Cabang') }}</h3>
                            <p class="text-xs text-base-content/60 mb-3">{{ __('Pilih perusahaan yang dapat diakses user, lalu centang cabang-cabang yang di-assign.') }}</p>

                            <div class="space-y-4">
                                @foreach($companies as $company)
                                    <div class="border border-base-300 rounded-lg p-3 bg-base-200/30">
                                        <label class="flex items-center gap-2 cursor-pointer font-medium text-sm">
                                            <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" 
                                                   :checked="selectedCompanies.includes({{ $company->id }})"
                                                   @change="toggleCompany({{ $company->id }})"
                                                   class="checkbox checkbox-sm checkbox-primary">
                                            <span>{{ $company->name }} ({{ $company->code }})</span>
                                        </label>

                                        <div class="pl-6 pt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2 border-t border-base-300/50"
                                             x-show="selectedCompanies.includes({{ $company->id }})">
                                            @foreach($company->branches as $branch)
                                                <label class="flex items-center gap-2 cursor-pointer text-xs">
                                                    <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"
                                                           {{ in_array($branch->id, $userBranchIds) ? 'checked' : '' }}
                                                           class="checkbox checkbox-xs checkbox-secondary">
                                                    <span>{{ $branch->name }} @if($branch->is_pusat)<span class="text-primary font-semibold">({{ __('Pusat') }})</span>@else({{ $branch->code }})@endif</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="form-control mb-4">
                        <label class="label cursor-pointer justify-start gap-3 px-0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="checkbox checkbox-primary">
                            <span class="label-text">{{ __('Pengguna Aktif') }}</span>
                        </label>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Perbarui Pengguna') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
