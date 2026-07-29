<x-app-layout>
    <x-slot name="header">Create Document</x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('documents.store') }}">
                        @csrf

                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" id="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label for="division_id" class="block text-sm font-medium text-gray-700">Division</label>
                            <select name="division_id" id="division_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="">Select division...</option>
                                @foreach($divisions as $div)
                                    <option value="{{ $div->id }}">{{ $div->code }} - {{ $div->name }}</option>
                                @endforeach
                            </select>
                            @error('division_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Create Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
