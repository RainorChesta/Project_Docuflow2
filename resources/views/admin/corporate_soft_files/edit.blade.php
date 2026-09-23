<x-app-layout>
    <x-slot name="header">{{ __('Edit Soft File Korporat') }}</x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full px-4 sm:px-6">
            {{-- Breadcrumb --}}
            <div class="flex items-center gap-2 mb-4 text-xs sm:text-sm text-base-content/60">
                <a href="{{ route('admin.corporate-soft-files.index') }}" class="hover:text-primary transition-colors">{{ __('Soft File Korporat') }}</a>
                <span>/</span>
                <span class="text-base-content font-semibold">{{ __('Edit Soft File') }}</span>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm rounded-2xl overflow-hidden">
                <div class="card-body p-6">
                    <h2 class="text-lg font-bold text-base-content mb-1">{{ __('Edit Soft File Korporat') }}</h2>
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

                        {{-- Current File & Upload Replacement --}}
                        <div class="p-4 bg-base-200/40 rounded-2xl border border-base-300 space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-base-content uppercase block">{{ __('Berkas Saat Ini:') }}</span>
                                    <span class="text-sm font-semibold text-primary">{{ $corporateSoftFile->file_original_name }}</span>
                                    @if($corporateSoftFile->file_size)
                                        <span class="text-xs text-base-content/50">({{ number_format($corporateSoftFile->file_size / 1024, 1) }} KB)</span>
                                    @endif
                                </div>
                                <a href="{{ route('admin.corporate-soft-files.download', $corporateSoftFile) }}" class="btn btn-outline btn-xs gap-1 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    {{ __('Unduh') }}
                                </a>
                            </div>

                            <div class="form-control pt-2 border-t border-base-200" x-data="{
                                fileName: '',
                                fileSize: '',
                                fileExt: '',
                                isDragging: false,
                                handleFiles(files) {
                                    if (files && files.length > 0) {
                                        const f = files[0];
                                        this.fileName = f.name;
                                        this.fileSize = (f.size / (1024 * 1024) >= 1) 
                                            ? (f.size / (1024 * 1024)).toFixed(2) + ' MB' 
                                            : (f.size / 1024).toFixed(1) + ' KB';
                                        this.fileExt = f.name.split('.').pop().toUpperCase();
                                        $refs.fileInput.files = files;
                                    }
                                },
                                clearFile() {
                                    this.fileName = '';
                                    this.fileSize = '';
                                    this.fileExt = '';
                                    $refs.fileInput.value = '';
                                }
                            }">
                                <label class="label font-medium text-xs">
                                    <span class="label-text font-semibold">{{ __('Ganti Berkas (.docx, .pdf) - Opsional') }}</span>
                                </label>

                                <div class="relative border-2 border-dashed rounded-2xl p-4 transition-all text-center cursor-pointer"
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
                                           class="hidden @error('file') is-invalid @enderror">

                                    <template x-if="!fileName">
                                        <div class="flex flex-col items-center justify-center space-y-1.5 py-1">
                                            <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shadow-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold text-base-content">
                                                    <span class="text-primary hover:underline">{{ __('Klik untuk mengganti file') }}</span> {{ __('atau seret (drag & drop) ke sini') }}
                                                </p>
                                                <p class="text-[11px] text-base-content/50 mt-0.5">
                                                    {{ __('Biarkan kosong jika tidak ingin mengubah berkas. Format: .docx, .doc, .pdf (Maks 15 MB)') }}
                                                </p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="fileName">
                                        <div class="flex items-center justify-between p-2.5 bg-base-100 rounded-xl border border-base-300 shadow-xs text-left" @click.stop>
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-xl bg-primary/15 text-primary flex items-center justify-center font-bold text-xs shrink-0" x-text="fileExt"></div>
                                                <div class="min-w-0">
                                                    <p class="text-xs font-bold text-base-content truncate" x-text="fileName"></p>
                                                    <p class="text-[11px] text-base-content/50" x-text="fileSize"></p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2 shrink-0">
                                                <button type="button" @click.stop="$refs.fileInput.click()" class="btn btn-xs btn-outline btn-primary rounded-lg">
                                                    {{ __('Ganti') }}
                                                </button>
                                                <button type="button" @click.stop="clearFile()" class="btn btn-xs btn-ghost btn-circle text-error" title="{{ __('Batal Ganti') }}">
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                @error('file')
                                    <span class="text-xs text-error mt-1.5 font-medium block">{{ $message }}</span>
                                @enderror
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
                            $isAllCompanies = old('is_all_companies', $corporateSoftFile->is_all_companies ? '1' : '0') == '1';
                        @endphp
                        <div x-data="{ allCompanies: {{ $isAllCompanies ? 'true' : 'false' }} }" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-xs sm:text-sm text-base-content">{{ __('Akses Perusahaan (Company)') }}</label>
                                <label class="label cursor-pointer gap-2 p-0">
                                    <span class="label-text text-xs font-semibold">{{ __('Berlaku untuk Semua Perusahaan') }}</span>
                                    <input type="checkbox" name="is_all_companies" value="1" x-model="allCompanies" {{ $isAllCompanies ? 'checked' : '' }} class="toggle toggle-primary toggle-sm">
                                </label>
                            </div>

                            <div x-show="!allCompanies" x-cloak x-transition class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-2" style="{{ $isAllCompanies ? 'display: none;' : '' }}">
                                <span class="text-xs text-base-content/60 font-medium block mb-2">{{ __('Pilih Perusahaan yang dapat mengakses:') }}</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                                    @foreach($companies as $company)
                                        <label class="label cursor-pointer justify-start gap-2.5 p-1 hover:bg-base-300/40 rounded-lg">
                                            <input type="checkbox" name="company_ids[]" value="{{ $company->id }}" class="checkbox checkbox-primary checkbox-sm rounded-md" {{ in_array($company->id, $selectedCompanyIds) ? 'checked' : '' }}>
                                            <span class="label-text text-xs font-medium">{{ $company->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Branch Access --}}
                        @php
                            $selectedBranchIds = old('branch_ids', $corporateSoftFile->branches->pluck('id')->all());
                            $isAllBranches = old('is_all_branches', $corporateSoftFile->is_all_branches ? '1' : '0') == '1';
                        @endphp
                        <div x-data="{ allBranches: {{ $isAllBranches ? 'true' : 'false' }} }" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-xs sm:text-sm text-base-content">{{ __('Akses Cabang (Branch)') }}</label>
                                <label class="label cursor-pointer gap-2 p-0">
                                    <span class="label-text text-xs font-semibold">{{ __('Berlaku untuk Semua Cabang') }}</span>
                                    <input type="checkbox" name="is_all_branches" value="1" x-model="allBranches" {{ $isAllBranches ? 'checked' : '' }} class="toggle toggle-secondary toggle-sm">
                                </label>
                            </div>

                            <div x-show="!allBranches" x-cloak x-transition class="p-4 bg-base-200/50 rounded-2xl border border-base-300 space-y-2" style="{{ $isAllBranches ? 'display: none;' : '' }}">
                                <span class="text-xs text-base-content/60 font-medium block mb-2">{{ __('Pilih Cabang yang dapat mengakses:') }}</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto">
                                    @foreach($branches as $branch)
                                        <label class="label cursor-pointer justify-start gap-2.5 p-1 hover:bg-base-300/40 rounded-lg">
                                            <input type="checkbox" name="branch_ids[]" value="{{ $branch->id }}" class="checkbox checkbox-secondary checkbox-sm rounded-md" {{ in_array($branch->id, $selectedBranchIds) ? 'checked' : '' }}>
                                            <span class="label-text text-xs font-medium">{{ $branch->company?->name }} - {{ $branch->name }}</span>
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
