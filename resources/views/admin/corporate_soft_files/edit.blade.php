<x-app-layout>
    <x-slot name="header">{{ __('Edit Soft File Kop') }}</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-4 text-xs sm:text-sm text-base-content/60">
                <a href="{{ route('admin.corporate-soft-files.index') }}" class="hover:text-primary transition-colors">{{ __('Soft File Kop') }}</a>
                <span>/</span>
                <span class="text-base-content font-semibold">{{ __('Edit Soft File') }}</span>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="card-body p-6">
                    <h2 class="text-lg font-bold text-base-content mb-1">{{ __('Edit Soft File Kop') }}</h2>
                    <p class="text-xs text-base-content/60 mb-6">{{ __('Perbarui informasi, berkas pengganti, dan pengaturan hak akses perusahaan/cabang.') }}</p>

                    @if($errors->any())
                        <div class="alert alert-error mb-6 rounded-xl shadow-xs">
                            <ul class="list-disc list-inside text-xs">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.corporate-soft-files.update', $corporateSoftFile) }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Judul Soft File --}}
                        <div class="form-control">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('Nama / Judul Soft File') }} <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title', $corporateSoftFile->title) }}" required placeholder="{{ __('Contoh: Kop Surat Resmi PT Alpha Pusat') }}" class="input input-bordered w-full rounded-xl @error('title') input-error @enderror">
                        </div>

                        {{-- Deskripsi --}}
                        <div class="form-control">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('Deskripsi / Keterangan') }}</span>
                            </label>
                            <textarea name="description" rows="2" placeholder="{{ __('Keterangan singkat peruntukan soft file ini...') }}" class="textarea textarea-bordered w-full rounded-xl">{{ old('description', $corporateSoftFile->description) }}</textarea>
                        </div>

                        {{-- Locked File Information Card --}}
                        <div class="p-5 bg-base-200/50 rounded-2xl border border-base-300/80 space-y-3 shadow-2xs">
                            <div class="flex items-center justify-between gap-3 flex-wrap">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-primary/15 text-primary flex items-center justify-center font-extrabold text-xs shrink-0 shadow-xs">
                                        @if($corporateSoftFile->isPdf())
                                            <span class="text-error font-extrabold">PDF</span>
                                        @elseif($corporateSoftFile->isImage())
                                            <span class="text-warning font-extrabold">IMG</span>
                                        @else
                                            <span class="text-primary font-extrabold">DOCX</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs font-bold text-base-content uppercase tracking-wider block">{{ __('Berkas Soft File Terkunci') }}</span>
                                            <span class="badge badge-neutral badge-xs font-bold gap-1 px-1.5 py-0.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                                {{ __('Terkunci') }}
                                            </span>
                                            <span class="badge {{ $corporateSoftFile->isA4() ? 'badge-secondary' : 'badge-primary' }} badge-xs font-mono font-bold">
                                                {{ $corporateSoftFile->paper_size_label }}
                                            </span>
                                        </div>
                                        <span class="text-sm font-semibold text-base-content truncate block mt-0.5">{{ $corporateSoftFile->file_original_name }}</span>
                                        @if($corporateSoftFile->file_size)
                                            <span class="text-[11px] text-base-content/50 block">({{ number_format($corporateSoftFile->file_size / 1024, 1) }} KB)</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('admin.corporate-soft-files.preview', $corporateSoftFile) }}" target="_blank" class="btn btn-ghost btn-xs gap-1 rounded-lg border border-base-300 hover:bg-base-200" title="{{ __('Lihat Pratinjau Soft File') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        {{ __('Pratinjau') }}
                                    </a>
                                    <a href="{{ route('admin.corporate-soft-files.download', $corporateSoftFile) }}" class="btn btn-outline btn-primary btn-xs gap-1 rounded-lg" title="{{ __('Unduh Berkas Asli') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        {{ __('Unduh') }}
                                    </a>
                                </div>
                            </div>
                            <div class="p-3 bg-base-100 rounded-xl border border-base-300/60 text-xs text-base-content/70 flex items-start gap-2.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-info shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>{{ __('Berkas soft file kop surat ini terkunci untuk menjaga konsistensi dokumen yang sudah menggunakannya. Anda dapat memperbarui nama judul, deskripsi, format kertas dokumen, dan hak akses perusahaan/cabang di bawah ini.') }}</span>
                            </div>
                        </div>

                        {{-- Target Ukuran Kertas (Paper Size) --}}
                        <div class="form-control" x-data="{ paperSize: '{{ old('paper_size', $corporateSoftFile->paper_size ?? 'f4') }}' }">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('Target Ukuran Kertas Dokumen') }}</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border transition-all cursor-pointer select-none"
                                       :class="paperSize === 'f4' ? 'bg-primary/10 border-primary shadow-xs ring-1 ring-primary/30' : 'bg-base-100 border-base-300 hover:bg-base-200/50'">
                                    <input type="radio" name="paper_size" value="f4" x-model="paperSize" class="radio radio-primary radio-sm mt-0.5 shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-base-content leading-tight">{{ __('F4 / Folio (Standar)') }}</span>
                                            <span class="badge badge-neutral badge-xs font-mono font-semibold shrink-0">210 × 330 mm</span>
                                        </div>
                                        <p class="text-[11px] text-base-content/60 mt-1 leading-normal">{{ __('Ukuran kertas standar default untuk operasional korporat.') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3.5 rounded-2xl border transition-all cursor-pointer select-none"
                                       :class="paperSize === 'a4' ? 'bg-secondary/10 border-secondary shadow-xs ring-1 ring-secondary/30' : 'bg-base-100 border-base-300 hover:bg-base-200/50'">
                                    <input type="radio" name="paper_size" value="a4" x-model="paperSize" class="radio radio-secondary radio-sm mt-0.5 shrink-0">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <span class="text-xs sm:text-sm font-bold text-base-content leading-tight">{{ __('A4 (Khusus Cabang Cahaya Diagnostic Centre)') }}</span>
                                            <span class="badge badge-secondary badge-xs font-mono font-semibold shrink-0">210 × 297 mm</span>
                                        </div>
                                        <p class="text-[11px] text-base-content/60 mt-1 leading-normal">{{ __('Format ukuran standar A4 khusus untuk operasional dokumen cabang Cahaya Diagnostic Centre.') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="divider my-2">{{ __('Hak Akses & Scoping') }}</div>

                        {{-- Role Access --}}
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold">{{ __('Role yang Diizinkan Mengakses') }}</span>
                                <span class="label-text-alt text-base-content/50">{{ __('Kosongkan jika semua role diizinkan') }}</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-base-200/50 rounded-2xl border border-base-300">
                                @php
                                    $rolesList = [
                                        'staff' => __('Staff'),
                                        'head' => __('Head / Kepala Unit'),
                                        'direktur' => __('Direktur'),
                                    ];
                                    $currentRoles = old('allowed_roles', $corporateSoftFile->allowed_roles ?? []);
                                    if (!is_array($currentRoles)) $currentRoles = [];
                                    if (in_array('user', $currentRoles) && !in_array('staff', $currentRoles)) {
                                        $currentRoles[] = 'staff';
                                    }
                                @endphp
                                @foreach($rolesList as $roleKey => $roleLabel)
                                    <label class="label cursor-pointer justify-start gap-2.5 p-0">
                                        <input type="checkbox" name="allowed_roles[]" value="{{ $roleKey }}" class="checkbox checkbox-primary checkbox-sm rounded-md" {{ in_array($roleKey, $currentRoles) ? 'checked' : '' }}>
                                        <span class="label-text font-medium text-xs sm:text-sm">{{ $roleLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Company Access --}}
                        @php
                            $selectedCompanyIds = old('company_ids', $corporateSoftFile->companies->pluck('id')->all());
                            $isAllCompanies = old('is_all_companies', $corporateSoftFile->is_all_companies ? '1' : '0');
                        @endphp
                        <div x-data="{
                            allCompanies: '{{ $isAllCompanies }}',
                            searchCompany: '',
                            selectedCompanies: {{ json_encode(array_map('strval', $selectedCompanyIds)) }},
                            toggleCompany(id) {
                                id = String(id);
                                if (this.selectedCompanies.includes(id)) {
                                    this.selectedCompanies = this.selectedCompanies.filter(item => item !== id);
                                } else {
                                    this.selectedCompanies.push(id);
                                }
                            },
                            selectAllCompanies(ids) {
                                ids.forEach(id => {
                                    id = String(id);
                                    if (!this.selectedCompanies.includes(id)) {
                                        this.selectedCompanies.push(id);
                                    }
                                });
                            },
                            clearAllCompanies() {
                                this.selectedCompanies = [];
                            }
                        }" class="space-y-3">
                            <div class="form-control w-full">
                                <label class="label pb-1.5 flex items-center justify-between">
                                    <span class="label-text font-bold text-xs sm:text-sm text-base-content flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        {{ __('Akses Perusahaan (Company)') }}
                                    </span>
                                    <span class="text-[11px] font-semibold text-primary" x-show="allCompanies === '0'" x-text="selectedCompanies.length + ' Perusahaan Dipilih'"></span>
                                </label>
                                <select name="is_all_companies" x-model="allCompanies" class="select select-bordered select-sm w-full font-medium rounded-xl text-xs sm:text-sm bg-base-100 focus:border-primary">
                                    <option value="1">🌐 {{ __('Berlaku untuk Semua Perusahaan (Global)') }}</option>
                                    <option value="0">🏢 {{ __('Pilih Perusahaan Tertentu (Dropdown Spesifik)') }}</option>
                                </select>
                            </div>

                            <div x-show="allCompanies === '0'" x-cloak x-transition class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-3" style="{{ $isAllCompanies == '1' ? 'display: none;' : '' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <span class="text-xs text-base-content/70 font-semibold">{{ __('Daftar Perusahaan yang Diizinkan:') }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="selectAllCompanies({{ json_encode($companies->pluck('id')->map(fn($id) => (string)$id)->all()) }})" class="btn btn-2xs btn-ghost text-primary font-bold">
                                            {{ __('Pilih Semua') }}
                                        </button>
                                        <span class="text-base-content/30">•</span>
                                        <button type="button" @click="clearAllCompanies()" class="btn btn-2xs btn-ghost text-error font-medium">
                                            {{ __('Hapus Semua') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- Search box --}}
                                <div class="relative">
                                    <input type="text" x-model="searchCompany" placeholder="{{ __('Cari nama perusahaan...') }}" class="input input-xs input-bordered w-full rounded-lg bg-base-100 pl-8 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto pr-1">
                                    @foreach($companies as $company)
                                        <label x-show="!searchCompany || '{{ strtolower(addslashes($company->name)) }}'.includes(searchCompany.toLowerCase())" 
                                               class="flex items-center justify-between gap-2.5 p-2 rounded-xl border transition-all cursor-pointer select-none"
                                               :class="selectedCompanies.includes('{{ $company->id }}') ? 'bg-primary/10 border-primary/40 shadow-xs' : 'bg-base-100 border-base-200/80 hover:bg-base-200/60'">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" 
                                                       :checked="selectedCompanies.includes('{{ $company->id }}')"
                                                       @change="toggleCompany('{{ $company->id }}')"
                                                       class="checkbox checkbox-primary checkbox-sm rounded-md">
                                                <span class="text-xs font-semibold text-base-content truncate">{{ $company->name }}</span>
                                            </div>
                                            @if($company->code)
                                                <span class="badge badge-ghost badge-xs shrink-0 font-mono text-[10px]">{{ $company->code }}</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Branch Access --}}
                        @php
                            $selectedBranchIds = old('branch_ids', $corporateSoftFile->branches->pluck('id')->all());
                            $isAllBranches = old('is_all_branches', $corporateSoftFile->is_all_branches ? '1' : '0');
                        @endphp
                        <div x-data="{
                            allBranches: '{{ $isAllBranches }}',
                            searchBranch: '',
                            selectedBranches: {{ json_encode(array_map('strval', $selectedBranchIds)) }},
                            toggleBranch(id) {
                                id = String(id);
                                if (this.selectedBranches.includes(id)) {
                                    this.selectedBranches = this.selectedBranches.filter(item => item !== id);
                                } else {
                                    this.selectedBranches.push(id);
                                }
                            },
                            selectAllBranches(ids) {
                                ids.forEach(id => {
                                    id = String(id);
                                    if (!this.selectedBranches.includes(id)) {
                                        this.selectedBranches.push(id);
                                    }
                                });
                            },
                            clearAllBranches() {
                                this.selectedBranches = [];
                            }
                        }" class="space-y-3">
                            <div class="form-control w-full">
                                <label class="label pb-1.5 flex items-center justify-between">
                                    <span class="label-text font-bold text-xs sm:text-sm text-base-content flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
                                        {{ __('Akses Cabang (Branch)') }}
                                    </span>
                                    <span class="text-[11px] font-semibold text-secondary" x-show="allBranches === '0'" x-text="selectedBranches.length + ' Cabang Dipilih'"></span>
                                </label>
                                <select name="is_all_branches" x-model="allBranches" class="select select-bordered select-sm w-full font-medium rounded-xl text-xs sm:text-sm bg-base-100 focus:border-secondary">
                                    <option value="1">🌐 {{ __('Berlaku untuk Semua Cabang (Global)') }}</option>
                                    <option value="0">🏛️ {{ __('Pilih Cabang Tertentu (Dropdown Spesifik)') }}</option>
                                </select>
                            </div>

                            <div x-show="allBranches === '0'" x-cloak x-transition class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-3" style="{{ $isAllBranches == '1' ? 'display: none;' : '' }}">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <span class="text-xs text-base-content/70 font-semibold">{{ __('Daftar Cabang yang Diizinkan:') }}</span>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" @click="selectAllBranches({{ json_encode($branches->pluck('id')->map(fn($id) => (string)$id)->all()) }})" class="btn btn-2xs btn-ghost text-secondary font-bold">
                                            {{ __('Pilih Semua') }}
                                        </button>
                                        <span class="text-base-content/30">•</span>
                                        <button type="button" @click="clearAllBranches()" class="btn btn-2xs btn-ghost text-error font-medium">
                                            {{ __('Hapus Semua') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- Search box --}}
                                <div class="relative">
                                    <input type="text" x-model="searchBranch" placeholder="{{ __('Cari nama cabang atau perusahaan...') }}" class="input input-xs input-bordered w-full rounded-lg bg-base-100 pl-8 text-xs">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto pr-1">
                                    @foreach($branches as $branch)
                                        <label x-show="!searchBranch || '{{ strtolower(addslashes(($branch->company?->name ?? '') . ' ' . $branch->name)) }}'.includes(searchBranch.toLowerCase())" 
                                               class="flex items-center justify-between gap-2.5 p-2 rounded-xl border transition-all cursor-pointer select-none"
                                               :class="selectedBranches.includes('{{ $branch->id }}') ? 'bg-secondary/10 border-secondary/40 shadow-xs' : 'bg-base-100 border-base-200/80 hover:bg-base-200/60'">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" 
                                                       :checked="selectedBranches.includes('{{ $branch->id }}')"
                                                       @change="toggleBranch('{{ $branch->id }}')"
                                                       class="checkbox checkbox-secondary checkbox-sm rounded-md">
                                                <div class="min-w-0 flex flex-col">
                                                    <span class="text-xs font-semibold text-base-content truncate">{{ $branch->name }}</span>
                                                    <span class="text-[10px] text-base-content/50 truncate">{{ $branch->company?->name }}</span>
                                                </div>
                                            </div>
                                            @if($branch->code)
                                                <span class="badge badge-ghost badge-xs shrink-0 font-mono text-[10px]">{{ $branch->code }}</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-end gap-2 pt-4 border-t border-base-200">
                            <a href="{{ route('admin.corporate-soft-files.index') }}" class="btn btn-ghost btn-sm rounded-xl">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary btn-sm px-6 rounded-xl font-bold shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                {{ __('Perbarui Soft File') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
