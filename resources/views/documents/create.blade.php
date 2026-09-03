<x-app-layout>
    <x-slot name="header">{{ __('Buat Dokumen') }}</x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto w-full px-0">

            {{-- Template banner when creating from template --}}
            @if($selectedTemplate)
            <div class="alert alert-info mb-4 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                <div>
                    <div class="font-semibold">{{ __('Menggunakan template:') }} {{ $selectedTemplate->title }}</div>
                    <div class="text-xs opacity-80">{{ __('Tipe dokumen dan nomor akan diisi otomatis. File template akan disalin ke dokumen baru.') }}</div>
                </div>
                <a href="{{ route('documents.choose') }}" class="btn btn-ghost btn-sm">{{ __('Ganti') }}</a>
            </div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Hidden template_id --}}
                        @if($selectedTemplate)
                            <input type="hidden" name="template_id" value="{{ $selectedTemplate->id }}">
                        @endif

                        {{-- Document Type --}}
                        <div class="form-control w-full mb-4">
                            <label for="document_type_id" class="label">
                                <span class="label-text font-medium">{{ __('Tipe Dokumen') }}</span>
                            </label>
                            @if($selectedTemplate)
                                {{-- Auto-filled from template — disabled display + hidden input --}}
                                <input type="text"
                                       value="{{ $selectedTemplate->documentType->code }} - {{ $selectedTemplate->documentType->name }}"
                                       class="input input-bordered w-full bg-base-200 cursor-not-allowed" disabled>
                                <input type="hidden" name="document_type_id" id="document_type_id" value="{{ $selectedTemplate->document_type_id }}">
                                <p class="text-xs text-info mt-1">{{ __('Tipe dokumen ditentukan oleh template.') }}</p>
                            @else
                            <div x-data="{
                                    search: '',
                                    open: false,
                                    selectedId: '{{ old('document_type_id') }}',
                                    options: [
                                        @foreach($documentTypes as $type)
                                            { id: '{{ $type->id }}', code: '{{ strtoupper($type->code) }}', label: '{{ $type->code }} - {{ $type->name }}' },
                                        @endforeach
                                    ],
                                    init() {
                                        window.docTypeMap = {
                                            @foreach($documentTypes as $type)
                                                '{{ $type->id }}': { code: '{{ strtoupper($type->code) }}' },
                                            @endforeach
                                        };
                                    },
                                    get filteredOptions() {
                                        if (this.search === '') return this.options;
                                        const searchLower = this.search.toLowerCase();
                                        return this.options.filter(opt => opt.label.toLowerCase().includes(searchLower));
                                    },
                                    get selectedLabel() {
                                        let selected = this.options.find(opt => opt.id == this.selectedId);
                                        return selected ? selected.label : '{{ __('Pilih tipe dokumen...') }}';
                                    },
                                    selectOption(id) {
                                        this.selectedId = id;
                                        this.open = false;
                                        this.search = '';
                                        $nextTick(() => {
                                            document.getElementById('document_type_id').dispatchEvent(new Event('change'));
                                        });
                                    }
                                }"
                                class="relative w-full"
                                @click.away="open = false"
                            >
                                <!-- Hidden input for form submission and JS events -->
                                <input type="hidden" name="document_type_id" id="document_type_id" :value="selectedId">

                                <!-- Trigger button -->
                                <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" class="select select-bordered w-full flex items-center justify-between font-normal" :class="{'!outline-none !ring-2 !ring-primary/20 !border-primary': open}">
                                    <span x-text="selectedLabel" :class="{'text-base-content/50': !selectedId}"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <!-- Dropdown menu -->
                                <div x-show="open" style="display: none;" class="absolute z-10 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-lg">
                                    <div class="p-2 border-b border-base-300">
                                        <input x-ref="searchInput" type="text" x-model="search" class="input input-sm input-bordered w-full" placeholder="{{ __('Cari tipe dokumen...') }}" @keydown.enter.prevent="">
                                    </div>
                                    <ul class="max-h-60 overflow-y-auto p-1">
                                        <template x-for="option in filteredOptions" :key="option.id">
                                            <li>
                                                <button type="button" @click="selectOption(option.id)" class="w-full text-left px-3 py-2 rounded-lg hover:bg-base-200 transition-colors" :class="{'bg-base-200 font-medium': selectedId == option.id}">
                                                    <span x-text="option.label"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <li x-show="filteredOptions.length === 0" class="text-center py-3 text-base-content/50 text-sm">
                                            {{ __('Tipe dokumen tidak ditemukan.') }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @endif
                            @error('document_type_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Format Choice / Format Dokumen --}}
                        @php $currentFormatChoice = old('format_choice', 'baru'); @endphp
                        <div class="form-control w-full mb-5" x-data="{ format: '{{ $currentFormatChoice }}' }">
                            <label class="label">
                                <span class="label-text font-medium">{{ __('Format Penomoran Dokumen') }}</span>
                            </label>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <!-- Option 1: Format Baru (Default) -->
                                <label class="border rounded-xl p-3.5 flex items-start gap-3 cursor-pointer transition-all hover:bg-base-200/60"
                                       :class="format === 'baru' ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-base-300 bg-base-100'">
                                    <input type="radio" name="format_choice" value="baru" x-model="format"
                                           class="radio radio-primary radio-sm mt-0.5"
                                           @change="$nextTick(() => window.onFormatChoiceChange('baru'))">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-sm">{{ __('Format Baru') }}</span>
                                            <span class="badge badge-primary badge-sm">{{ __('Otomatis') }}</span>
                                        </div>
                                        <p class="text-xs text-base-content/60 mt-1">
                                            {{ __('Penomoran standar baru terpadu dengan kode divisi.') }}
                                        </p>
                                    </div>
                                </label>

                                <!-- Option 2: Format Lama -->
                                <label class="border rounded-xl p-3.5 flex items-start gap-3 cursor-pointer transition-all hover:bg-base-200/60"
                                       :class="format === 'lama' ? 'border-secondary bg-secondary/5 ring-1 ring-secondary' : 'border-base-300 bg-base-100'">
                                    <input type="radio" name="format_choice" value="lama" x-model="format"
                                           class="radio radio-secondary radio-sm mt-0.5"
                                           @change="$nextTick(() => window.onFormatChoiceChange('lama'))">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-sm">{{ __('Format Lama') }}</span>
                                            <span class="badge badge-ghost badge-sm">{{ __('Format Lama') }}</span>
                                        </div>
                                        <p class="text-xs text-base-content/60 mt-1">
                                            {{ __('Penomoran menggunakan format terdahulu (khusus SOP menggunakan unit kerja).') }}
                                        </p>
                                    </div>
                                </label>
                            </div>

                            @error('format_choice') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Document Number --}}
                        <div class="form-control w-full mb-4">
                            <label for="document_number_field" class="label">
                                <span class="label-text font-medium">{{ __('Nomor Dokumen') }}</span>
                                <span id="document-number-hint" class="label-text-alt text-base-content/50">{{ __('Preview otomatis') }}</span>
                            </label>
                            <input type="text" id="document_number_field"
                                   value="{{ old('document_number', $initialDocumentNumber ?? __('Pilih tipe dokumen dahulu...')) }}"
                                   class="input input-bordered w-full font-mono bg-base-200" disabled>
                            @error('document_number') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-base-content/50 mt-1">
                                {{ __('Nomor final dihitung ulang saat disimpan — preview ini hanya perkiraan.') }}
                            </p>
                        </div>

                        {{-- Company & Branch --}}
                        @if(auth()->user()->isAdmin() || auth()->user()->isDirector())
                            @php
                                $defaultCompanyIds = old('company_ids', ($activeBranch ? [$activeBranch->company_id] : ($companies->first() ? [$companies->first()->id] : [])));
                                $defaultBranchIds = old('branch_ids', ($activeBranch ? [$activeBranch->id] : []));
                                if (old('branch_id') && !in_array(old('branch_id'), $defaultBranchIds)) {
                                    $defaultBranchIds[] = old('branch_id');
                                }
                            @endphp

                            <div class="mb-5"
                                 x-data="{
                                     selectedCompanies: {{ json_encode($defaultCompanyIds) }}.map(String),
                                     selectedBranches: {{ json_encode($defaultBranchIds) }}.map(String),
                                     updateBranch() {
                                         let el = document.getElementById('branch_id');
                                         if (el) {
                                             let primary = this.selectedBranches.length > 0 ? this.selectedBranches[0] : '';
                                             if (el.value !== primary) {
                                                 el.value = primary;
                                                 el.dispatchEvent(new Event('change'));
                                             }
                                         }
                                     },
                                     init() {
                                         this.$watch('selectedBranches', () => {
                                             this.$nextTick(() => this.updateBranch());
                                         });
                                     }
                                 }">
                                
                                <p class="text-xs text-base-content/60 mb-3">{{ __('Pilih perusahaan yang dapat diakses user, lalu centang cabang-cabang yang di-assign.') }}</p>

                                <!-- Hidden branch_id field to connect with next-number preview & form submission -->
                                <input type="hidden" name="branch_id" id="branch_id" :value="selectedBranches.length > 0 ? selectedBranches[0] : ''" value="{{ $defaultBranchIds[0] ?? '' }}">

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
                                <p class="text-xs text-base-content/50 mt-2">{{ __('Kode cabang yang dipilih pertama akan menjadi dasar penomoran dokumen.') }}</p>
                                @error('branch_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                                @error('branch_ids') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="form-control w-full mb-4">
                                <label for="branch_id" class="label">
                                    <span class="label-text font-medium">{{ __('Cabang (Branch)') }}</span>
                                </label>
                                <input type="text" value="{{ $activeBranch ? $activeBranch->company?->name . ' — ' . $activeBranch->name . ' (' . $activeBranch->effective_code . ')' : '—' }}" class="input input-bordered w-full bg-base-200" disabled>
                                <input type="hidden" name="branch_id" id="branch_id" value="{{ $activeBranch?->id }}">
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Sesuai cabang aktif yang dipilih di switcher atas.') }}</p>
                                @error('branch_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        {{-- Title --}}
                        <div class="form-control w-full mb-4">
                            <label for="title" class="label">
                                <span class="label-text font-medium">{{ __('Judul') }}</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title', $selectedTemplate?->title) }}" class="input input-bordered w-full" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Division --}}
                        <div id="container-division" class="form-control w-full mb-4">
                            <label for="division_id" class="label">
                                <span class="label-text font-medium">{{ __('Divisi') }}</span>
                            </label>
                            @if(auth()->user()->isAdmin() || auth()->user()->isDirector())
                                <select name="division_id" id="division_id" class="select select-bordered w-full" required>
                                    <option value="">{{ __('Pilih divisi...') }}</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>
                                            {{ $div->code }} - {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Pilih divisi dokumen.') }}</p>
                            @else
                                @php($myDivision = auth()->user()->division)
                                <input type="text" value="{{ $myDivision ? $myDivision->code . ' - ' . $myDivision->name : '—' }}" class="input input-bordered w-full bg-base-200" disabled>
                                <input type="hidden" name="division_id" value="{{ auth()->user()->division_id }}">
                                <p class="text-xs text-base-content/50 mt-1">{{ __('Otomatis sesuai divisi akun kamu.') }}</p>
                            @endif
                            @error('division_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Unit Kerja (Khusus SOP) --}}
                        <div id="container-unit-kerja" class="form-control w-full mb-4" style="display: none;">
                            <label for="unit_kerja_id" class="label">
                                <span class="label-text font-medium">{{ __('Unit Kerja') }} <span class="text-error">*</span></span>
                            </label>
                            <select name="unit_kerja_id" id="unit_kerja_id" class="select select-bordered w-full">
                                <option value="">{{ __('Pilih unit kerja...') }}</option>
                                @foreach($unitKerjas as $uk)
                                    <option value="{{ $uk->id }}" data-branch-id="{{ $uk->cabang_id }}" {{ old('unit_kerja_id') == $uk->id ? 'selected' : '' }}>
                                        {{ $uk->kode_unit_kerja }} - {{ $uk->nama_unit_kerja }} ({{ $uk->cabang?->name }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-base-content/50 mt-1">{{ __('Unit kerja cabang untuk penomoran dokumen SOP.') }}</p>
                            @error('unit_kerja_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="divider"></div>

                        {{-- Expiration --}}
                        <div class="bg-base-200/50 p-4 rounded-xl border border-base-300 mb-4">
                            <div class="flex items-center justify-between mb-3">
                                <label class="font-medium text-sm text-base-content flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ __('Masa Berlaku & Tanggal Kedaluwarsa (Opsional)') }}
                                </label>
                                <button type="button" id="btn-clear-expiration" class="btn btn-ghost btn-xs text-base-content/60 hover:text-error">
                                    {{ __('Reset') }}
                                </button>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Input Hari -->
                                <div class="form-control w-full">
                                    <label for="expiration_days" class="label py-1">
                                        <span class="label-text text-xs font-medium text-base-content/70">{{ __('Masa Berlaku (Hari)') }}</span>
                                    </label>
                                    <div class="relative">
                                        <input type="number" id="expiration_days" min="1" step="1"
                                               placeholder="{{ __('Contoh: 30') }}"
                                               class="input input-bordered w-full pr-14">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-medium text-base-content/50 pointer-events-none">
                                            {{ __('Hari') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Input Tanggal -->
                                <div class="form-control w-full">
                                    <label for="expiration_date" class="label py-1">
                                        <span class="label-text text-xs font-medium text-base-content/70">{{ __('Tanggal Kedaluwarsa') }}</span>
                                    </label>
                                    <input type="date" name="expiration_date" id="expiration_date"
                                           value="{{ old('expiration_date') }}"
                                           min="{{ date('Y-m-d') }}"
                                           class="input input-bordered w-full">
                                </div>
                            </div>

                            <!-- Quick Presets -->
                            <div class="flex flex-wrap items-center gap-1.5 mt-3">
                                <span class="text-xs text-base-content/60 mr-1">{{ __('Pintasan:') }}</span>
                                <button type="button" class="btn btn-xs btn-outline btn-primary rounded-full font-normal expiration-preset-btn" data-days="30">+30 {{ __('Hari') }}</button>
                                <button type="button" class="btn btn-xs btn-outline btn-primary rounded-full font-normal expiration-preset-btn" data-days="90">+90 {{ __('Hari (3 Bln)') }}</button>
                                <button type="button" class="btn btn-xs btn-outline btn-primary rounded-full font-normal expiration-preset-btn" data-days="180">+180 {{ __('Hari (6 Bln)') }}</button>
                                <button type="button" class="btn btn-xs btn-outline btn-primary rounded-full font-normal expiration-preset-btn" data-days="365">+1 {{ __('Tahun') }}</button>
                                <button type="button" class="btn btn-xs btn-outline btn-primary rounded-full font-normal expiration-preset-btn" data-days="730">+2 {{ __('Tahun') }}</button>
                            </div>

                            <!-- Info / Display Result -->
                            <div id="expiration_info_display" class="text-xs font-medium text-primary mt-2.5 hidden flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span id="expiration_info_text"></span>
                            </div>

                            <p class="text-xs text-base-content/50 mt-2">
                                {{ __('Jika dikosongkan, dokumen akan otomatis kedaluwarsa sesuai masa retensi default yang diatur admin.') }}
                            </p>
                            @error('expiration_date') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Upload section — hidden when using template --}}
                        @if(!$selectedTemplate)
                        <div class="form-control mb-2">
                            <label class="label cursor-pointer justify-start gap-3">
                                <input type="checkbox" name="is_upload" id="is_upload" value="1"
                                       class="checkbox checkbox-sm checkbox-primary"
                                       {{ old('is_upload') ? 'checked' : '' }}>
                                <span class="label-text font-medium">{{ __('Unggah dokumen yang sudah ada (bukan ditulis di editor)') }}</span>
                            </label>
                        </div>

                        <div id="upload-field" class="form-control w-full mb-4 {{ old('is_upload') ? '' : 'hidden' }}">
                            <label for="file" class="label">
                                <span class="label-text font-medium">{{ __('Berkas Dokumen') }}</span>
                            </label>
                            <input type="file" name="file" id="file" accept=".pdf,.docx"
                                   class="file-input file-input-bordered w-full">
                            <p class="text-xs text-base-content/50 mt-1">{{ __('Hanya PDF atau DOCX, maksimal 10MB.') }}</p>
                            @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-info mt-1">
                                {{ __('Setelah berkas dipilih, isi nomor dokumen di atas sesuai nomor resmi pada berkas fisik.') }}
                            </p>
                        </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                            <a href="{{ route('documents.choose') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                {{ __('Buat Dokumen') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var typeSelect = document.getElementById('document_type_id');
            var numberField = document.getElementById('document_number_field');
            var numberHint = document.getElementById('document-number-hint');
            var uploadCheckbox = document.getElementById('is_upload');
            var uploadField = document.getElementById('upload-field');
            var fileInput = document.getElementById('file');

            var lastPreview = numberField.value;

            var branchSelect = document.getElementById('branch_id');
            var divisionSelect = document.getElementById('division_id');
            var unitKerjaSelect = document.getElementById('unit_kerja_id');
            var containerUnitKerja = document.getElementById('container-unit-kerja');
            var containerDivision = document.getElementById('container-division');

            function isCurrentTypeSop() {
                var typeId = typeSelect.value;
                if (!typeId) return false;
                @if($selectedTemplate)
                    return {{ strtoupper($selectedTemplate->documentType->code) === 'SOP' ? 'true' : 'false' }};
                @else
                    if (window.docTypeMap && window.docTypeMap[typeId]) {
                        return window.docTypeMap[typeId].code === 'SOP';
                    }
                    return false;
                @endif
            }

            function getFormatChoice() {
                var checkedRadio = document.querySelector('input[name="format_choice"]:checked');
                return checkedRadio ? checkedRadio.value : 'baru';
            }

            window.onFormatChoiceChange = function (val) {
                updateTypeVisibility();
                fetchPreview();
            };

            function updateTypeVisibility() {
                var isSop = isCurrentTypeSop();
                var format = getFormatChoice();
                
                if (containerUnitKerja && containerDivision) {
                    if (format === 'lama') {
                        containerDivision.style.display = 'none';
                        if (divisionSelect && divisionSelect.tagName === 'SELECT') {
                            divisionSelect.removeAttribute('required');
                        }

                        if (isSop) {
                            containerUnitKerja.style.display = 'block';
                            if (unitKerjaSelect) {
                                unitKerjaSelect.setAttribute('required', 'required');
                            }
                        } else {
                            containerUnitKerja.style.display = 'none';
                            if (unitKerjaSelect) {
                                unitKerjaSelect.removeAttribute('required');
                            }
                        }
                    } else {
                        // Format Baru
                        containerUnitKerja.style.display = 'none';
                        if (unitKerjaSelect) {
                            unitKerjaSelect.removeAttribute('required');
                        }

                        containerDivision.style.display = 'block';
                        if (divisionSelect && divisionSelect.tagName === 'SELECT') {
                            divisionSelect.setAttribute('required', 'required');
                        }
                    }
                    
                    if (!uploadCheckbox || !uploadCheckbox.checked) {
                        numberField.disabled = true;
                        numberField.removeAttribute('name');
                        numberField.classList.add('bg-base-200');
                        numberField.value = lastPreview || @json(__('Pilih tipe dokumen dahulu...'));
                        numberHint.textContent = @json(__('Preview otomatis'));
                    } else {
                        numberField.disabled = false;
                        numberField.name = 'document_number';
                        numberField.classList.remove('bg-base-200');
                        numberField.value = lastPreview || '';
                        numberHint.textContent = @json(__('Manual input based on file'));
                    }
                }
            }

            function filterUnitKerjasByBranch() {
                if (!unitKerjaSelect) return;
                var currentBranchId = branchSelect ? branchSelect.value : '';

                var options = unitKerjaSelect.querySelectorAll('option');
                options.forEach(function(opt) {
                    if (!opt.value) return; // Skip placeholder
                    var optBranch = opt.getAttribute('data-branch-id');
                    var isVisible = !currentBranchId || !optBranch || optBranch === currentBranchId;
                    opt.hidden = !isVisible;
                    if (!isVisible && opt.selected) {
                        unitKerjaSelect.value = '';
                    }
                });
            }

            function fetchPreview() {
                var typeId = typeSelect.value;
                if (!typeId) {
                    if (getFormatChoice() === 'baru') {
                        numberField.value = @json(__('Pilih tipe dokumen dahulu...'));
                    }
                    lastPreview = '';
                    return;
                }

                updateTypeVisibility();

                var branchId = branchSelect ? branchSelect.value : '';
                var divisionId = divisionSelect ? divisionSelect.value : '';
                var unitKerjaId = unitKerjaSelect ? unitKerjaSelect.value : '';
                var format = getFormatChoice();

                var url = '{{ route('documents.next-number') }}?document_type_id=' + encodeURIComponent(typeId) + '&format_choice=' + encodeURIComponent(format);
                if (branchId) {
                    url += '&branch_id=' + encodeURIComponent(branchId);
                }
                if (divisionId) {
                    url += '&division_id=' + encodeURIComponent(divisionId);
                }
                if (unitKerjaId) {
                    url += '&unit_kerja_id=' + encodeURIComponent(unitKerjaId);
                }

                fetch(url, {
                    headers: { 'Accept': 'application/json' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        lastPreview = data.number;
                        if (numberField.disabled) {
                            numberField.value = data.number;
                        }
                    })
                    .catch(function () {
                        if (numberField.disabled) {
                            numberField.value = @json(__('Failed to load preview'));
                        }
                    });
            }

            function setUploadMode(isFileChosen) {
                if (isFileChosen) {
                    numberField.disabled = false;
                    numberField.name = 'document_number';
                    numberField.classList.remove('bg-base-200');
                    numberField.value = lastPreview || '';
                    numberHint.textContent = @json(__('Manual input based on file'));
                } else {
                    numberField.disabled = true;
                    numberField.removeAttribute('name');
                    numberField.classList.add('bg-base-200');
                    numberField.value = lastPreview || @json(__('Pilih tipe dokumen dahulu...'));
                    numberHint.textContent = @json(__('Preview otomatis'));
                }
            }

            typeSelect.addEventListener('change', function () {
                updateTypeVisibility();
                fetchPreview();
            });
            document.querySelectorAll('input[name="format_choice"]').forEach(function (radio) {
                radio.addEventListener('change', function () {
                    updateTypeVisibility();
                    fetchPreview();
                });
            });
            if (branchSelect) {
                branchSelect.addEventListener('change', function () {
                    filterUnitKerjasByBranch();
                    fetchPreview();
                });
            }
            if (divisionSelect) {
                divisionSelect.addEventListener('change', fetchPreview);
            }
            if (unitKerjaSelect) {
                unitKerjaSelect.addEventListener('change', fetchPreview);
            }
            filterUnitKerjasByBranch();
            updateTypeVisibility();

            if (uploadCheckbox) {
                uploadCheckbox.addEventListener('change', function () {
                    if (uploadCheckbox.checked) {
                        uploadField.classList.remove('hidden');
                    } else {
                        uploadField.classList.add('hidden');
                        fileInput.value = '';
                        setUploadMode(false);
                    }
                });
            }

            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    setUploadMode(fileInput.files.length > 0);
                });
            }

            // Restore state kalau form reload akibat error validasi, or auto-trigger for template
            var defaultPlaceholder = @json(__('Pilih tipe dokumen dahulu...'));
            if (typeSelect.value && (!numberField.value || numberField.value === defaultPlaceholder)) {
                fetchPreview();
            }
            if (uploadCheckbox && uploadCheckbox.checked) {
                uploadField.classList.remove('hidden');
            }

            // Expiration bidirectional synchronization
            var expirationDateInput = document.getElementById('expiration_date');
            var expirationDaysInput = document.getElementById('expiration_days');
            var expirationInfoDisplay = document.getElementById('expiration_info_display');
            var expirationInfoText = document.getElementById('expiration_info_text');
            var clearExpirationBtn = document.getElementById('btn-clear-expiration');
            var presetButtons = document.querySelectorAll('.expiration-preset-btn');

            function getTodayDate() {
                var now = new Date();
                return new Date(now.getFullYear(), now.getMonth(), now.getDate());
            }

            function formatDateString(d) {
                var year = d.getFullYear();
                var month = String(d.getMonth() + 1).padStart(2, '0');
                var day = String(d.getDate()).padStart(2, '0');
                return year + '-' + month + '-' + day;
            }

            function updateDisplayInfo(targetDate, daysCount) {
                if (!targetDate) {
                    expirationInfoDisplay.classList.add('hidden');
                    expirationInfoText.textContent = '';
                    return;
                }

                var locale = '{{ app()->getLocale() }}';
                var dayFormatter = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
                var formattedReadable = dayFormatter.format(targetDate);

                var daysText = daysCount !== undefined ? daysCount : Math.round((targetDate.getTime() - getTodayDate().getTime()) / (1000 * 60 * 60 * 24));
                
                if (daysText === 0) {
                    expirationInfoText.textContent = @json(__('Kedaluwarsa hari ini: ')) + formattedReadable;
                } else if (daysText > 0) {
                    expirationInfoText.textContent = @json(__('Kedaluwarsa pada: ')) + formattedReadable + ' (' + daysText + ' ' + @json(__('hari lagi')) + ')';
                } else {
                    expirationInfoText.textContent = @json(__('Kedaluwarsa pada: ')) + formattedReadable + ' (' + Math.abs(daysText) + ' ' + @json(__('hari lalu')) + ')';
                }
                expirationInfoDisplay.classList.remove('hidden');
            }

            function onDaysChanged() {
                var daysVal = parseInt(expirationDaysInput.value, 10);
                if (isNaN(daysVal) || daysVal <= 0) {
                    expirationDateInput.value = '';
                    updateDisplayInfo(null);
                    return;
                }

                var today = getTodayDate();
                var targetDate = new Date(today.getFullYear(), today.getMonth(), today.getDate() + daysVal);
                expirationDateInput.value = formatDateString(targetDate);
                updateDisplayInfo(targetDate, daysVal);
            }

            function onDateChanged() {
                var dateVal = expirationDateInput.value;
                if (!dateVal) {
                    expirationDaysInput.value = '';
                    updateDisplayInfo(null);
                    return;
                }

                var parts = dateVal.split('-');
                if (parts.length === 3) {
                    var selectedDate = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
                    var today = getTodayDate();
                    var diffMs = selectedDate.getTime() - today.getTime();
                    var diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

                    if (diffDays > 0) {
                        expirationDaysInput.value = diffDays;
                    } else {
                        expirationDaysInput.value = '';
                    }
                    updateDisplayInfo(selectedDate, diffDays);
                }
            }

            function clearExpiration() {
                expirationDaysInput.value = '';
                expirationDateInput.value = '';
                updateDisplayInfo(null);
            }

            expirationDaysInput.addEventListener('input', onDaysChanged);
            expirationDateInput.addEventListener('change', onDateChanged);
            expirationDateInput.addEventListener('input', onDateChanged);

            if (clearExpirationBtn) {
                clearExpirationBtn.addEventListener('click', clearExpiration);
            }

            presetButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var days = parseInt(this.getAttribute('data-days'), 10);
                    if (days > 0) {
                        expirationDaysInput.value = days;
                        onDaysChanged();
                    }
                });
            });

            // Initialize on load if expiration_date is already present (e.g. from old input)
            if (expirationDateInput.value) {
                onDateChanged();
            }
        })();
    </script>
</x-app-layout>
