<x-app-layout>
    <x-slot name="header">{{ __('Edit Perusahaan') }}</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.companies.update', $company) }}">
                    @csrf @method('PUT')
                    @if($errors->any())
                        <div class="alert alert-error mb-4">
                            <ul class="text-sm">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="form-control w-full mb-4">
                        <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Perusahaan') }}</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}" class="input input-bordered w-full" required>
                    </div>

                    <div class="form-control w-full mb-4">
                        <label for="code" class="label"><span class="label-text font-medium">{{ __('Kode Perusahaan') }}</span></label>
                        <input type="text" name="code" id="code" value="{{ old('code', $company->code) }}" class="input input-bordered w-full uppercase" required>
                    </div>

                    <div class="flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.companies.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            {{ __('Perbarui') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
