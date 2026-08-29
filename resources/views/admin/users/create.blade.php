<x-app-layout>
    <x-slot name="header">{{ __('Tambah Pengguna') }}</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6"
                 x-data="{
                     role: '{{ old('system_role', 'user') }}',
                     selectedCompanies: {{ json_encode(old('company_ids', [])) }}.map(String),
                     selectedBranches: {{ json_encode(old('branch_ids', [])) }}.map(String)
                 }">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
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
                            <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Lengkap') }} <span class="text-error">*</span></span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control w-full">
                            <label for="email" class="label"><span class="label-text font-medium">{{ __('Email') }} <span class="text-error">*</span></span></label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="input input-bordered w-full" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="nip" class="label">
                                <span class="label-text font-medium">{{ __('NIP') }}</span>
                                <span class="label-text-alt text-base-content/50" x-show="role === 'direktur'">{{ __('(N/A)') }}</span>
                            </label>
                            <input type="text" name="nip" id="nip" value="{{ old('nip') }}"
                                   :disabled="role === 'direktur'"
                                   :placeholder="role === 'direktur' ? '—' : 'Contoh: 198501152010121001'"
                                   :class="role === 'direktur' ? 'bg-base-200 cursor-not-allowed opacity-60' : ''"
                                   class="input input-bordered w-full">
                        </div>
                        <div class="form-control w-full">
                            <label for="phone_number" class="label"><span class="label-text font-medium">{{ __('Nomor Telepon') }}</span></label>
                            <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" placeholder="Contoh: 081234567890" class="input input-bordered w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="password" class="label"><span class="label-text font-medium">{{ __('Password') }} <span class="text-error">*</span></span></label>
                            <input type="password" name="password" id="password" class="input input-bordered w-full" required>
                        </div>
                        <div class="form-control w-full">
                            <label for="password_confirmation" class="label"><span class="label-text font-medium">{{ __('Konfirmasi Password') }} <span class="text-error">*</span></span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="input input-bordered w-full" required>
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
                                    <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>{{ $div->code }} - {{ $div->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control w-full">
                            <label for="system_role" class="label"><span class="label-text font-medium">{{ __('Peran Sistem (Role)') }} <span class="text-error">*</span></span></label>
                            <select name="system_role" id="system_role" x-model="role" class="select select-bordered w-full" required>
                                <option value="user" {{ old('system_role', 'user') === 'user' ? 'selected' : '' }}>User (Staff)</option>
                                <option value="head" {{ old('system_role') === 'head' ? 'selected' : '' }}>Division Head (Kepala Divisi)</option>
                                <option value="direktur" {{ old('system_role') === 'direktur' ? 'selected' : '' }}>Direktur</option>
                                <option value="admin" {{ old('system_role') === 'admin' ? 'selected' : '' }}>System Admin</option>
                            </select>
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

                            <div x-data="{
                                search: '',
                                open: false,
                                companies: [
                                    @foreach($companies as $company)
                                        { id: {{ $company->id }}, name: '{{ addslashes($company->name) }} ({{ addslashes($company->code) }})', branchIds: {{ $company->branches->pluck('id')->toJson() }} },
                                    @endforeach
                                ],
                                get filteredCompanies() {
                                    if (this.search === '') {
                                        return this.companies.filter(c => !selectedCompanies.includes(String(c.id)));
                                    }
                                    return this.companies.filter(c => 
                                        !selectedCompanies.includes(String(c.id)) && 
                                        c.name.toLowerCase().includes(this.search.toLowerCase())
                                    );
                                },
                                toggleCompany(id) {
                                    id = String(id);
                                    if (selectedCompanies.includes(id)) {
                                        selectedCompanies = selectedCompanies.filter(c => c !== id);
                                        // Also remove its branches
                                        let company = this.companies.find(c => String(c.id) === id);
                                        if(company) {
                                            selectedBranches = selectedBranches.filter(b => !company.branchIds.map(String).includes(String(b)));
                                        }
                                    } else {
                                        selectedCompanies.push(id);
                                    }
                                    this.search = '';
                                    this.$refs.searchInput.focus();
                                }
                            }" class="relative mb-6">
                                
                                <div class="border border-base-300 rounded-lg p-2 min-h-[3rem] flex flex-wrap gap-2 items-center bg-base-100 cursor-text"
                                     @click="open = true; $refs.searchInput.focus()"
                                     @click.away="open = false">
                                    
                                    <template x-for="id in selectedCompanies" :key="id">
                                        <div class="badge badge-primary gap-1 p-3">
                                            <span x-text="companies.find(c => String(c.id) === String(id))?.name"></span>
                                            <button type="button" @click.stop="toggleCompany(id)" class="hover:bg-primary-focus rounded-full p-0.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                            <input type="hidden" name="company_ids[]" :value="id">
                                        </div>
                                    </template>
                                    
                                    <input type="text" x-ref="searchInput" x-model="search" @focus="open = true" @keydown.backspace="if(search === '' && selectedCompanies.length > 0) toggleCompany(selectedCompanies[selectedCompanies.length - 1])" class="flex-1 outline-none bg-transparent min-w-[150px] text-sm" placeholder="{{ __('Cari perusahaan...') }}">
                                </div>
                                
                                <div x-show="open" 
                                     x-transition
                                     class="absolute z-10 mt-1 w-full bg-base-100 border border-base-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <template x-if="filteredCompanies.length === 0">
                                        <div class="p-3 text-sm text-base-content/60 text-center">{{ __('Tidak ada perusahaan ditemukan') }}</div>
                                    </template>
                                    <template x-for="company in filteredCompanies" :key="company.id">
                                        <div @click="toggleCompany(company.id)" class="p-3 hover:bg-base-200 cursor-pointer text-sm">
                                            <span x-text="company.name"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="space-y-4">
                                @foreach($companies as $company)
                                    <div class="border border-base-300 rounded-lg p-4 bg-base-200/30"
                                         x-show="selectedCompanies.includes(String({{ $company->id }}))"
                                         x-data="{ 
                                            companyId: {{ $company->id }},
                                            branchIds: {{ $company->branches->pluck('id')->toJson() }}
                                         }">
                                        
                                        <div class="flex items-center justify-between border-b border-base-300 pb-2 mb-3">
                                            <span class="font-medium text-sm">{{ $company->name }} ({{ $company->code }}) - Cabang</span>
                                            <label class="flex items-center gap-2 cursor-pointer text-xs">
                                                <input type="checkbox" 
                                                       :checked="branchIds.length > 0 && branchIds.every(b => selectedBranches.includes(String(b)))"
                                                       @change="
                                                            if ($el.checked) {
                                                                branchIds.forEach(b => { if (!selectedBranches.includes(String(b))) selectedBranches.push(String(b)); });
                                                            } else {
                                                                selectedBranches = selectedBranches.filter(b => !branchIds.map(String).includes(String(b)));
                                                            }
                                                       "
                                                       class="checkbox checkbox-xs checkbox-primary">
                                                <span>{{ __('Pilih Semua') }}</span>
                                            </label>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            @foreach($company->branches as $branch)
                                                <label class="flex items-center gap-2 cursor-pointer text-xs">
                                                    <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"
                                                           x-model="selectedBranches"
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
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="checkbox checkbox-primary">
                            <span class="label-text">{{ __('Pengguna Aktif') }}</span>
                        </label>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            {{ __('Buat Pengguna') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
