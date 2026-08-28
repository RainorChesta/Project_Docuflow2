<x-app-layout>
    <x-slot name="header">{{ __('Edit Kategori Template') }}</x-slot>

    <div class="py-6">
        <div class="max-w-lg mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.template-categories.update', $templateCategory) }}">
                        @csrf @method('PUT')

                        <div class="form-control w-full mb-4">
                            <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Kategori') }}</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $templateCategory->name) }}" class="input input-bordered w-full" required>
                            @error('name') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-wrap justify-end">
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
