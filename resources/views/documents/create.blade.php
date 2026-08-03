<x-app-layout>
    <x-slot name="header">Create Document</x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="document_type_id" class="label">
                                <span class="label-text font-medium">Tipe Dokumen</span>
                            </label>
                            <select name="document_type_id" id="document_type_id" class="select select-bordered w-full" required>
                                <option value="">Pilih tipe dokumen...</option>
                                @foreach($documentTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('document_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->code }} - {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('document_type_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="title" class="label">
                                <span class="label-text font-medium">Title</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" class="input input-bordered w-full" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text font-medium">Division</span>
                            </label>
                            @php($myDivision = auth()->user()->division)
                            <input type="text" value="{{ $myDivision ? $myDivision->code . ' - ' . $myDivision->name : '—' }}" class="input input-bordered w-full bg-base-200" disabled>
                            <input type="hidden" name="division_id" value="{{ auth()->user()->division_id }}">
                            <p class="text-xs text-base-content/50 mt-1">Otomatis sesuai divisi akun kamu.</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">Create Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
