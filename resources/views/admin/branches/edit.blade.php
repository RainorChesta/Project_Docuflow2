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
                        <div x-data="{
                            search: '',
                            open: false,
                            value: '{{ old('company_id', $branch->company_id) }}',
                            options: [
                                @foreach($companies as $comp)
                                    { id: '{{ $comp->id }}', label: '{{ addslashes($comp->name) }} ({{ addslashes($comp->code) }})' },
                                @endforeach
                            ],
                            get filteredOptions() {
                                if (this.search === '') return this.options;
                                return this.options.filter(i => i.label.toLowerCase().includes(this.search.toLowerCase()));
                            },
                            get selectedLabel() {
                                const option = this.options.find(i => i.id == this.value);
                                return option ? option.label : '{{ __('Pilih Perusahaan') }}';
                            }
                        }" class="relative w-full" @keydown.escape="open = false">
                            <input type="hidden" name="company_id" x-model="value" required>
                            
                            <div @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" class="select select-bordered w-full flex items-center justify-between cursor-pointer" :class="{ 'select-primary': open }">
                                <span x-text="selectedLabel" :class="{ 'text-base-content/50': !value }"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div x-show="open" 
                                 @click.outside="open = false" 
                                 x-transition
                                 class="absolute z-50 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-xl max-h-64 flex flex-col" 
                                 style="display: none;">
                                <div class="p-2 sticky top-0 bg-base-100 rounded-t-box border-b border-base-200">
                                    <input x-ref="searchInput" type="text" x-model="search" class="input input-sm input-bordered w-full" placeholder="{{ __('Cari Perusahaan...') }}" @keydown.enter.prevent="">
                                </div>
                                <ul class="overflow-y-auto p-2 flex flex-col gap-1">
                                    <template x-for="option in filteredOptions" :key="option.id">
                                        <li @click="value = option.id; open = false; search = ''" 
                                            class="px-3 py-2 cursor-pointer rounded-btn text-sm transition-colors"
                                            :class="value == option.id ? 'bg-primary text-primary-content' : 'hover:bg-base-200'"
                                            x-text="option.label"></li>
                                    </template>
                                    <li x-show="filteredOptions.length === 0" class="px-3 py-4 text-sm text-base-content/50 text-center">
                                        {{ __('Tidak ada perusahaan yang cocok') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    @if($branch->is_pusat)
                        <div class="alert alert-info text-xs mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <span>{{ __('Ini adalah Kantor Pusat. Kode cabang otomatis mengikuti kode perusahaan induk.') }}</span>
                        </div>
                    @endif

                    <div class="form-control w-full mb-4">
                        <label for="name" class="label"><span class="label-text font-medium">{{ __('Nama Cabang') }}</span></label>
                        <input type="text" name="name" id="name" value="{{ old('name', $branch->name) }}" class="input input-bordered w-full uppercase" oninput="this.value = this.value.toUpperCase()" required>
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
