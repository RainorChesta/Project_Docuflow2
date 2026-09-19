<x-app-layout>
    <x-slot name="header">{{ __('Edit Pengguna') }}</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full px-0">
            @php
                $userCompanyIds = old('company_ids', $user->companies->pluck('id')->toArray()) ?: [];
                $userBranchIds = old('branch_ids', $user->branches->pluck('id')->toArray()) ?: [];
                $userUnitKerjaIds = old('unit_kerja_ids', $user->allUnitKerjaIds()) ?: [];
            @endphp
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6"
                 x-data="{
                     role: '{{ old('system_role', $user->system_role) }}',
                     selectedCompanies: ({{ json_encode($userCompanyIds) }} || []).map(String),
                     selectedBranches: ({{ json_encode($userBranchIds) }} || []).map(String)
                 }">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" autocomplete="off">
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

                    @if($user->isPendingVerification())
                        <div class="alert alert-warning/15 border border-warning/40 rounded-xl mb-5 text-sm flex items-start gap-3 shadow-sm bg-warning/10">
                            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5 mt-0.5 text-warning" fill="none" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <div class="font-bold text-sm text-base-content">{{ __('Akun Ini Menunggu Verifikasi') }}</div>
                                <div class="text-xs text-base-content/80 mt-0.5 leading-relaxed">
                                    {{ __('Akun ini belum memiliki penugasan lengkap. Untuk memverifikasi dan membuka akses akun pengguna ini, silakan tentukan Unit Kerja serta lakukan penugasan Perusahaan & Cabang di bawah, lalu klik Perbarui Pengguna.') }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Lengkap') }} <span class="text-error">*</span></span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="input input-bordered w-full" autocomplete="off" required>
                        </div>
                        <div class="form-control w-full">
                            <label for="email" class="label"><span class="label-text font-medium">{{ __('Email') }} <span class="text-error">*</span></span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="input input-bordered w-full" autocomplete="off" required>
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
                                   autocomplete="off"
                                   class="input input-bordered w-full">
                        </div>
                        <div class="form-control w-full">
                            <label for="phone_number" class="label"><span class="label-text font-medium">{{ __('Nomor Telepon') }}</span></label>
                            <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}" placeholder="Contoh: 081234567890" autocomplete="off" class="input input-bordered w-full">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div class="form-control w-full">
                            <label for="password" class="label"><span class="label-text font-medium">{{ __('Password (kosongkan jika tidak diubah)') }}</span></label>
                            <input type="password" name="password" id="password" class="input input-bordered w-full" autocomplete="new-password">
                        </div>
                        <div class="form-control w-full">
                            <label for="password_confirmation" class="label"><span class="label-text font-medium">{{ __('Konfirmasi Password') }}</span></label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="input input-bordered w-full" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        {{-- Role --}}
                        <div class="form-control w-full">
                            <label for="system_role" class="label"><span class="label-text font-medium">{{ __('Peran Sistem (Role)') }} <span class="text-error">*</span></span></label>
                            <select name="system_role" id="system_role" x-model="role" class="select select-bordered w-full" required>
                                <option value="user" {{ old('system_role', $user->system_role) === 'user' ? 'selected' : '' }}>User (Staff)</option>
                                <option value="head" {{ old('system_role', $user->system_role) === 'head' ? 'selected' : '' }}>Head / Kepala Unit Kerja</option>
                                @if($user->system_role === 'direktur')
                                    <option value="direktur" {{ old('system_role', $user->system_role) === 'direktur' ? 'selected' : '' }}>Direktur</option>
                                @endif
                                <option value="admin" {{ old('system_role', $user->system_role) === 'admin' ? 'selected' : '' }}>System Admin</option>
                            </select>
                            @if($user->system_role !== 'direktur')
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Role Direktur hanya dapat ditentukan saat penambahan user baru.') }}</p>
                            @endif
                        </div>

                        {{-- Status Akun --}}
                        <div class="form-control w-full">
                            <label class="label"><span class="label-text font-medium">{{ __('Status Akun') }}</span></label>
                            <label class="label cursor-pointer justify-start gap-3 px-0 pt-2">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="checkbox checkbox-primary">
                                <span class="label-text">{{ __('Pengguna Aktif') }}</span>
                            </label>
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
                                        {
                                            id: {{ $company->id }},
                                            name: '{{ addslashes($company->name) }} ({{ addslashes($company->code) }})',
                                            branchIds: {{ $company->branches->pluck('id')->map(fn($id) => (string)$id)->toJson() }}
                                        },
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
                                        let comp = this.companies.find(c => String(c.id) === id);
                                        if (comp) {
                                            selectedBranches = selectedBranches.filter(b => !comp.branchIds.includes(String(b)));
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
                                            branchIds: {{ $company->branches->pluck('id')->map(fn($id) => (string)$id)->toJson() }},
                                            toggleAllBranches(checked) {
                                                if (checked) {
                                                    this.branchIds.forEach(id => {
                                                        if (!selectedBranches.includes(String(id))) selectedBranches.push(String(id));
                                                    });
                                                } else {
                                                    let bIds = this.branchIds.map(String);
                                                    selectedBranches = selectedBranches.filter(id => !bIds.includes(String(id)));
                                                }
                                            }
                                         }">
                                        
                                        <div class="flex items-center justify-between border-b border-base-300 pb-2 mb-3">
                                            <span class="font-medium text-sm">{{ $company->name }} ({{ $company->code }}) - Cabang</span>
                                            <label class="flex items-center gap-2 cursor-pointer text-xs">
                                                <input type="checkbox" 
                                                       :checked="branchIds.length > 0 && branchIds.every(b => selectedBranches.includes(String(b)))"
                                                       @change="toggleAllBranches($el.checked)"
                                                       class="checkbox checkbox-xs checkbox-primary">
                                                <span>{{ __('Pilih Semua Cabang') }}</span>
                                            </label>
                                        </div>

                                        <div class="space-y-3">
                                            @foreach($company->branches as $branch)
                                                @php
                                                    $isPusat = (bool) $branch->is_pusat;
                                                    $defaultSelected = old('branch_unit_kerjas.' . $branch->id, $branchUnitKerjasMap[$branch->id] ?? (in_array((string)$branch->id, array_map('strval', $userBranchIds)) ? $userUnitKerjaIds : []));
                                                    $defaultSelectedJson = json_encode(array_values(array_map('strval', $defaultSelected ?: [])));
                                                @endphp
                                                <div class="border border-base-200 rounded-xl p-3 bg-base-100 shadow-xs transition-all duration-200 hover:border-base-300"
                                                     x-data="{
                                                        branchId: '{{ $branch->id }}',
                                                        isPusat: {{ $isPusat ? 'true' : 'false' }},
                                                        subOpen: false,
                                                        subSearch: '',
                                                        selectedItems: {{ $defaultSelectedJson }},
                                                        itemsList: [
                                                            @foreach($unitKerjas as $uk)
                                                                { id: '{{ $uk->id }}', name: '{{ addslashes($uk->kode_unit_kerja) }} - {{ addslashes($uk->nama_unit_kerja) }}' },
                                                            @endforeach
                                                        ],
                                                        get filteredItems() {
                                                            if (!this.subSearch) return this.itemsList;
                                                            return this.itemsList.filter(item => item.name.toLowerCase().includes(this.subSearch.toLowerCase()));
                                                        },
                                                        toggleItem(id) {
                                                            id = String(id);
                                                            if (this.selectedItems.includes(id)) {
                                                                this.selectedItems = this.selectedItems.filter(i => i !== id);
                                                            } else {
                                                                this.selectedItems.push(id);
                                                            }
                                                        }
                                                     }">

                                                    {{-- Branch Checkbox Row --}}
                                                    <div class="flex items-center justify-between">
                                                        <label class="flex items-center gap-3 cursor-pointer select-none flex-1">
                                                            <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}"
                                                                   x-model="selectedBranches"
                                                                   class="checkbox checkbox-sm {{ $isPusat ? 'checkbox-primary' : 'checkbox-secondary' }}">
                                                            <div class="flex flex-col">
                                                                <span class="text-sm font-semibold text-base-content">{{ $branch->name }}</span>
                                                                <span class="text-xs text-base-content/60">
                                                                    @if($isPusat)
                                                                        {{ __('Kantor Pusat Perusahaan') }}
                                                                    @else
                                                                        {{ __('Cabang Perusahaan') }} ({{ $branch->code ?? '—' }})
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        </label>
                                                        @if($isPusat)
                                                            <span class="badge badge-sm badge-primary font-medium">{{ __('Pusat') }}</span>
                                                        @else
                                                            <span class="badge badge-sm badge-ghost text-xs">{{ $branch->code ?? 'Cabang' }}</span>
                                                        @endif
                                                    </div>

                                                    {{-- Sub-Picker: Appears when Branch is Checked --}}
                                                    <div x-show="selectedBranches.includes(branchId) && role !== 'direktur'" 
                                                         x-transition:enter="transition ease-out duration-200"
                                                         x-transition:enter-start="opacity-0 -translate-y-1"
                                                         x-transition:enter-end="opacity-100 translate-y-0"
                                                         class="mt-3 pt-3 border-t border-base-200">

                                                        <div class="flex items-center justify-between mb-1.5">
                                                            <label class="text-xs font-semibold text-primary flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                                <span>{{ __('Unit Kerja di') }} {{ $branch->name }} <span class="text-error">*</span></span>
                                                            </label>
                                                            <span class="text-[11px] text-base-content/50" x-text="selectedItems.length + ' {{ __('terpilih') }}'"></span>
                                                        </div>

                                                        {{-- Hidden Inputs for form submission --}}
                                                        <template x-for="id in selectedItems" :key="id">
                                                            <input type="hidden" name="branch_unit_kerjas[{{ $branch->id }}][]" :value="id">
                                                        </template>

                                                        {{-- Dropdown trigger --}}
                                                        <div class="relative" @click.away="subOpen = false">
                                                            <div class="input input-sm input-bordered w-full flex items-center justify-between cursor-pointer bg-base-100"
                                                                 @click="subOpen = !subOpen">
                                                                <span class="text-xs truncate" x-text="selectedItems.length > 0 ? selectedItems.length + ' {{ __('Unit Kerja Terpilih') }}' : '{{ __('-- Pilih Unit Kerja --') }}'"></span>
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-50 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                                            </div>

                                                            {{-- Searchable Dropdown Menu --}}
                                                            <div x-show="subOpen" 
                                                                 x-transition
                                                                 class="absolute z-30 mt-1 w-full bg-base-100 border border-base-300 rounded-lg shadow-xl flex flex-col">
                                                                <div class="p-2 border-b border-base-200">
                                                                    <input type="text" x-model="subSearch" class="input input-xs input-bordered w-full" placeholder="{{ __('Cari unit kerja...') }}">
                                                                </div>
                                                                <div class="max-h-48 overflow-y-auto p-1">
                                                                    <template x-if="filteredItems.length === 0">
                                                                        <div class="p-2 text-xs text-base-content/60 text-center">{{ __('Tidak ditemukan') }}</div>
                                                                    </template>
                                                                    <template x-for="item in filteredItems" :key="item.id">
                                                                        <label class="p-2 hover:bg-base-200/80 rounded cursor-pointer text-xs flex items-center gap-2.5 transition">
                                                                            <input type="checkbox" :checked="selectedItems.includes(item.id)" @change="toggleItem(item.id)" class="checkbox checkbox-xs {{ $isPusat ? 'checkbox-primary' : 'checkbox-secondary' }}">
                                                                            <span x-text="item.name" class="font-medium text-base-content"></span>
                                                                        </label>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Selected Badges --}}
                                                        <div class="flex flex-wrap gap-1.5 mt-2" x-show="selectedItems.length > 0">
                                                            <template x-for="id in selectedItems" :key="id">
                                                                <span class="badge badge-sm {{ $isPusat ? 'badge-primary' : 'badge-secondary' }} badge-outline gap-1 text-[11px] py-1">
                                                                    <span x-text="itemsList.find(i => i.id === id)?.name"></span>
                                                                    <button type="button" @click.stop="toggleItem(id)" class="hover:text-error text-xs font-bold leading-none">×</button>
                                                                </span>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
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
