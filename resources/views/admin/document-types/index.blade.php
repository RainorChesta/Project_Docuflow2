<x-app-layout>
    <x-slot name="header">{{ __('Tipe Dokumen') }}</x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto w-full">
            {{-- Toolbar: Add Button, Search Filter & Per Page Adjuster --}}
            <div class="mb-5 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <a href="{{ route('admin.document-types.create') }}" class="btn btn-primary btn-sm gap-1.5 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        <span>{{ __('Tipe Dokumen Baru') }}</span>
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.document-types.index') }}" class="flex flex-wrap items-center gap-2">
                    {{-- Search Input --}}
                    <div class="join flex-1 sm:flex-initial min-w-[220px]">
                        <div class="relative w-full">
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="{{ __('Cari kode atau nama...') }}"
                                   class="input input-bordered input-sm w-full pl-8 pr-7 join-item focus:outline-none">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-base-content/40">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            @if($search !== '')
                                <a href="{{ route('admin.document-types.index', ['per_page' => $perPage]) }}"
                                   class="absolute inset-y-0 right-0 pr-2 flex items-center text-base-content/40 hover:text-base-content"
                                   title="{{ __('Bersihkan pencarian') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </a>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary join-item px-3">
                            {{ __('Cari') }}
                        </button>
                    </div>

                    {{-- Per Page Selector --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-xs text-base-content/60 hidden sm:inline">{{ __('Tampilkan:') }}</span>
                        <select name="per_page"
                                class="select select-bordered select-sm text-xs"
                                onchange="this.form.submit()">
                            <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10 {{ __('per hal') }}</option>
                            <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25 {{ __('per hal') }}</option>
                            <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50 {{ __('per hal') }}</option>
                            <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100 {{ __('per hal') }}</option>
                        </select>
                    </div>
                </form>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4 text-sm py-2.5"><span>{{ session('success') }}</span></div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4 text-sm py-2.5"><span>{{ session('error') }}</span></div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="overflow-x-auto">
                        <table class="table min-w-[560px]">
                            <thead>
                                <tr class="border-b border-base-200">
                                    <th class="w-32">{{ __('Kode') }}</th>
                                    <th>{{ __('Keterangan') }}</th>
                                    <th class="w-36 text-center">{{ __('Total Dokumen') }}</th>
                                    <th class="w-24 text-right">{{ __('Aksi') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documentTypes as $type)
                                    <tr class="hover:bg-base-200/50 transition-colors">
                                        <td>
                                            <span class="badge badge-outline badge-primary font-mono font-semibold">{{ $type->code }}</span>
                                        </td>
                                        <td class="font-medium text-base-content">
                                            {{ $type->name }}
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-ghost badge-sm text-xs font-medium">
                                                {{ $type->documents_count }} {{ __('Dokumen') }}
                                            </span>
                                        </td>
                                        <td class="text-right whitespace-nowrap">
                                            <a href="{{ route('admin.document-types.edit', $type) }}"
                                               class="btn btn-ghost btn-xs btn-square text-primary"
                                               title="{{ __('Edit') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.document-types.destroy', $type) }}" class="inline" onsubmit="return confirm('{{ __('Hapus tipe dokumen ini?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-ghost btn-xs btn-square text-error ml-1"
                                                        title="{{ __('Hapus') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-10 text-base-content/60">
                                            @if($search !== '')
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                    </svg>
                                                    <p class="text-sm font-medium">{{ __('Tidak ada tipe dokumen yang cocok dengan pencarian ":search"', ['search' => $search]) }}</p>
                                                    <a href="{{ route('admin.document-types.index', ['per_page' => $perPage]) }}" class="btn btn-ghost btn-xs text-primary mt-1">
                                                        {{ __('Reset Pencarian') }}
                                                    </a>
                                                </div>
                                            @else
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <p class="text-sm font-medium">{{ __('Belum ada tipe dokumen.') }}</p>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Footer with Pagination & Result Count --}}
                    <div class="p-4 border-t border-base-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-base-content/70">
                        <div>
                            @if($documentTypes->total() > 0)
                                {{ __('Menampilkan :first sampai :last dari :total tipe dokumen', [
                                    'first' => $documentTypes->firstItem(),
                                    'last' => $documentTypes->lastItem(),
                                    'total' => $documentTypes->total()
                                ]) }}
                            @else
                                {{ __('Total :total tipe dokumen', ['total' => 0]) }}
                            @endif
                        </div>
                        <div class="shrink-0">
                            {{ $documentTypes->links('vendor.pagination.dokuflow') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
