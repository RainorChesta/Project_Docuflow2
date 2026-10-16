<x-app-layout>
    <x-slot name="header">{{ __('Tipe Dokumen Baru') }}</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.document-types.store') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="code" class="label"><span class="label-text font-medium">{{ __('Kode Dokumen') }} <span class="text-error">*</span></span></label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}"
                                   placeholder="Contoh: SK, SOP, S.ED"
                                   class="input input-bordered w-full uppercase"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase();"
                                   required>
                            @error('code') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="name" class="label"><span class="label-text font-medium">{{ __('Keterangan / Nama Dokumen') }} <span class="text-error">*</span></span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}"
                                   placeholder="Contoh: Standar Operasional Prosedur, Surat Edaran"
                                   class="input input-bordered w-full" required>
                            @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-6">
                            <label for="category" class="label"><span class="label-text font-medium">{{ __('Kategori Dokumen') }} <span class="text-error">*</span></span></label>
                            <select name="category" id="category" class="select select-bordered w-full" required>
                                <option value="" disabled {{ old('category') ? '' : 'selected' }}>{{ __('Pilih kategori dokumen...') }}</option>
                                <option value="naskah_dinas" {{ old('category') === 'naskah_dinas' ? 'selected' : '' }}>
                                    {{ __('Naskah Dinas (Official Correspondence)') }}
                                </option>
                                <option value="akreditasi" {{ old('category') === 'akreditasi' ? 'selected' : '' }}>
                                    {{ __('Dokumen Akreditasi (Accreditation Documents)') }}
                                </option>
                            </select>
                            <p class="text-xs text-base-content/60 mt-1.5 leading-relaxed">
                                {{ __('Dokumen Akreditasi akan menyertakan kode unit kerja pada format penomoran dokumen.') }}
                            </p>
                            @error('category') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <a href="{{ route('admin.document-types.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                <span>{{ __('Simpan') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
