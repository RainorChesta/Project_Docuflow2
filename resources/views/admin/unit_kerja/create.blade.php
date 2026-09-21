<x-app-layout>
    <x-slot name="header">{{ __('Tambah Unit Kerja') }}</x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-6"
                 x-data="{
                     kode: '{{ old('kode_unit_kerja', '') }}',
                     nama: '{{ old('nama_unit_kerja', '') }}',
                     get previewCode() {
                         return (this.kode || '01').toUpperCase().trim();
                     }
                 }">
                
                <div class="mb-5 pb-4 border-b border-base-200">
                    <h2 class="text-lg font-bold text-base-content">{{ __('Tambah Master Unit Kerja') }}</h2>
                    <p class="text-xs text-base-content/60 mt-0.5">
                        {{ __('Unit kerja berlaku secara global untuk penomoran dokumen di seluruh Cabang PT.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('admin.unit-kerja.store') }}">
                    @csrf

                    {{-- Kode Unit Kerja --}}
                    <div class="form-control w-full mb-4">
                        <label for="kode_unit_kerja" class="label">
                            <span class="label-text font-medium">{{ __('Kode Unit Kerja') }} <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/50">{{ __('Contoh: 01, 02, TMM') }}</span>
                        </label>
                        <input type="text"
                               name="kode_unit_kerja"
                               id="kode_unit_kerja"
                               x-model="kode"
                               value="{{ old('kode_unit_kerja') }}"
                               placeholder="01"
                               class="input input-bordered w-full font-mono uppercase font-semibold"
                               maxlength="50"
                               required
                               autofocus>
                        @error('kode_unit_kerja')
                            <p class="text-sm text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Nama Unit Kerja --}}
                    <div class="form-control w-full mb-4">
                        <label for="nama_unit_kerja" class="label">
                            <span class="label-text font-medium">{{ __('Nama Unit Kerja') }} <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/50">{{ __('Contoh: Tim Manajemen Mutu') }}</span>
                        </label>
                        <input type="text"
                               name="nama_unit_kerja"
                               id="nama_unit_kerja"
                               x-model="nama"
                               value="{{ old('nama_unit_kerja') }}"
                               placeholder="{{ __('Tim Manajemen Mutu') }}"
                               class="input input-bordered w-full"
                               maxlength="255"
                               required>
                        @error('nama_unit_kerja')
                            <p class="text-sm text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- PIC Unit Kerja --}}
                    <div class="form-control w-full mb-5">
                        <label for="pic_user_id" class="label">
                            <span class="label-text font-medium">{{ __('PIC / Koordinator Unit Kerja') }}</span>
                            <span class="label-text-alt text-base-content/60">{{ __('Penanggung Jawab Review Tahap 1 di Cabang') }}</span>
                        </label>
                        <div x-data="{
                            search: '',
                            open: false,
                            value: '{{ old('pic_user_id', '') }}',
                            options: [
                                { id: '', label: '{{ __('— Tidak Ada / Belum Ditentukan —') }}', sub: '' },
                                @foreach($users as $u)
                                    { 
                                        id: '{{ $u->id }}', 
                                        label: '{{ addslashes($u->name) }}', 
                                        sub: '{{ addslashes($u->email) }}{{ $u->nip ? ' • NIP: ' . addslashes($u->nip) : '' }}{{ $u->system_role ? ' • (' . strtoupper($u->system_role) . ')' : '' }}' 
                                    },
                                @endforeach
                            ],
                            get filteredOptions() {
                                if (this.search === '') return this.options;
                                const q = this.search.toLowerCase();
                                return this.options.filter(i => i.label.toLowerCase().includes(q) || (i.sub && i.sub.toLowerCase().includes(q)));
                            },
                            get selectedOption() {
                                return this.options.find(i => i.id == this.value) || this.options[0];
                            }
                        }" class="relative w-full" @keydown.escape="open = false">
                            <input type="hidden" name="pic_user_id" x-model="value">
                            
                            <div @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())" class="select select-bordered w-full flex items-center justify-between cursor-pointer" :class="{ 'select-primary': open }">
                                <div class="truncate text-left">
                                    <span x-text="selectedOption.label" :class="{ 'text-base-content/50': !value, 'font-medium': value }"></span>
                                    <span x-show="selectedOption.sub" x-text="' (' + selectedOption.sub + ')'" class="text-xs text-base-content/50 ml-1"></span>
                                </div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 opacity-50 shrink-0 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>

                            <div x-show="open" 
                                 @click.outside="open = false" 
                                 x-transition
                                 class="absolute z-50 w-full mt-1 bg-base-100 border border-base-300 rounded-box shadow-xl max-h-64 flex flex-col" 
                                 style="display: none;">
                                <div class="p-2 sticky top-0 bg-base-100 rounded-t-box border-b border-base-200">
                                    <input x-ref="searchInput" type="text" x-model="search" class="input input-sm input-bordered w-full" placeholder="{{ __('Cari user / NIP / email...') }}" @keydown.enter.prevent="">
                                </div>
                                <ul class="overflow-y-auto p-2 flex flex-col gap-1">
                                    <template x-for="option in filteredOptions" :key="option.id">
                                        <li @click="value = option.id; open = false; search = ''" 
                                            class="px-3 py-2 cursor-pointer rounded-btn text-sm transition-colors flex flex-col"
                                            :class="value == option.id ? 'bg-primary text-primary-content' : 'hover:bg-base-200'">
                                            <span class="font-medium" x-text="option.label"></span>
                                            <span x-show="option.sub" class="text-xs opacity-75" x-text="option.sub"></span>
                                        </li>
                                    </template>
                                    <li x-show="filteredOptions.length === 0" class="px-3 py-4 text-sm text-base-content/50 text-center">
                                        {{ __('Tidak ada pengguna yang cocok') }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {{-- Live Preview Penomoran --}}
                    <div class="p-3.5 rounded-xl bg-base-200/60 border border-base-300 mb-6 space-y-1">
                        <div class="text-xs font-semibold text-base-content/70 flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ __('Format Penomoran Dokumen di Cabang PT') }}
                        </div>
                        <div class="font-mono text-xs font-bold text-primary">
                            001/SK-<span x-text="previewCode"></span>/MMC/{{ \Carbon\Carbon::now()->format('m') }}/{{ \Carbon\Carbon::now()->format('Y') }}
                        </div>
                        <div class="text-[11px] text-base-content/50">
                            {{ __('Berlaku untuk semua tipe dokumen (SK, SOP, Surat, dll.) di cabang.') }}
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <a href="{{ route('admin.unit-kerja.index') }}" class="btn btn-ghost btn-sm">{{ __('Batal') }}</a>
                        <button type="submit" class="btn btn-primary btn-sm gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('Simpan Unit Kerja') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
