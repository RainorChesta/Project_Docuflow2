<x-app-layout>
    <x-slot name="header">{{ __('Upload Template Baru') }}</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.templates.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="title" class="label"><span class="label-text font-medium">{{ __('Judul Template') }}</span></label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" class="input input-bordered w-full" placeholder="{{ __('Contoh: Surat Keterangan Kerja') }}" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="description" class="label"><span class="label-text font-medium">{{ __('Deskripsi') }} <span class="text-base-content/40">({{ __('opsional') }})</span></span></label>
                            <textarea name="description" id="description" rows="3" class="textarea textarea-bordered w-full" placeholder="{{ __('Penjelasan singkat tentang template ini...') }}">{{ old('description') }}</textarea>
                            @error('description') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="document_type_id" class="label"><span class="label-text font-medium">{{ __('Tipe Dokumen') }}</span></label>
                            <select name="document_type_id" id="document_type_id" class="select select-bordered w-full" required>
                                <option value="" disabled {{ old('document_type_id') ? '' : 'selected' }}>{{ __('Pilih tipe dokumen...') }}</option>
                                @foreach($documentTypes as $dt)
                                    <option value="{{ $dt->id }}" {{ old('document_type_id') == $dt->id ? 'selected' : '' }}>{{ $dt->code }} - {{ $dt->name }}</option>
                                @endforeach
                            </select>
                            @error('document_type_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-6">
                            <label for="file" class="label"><span class="label-text font-medium">{{ __('File Template') }} (.docx)</span></label>
                            <input type="file" name="file" id="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="file-input file-input-bordered w-full" required>
                            <label class="label"><span class="label-text-alt text-base-content/50">{{ __('Maks. 10MB — format .docx') }}</span></label>
                            @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                                {{ __('Upload') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
