<x-app-layout>
    <x-slot name="header">{{ __('Edit Unit Kerja') }}</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.unit-kerja.update', $unitKerja) }}">
                    @csrf
                    @method('PUT')

                    <div x-data="{
                        selectedCompanyId: '{{ old('company_id', $selectedCompanyId ?? '') }}',
                        selectedCabangId: '{{ old('cabang_id', $selectedCabangId ?? $unitKerja->cabang_id) }}',

                        companySearch: '',
                        companyOpen: false,
                        companies: {{ $companies->map(fn($c) => [
                            'id' => (string) $c->id,
                            'name' => $c->name,
                            'code' => $c->code,
                            'label' => $c->name . ' (' . $c->code . ')',
                            'branches' => $c->branches->map(fn($b) => [
                                'id' => (string) $b->id,
                                'name' => $b->name,
                                'code' => $b->effective_code,
                                'label' => $b->name . ' (' . $b->effective_code . ')',
                            ])->values()->all(),
                        ])->toJson() }},

                        cabangSearch: '',
                        cabangOpen: false,

                        get filteredCompanies() {
                            if (!this.companySearch.trim()) return this.companies;
                            const q = this.companySearch.toLowerCase();
                            return this.companies.filter(c => c.label.toLowerCase().includes(q));
                        },

                        get selectedCompany() {
                            return this.companies.find(c => c.id === this.selectedCompanyId) || null;
                        },

                        get companyLabel() {
                            return this.selectedCompany ? this.selectedCompany.label : '{{ __('Pilih Perusahaan...') }}';
                        },

                        get availableBranches() {
                            if (!this.selectedCompany) return [];
                            return this.selectedCompany.branches;
                        },

                        get filteredBranches() {
                            if (!this.cabangSearch.trim()) return this.availableBranches;
                            const q = this.cabangSearch.toLowerCase();
                            return this.availableBranches.filter(b => b.label.toLowerCase().includes(q));
                        },

                        get selectedBranch() {
                            if (!this.selectedCabangId) return null;
                            return this.availableBranches.find(b => b.id === this.selectedCabangId) || null;
                        },

                        get cabangLabel() {
                            if (!this.selectedCompanyId) {
                                return '{{ __('Pilih perusahaan terlebih dahulu...') }}';
                            }
                            return this.selectedBranch ? this.selectedBranch.label : '{{ __('Pilih Cabang...') }}';
                        },

                        selectCompany(id) {
                            if (this.selectedCompanyId !== id) {
                                this.selectedCompanyId = id;
                                this.selectedCabangId = ''; // Reset Cabang dropdown every time Perusahaan selection changes
                                this.cabangSearch = '';
                            }
                            this.companyOpen = false;
                            this.companySearch = '';
                        },

                        selectCabang(id) {
                            this.selectedCabangId = id;
                            this.cabangOpen = false;
                            this.cabangSearch = '';
                        }
                    }">
                        {{-- 1. Perusahaan (Company) Searchable Dropdown --}}
                        <div class="form-control w-full mb-4">
                            <label for="company_id" class="label">
                                <span class="label-text font-medium">{{ __('Perusahaan') }} <span class="text-error">*</span></span>
                            </label>

                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="company_id" id="company_id" :value="selectedCompanyId" required>

                            <div class="relative w-full" @click.outside="companyOpen = false">
                                <!-- Trigger Button -->
                                <button type="button"
                                        @click="companyOpen = !companyOpen; if(companyOpen) $nextTick(() => $refs.companySearchInput.focus())"
                                        class="select select-bordered w-full flex items-center justify-between font-normal"
                                        :class="{'!outline-none !ring-2 !ring-primary/20 !border-primary': companyOpen}">
                                    <span class="truncate" x-text="companyLabel" :class="{'text-base-content/50': !selectedCompanyId}"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown popup with Search/Autocomplete -->
                                <div x-show="companyOpen"
                                     style="display: none;"
                                     class="absolute z-20 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-base-300 bg-base-200/40">
                                        <div class="relative">
                                            <input x-ref="companySearchInput"
                                                   type="text"
                                                   x-model="companySearch"
                                                   class="input input-sm input-bordered w-full pl-8"
                                                   placeholder="{{ __('Cari perusahaan...') }}"
                                                   @keydown.enter.prevent="">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto p-1 text-sm">
                                        <template x-for="comp in filteredCompanies" :key="comp.id">
                                            <li>
                                                <button type="button"
                                                        @click="selectCompany(comp.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-base-200 transition-colors flex items-center justify-between"
                                                        :class="{'bg-primary/10 text-primary font-semibold': selectedCompanyId === comp.id}">
                                                    <span x-text="comp.label"></span>
                                                    <span class="text-xs opacity-60" x-text="comp.branches.length + ' cabang'"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <li x-show="filteredCompanies.length === 0" class="text-center py-4 text-base-content/50 text-xs">
                                            {{ __('Perusahaan tidak ditemukan.') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p class="text-xs text-base-content/50 mt-1">{{ __('Pilih perusahaan induk terlebih dahulu.') }}</p>
                            @error('company_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- 2. Cabang (Branch) Cascading Searchable Dropdown --}}
                        <div class="form-control w-full mb-4">
                            <label for="cabang_id" class="label">
                                <span class="label-text font-medium">{{ __('Cabang') }} <span class="text-error">*</span></span>
                            </label>

                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="cabang_id" id="cabang_id" :value="selectedCabangId" required>

                            <div class="relative w-full" @click.outside="cabangOpen = false">
                                <!-- Trigger Button (Disabled until Perusahaan is selected) -->
                                <button type="button"
                                        :disabled="!selectedCompanyId"
                                        @click="if(selectedCompanyId) { cabangOpen = !cabangOpen; if(cabangOpen) $nextTick(() => $refs.cabangSearchInput.focus()); }"
                                        class="select select-bordered w-full flex items-center justify-between font-normal"
                                        :class="{
                                            'bg-base-200 cursor-not-allowed opacity-60 text-base-content/50': !selectedCompanyId,
                                            '!outline-none !ring-2 !ring-primary/20 !border-primary': cabangOpen
                                        }">
                                    <span class="truncate" x-text="cabangLabel" :class="{'text-base-content/50': !selectedCabangId}"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown popup with Search/Autocomplete -->
                                <div x-show="cabangOpen && selectedCompanyId"
                                     style="display: none;"
                                     class="absolute z-20 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-xl overflow-hidden">
                                    <div class="p-2 border-b border-base-300 bg-base-200/40">
                                        <div class="relative">
                                            <input x-ref="cabangSearchInput"
                                                   type="text"
                                                   x-model="cabangSearch"
                                                   class="input input-sm input-bordered w-full pl-8"
                                                   placeholder="{{ __('Cari cabang...') }}"
                                                   @keydown.enter.prevent="">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto p-1 text-sm">
                                        <template x-for="branch in filteredBranches" :key="branch.id">
                                            <li>
                                                <button type="button"
                                                        @click="selectCabang(branch.id)"
                                                        class="w-full text-left px-3 py-2 rounded-lg hover:bg-base-200 transition-colors flex items-center justify-between"
                                                        :class="{'bg-primary/10 text-primary font-semibold': selectedCabangId === branch.id}">
                                                    <span x-text="branch.label"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <li x-show="availableBranches.length === 0" class="text-center py-4 text-base-content/50 text-xs">
                                            {{ __('Perusahaan ini belum memiliki cabang terdaftar.') }}
                                        </li>
                                        <li x-show="availableBranches.length > 0 && filteredBranches.length === 0" class="text-center py-4 text-base-content/50 text-xs">
                                            {{ __('Cabang tidak ditemukan.') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <p class="text-xs text-base-content/50 mt-1" x-text="!selectedCompanyId ? '{{ __('Pilih perusahaan untuk mengaktifkan pilihan cabang.') }}' : '{{ __('Hanya menampilkan cabang dari perusahaan yang dipilih.') }}'"></p>
                            @error('cabang_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- 3. Kode Unit Kerja --}}
                        <div class="form-control w-full mb-4">
                            <label for="kode_unit_kerja" class="label">
                                <span class="label-text font-medium">{{ __('Kode Unit Kerja') }} <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="kode_unit_kerja" id="kode_unit_kerja" value="{{ old('kode_unit_kerja', $unitKerja->kode_unit_kerja) }}" class="input input-bordered w-full uppercase font-mono" maxlength="50" required>
                            <p class="text-xs text-base-content/50 mt-1">{{ __('Kode angka/singkatan unit kerja untuk nomor surat SOP (contoh: 11).') }}</p>
                            @error('kode_unit_kerja') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- 4. Nama Unit Kerja --}}
                        <div class="form-control w-full mb-6">
                            <label for="nama_unit_kerja" class="label">
                                <span class="label-text font-medium">{{ __('Nama Unit Kerja') }} <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="nama_unit_kerja" id="nama_unit_kerja" value="{{ old('nama_unit_kerja', $unitKerja->nama_unit_kerja) }}" class="input input-bordered w-full" required>
                            @error('nama_unit_kerja') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t border-base-200">
                        <a href="{{ route('admin.unit-kerja.index', ['cabang_id' => $unitKerja->cabang_id]) }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Perbarui') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
