<x-app-layout>
    <x-slot name="header">{{ __('Edit Template') }}</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.templates.update', $template) }}" enctype="multipart/form-data">
                        @csrf @method('PUT')

                        <div class="form-control w-full mb-4">
                            <label for="title" class="label"><span class="label-text font-medium">{{ __('Judul Template') }}</span></label>
                            <input type="text" name="title" id="title" value="{{ old('title', $template->title) }}" class="input input-bordered w-full" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="description" class="label"><span class="label-text font-medium">{{ __('Deskripsi') }} <span class="text-base-content/40">({{ __('opsional') }})</span></span></label>
                            <textarea name="description" id="description" rows="3" class="textarea textarea-bordered w-full">{{ old('description', $template->description) }}</textarea>
                            @error('description') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="document_type_id" class="label"><span class="label-text font-medium">{{ __('Tipe Dokumen') }}</span></label>
                            <select name="document_type_id" id="document_type_id" class="select select-bordered w-full" required>
                                @foreach($documentTypes as $dt)
                                    <option value="{{ $dt->id }}" {{ old('document_type_id', $template->document_type_id) == $dt->id ? 'selected' : '' }}>{{ $dt->code }} - {{ $dt->name }}</option>
                                @endforeach
                            </select>
                            @error('document_type_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label class="label"><span class="label-text font-medium">{{ __('File Saat Ini') }}</span></label>
                            <div class="flex items-center gap-2 p-3 bg-base-200 rounded-lg">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="text-sm">{{ $template->file_original_name }}</span>
                                <a href="{{ route('admin.templates.download', $template) }}" class="btn btn-ghost btn-xs ml-auto" title="{{ __('Download') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                </a>
                            </div>
                        </div>

                        <div class="form-control w-full mb-6">
                            <label for="file" class="label"><span class="label-text font-medium">{{ __('Ganti File') }} <span class="text-base-content/40">({{ __('opsional') }})</span></span></label>
                            <input type="file" name="file" id="file" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="file-input file-input-bordered w-full">
                            <label class="label"><span class="label-text-alt text-base-content/50">{{ __('Kosongkan jika tidak ingin mengganti file.') }}</span></label>
                            @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap justify-end gap-2">
                            <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                {{ __('Perbarui') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
