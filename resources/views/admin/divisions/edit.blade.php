<x-app-layout>
    <x-slot name="header">Edit Division</x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.divisions.update', $division) }}">
                    @csrf @method('PUT')
                    <div class="mb-4">
                        <label for="code" class="block text-sm font-medium text-gray-700">Code</label>
                        <input type="text" name="code" id="code" value="{{ old('code', $division->code) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" maxlength="10" required>
                        @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $division->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
