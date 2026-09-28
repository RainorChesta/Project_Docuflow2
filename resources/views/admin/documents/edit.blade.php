<x-app-layout>
    <x-slot name="header">{{ __('Edit Informasi Dokumen') }}</x-slot>

    <div class="py-6 space-y-6">
        <div class="max-w-4xl mx-auto w-full space-y-6">

            {{-- Breadcrumbs & Back Navigation --}}
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-base-content/60">
                    <a href="{{ route('admin.documents.index') }}" class="hover:text-primary transition-colors flex items-center gap-1.5 font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ __('Kembali ke Semua Dokumen') }}
                    </a>
                    <span>/</span>
                    <span class="text-base-content font-semibold truncate max-w-xs">{{ $document->title }}</span>
                </div>
            </div>

            {{-- Header Alert Banner --}}
            <div class="alert bg-primary/10 border border-primary/25 text-base-content shadow-xs rounded-2xl flex items-start sm:items-center gap-3.5">
                <div class="w-9 h-9 rounded-xl bg-primary text-primary-content flex items-center justify-center shrink-0 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-xs sm:text-sm">
                    <div class="font-bold text-primary">{{ __('Perubahan Langsung Administrator') }}</div>
                    <div class="text-base-content/75 mt-0.5">
                        {{ __('Sebagai Administrator, pembaruan judul, nomor, format, maupun metadata dokumen ini akan langsung disimpan ke sistem tanpa memerlukan alur persetujuan (approval) ulang.') }}
                    </div>
                </div>
            </div>

            {{-- Validation Errors --}}
            @if($errors->any())
                <div class="alert alert-error shadow-xs border border-error/20 rounded-2xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm">
                        <div class="font-bold">{{ __('Terjadi kesalahan input:') }}</div>
                        <ul class="list-disc list-inside mt-1 text-xs">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Form Card --}}
            <div class="card bg-base-100 border border-base-300 rounded-2xl shadow-xs overflow-hidden"
                 x-data="{
                     companyId: '{{ old('company_id', $document->branch?->company_id ?? $document->company_id ?? '') }}',
                     branchId: '{{ old('branch_id', $document->branch_id ?? '') }}',
                     branches: {{ Js::from($branches) }},
                     formatChoice: '{{ old('format_choice', $document->format_choice ?? 'baru') }}',
                     
                     get filteredBranches() {
                         if (!this.companyId) return this.branches;
                         return this.branches.filter(b => b.company_id == this.companyId);
                     },

                     onCompanyChange() {
                         const currentBranchStillValid = this.filteredBranches.some(b => b.id == this.branchId);
                         if (!currentBranchStillValid) {
                             this.branchId = '';
                         }
                     }
                 }">
                <div class="card-body p-6 sm:p-8 space-y-6">

                    <div class="flex items-center justify-between pb-4 border-b border-base-200">
                        <div>
                            <h3 class="text-lg font-bold text-base-content flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                {{ __('Formulir Edit Dokumen') }}
                            </h3>
                            <p class="text-xs text-base-content/60 mt-0.5">
                                {{ __('ID Dokumen: #') }}{{ $document->id }} &bull; {{ __('Pembuat:') }} {{ $document->owner?->name ?? '—' }}
                            </p>
                        </div>

                        <div class="badge badge-ghost badge-sm font-mono">
                            {{ $document->displayVersion()?->version_number ? 'v'.$document->displayVersion()->version_number : 'v1' }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.documents.update', $document) }}" class="space-y-5">
                        @csrf
                        @method('PUT')

                        {{-- Judul Dokumen --}}
                        <div class="form-control w-full space-y-1.5">
                            <label for="title" class="label py-0">
                                <span class="label-text font-bold text-xs text-base-content">{{ __('Judul Dokumen') }} <span class="text-error">*</span></span>
                            </label>
                            <input type="text"
                                   id="title"
                                   name="title"
                                   value="{{ old('title', $document->title) }}"
                                   required
                                   maxlength="255"
                                   placeholder="{{ __('Masukkan judul dokumen...') }}"
                                   class="input input-bordered w-full text-sm font-medium focus:border-primary">
                            <p class="text-[11px] text-base-content/50">{{ __('Nama atau judul resmi dokumen yang akan ditampilkan di seluruh sistem.') }}</p>
                        </div>

                        {{-- Format Dokumen Selection (Baru vs Lama) --}}
                        <div class="form-control w-full space-y-1.5">
                            <label class="label py-0">
                                <span class="label-text font-bold text-xs text-base-content">{{ __('Format Dokumen') }} <span class="text-error">*</span></span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                {{-- Format Baru Option --}}
                                <label class="relative flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                                       :class="formatChoice === 'baru' ? 'border-primary bg-primary/5 shadow-xs ring-1 ring-primary/20' : 'border-base-300 hover:bg-base-200/40'">
                                    <input type="radio"
                                           name="format_choice"
                                           value="baru"
                                           x-model="formatChoice"
                                           class="radio radio-primary radio-sm">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-xs text-base-content flex items-center gap-1.5">
                                            <span>{{ __('Format Baru') }}</span>
                                            <span class="badge badge-xs bg-indigo-500/15 text-indigo-700 dark:text-indigo-300 border-0 font-semibold">{{ __('Standard') }}</span>
                                        </div>
                                        <p class="text-[11px] text-base-content/60 mt-0.5">{{ __('Menggunakan format penomoran dan tata letak dokumen standar terkini.') }}</p>
                                    </div>
                                </label>

                                {{-- Format Lama Option --}}
                                <label class="relative flex items-center gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                                       :class="formatChoice === 'lama' ? 'border-primary bg-primary/5 shadow-xs ring-1 ring-primary/20' : 'border-base-300 hover:bg-base-200/40'">
                                    <input type="radio"
                                           name="format_choice"
                                           value="lama"
                                           x-model="formatChoice"
                                           class="radio radio-primary radio-sm">
                                    <div class="min-w-0 flex-1">
                                        <div class="font-bold text-xs text-base-content flex items-center gap-1.5">
                                            <span>{{ __('Format Lama') }}</span>
                                            <span class="badge badge-xs bg-amber-500/15 text-amber-700 dark:text-amber-300 border-0 font-semibold">{{ __('Legacy') }}</span>
                                        </div>
                                        <p class="text-[11px] text-base-content/60 mt-0.5">{{ __('Menggunakan format dokumen manual atau kearsipan versi terdahulu.') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Nomor Dokumen & Tipe Dokumen --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Nomor Dokumen --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="document_number" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Nomor Dokumen') }}</span>
                                </label>
                                <input type="text"
                                       id="document_number"
                                       name="document_number"
                                       value="{{ old('document_number', $document->document_number) }}"
                                       maxlength="150"
                                       placeholder="{{ __('Contoh: 001/SK/DIR/IX/2026') }}"
                                       class="input input-bordered w-full font-mono text-xs focus:border-primary">
                                <p class="text-[11px] text-base-content/50">{{ __('Nomor surat/dokumen resmi.') }}</p>
                            </div>

                            {{-- Tipe Dokumen --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="document_type_id" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Tipe Dokumen') }} <span class="text-error">*</span></span>
                                </label>
                                <select id="document_type_id"
                                        name="document_type_id"
                                        required
                                        class="select select-bordered w-full text-xs font-medium focus:border-primary">
                                    <option value="" disabled>{{ __('Pilih tipe dokumen...') }}</option>
                                    @foreach($documentTypes as $type)
                                        <option value="{{ $type->id }}" {{ old('document_type_id', $document->document_type_id) == $type->id ? 'selected' : '' }}>
                                            {{ $type->code }} - {{ $type->name }} ({{ $type->category === 'naskah_dinas' ? __('Naskah Dinas') : __('Akreditasi') }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-base-content/50">{{ __('Jenis/klasifikasi naskah dokumen.') }}</p>
                            </div>
                        </div>

                        {{-- Perusahaan & Cabang --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Perusahaan --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="company_id" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Perusahaan') }}</span>
                                </label>
                                <select id="company_id"
                                        name="company_id"
                                        x-model="companyId"
                                        @change="onCompanyChange()"
                                        class="select select-bordered w-full text-xs font-medium focus:border-primary">
                                    <option value="">{{ __('Semua Perusahaan / Global') }}</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}">
                                            {{ $company->name }} {{ $company->code ? '('.$company->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Cabang --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="branch_id" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Cabang') }}</span>
                                </label>
                                <select id="branch_id"
                                        name="branch_id"
                                        x-model="branchId"
                                        class="select select-bordered w-full text-xs font-medium focus:border-primary">
                                    <option value="">{{ __('Semua Cabang / Tidak Terikat') }}</option>
                                    <template x-for="branch in filteredBranches" :key="branch.id">
                                        <option :value="branch.id"
                                                :selected="branch.id == branchId"
                                                x-text="branch.name + (branch.code ? ' (' + branch.code + ')' : '')">
                                        </option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        {{-- Unit Kerja & Visibilitas --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Unit Kerja --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="unit_kerja_id" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Unit Kerja') }}</span>
                                </label>
                                <select id="unit_kerja_id"
                                        name="unit_kerja_id"
                                        class="select select-bordered w-full text-xs font-medium focus:border-primary">
                                    <option value="">{{ __('Tanpa Unit Kerja / Umum') }}</option>
                                    @foreach($unitKerjas as $uk)
                                        <option value="{{ $uk->id }}" {{ old('unit_kerja_id', $document->unit_kerja_id) == $uk->id ? 'selected' : '' }}>
                                            {{ $uk->name }} {{ $uk->code ? '('.$uk->code.')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Visibilitas --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="visibility" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Visibilitas Dokumen') }} <span class="text-error">*</span></span>
                                </label>
                                <select id="visibility"
                                        name="visibility"
                                        required
                                        class="select select-bordered w-full text-xs font-medium focus:border-primary">
                                    <option value="general" {{ old('visibility', $document->visibility) === 'general' ? 'selected' : '' }}>{{ __('Umum (Dapat diakses seluruh pengguna dalam cakupan)') }}</option>
                                    <option value="unit_kerja" {{ old('visibility', $document->visibility) === 'unit_kerja' ? 'selected' : '' }}>{{ __('Unit Kerja (Hanya anggota Unit Kerja terkait)') }}</option>
                                    <option value="personal" {{ old('visibility', $document->visibility) === 'personal' ? 'selected' : '' }}>{{ __('Pribadi / Khusus (Pemilik & Penerima Bagikan)') }}</option>
                                </select>
                            </div>
                        </div>

                        {{-- Tanggal Kedaluwarsa & Ukuran Kertas --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Tanggal Kedaluwarsa --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="expiration_date" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Tanggal Kedaluwarsa') }}</span>
                                </label>
                                <input type="date"
                                       id="expiration_date"
                                       name="expiration_date"
                                       value="{{ old('expiration_date', $document->expiration_date ? $document->expiration_date->format('Y-m-d') : '') }}"
                                       class="input input-bordered w-full text-xs font-medium focus:border-primary">
                                <p class="text-[11px] text-base-content/50">{{ __('Kosongkan jika mengikuti masa retensi standar.') }}</p>
                            </div>

                            {{-- Ukuran Kertas --}}
                            <div class="form-control w-full space-y-1.5">
                                <label for="paper_size" class="label py-0">
                                    <span class="label-text font-bold text-xs text-base-content">{{ __('Ukuran Kertas Ekspor') }}</span>
                                </label>
                                <select id="paper_size"
                                        name="paper_size"
                                        class="select select-bordered w-full text-xs font-medium focus:border-primary">
                                    <option value="A4" {{ old('paper_size', $document->paper_size ?? 'A4') === 'A4' ? 'selected' : '' }}>A4 (210 x 297 mm)</option>
                                    <option value="F4" {{ old('paper_size', $document->paper_size) === 'F4' ? 'selected' : '' }}>F4 / Folio (215 x 330 mm)</option>
                                    <option value="Letter" {{ old('paper_size', $document->paper_size) === 'Letter' ? 'selected' : '' }}>Letter (216 x 279 mm)</option>
                                    <option value="Legal" {{ old('paper_size', $document->paper_size) === 'Legal' ? 'selected' : '' }}>Legal (216 x 356 mm)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="pt-5 border-t border-base-200 flex flex-col sm:flex-row items-center justify-end gap-2.5">
                            <a href="{{ route('admin.documents.index') }}" class="btn btn-ghost btn-sm w-full sm:w-auto font-medium">
                                {{ __('Batal') }}
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm w-full sm:w-auto px-6 font-semibold gap-2 shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ __('Simpan Perubahan') }}</span>
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
