<x-app-layout>
    <x-slot name="header">Edit Division</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.divisions.update', $division) }}">
                    @csrf @method('PUT')
                    <div class="form-control w-full mb-4">
                        <label for="code" class="label">
                            <span class="label-text font-medium">Code</span>
                        </label>
                        <input type="text" name="code" id="code" value="{{ old('code', $division->code) }}" class="input input-bordered w-full uppercase" maxlength="10" required>
                        @error('code') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-control w-full mb-4">
                        <label for="name" class="label">
                            <span class="label-text font-medium">Name</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $division->name) }}" class="input input-bordered w-full capitalize" required>
                        @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex flex-wrap justify-end">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
