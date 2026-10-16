<x-app-layout>
    <x-slot name="header">{{ __('Edit Tipe Dokumen') }}</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.document-types.update', $documentType) }}">
                        @csrf @method('PUT')

                        <div class="form-control w-full mb-4">
                            <label for="code" class="label"><span class="label-text font-medium">{{ __('Kode Dokumen') }} <span class="text-error">*</span></span></label>
                            <input type="text" name="code" id="code" value="{{ old('code', $documentType->code) }}"
                                   class="input input-bordered w-full uppercase"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase();"
                                   required>
                            @error('code') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="name" class="label"><span class="label-text font-medium">{{ __('Keterangan / Nama Dokumen') }} <span class="text-error">*</span></span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $documentType->name) }}" class="input input-bordered w-full" required>
                            @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-6">
                            <label for="category" class="label"><span class="label-text font-medium">{{ __('Kategori Dokumen') }} <span class="text-error">*</span></span></label>
                            <select name="category" id="category" class="select select-bordered w-full" required>
                                <option value="naskah_dinas" {{ old('category', $documentType->category) === 'naskah_dinas' ? 'selected' : '' }}>
                                    {{ __('Naskah Dinas (Official Correspondence)') }}
                                </option>
                                <option value="akreditasi" {{ old('category', $documentType->category) === 'akreditasi' ? 'selected' : '' }}>
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                <span>{{ __('Perbarui') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
