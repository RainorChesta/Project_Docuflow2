<x-app-layout>
    <x-slot name="header">New Document Type</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.document-types.store') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="code" class="label"><span class="label-text font-medium">Kode Dokumen</span></label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}"
                                   class="input input-bordered w-full uppercase"
                                   style="text-transform: uppercase;"
                                   oninput="this.value = this.value.toUpperCase();"
                                   required>
                            @error('code') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="name" class="label"><span class="label-text font-medium">Keterangan</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input input-bordered w-full" required>
                            @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap justify-end">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
