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

                        @if(auth()->user()->isAdmin())
                            <div class="form-control w-full mb-4">
                                <label for="division_id" class="label">
                                    <span class="label-text font-medium">Division</span>
                                </label>
                                <select name="division_id" id="division_id" class="select select-bordered w-full" required>
                                    <option value="">Pilih divisi...</option>
                                    @foreach($divisions as $div)
                                        <option value="{{ $div->id }}" {{ old('division_id') == $div->id ? 'selected' : '' }}>
                                            {{ $div->code }} - {{ $div->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-base-content/50 mt-1">Admin tidak terikat divisi tertentu, wajib pilih manual.</p>
                                @error('division_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <div class="form-control w-full mb-4">
                                <label class="label">
                                    <span class="label-text font-medium">Division</span>
                                </label>
                                <input type="text"
                                       value="{{ auth()->user()->division ? auth()->user()->division->code . ' - ' . auth()->user()->division->name : 'Belum ada divisi' }}"
                                       class="input input-bordered w-full bg-base-200" disabled>
                                <p class="text-xs text-base-content/50 mt-1">Otomatis sesuai divisi akun kamu.</p>
                            </div>
                        @endif

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">Create Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
