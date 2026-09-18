<x-app-layout>
    <x-slot name="header">{{ __('Master Unit Kerja') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full space-y-6">

            {{-- Top Header & Actions --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-base-content">{{ __('Master Unit Kerja') }}</h2>
                    <p class="text-xs text-base-content/60 mt-0.5">
                        {{ __('Kelola master unit kerja untuk penomoran dokumen di seluruh Cabang PT.') }}
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.unit-kerja.create') }}" class="btn btn-primary btn-sm gap-2 shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ __('Unit Kerja Baru') }}
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success shadow-xs text-sm py-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-error shadow-xs text-sm py-2.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            {{-- Filter & Search Toolbar --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <form method="GET" action="{{ route('admin.unit-kerja.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative flex-1 max-w-md">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="{{ __('Cari kode / nama unit kerja...') }}"
                               class="input input-bordered input-sm w-full pl-8 pr-7 text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        @if($search)
                            <a href="{{ route('admin.unit-kerja.index') }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content">
                                &times;
                            </a>
                        @endif
                    </div>
                    <div class="text-xs text-base-content/60">
                        {{ __('Total: ') }} <span class="font-bold text-base-content">{{ $totalUnitKerjas }}</span> {{ __('Unit Kerja') }}
                    </div>
                </form>
            </div>

            {{-- Data Table --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table min-w-[640px]">
                        <thead class="bg-base-200/50">
                            <tr>
                                <th class="w-32">{{ __('Kode') }}</th>
                                <th>{{ __('Nama Unit Kerja') }}</th>
                                <th class="w-36 text-center">{{ __('Pengguna') }}</th>
                                <th class="w-36 text-center">{{ __('Dokumen') }}</th>
                                <th class="w-24 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200/60">
                            @forelse($unitKerjas as $unit)
                                <tr class="hover:bg-base-200/40 transition">
                                    <td>
                                        <span class="badge badge-neutral font-mono font-bold text-xs tracking-wider px-2.5 py-1">
                                            {{ $unit->kode_unit_kerja }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-semibold text-sm text-base-content">{{ $unit->nama_unit_kerja }}</div>
                                        <div class="text-[11px] text-base-content/50 font-mono">
                                            {{ __('Format penomoran: [No]/[Tipe]-') }}{{ $unit->kode_unit_kerja }}{{ __('/[Cabang]/[Bulan]/[Tahun]') }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-sm badge-ghost font-medium">
                                            {{ $unit->users_count }} {{ __('User') }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-sm badge-ghost font-medium">
                                            {{ $unit->documents_count }} {{ __('Dokumen') }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            <a href="{{ route('admin.unit-kerja.edit', $unit) }}" class="btn btn-ghost btn-xs btn-square text-primary" title="{{ __('Edit') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            @if($unit->users_count === 0 && $unit->documents_count === 0)
                                                <form method="POST" action="{{ route('admin.unit-kerja.destroy', $unit) }}" class="inline" onsubmit="return confirm('{{ __('Hapus unit kerja ini?') }}')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-ghost btn-xs btn-square text-error" title="{{ __('Hapus') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-base-content/50 text-sm">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                        {{ __('Tidak ada data unit kerja yang ditemukan.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($unitKerjas->hasPages())
                    <div class="p-4 border-t border-base-200">
                        {{ $unitKerjas->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
