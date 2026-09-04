<x-app-layout>
    <x-slot name="header">{{ __('Tempat Sampah (Trash)') }}</x-slot>

    <div class="py-6" x-data="{ 
        selected: [], 
        allIds: {{ json_encode($trashedDocuments->pluck('id')->all()) }},
        toggleAll() {
            if (this.selected.length === this.allIds.length) {
                this.selected = [];
            } else {
                this.selected = [...this.allIds];
            }
        }
    }">
        <div class="max-w-7xl mx-auto w-full space-y-4">
            
            {{-- Header Actions & Auto-Delete Banner --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-base-content">{{ __('Tempat Sampah Dokumen') }}</h3>
                    <p class="text-sm text-base-content/60">
                        {{ __('Dokumen yang dihapus akan disimpan di sini dan dihapus secara permanen secara otomatis setelah 30 hari.') }}
                    </p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success shadow-sm border-0 bg-success/10 text-success-content">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error shadow-sm border-0 bg-error/10 text-error-content">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Bulk Actions Bar (Appears when checkboxes are selected) --}}
            <div x-show="selected.length > 0" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="card bg-primary text-primary-content p-4 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 font-medium text-sm">
                    <span class="badge badge-outline bg-white/20 text-white border-0 font-bold px-2 py-1" x-text="selected.length"></span>
                    <span>{{ __('dokumen dipilih') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    {{-- Bulk Restore Button --}}
                    <button type="button" 
                            x-on:click="$dispatch('open-modal', 'confirm-bulk-restore')" 
                            class="btn btn-sm bg-white text-primary hover:bg-white/90 border-0 gap-1 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ __('Pulihkan Terpilih') }}
                    </button>

                    {{-- Bulk Force Delete Button --}}
                    <button type="button" 
                            x-on:click="$dispatch('open-modal', 'confirm-bulk-force-delete')" 
                            class="btn btn-sm btn-error text-white gap-1 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('Hapus Permanen Terpilih') }}
                    </button>
                </div>
            </div>

            {{-- Filter & Search Card --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <form method="GET" action="{{ route('trash.index') }}" class="flex items-center gap-3">
                    <div class="relative flex-1">
                        <input type="text" 
                               name="search" 
                               value="{{ $search }}" 
                               placeholder="{{ __('Cari dokumen berdasarkan judul atau nomor dokumen...') }}" 
                               class="input input-sm input-bordered w-full pr-10" />
                        @if($search)
                            <a href="{{ route('trash.index') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </a>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        {{ __('Cari') }}
                    </button>
                </form>
            </div>

            {{-- Trashed Documents Table --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table min-w-[800px]">
                        <thead>
                            <tr>
                                <th class="w-10 text-center">
                                    <input type="checkbox" 
                                           class="checkbox checkbox-sm" 
                                           :checked="allIds.length > 0 && selected.length === allIds.length"
                                           @change="toggleAll()" 
                                           title="{{ __('Pilih Semua') }}">
                                </th>
                                <th>{{ __('Judul / Nomor Dokumen') }}</th>
                                <th>{{ __('Pemilik') }}</th>
                                <th>{{ __('Divisi / Lokasi') }}</th>
                                <th>{{ __('Tanggal Dihapus') }}</th>
                                <th>{{ __('Sisa Waktu Auto-Delete') }}</th>
                                <th class="text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trashedDocuments as $doc)
                                @php
                                    $deletedAt = \Carbon\Carbon::parse($doc->deleted_at);
                                    $autoDeleteAt = $deletedAt->copy()->addDays(30);
                                    $daysRemaining = max(0, (int) now()->diffInDays($autoDeleteAt, false));
                                @endphp
                                <tr class="hover:bg-base-200/30 transition-colors" :class="selected.includes({{ $doc->id }}) ? 'bg-primary/5' : ''">
                                    <td class="text-center">
                                        <input type="checkbox" 
                                               value="{{ $doc->id }}" 
                                               x-model.number="selected" 
                                               class="checkbox checkbox-sm">
                                    </td>
                                    <td>
                                        <div class="font-bold text-base-content">{{ $doc->title }}</div>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                            <span class="font-mono text-xs font-medium text-base-content/70 bg-base-200/60 px-1.5 py-0.5 rounded border border-base-300/40 shrink-0">{{ $doc->document_number }}</span>
                                            <x-document-format-badge :format="$doc->format_choice" />
                                            @if($doc->documentType)
                                                <span class="badge badge-ghost badge-xs max-w-[160px] inline-flex items-center text-base-content/70 border border-base-300/40 shrink-0" title="{{ $doc->documentType->name }}">
                                                    <span class="truncate">{{ $doc->documentType->name }}</span>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-medium text-sm text-base-content">{{ $doc->owner?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $doc->owner?->email ?? '' }}</div>
                                    </td>
                                    <td>
                                        <div class="text-sm text-base-content/80 font-medium">
                                            {{ $doc->division?->name ?? __('Tanpa Divisi') }}
                                        </div>
                                        <div class="text-xs text-base-content/50">
                                            {{ $doc->branch?->company?->name ?? '' }} {{ $doc->branch ? '('.$doc->branch->name.')' : '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm text-base-content/80 font-medium">
                                            {{ $deletedAt->format('d M Y H:i') }}
                                        </div>
                                        <div class="text-xs text-base-content/50">
                                            {{ $deletedAt->diffForHumans() }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($daysRemaining > 7)
                                            <span class="badge badge-warning badge-sm gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ $daysRemaining }} {{ __('hari lagi') }}
                                            </span>
                                        @elseif($daysRemaining > 0)
                                            <span class="badge badge-error badge-sm gap-1 text-white animate-pulse">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                                {{ $daysRemaining }} {{ __('hari lagi') }}
                                            </span>
                                        @else
                                            <span class="badge badge-error badge-sm font-bold text-white">
                                                {{ __('Akan dihapus hari ini') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- Restore Button --}}
                                            @can('restore', $doc)
                                                <button type="button" 
                                                        x-on:click="$dispatch('open-modal', 'confirm-restore-{{ $doc->id }}')" 
                                                        class="btn btn-ghost btn-xs btn-square text-success hover:bg-success/10" 
                                                        title="{{ __('Pulihkan Dokumen') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                    </svg>
                                                </button>
                                            @endcan

                                            {{-- Force Delete Button --}}
                                            @can('forceDelete', $doc)
                                                <button type="button" 
                                                        x-on:click="$dispatch('open-modal', 'confirm-force-delete-{{ $doc->id }}')" 
                                                        class="btn btn-ghost btn-xs btn-square text-error hover:bg-error/10" 
                                                        title="{{ __('Hapus Permanen') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-base-content/60 py-12">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center text-base-content/40">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </div>
                                            <p class="font-medium text-base-content/70">{{ __('Tempat sampah kosong.') }}</p>
                                            <p class="text-xs text-base-content/40">{{ __('Tidak ada dokumen yang sedang berada di tempat sampah.') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($trashedDocuments->hasPages())
                    <div class="p-4 border-t border-base-200">
                        {{ $trashedDocuments->links() }}
                    </div>
                @endif
            </div>

            {{-- Confirmation Modals for Row Actions --}}
            @foreach($trashedDocuments as $doc)
                @can('restore', $doc)
                    <x-confirm-modal
                        name="confirm-restore-{{ $doc->id }}"
                        :title="__('Pulihkan Dokumen?')"
                        :message="__('Apakah Anda yakin ingin memulihkan dokumen \':title\' dari tempat sampah?', ['title' => $doc->title])"
                        :action="route('trash.restore', $doc->id)"
                        method="POST"
                        :confirmLabel="__('Pulihkan')"
                        :cancelLabel="__('Batal')"
                        confirmClass="btn-success text-white"
                        confirmIcon="restore"
                    />
                @endcan

                @can('forceDelete', $doc)
                    <x-confirm-modal
                        name="confirm-force-delete-{{ $doc->id }}"
                        :title="__('Hapus Dokumen Permanen?')"
                        :message="__('Apakah Anda yakin ingin menghapus PERMANEN dokumen \':title\'? Tindakan ini tidak dapat dibatalkan!', ['title' => $doc->title])"
                        :action="route('trash.force-delete', $doc->id)"
                        method="DELETE"
                        :confirmLabel="__('Hapus Permanen')"
                        :cancelLabel="__('Batal')"
                        confirmClass="btn-error text-white"
                        confirmIcon="trash"
                    />
                @endcan
            @endforeach

            {{-- Bulk Action Confirmation Modals --}}
            <x-confirm-modal
                name="confirm-bulk-restore"
                :title="__('Pulihkan Dokumen Terpilih?')"
                :message="__('Apakah Anda yakin ingin memulihkan semua dokumen terpilih dari tempat sampah?')"
                :action="route('trash.bulk-restore')"
                method="POST"
                :confirmLabel="__('Pulihkan Terpilih')"
                :cancelLabel="__('Batal')"
                confirmClass="btn-success text-white"
                confirmIcon="restore"
            >
                <template x-for="id in selected" :key="'modal-bulk-restore-'+id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </x-confirm-modal>

            <x-confirm-modal
                name="confirm-bulk-force-delete"
                :title="__('Hapus Permanen Dokumen Terpilih?')"
                :message="__('Apakah Anda yakin ingin menghapus PERMANEN semua dokumen yang dipilih? Tindakan ini tidak dapat dibatalkan!')"
                :action="route('trash.bulk-force-delete')"
                method="DELETE"
                :confirmLabel="__('Hapus Permanen Terpilih')"
                :cancelLabel="__('Batal')"
                confirmClass="btn-error text-white"
                confirmIcon="trash"
            >
                <template x-for="id in selected" :key="'modal-bulk-delete-'+id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </x-confirm-modal>
        </div>
    </div>
</x-app-layout>
