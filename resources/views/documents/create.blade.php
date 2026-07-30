<x-app-layout>
    <x-slot name="header">Create Document</x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.store') }}">
                        @csrf

                        <div class="form-control w-full mb-4">
                            <label for="title" class="label">
                                <span class="label-text font-medium">Title</span>
                            </label>
                            <input type="text" name="title" id="title" class="input input-bordered w-full" required>
                            @error('title') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-control w-full mb-4">
                            <label for="division_id" class="label">
                                <span class="label-text font-medium">Division</span>
                            </label>
                            <select name="division_id" id="division_id" class="select select-bordered w-full" required>
                                <option value="">Select division...</option>
                                @foreach($divisions as $div)
                                    <option value="{{ $div->id }}">{{ $div->code }} - {{ $div->name }}</option>
                                @endforeach
                            </select>
                            @error('division_id') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
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
