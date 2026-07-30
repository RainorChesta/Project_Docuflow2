<x-app-layout>
    <x-slot name="header">Create Division</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-6">
                <form method="POST" action="{{ route('admin.divisions.store') }}">
                    @csrf
                    <div class="form-control w-full mb-4">
                        <label for="code" class="label">
                            <span class="label-text font-medium">Code</span>
                        </label>
                        <input type="text" name="code" id="code" class="input input-bordered w-full" maxlength="10" required>
                        @error('code') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="name" class="label">
                            <span class="label-text font-medium">Name</span>
                        </label>
                        <input type="text" name="name" id="name" class="input input-bordered w-full" required>
                        @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
