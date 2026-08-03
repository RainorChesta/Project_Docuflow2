<x-app-layout>
    <x-slot name="header">New Document Type</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.document-types.store') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="code" class="label"><span class="label-text font-medium">Kode Dokumen</span></label>
                            <input type="text" name="code" id="code" value="{{ old('code') }}" class="input input-bordered w-full" required>
                            @error('code') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="name" class="label"><span class="label-text font-medium">Keterangan</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" class="input input-bordered w-full" required>
                            @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
