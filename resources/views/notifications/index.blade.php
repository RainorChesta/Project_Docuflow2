<x-app-layout>
    <x-slot name="header">{{ __('Notifikasi') }}</x-slot>

    <div class="py-6" x-data="{
        selected: [],
        allIds: {{ json_encode($notifications->pluck('id')->all()) }},
        toggleAll() {
            const pageAllSelected = this.allIds.length > 0 && this.allIds.every(id => this.selected.includes(id));
            if (pageAllSelected) {
                this.selected = this.selected.filter(id => !this.allIds.includes(id));
            } else {
                this.selected = Array.from(new Set([...this.selected, ...this.allIds]));
            }
        },
        clearAll() {
            this.selected = [];
        },
        iconForType(type) {
            const icons = {
                signature: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z' />`,
                stamp: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' />`,
                document: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' />`,
                approval: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' />`,
                rejected: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z' />`,
                expired: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z' />`,
                bell: `<path stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' />`,
            };
            return icons[type] || icons.bell;
        }
    }">
        <div class="max-w-7xl mx-auto w-full space-y-4">

            {{-- Header Actions --}}
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-xl font-bold text-base-content">{{ __('Daftar Notifikasi') }}</h3>
                    <p class="text-sm text-base-content/60">
                        {{ __('Lihat semua pemberitahuan, aktivitas dokumen, persetujuan, dan tanda tangan Anda.') }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-wrap sm:flex-nowrap">
                    @if($unreadCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline btn-primary gap-1 font-semibold">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ __('Tandai Semua Dibaca') }}
                            </button>
                        </form>
                    @endif

                    @if($totalCount > 0)
                        <button type="button"
                                x-on:click="$dispatch('open-modal', 'confirm-delete-all-notifications')"
                                class="btn btn-sm btn-ghost text-error hover:bg-error/10 gap-1 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            {{ __('Hapus Semua') }}
                        </button>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Bulk Actions Bar (Appears when checkboxes are selected) --}}
            <div x-show="selected.length > 0"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="card bg-primary text-primary-content p-4 shadow-lg flex flex-col sm:flex-row items-center justify-between gap-3"
                 x-cloak>
                <div class="flex items-center gap-2 font-medium text-sm">
                    <span class="badge badge-outline bg-white/20 text-white border-0 font-bold px-2 py-1" x-text="selected.length"></span>
                    <span>{{ __('notifikasi dipilih') }}</span>
                    <button type="button" @click="clearAll()" class="btn btn-ghost btn-xs text-white/80 hover:text-white rounded-lg ml-2">
                        {{ __('Batalkan Pilihan') }}
                    </button>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    {{-- Bulk Mark as Read --}}
                    <form method="POST" action="{{ route('notifications.bulk-read') }}" class="inline">
                        @csrf
                        <template x-for="id in selected" :key="'bulk-read-'+id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="submit" class="btn btn-sm bg-white text-primary hover:bg-white/90 border-0 gap-1 font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Tandai Dibaca Terpilih') }}
                        </button>
                    </form>

                    {{-- Bulk Delete Button --}}
                    <button type="button"
                            x-on:click="$dispatch('open-modal', 'confirm-bulk-delete-notifications')"
                            class="btn btn-sm btn-error text-white gap-1 font-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ __('Hapus Terpilih') }}
                    </button>
                </div>
            </div>

            {{-- Filter & Search Card --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm p-4">
                <form method="GET" action="{{ route('notifications.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                    {{-- Status Filter Tabs --}}
                    <div class="flex items-center gap-1.5 p-1 bg-base-200/60 rounded-xl shrink-0 self-start">
                        <a href="{{ route('notifications.index', ['filter' => 'all', 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $filter === 'all' ? 'bg-base-100 text-primary shadow-xs font-bold' : 'text-base-content/60 hover:text-base-content' }}">
                            {{ __('Semua') }}
                        </a>
                        <a href="{{ route('notifications.index', ['filter' => 'unread', 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 {{ $filter === 'unread' ? 'bg-base-100 text-primary shadow-xs font-bold' : 'text-base-content/60 hover:text-base-content' }}">
                            <span>{{ __('Belum Dibaca') }}</span>
                            @if($unreadCount > 0)
                                <span class="badge badge-primary badge-xs text-white font-bold px-1.5">{{ $unreadCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index', ['filter' => 'read', 'search' => $search]) }}"
                           class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $filter === 'read' ? 'bg-base-100 text-primary shadow-xs font-bold' : 'text-base-content/60 hover:text-base-content' }}">
                            {{ __('Sudah Dibaca') }}
                        </a>
                    </div>

                    {{-- Search Input --}}
                    <div class="flex items-center gap-2 flex-1 max-w-md ml-auto w-full">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        <div class="relative flex-1">
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="{{ __('Cari notifikasi berdasarkan judul, pesan, atau dokumen...') }}"
                                   class="input input-sm input-bordered w-full pr-10 rounded-xl" />
                            @if($search)
                                <a href="{{ route('notifications.index', ['filter' => $filter]) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </a>
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <span class="hidden sm:inline">{{ __('Cari') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Notifications List Card --}}
            <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
                @if($notifications->count() > 0)
                    {{-- Master Checkbox Header --}}
                    <div class="flex items-center justify-between px-4 py-2.5 bg-base-200/40 border-b border-base-300/60 text-xs text-base-content/70">
                        <label class="flex items-center gap-2.5 cursor-pointer font-semibold select-none">
                            <input type="checkbox"
                                   class="checkbox checkbox-sm checkbox-primary rounded"
                                   :checked="allIds.length > 0 && allIds.every(id => selected.includes(id))"
                                   @change="toggleAll()"
                                   title="{{ __('Pilih Semua di Halaman Ini') }}">
                            <span>{{ __('Pilih Semua di Halaman Ini') }}</span>
                        </label>
                        <span class="text-base-content/50 font-normal">
                            {{ __('Menampilkan :count dari :total notifikasi', ['count' => $notifications->count(), 'total' => $notifications->total()]) }}
                        </span>
                    </div>

                    {{-- Notification Items List --}}
                    <div class="divide-y divide-base-200">
                        @foreach($notifications as $notif)
                            @php
                                $isUnread = !$notif['read'];
                                $isRejected = $notif['status'] === 'rejected' || $notif['icon'] === 'rejected' || str_contains($notif['type'], 'reject') || str_contains($notif['type'], 'revoked');
                                $isExpired = $notif['status'] === 'expired' || $notif['icon'] === 'expired' || str_contains($notif['type'], 'expir');
                                $isApproved = $notif['status'] === 'approved' || str_contains($notif['type'], 'approved');
                                $isStamp = str_contains($notif['type'], 'stamp') || ($notif['request_type'] ?? '') === 'stamp' || ($notif['is_stamp'] ?? false);
                                $isSignature = (str_contains($notif['type'], 'signature') || ($notif['has_signature'] ?? false) || ($notif['request_type'] ?? '') === 'signature') && !$isStamp && !$isApproved && !$isRejected && !$isExpired;
                            @endphp
                            <div class="p-4 flex items-start gap-3 sm:gap-4 transition-colors hover:bg-base-200/40 {{ $isUnread ? 'bg-primary/5' : 'bg-base-100' }}"
                                 :class="selected.includes('{{ $notif['id'] }}') ? 'bg-primary/10' : ''">

                                {{-- Row Selection Checkbox --}}
                                <div class="pt-1 shrink-0">
                                    <input type="checkbox"
                                           value="{{ $notif['id'] }}"
                                           x-model="selected"
                                           class="checkbox checkbox-sm checkbox-primary rounded">
                                </div>

                                {{-- Icon Container --}}
                                <div class="shrink-0 h-10 w-10 sm:h-11 sm:w-11 flex items-center justify-center rounded-2xl {{ $isRejected || $isExpired ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400 ring-1 ring-rose-500/20' : ($isApproved ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/20' : ($isUnread ? 'bg-primary/15 text-primary ring-1 ring-primary/25' : 'bg-base-200 text-base-content/50 ring-1 ring-base-300/40')) }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        @if($isSignature)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        @elseif($isStamp || str_contains($notif['type'], 'document'))
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        @elseif($isApproved)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @elseif($isRejected)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        @elseif($isExpired)
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                        @endif
                                    </svg>
                                </div>

                                {{-- Content Area --}}
                                <div class="flex-1 min-w-0">
                                    {{-- Row Header --}}
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1.5 mb-1">
                                        <div class="flex items-center gap-2 flex-wrap min-w-0">
                                            <span class="text-sm {{ $isUnread ? 'font-bold text-base-content' : 'font-semibold text-base-content/80' }} leading-snug">
                                                {{ $notif['title'] }}
                                            </span>

                                            {{-- Badges --}}
                                            @if($isExpired)
                                                <span class="badge badge-sm bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 font-semibold">{{ __('Kedaluwarsa') }}</span>
                                            @elseif($isRejected)
                                                <span class="badge badge-sm bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 font-semibold">{{ __('Ditolak') }}</span>
                                            @elseif($isApproved)
                                                <span class="badge badge-sm bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-semibold">{{ __('Disetujui') }}</span>
                                            @endif

                                            @if($isStamp)
                                                <span class="badge badge-sm bg-base-200 text-base-content/70 border border-base-300/60 font-semibold">{{ __('Stempel') }}</span>
                                            @elseif($isSignature)
                                                <span class="badge badge-sm bg-primary/10 text-primary border border-primary/20 font-semibold">{{ __('TTD') }}</span>
                                            @endif

                                            @if($isUnread)
                                                <span class="badge badge-xs bg-primary text-white font-bold">{{ __('Baru') }}</span>
                                            @endif
                                        </div>

                                        {{-- Timestamps --}}
                                        <div class="flex items-center gap-1.5 text-xs text-base-content/50 shrink-0">
                                            <span>{{ $notif['time'] }}</span>
                                            @if(!empty($notif['created_at']))
                                                <span class="hidden md:inline">({{ \Carbon\Carbon::parse($notif['created_at'])->format('d M Y H:i') }})</span>
                                            @endif
                                            @if($isUnread)
                                                <span class="h-2 w-2 rounded-full bg-primary ring-2 ring-base-100"></span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Message --}}
                                    <p class="text-xs sm:text-sm text-base-content/70 leading-relaxed break-words [overflow-wrap:anywhere] mt-1">
                                        {{ $notif['message'] }}
                                    </p>

                                    {{-- Document Chip --}}
                                    @if(!empty($notif['document_title']))
                                        <div class="mt-2.5 inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-base-200/50 dark:bg-base-300/30 border border-base-200 dark:border-base-300/50 max-w-full">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span class="text-xs font-medium text-base-content truncate">{{ $notif['document_title'] }}</span>
                                            @if(!empty($notif['document_number']))
                                                <span class="text-[11px] text-base-content/40 font-mono shrink-0">({{ $notif['document_number'] }})</span>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Rejection / Note Callout Block --}}
                                    @if(!empty($notif['reason']))
                                        <div class="mt-2.5 p-3 rounded-xl bg-rose-500/5 border border-rose-500/15 text-xs min-w-0 max-w-full">
                                            <div class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400 font-bold text-[11px] mb-1 uppercase tracking-wider">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span>{{ __('Alasan Penolakan') }}:</span>
                                            </div>
                                            <div class="text-base-content/80 font-normal leading-relaxed break-words whitespace-pre-wrap">
                                                {{ $notif['reason'] }}
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Row Actions --}}
                                    <div class="mt-3 flex items-center justify-between gap-3 pt-2 border-t border-base-200/60">
                                        <div class="flex items-center gap-2">
                                            @if(!empty($notif['url']) && $notif['url'] !== '#')
                                                <a href="{{ $notif['url'] }}"
                                                   class="btn btn-xs btn-primary gap-1 font-semibold rounded-lg">
                                                    <span>{{ __('Buka Dokumen') }}</span>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                    </svg>
                                                </a>
                                            @endif

                                            @if($isUnread)
                                                <form method="POST" action="{{ route('notifications.read', $notif['id']) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-ghost text-base-content/70 hover:text-base-content font-medium rounded-lg">
                                                        {{ __('Tandai Dibaca') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </div>

                                        {{-- Delete Button (Single) --}}
                                        <button type="button"
                                                x-on:click="$dispatch('open-modal', 'confirm-delete-notification-{{ $notif['id'] }}')"
                                                class="btn btn-xs btn-ghost btn-square text-base-content/40 hover:text-error hover:bg-error/10"
                                                title="{{ __('Hapus Notifikasi') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Single Notification Delete Modal --}}
                            <x-confirm-modal
                                name="confirm-delete-notification-{{ $notif['id'] }}"
                                :title="__('Hapus Notifikasi?')"
                                :message="__('Apakah Anda yakin ingin menghapus notifikasi ini?')"
                                :action="route('notifications.destroy', $notif['id'])"
                                method="DELETE"
                                :confirmLabel="__('Hapus')"
                                :cancelLabel="__('Batal')"
                                confirmClass="btn-error text-white"
                                confirmIcon="trash"
                            />
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($notifications->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    <div class="flex flex-col items-center justify-center py-16 text-base-content/40 px-4">
                        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-3 text-base-content/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <p class="font-bold text-base text-base-content/70">
                            @if($search)
                                {{ __('Tidak ditemukan notifikasi untuk pencarian ":search"', ['search' => $search]) }}
                            @elseif($filter === 'unread')
                                {{ __('Tidak ada notifikasi yang belum dibaca') }}
                            @else
                                {{ __('Belum ada notifikasi') }}
                            @endif
                        </p>
                        <p class="text-xs text-base-content/50 mt-1 text-center max-w-sm">
                            {{ __('Semua aktivitas penting seperti permohonan persetujuan, tanda tangan, dan pembaruan dokumen akan muncul di sini.') }}
                        </p>
                        @if($search || $filter !== 'all')
                            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-ghost mt-4 text-primary">
                                {{ __('Reset Filter & Pencarian') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Bulk Delete Confirmation Modal --}}
            <x-confirm-modal
                name="confirm-bulk-delete-notifications"
                :title="__('Hapus Notifikasi Terpilih?')"
                :message="__('Apakah Anda yakin ingin menghapus semua notifikasi terpilih? Tindakan ini tidak dapat dibatalkan.')"
                :action="route('notifications.bulk-delete')"
                method="DELETE"
                :confirmLabel="__('Hapus Terpilih')"
                :cancelLabel="__('Batal')"
                confirmClass="btn-error text-white"
                confirmIcon="trash"
            >
                <template x-for="id in selected" :key="'modal-bulk-delete-'+id">
                    <input type="hidden" name="ids[]" :value="id">
                </template>
            </x-confirm-modal>

            {{-- Delete All Notifications Confirmation Modal --}}
            <x-confirm-modal
                name="confirm-delete-all-notifications"
                :title="__('Hapus Semua Notifikasi?')"
                :message="__('Apakah Anda yakin ingin menghapus SELURUH notifikasi Anda? Tindakan ini tidak dapat dibatalkan.')"
                :action="route('notifications.delete-all')"
                method="DELETE"
                :confirmLabel="__('Hapus Semua')"
                :cancelLabel="__('Batal')"
                confirmClass="btn-error text-white"
                confirmIcon="trash"
            />

        </div>
    </div>
</x-app-layout>

