<x-app-layout>
    <x-slot name="header">{{ __('Edit Cabang') }}</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6">
                <form method="POST" action="{{ route('admin.branches.update', $branch) }}">
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
                        <label for="company_id" class="label"><span class="label-text font-medium">{{ __('Perusahaan (Main Office)') }}</span></label>
                        <select name="company_id" id="company_id" class="select select-bordered w-full" required>
                            @foreach($companies as $comp)
                                <option value="{{ $comp->id }}" {{ old('company_id', $branch->company_id) == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }} ({{ $comp->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($branch->is_pusat)
                        <div class="alert alert-info text-xs mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ __('Ini adalah Kantor Pusat. Kode cabang otomatis mengikuti kode perusahaan induk.') }}</span>
                        </div>
                    @endif

                    <div class="form-control w-full mb-4">
                        <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Cabang') }}</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $branch->name) }}" class="input input-bordered w-full" required>
                    </div>

                    @if(!$branch->is_pusat)
                        <div class="form-control w-full mb-4">
                            <label for="code" class="label">
                                <span class="label-text font-medium">{{ __('Kode Cabang') }}</span>
                                <span class="label-text-alt text-base-content/60">{{ __('Unique per perusahaan') }}</span>
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code', $branch->code) }}" class="input input-bordered w-full uppercase" required>
                        </div>
                    @endif

                    <div class="flex flex-wrap justify-end gap-2">
                        <a href="{{ route('admin.branches.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
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
