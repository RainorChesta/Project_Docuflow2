<x-app-layout>
    <x-slot name="header">{{ __('Tambah Soft File Korporat') }}</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-4 text-xs sm:text-sm text-base-content/60">
                <a href="{{ route('admin.corporate-soft-files.index') }}" class="hover:text-primary transition-colors">{{ __('Soft File Korporat') }}</a>
                <span>/</span>
                <span class="text-base-content font-semibold">{{ __('Tambah Baru') }}</span>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="card-body p-6">
                    <h2 class="text-lg font-bold text-base-content mb-1">{{ __('Unggah Berkas Soft File Korporat') }}</h2>
                    <p class="text-xs text-base-content/60 mb-6">{{ __('Unggah file dokumen DOCX (kop surat, format template, dsb) dan atur hak akses perusahaan dan cabang.') }}</p>

                    @if($errors->any())
                        <div class="alert alert-error mb-6 rounded-xl shadow-xs">
                            <ul class="list-disc list-inside text-xs">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.corporate-soft-files.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        {{-- Judul Soft File --}}
                        <div class="form-control">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('Nama / Judul Soft File') }} <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title') }}" required placeholder="{{ __('Contoh: Kop Surat Resmi PT Alpha Pusat') }}" class="input input-bordered w-full rounded-xl @error('title') input-error @enderror">
                        </div>

                        {{-- Deskripsi --}}
                        <div class="form-control">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('Deskripsi / Keterangan') }}</span>
                            </label>
                            <textarea name="description" rows="2" placeholder="{{ __('Keterangan singkat peruntukan soft file ini...') }}" class="textarea textarea-bordered w-full rounded-xl">{{ old('description') }}</textarea>
                        </div>

                        {{-- Target Ukuran Kertas (Paper Size) --}}
                        <div class="form-control" x-data="{ paperSize: '{{ old('paper_size', 'f4') }}' }">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('Target Ukuran Kertas Dokumen') }} <span class="text-error">*</span></span>
                                <span class="label-text-alt text-base-content/50">{{ __('Menentukan format standar dokumen saat kop diterapkan') }}</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-3.5 rounded-2xl border transition-all cursor-pointer select-none"
                                       :class="paperSize === 'f4' ? 'bg-primary/10 border-primary shadow-xs ring-1 ring-primary/30' : 'bg-base-100 border-base-300 hover:bg-base-200/50'">
                                    <input type="radio" name="paper_size" value="f4" x-model="paperSize" class="radio radio-primary radio-sm">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-xs sm:text-sm font-bold text-base-content">{{ __('F4 / Folio (Standar)') }}</span>
                                            <span class="badge badge-neutral badge-xs font-mono font-semibold">210 × 330 mm</span>
                                        </div>
                                        <p class="text-[11px] text-base-content/60 mt-0.5">{{ __('Ukuran kertas standar default untuk operasional korporat.') }}</p>
                                    </div>
                                </label>

                                <label class="flex items-center gap-3 p-3.5 rounded-2xl border transition-all cursor-pointer select-none"
                                       :class="paperSize === 'a4' ? 'bg-secondary/10 border-secondary shadow-xs ring-1 ring-secondary/30' : 'bg-base-100 border-base-300 hover:bg-base-200/50'">
                                    <input type="radio" name="paper_size" value="a4" x-model="paperSize" class="radio radio-secondary radio-sm">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-xs sm:text-sm font-bold text-base-content">{{ __('A4 (Khusus Cabang / Internasional)') }}</span>
                                            <span class="badge badge-secondary badge-xs font-mono font-semibold">210 × 297 mm</span>
                                        </div>
                                        <p class="text-[11px] text-base-content/60 mt-0.5">{{ __('Berkas selain A4 (seperti F4, Letter, Legal) akan diizinkan dan otomatis dikonversi ke format A4.') }}</p>
                                    </div>
                                </label>
                            </div>
                            @error('paper_size')
                                <span class="text-xs text-error mt-1.5 font-medium block">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Upload File DOCX / PDF with Drag & Drop --}}
                        <div class="form-control" x-data="{
                            fileName: '',
                            fileSize: '',
                            fileExt: '',
                            isDragging: false,
                            showImageRejectionModal: false,
                            rejectedFileName: '',
                            handleFiles(files) {
                                if (files && files.length > 0) {
                                    const f = files[0];
                                    const ext = f.name.split('.').pop().toLowerCase();
                                    const isImage = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'svg', 'tif', 'tiff'].includes(ext) || (f.type && f.type.startsWith('image/'));

                                    if (isImage) {
                                        this.rejectedFileName = f.name;
                                        this.showImageRejectionModal = true;
                                        this.clearFile();
                                        return;
                                    }

                                    this.fileName = f.name;
                                    this.fileSize = (f.size / (1024 * 1024) >= 1) 
                                        ? (f.size / (1024 * 1024)).toFixed(2) + ' MB' 
                                        : (f.size / 1024).toFixed(1) + ' KB';
                                    this.fileExt = ext.toUpperCase();
                                    $refs.fileInput.files = files;
                                }
                            },
                            clearFile() {
                                this.fileName = '';
                                this.fileSize = '';
                                this.fileExt = '';
                                if ($refs.fileInput) {
                                    $refs.fileInput.value = '';
                                }
                            }
                        }">
                            <label class="label font-medium text-xs sm:text-sm">
                                <span class="label-text font-bold">{{ __('File Dokumen / Kop (.docx, .pdf)') }} <span class="text-error">*</span></span>
                            </label>

                            <div class="relative border-2 border-dashed rounded-2xl p-4 sm:p-6 transition-all text-center cursor-pointer"
                                 :class="isDragging ? 'border-primary bg-primary/10 shadow-md ring-2 ring-primary/30' : (fileName ? 'border-success/60 bg-success/5' : 'border-base-300 hover:border-primary/60 hover:bg-base-200/40 bg-base-100')"
                                 @dragover.prevent="isDragging = true"
                                 @dragleave.prevent="isDragging = false"
                                 @drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)"
                                 @click="$refs.fileInput.click()">
                                
                                <input type="file" 
                                       name="file" 
                                       x-ref="fileInput" 
                                       accept=".docx,.doc,.pdf,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/msword"
                                       @change="handleFiles($event.target.files)"
                                       required 
                                       class="hidden @error('file') is-invalid @enderror">

                                <template x-if="!fileName">
                                    <div class="flex flex-col items-center justify-center space-y-2 py-2">
                                        <div class="w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shadow-xs">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-base-content">
                                                <span class="text-primary hover:underline">{{ __('Klik untuk memilih file') }}</span> {{ __('atau seret (drag & drop) ke sini') }}
                                            </p>
                                            <p class="text-xs text-base-content/50 mt-1">
                                                {{ __('Hanya mendukung: Dokumen Word (.docx) atau Berkas PDF (.pdf) — Maks 15 MB') }}
                                            </p>
                                            <p class="text-[11px] text-error font-medium mt-0.5">
                                                {{ __('⚠️ Format gambar (JPEG/PNG) tidak diizinkan') }}
                                            </p>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="fileName">
                                    <div class="flex items-center justify-between p-2 sm:p-3 bg-base-100 rounded-xl border border-base-300 shadow-xs text-left" @click.stop>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-xl bg-primary/15 text-primary flex items-center justify-center font-bold text-xs shrink-0" x-text="fileExt"></div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-base-content truncate" x-text="fileName"></p>
                                                <p class="text-xs text-base-content/50" x-text="fileSize"></p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2 shrink-0">
                                            <button type="button" @click.stop="$refs.fileInput.click()" class="btn btn-xs btn-outline btn-primary rounded-lg">
                                                {{ __('Ganti') }}
                                            </button>
                                            <button type="button" @click.stop="clearFile()" class="btn btn-xs btn-ghost btn-circle text-error" title="{{ __('Hapus File') }}">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            @error('file')
                                <span class="text-xs text-error mt-1.5 font-medium block">{{ $message }}</span>
                            @enderror

                            {{-- Image Format Rejection Modal --}}
                            <div x-show="showImageRejectionModal" 
                                 x-cloak 
                                 class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0">
                                <div class="bg-base-100 rounded-3xl max-w-md w-full p-6 shadow-2xl border border-base-300 text-center space-y-4"
                                     @click.outside="showImageRejectionModal = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 scale-95"
                                     x-transition:enter-end="opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100 scale-100"
                                     x-transition:leave-end="opacity-0 scale-95">
                                    <div class="w-16 h-16 rounded-2xl bg-error/10 text-error flex items-center justify-center mx-auto shadow-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>
                                    <div class="space-y-1.5">
                                        <h3 class="text-base sm:text-lg font-extrabold text-base-content">{{ __('Format Gambar Ditolak') }}</h3>
                                        <p class="text-xs font-semibold text-error bg-error/10 py-1 px-2.5 rounded-lg inline-block" x-text="rejectedFileName"></p>
                                        <p class="text-xs text-base-content/70 text-left pt-2 leading-relaxed">
                                            {{ __('Sistem tidak menerima soft file kop surat dalam format gambar (JPEG, PNG, dll) demi menjaga ketajaman resolusi, presisi margin, dan penempatan header-footer resmi.') }}
                                        </p>
                                        <div class="p-3 bg-base-200/60 rounded-xl text-left border border-base-300 text-[11px] text-base-content/80 space-y-1 mt-2">
                                            <p class="font-bold text-base-content flex items-center gap-1.5">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Format yang Diizinkan:') }}
                                            </p>
                                            <ul class="list-disc list-inside space-y-0.5 text-base-content/70 pl-1">
                                                <li><strong>Microsoft Word</strong> (.docx, .doc)</li>
                                                <li><strong>Dokumen PDF</strong> (.pdf)</li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="pt-2">
                                        <button type="button" @click="showImageRejectionModal = false" class="btn btn-primary btn-sm w-full rounded-xl font-bold shadow-xs">
                                            {{ __('Saya Mengerti, Pilih Berkas Lain') }}
                                        </button>
                                    </div>
                                </div>
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
                                @endphp
                                @foreach($rolesList as $roleKey => $roleLabel)
                                    <label class="label cursor-pointer justify-start gap-2.5 p-0">
                                        <input type="checkbox" name="allowed_roles[]" value="{{ $roleKey }}" class="checkbox checkbox-primary checkbox-sm rounded-md" {{ is_array(old('allowed_roles')) && in_array($roleKey, old('allowed_roles')) ? 'checked' : '' }}>
                                        <span class="label-text font-medium text-xs sm:text-sm">{{ $roleLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Company Access --}}
                        <div x-data="{
                            allCompanies: '{{ old('is_all_companies', '1') }}',
                            searchCompany: '',
                            selectedCompanies: {{ json_encode(array_map('strval', old('company_ids', []))) }},
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

                            <div x-show="allCompanies === '0'" x-cloak x-transition class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-3" style="{{ old('is_all_companies', '1') == '1' ? 'display: none;' : '' }}">
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
                        <div x-data="{
                            allBranches: '{{ old('is_all_branches', '1') }}',
                            searchBranch: '',
                            selectedBranches: {{ json_encode(array_map('strval', old('branch_ids', []))) }},
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

                            <div x-show="allBranches === '0'" x-cloak x-transition class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-3" style="{{ old('is_all_branches', '1') == '1' ? 'display: none;' : '' }}">
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
                                {{ __('Simpan Soft File') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
