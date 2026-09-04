<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Persetujuan & Riwayat TTD') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Kelola permintaan penggunaan tanda tangan Anda serta riwayat pengajuan') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if(($counts['pending'] ?? 0) > 0)
                    <span class="badge badge-warning gap-1.5 text-xs py-2.5 px-3 font-semibold shadow-sm">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-600 animate-ping"></span>
                        {{ $counts['pending'] }} {{ __('Menunggu Persetujuan') }}
                    </span>
                    <button type="button" onclick="document.getElementById('approve-all-modal').showModal()" class="btn btn-success btn-xs gap-1 font-semibold text-white shadow-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        {{ __('Setujui Semua Pending') }} ({{ $counts['pending'] }})
                    </button>
                @else
                    <span class="badge badge-ghost gap-1.5 text-xs py-2.5 px-3 text-base-content/70">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        {{ __('Semua Tuntas') }}
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6" x-data="{
        selected: [],
        allPendingIds: {{ json_encode($incomingRequests->getCollection()->where('status', 'pending')->pluck('id')->values()->all()) }},
        toggleAll() {
            if (this.selected.length === this.allPendingIds.length) {
                this.selected = [];
            } else {
                this.selected = [...this.allPendingIds];
            }
        },
        isAllSelected() {
            return this.allPendingIds.length > 0 && this.selected.length === this.allPendingIds.length;
        },
        isSelected(id) {
            return this.selected.includes(id);
        },
        toggleRow(id) {
            if (this.selected.includes(id)) {
                this.selected = this.selected.filter(item => item !== id);
            } else {
                this.selected.push(id);
            }
        }
    }">
        @if(session('success'))
            <div class="alert alert-success shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-error shadow-sm">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        {{-- Bulk Action Floating Banner --}}
        <div x-show="selected.length > 0" 
             x-cloak 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="sticky top-4 z-30 p-3.5 rounded-2xl bg-base-100 border-2 border-primary shadow-xl flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-sm">
                    <span x-text="selected.length"></span>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-base-content">
                        <span x-text="selected.length"></span> {{ __('permintaan tanda tangan dipilih') }}
                    </h4>
                    <p class="text-xs text-base-content/60">
                        {{ __('Lakukan persetujuan atau penolakan serentak untuk seluruh data yang Anda tandai.') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <button type="button" @click="selected = []" class="btn btn-ghost btn-xs sm:btn-sm font-medium">
                    {{ __('Batal Pilih') }}
                </button>
                <button type="button" onclick="document.getElementById('bulk-reject-modal').showModal()" class="btn btn-error btn-outline btn-xs sm:btn-sm gap-1 font-semibold">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ __('Tolak yang Dipilih') }}
                </button>
                <button type="button" onclick="document.getElementById('bulk-approve-modal').showModal()" class="btn btn-success btn-xs sm:btn-sm gap-1 font-semibold text-white shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ __('Setujui yang Dipilih') }}
                </button>
            </div>
        </div>

        {{-- Incoming Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            {{-- Header & Stats --}}
            <div class="px-5 py-4 bg-base-200/40 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Permintaan Tanda Tangan Masuk') }}
                            <span class="badge badge-primary badge-sm font-semibold">{{ $incomingRequests->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Daftar pengguna yang meminta izin untuk menyematkan tanda tangan Anda pada dokumen.') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Filter and Search Controls Bar --}}
            <div class="p-4 border-b border-base-200 bg-base-100 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                {{-- Status Filter Tabs --}}
                <div class="flex items-center gap-1 overflow-x-auto pb-1 lg:pb-0">
                    <a href="{{ route('signatures.requests.index', array_merge(request()->query(), ['status' => 'all', 'incoming_page' => 1])) }}" 
                       class="btn btn-xs sm:btn-sm gap-1.5 rounded-lg {{ ($status ?? 'all') === 'all' ? 'btn-primary' : 'btn-ghost text-base-content/70' }}">
                        <span>{{ __('Semua') }}</span>
                        <span class="badge badge-sm {{ ($status ?? 'all') === 'all' ? 'bg-primary-content text-primary font-bold' : 'badge-ghost' }}">{{ $counts['all'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('signatures.requests.index', array_merge(request()->query(), ['status' => 'pending', 'incoming_page' => 1])) }}" 
                       class="btn btn-xs sm:btn-sm gap-1.5 rounded-lg {{ ($status ?? '') === 'pending' ? 'btn-warning text-warning-content' : 'btn-ghost text-base-content/70' }}">
                        <span>{{ __('Menunggu') }}</span>
                        <span class="badge badge-sm {{ ($status ?? '') === 'pending' ? 'bg-amber-900 text-amber-100 font-bold' : 'badge-warning badge-outline' }}">{{ $counts['pending'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('signatures.requests.index', array_merge(request()->query(), ['status' => 'approved', 'incoming_page' => 1])) }}" 
                       class="btn btn-xs sm:btn-sm gap-1.5 rounded-lg {{ ($status ?? '') === 'approved' ? 'btn-success text-white' : 'btn-ghost text-base-content/70' }}">
                        <span>{{ __('Disetujui') }}</span>
                        <span class="badge badge-sm {{ ($status ?? '') === 'approved' ? 'bg-white text-success font-bold' : 'badge-ghost' }}">{{ $counts['approved'] ?? 0 }}</span>
                    </a>
                    <a href="{{ route('signatures.requests.index', array_merge(request()->query(), ['status' => 'rejected', 'incoming_page' => 1])) }}" 
                       class="btn btn-xs sm:btn-sm gap-1.5 rounded-lg {{ ($status ?? '') === 'rejected' ? 'btn-error text-white' : 'btn-ghost text-base-content/70' }}">
                        <span>{{ __('Ditolak') }}</span>
                        <span class="badge badge-sm {{ ($status ?? '') === 'rejected' ? 'bg-white text-error font-bold' : 'badge-ghost' }}">{{ $counts['rejected'] ?? 0 }}</span>
                    </a>
                </div>

                {{-- Search Box & Per-Page Controls --}}
                <form method="GET" action="{{ route('signatures.requests.index') }}" class="flex items-center gap-2">
                    <input type="hidden" name="status" value="{{ $status ?? 'all' }}">
                    <div class="relative flex-1 sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-base-content/40">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" 
                               name="search" 
                               value="{{ $search ?? '' }}" 
                               placeholder="{{ __('Cari judul, nomor, pemohon...') }}" 
                               class="input input-sm input-bordered w-full pl-9 pr-8 text-xs rounded-lg bg-base-200/50 focus:bg-base-100" />
                        @if(!empty($search))
                            <a href="{{ route('signatures.requests.index', ['status' => $status ?? 'all']) }}" class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-base-content/40 hover:text-base-content">
                                ✕
                            </a>
                        @endif
                    </div>

                    <select name="per_page" onchange="this.form.submit()" class="select select-sm select-bordered text-xs rounded-lg bg-base-200/50">
                        <option value="10" {{ ($perPage ?? 15) == 10 ? 'selected' : '' }}>10 / hal</option>
                        <option value="15" {{ ($perPage ?? 15) == 15 ? 'selected' : '' }}>15 / hal</option>
                        <option value="25" {{ ($perPage ?? 15) == 25 ? 'selected' : '' }}>25 / hal</option>
                        <option value="50" {{ ($perPage ?? 15) == 50 ? 'selected' : '' }}>50 / hal</option>
                        <option value="100" {{ ($perPage ?? 15) == 100 ? 'selected' : '' }}>100 / hal</option>
                    </select>

                    <button type="submit" class="btn btn-sm btn-ghost btn-square">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </form>
            </div>

            @if($incomingRequests->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Tidak ada permintaan tanda tangan yang sesuai.') }}</p>
                    @if(!empty($search) || ($status ?? 'all') !== 'all')
                        <a href="{{ route('signatures.requests.index') }}" class="btn btn-ghost btn-xs text-primary mt-1">
                            {{ __('Reset Filter') }}
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[760px]">
                        <thead>
                            <tr class="bg-base-200/50 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-4 w-10 text-center">
                                    <input type="checkbox" 
                                           :checked="isAllSelected()" 
                                           @change="toggleAll()" 
                                           :disabled="allPendingIds.length === 0"
                                           class="checkbox checkbox-xs checkbox-primary rounded" 
                                           title="{{ __('Pilih semua data pending pada halaman ini') }}" />
                                </th>
                                <th class="py-3 px-4">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Pemohon') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-4">{{ __('Status') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($incomingRequests as $req)
                                <tr class="hover:bg-base-200/40 transition-colors {{ $req->isPending() ? 'bg-warning/5 font-normal' : '' }}">
                                    <td class="px-4 py-4 text-center">
                                        @if($req->isPending())
                                            <input type="checkbox" 
                                                   :value="{{ $req->id }}" 
                                                   :checked="isSelected({{ $req->id }})" 
                                                   @change="toggleRow({{ $req->id }})"
                                                   class="checkbox checkbox-xs checkbox-primary rounded" />
                                        @else
                                            <span class="text-base-content/20 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        <div class="space-y-1">
                                            @if($req->document)
                                                <div class="font-semibold text-sm text-base-content break-words line-clamp-2">
                                                    {{ $req->document->title }}
                                                </div>
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    @if($req->document->document_number)
                                                        <span class="text-[11px] font-mono text-base-content/60 bg-base-200 px-1.5 py-0.5 rounded">{{ $req->document->document_number }}</span>
                                                    @endif
                                                    @if($req->document->branch)
                                                        <span class="text-[11px] text-base-content/60 bg-base-200/80 px-1.5 py-0.5 rounded">{{ $req->document->branch->name }}</span>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="font-semibold text-sm text-base-content">{{ __('Dokumen Umum') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $req->requester?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $req->requester?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        <span title="{{ $req->requested_at ? $req->requested_at->format('d M Y H:i') : '' }}">
                                            {{ $req->requested_at ? $req->requested_at->diffForHumans() : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($req->isApproved())
                                            <span class="badge badge-success badge-sm gap-1 font-medium text-white">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Disetujui') }}
                                            </span>
                                        @elseif($req->isRejected())
                                            <div class="space-y-1">
                                                <span class="badge badge-error badge-sm gap-1 font-medium text-white">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Ditolak') }}
                                                </span>
                                                @if($req->rejected_reason)
                                                    <p class="text-[11px] text-error max-w-xs truncate" title="{{ $req->rejected_reason }}">
                                                        {{ $req->rejected_reason }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge badge-warning badge-sm gap-1 font-semibold animate-pulse">
                                                ⏳ {{ __('Pending') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1.5">
                                            @if($req->document)
                                                <a href="{{ route('documents.preview', ['document' => $req->document, 'from' => 'signature_requests']) }}" title="{{ __('Preview Dokumen') }}" class="btn btn-ghost btn-xs gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    <span>{{ __('Preview') }}</span>
                                                </a>
                                            @endif

                                            @if($req->isPending())
                                                <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').showModal()" class="btn btn-success btn-xs gap-1 font-medium text-white shadow-xs" title="{{ __('Setujui penggunaan tanda tangan Anda pada dokumen ini') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                    {{ __('Approve TTD') }}
                                                </button>

                                                {{-- Custom Approve Signature Modal --}}
                                                <dialog id="approve-modal-{{ $req->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                        <div class="p-6 pb-4">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-center gap-3.5">
                                                                    <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Permintaan Tanda Tangan') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tanda tangan Anda akan dibubuhkan secara otomatis ke dalam dokumen ini.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                                <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex items-center gap-2 flex-wrap">
                                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $req->document?->title ?? __('Dokumen') }}</span>
                                                                    </div>
                                                                    <p class="text-xs text-base-content/60 mt-1">
                                                                        {{ __('Diminta oleh') }}: <span class="font-medium text-base-content/80">{{ $req->requester?->name ?? '—' }}</span>
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div class="mt-3 p-3 rounded-xl bg-success/5 border border-success/15 flex items-start gap-2.5 text-xs text-base-content/70">
                                                                <svg class="w-4 h-4 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <span>{{ __('Pastikan Anda telah memeriksa isi dokumen sebelum memberikan persetujuan pembubuhan tanda tangan.') }}</span>
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('signatures.requests.approve', $req) }}" onsubmit="document.getElementById('approve-modal-{{ $req->id }}').close(); document.getElementById('loading-modal').showModal();">
                                                            @csrf
                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('approve-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                    {{ __('Ya, Setujui & Tanda Tangani') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>

                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium" title="{{ __('Tolak izin penggunaan tanda tangan Anda') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Reject TTD') }}
                                                </button>

                                                {{-- Reject Reason Modal --}}
                                                <dialog id="reject-modal-{{ $req->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs text-base-content">
                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
                                                        <div class="p-6 pb-4">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-center gap-3.5">
                                                                    <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Permintaan Tanda Tangan') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Izin tanda tangan tidak akan diberikan.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                                <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                </div>
                                                                <div class="min-w-0 flex-1">
                                                                    <div class="flex items-center gap-2 flex-wrap">
                                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $req->document?->title ?? __('Dokumen') }}</span>
                                                                    </div>
                                                                    <p class="text-xs text-base-content/60 mt-1">
                                                                        {{ __('Diminta oleh') }}: <span class="font-medium text-base-content/80">{{ $req->requester?->name ?? '—' }}</span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('signatures.requests.reject', $req) }}">
                                                            @csrf
                                                            <div class="px-6 pb-5 space-y-2">
                                                                <div class="flex items-center justify-between">
                                                                    <label for="reject-sig-reason-{{ $req->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                                        {{ __('Alasan Penolakan') }}
                                                                    </label>
                                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                                </div>
                                                                <div class="relative">
                                                                    <textarea 
                                                                        id="reject-sig-reason-{{ $req->id }}"
                                                                        name="reason" 
                                                                        maxlength="500"
                                                                        class="textarea textarea-bordered w-full text-sm text-base-content rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                                        placeholder="{{ __('Tuliskan alasan penolakan izin tanda tangan...') }}"></textarea>
                                                                </div>
                                                            </div>

                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('reject-modal-{{ $req->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    {{ __('Tolak Permintaan') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($incomingRequests->hasPages())
                <div class="px-5 py-3 border-t border-base-200 bg-base-200/20">
                    {{ $incomingRequests->links() }}
                </div>
            @endif
        </div>

        {{-- Bulk Approve Modal --}}
        <dialog id="bulk-approve-modal" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Permintaan Terpilih') }}</h3>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    {{ __('Konfirmasi pembubuhan tanda tangan serentak.') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('bulk-approve-modal').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                            ✕
                        </button>
                    </div>

                    <div class="mt-4 p-4 rounded-xl bg-success/10 border border-success/20 text-xs text-base-content/80 flex items-start gap-3">
                        <svg class="w-5 h-5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-bold text-success text-sm block mb-1">
                                <span x-text="selected.length"></span> {{ __('permintaan tanda tangan akan disetujui sekaligus') }}
                            </span>
                            <span>{{ __('Tanda tangan Anda akan segera dibubuhkan ke setiap dokumen yang bersangkutan dan pemohon akan menerima notifikasi.') }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('signatures.requests.bulk-approve') }}" onsubmit="document.getElementById('bulk-approve-modal').close(); document.getElementById('loading-modal').showModal();">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="request_ids[]" :value="id">
                    </template>
                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('bulk-approve-modal').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Ya, Setujui Semua yang Dipilih') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>{{ __('Batal') }}</button>
            </form>
        </dialog>

        {{-- Bulk Reject Modal --}}
        <dialog id="bulk-reject-modal" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs text-base-content">
            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg text-base-content">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Permintaan Terpilih') }}</h3>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    {{ __('Tolak izin penggunaan tanda tangan secara serentak.') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('bulk-reject-modal').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                            ✕
                        </button>
                    </div>

                    <div class="mt-4 p-4 rounded-xl bg-error/10 border border-error/20 text-xs text-base-content/80 flex items-start gap-3">
                        <svg class="w-5 h-5 text-error shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <span class="font-bold text-error text-sm block mb-1">
                                <span x-text="selected.length"></span> {{ __('permintaan tanda tangan akan ditolak') }}
                            </span>
                            <span>{{ __('Izin tanda tangan tidak akan diberikan pada dokumen-dokumen yang Anda pilih.') }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('signatures.requests.bulk-reject') }}">
                    @csrf
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="request_ids[]" :value="id">
                    </template>
                    <div class="px-6 pb-5 space-y-2">
                        <label for="bulk-reject-reason" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                            {{ __('Alasan Penolakan Massal') }}
                        </label>
                        <textarea 
                            id="bulk-reject-reason"
                            name="reason" 
                            maxlength="500"
                            class="textarea textarea-bordered w-full text-sm text-base-content rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[90px] p-3" 
                            placeholder="{{ __('Tuliskan alasan penolakan untuk seluruh permintaan terpilih...') }}"></textarea>
                    </div>

                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('bulk-reject-modal').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            {{ __('Tolak Semua yang Dipilih') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>{{ __('Batal') }}</button>
            </form>
        </dialog>

        {{-- Approve All Pending Modal --}}
        <dialog id="approve-all-modal" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Semua Pending') }}</h3>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    {{ __('Setujui seluruh :count permintaan tanda tangan yang sedang menunggu.', ['count' => $counts['pending'] ?? 0]) }}
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('approve-all-modal').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                            ✕
                        </button>
                    </div>

                    <div class="mt-4 p-4 rounded-xl bg-success/10 border border-success/20 text-xs text-base-content/80 flex items-start gap-3">
                        <svg class="w-5 h-5 text-success shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <span class="font-bold text-success text-sm block mb-1">
                                {{ __('Persetujuan Penuh :count Permintaan', ['count' => $counts['pending'] ?? 0]) }}
                            </span>
                            <span>{{ __('Seluruh permintaan tanda tangan pending yang ditujukan kepada Anda akan segera disetujui dan dibubuhkan ke dokumen masing-masing.') }}</span>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('signatures.requests.approve-all-pending') }}" onsubmit="document.getElementById('approve-all-modal').close(); document.getElementById('loading-modal').showModal();">
                    @csrf
                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('approve-all-modal').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ __('Ya, Setujui Semua (:count)', ['count' => $counts['pending'] ?? 0]) }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>{{ __('Batal') }}</button>
            </form>
        </dialog>

        {{-- Outgoing Requests Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 bg-base-200/40 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-secondary/10 text-secondary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Riwayat Pengajuan Tanda Tangan Saya') }}
                            <span class="badge badge-secondary badge-sm font-semibold">{{ $outgoingRequests->total() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Daftar tanda tangan pengguna lain yang pernah Anda ajukan untuk dokumen Anda.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($outgoingRequests->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <p class="text-sm font-medium">{{ __('Belum pernah mengajukan tanda tangan pengguna lain.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[720px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Pemilik TTD') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-4">{{ __('Status') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($outgoingRequests as $req)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4 max-w-xs">
                                        <div class="space-y-0.5">
                                            @if($req->document)
                                                <div class="font-semibold text-sm text-base-content break-words line-clamp-2">
                                                    {{ $req->document->title }}
                                                </div>
                                                @if($req->document->document_number)
                                                    <span class="text-xs font-mono text-base-content/50 block">{{ $req->document->document_number }}</span>
                                                @endif
                                            @else
                                                <span class="font-semibold text-sm text-base-content">{{ __('Dokumen Umum') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $req->targetUser?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $req->targetUser?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $req->requested_at ? $req->requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($req->isUsed())
                                            <span class="badge badge-neutral badge-sm gap-1 font-medium">✓ {{ __('Telah Digunakan') }}</span>
                                        @elseif($req->isApproved())
                                            <span class="badge badge-success badge-sm gap-1 font-medium text-white">{{ __('Disetujui (Siap Dibubuhkan)') }}</span>
                                        @elseif($req->isRejected())
                                            <div class="space-y-1">
                                                <span class="badge badge-error badge-sm gap-1 font-medium text-white">{{ __('Ditolak') }}</span>
                                                @if($req->rejected_reason)
                                                    <p class="text-[11px] text-error max-w-xs truncate" title="{{ $req->rejected_reason }}">
                                                        {{ $req->rejected_reason }}
                                                    </p>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge badge-warning badge-sm gap-1 font-medium">⏳ {{ __('Menunggu') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right whitespace-nowrap">
                                        @if($req->document)
                                            <a href="{{ route('documents.preview', ['document' => $req->document, 'from' => 'signature_requests']) }}" title="{{ __('Preview Dokumen') }}" class="btn btn-ghost btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span>{{ __('Preview') }}</span>
                                            </a>
                                        @else
                                            <span class="text-xs text-base-content/40">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($outgoingRequests->hasPages())
                <div class="px-5 py-3 border-t border-base-200 bg-base-200/20">
                    {{ $outgoingRequests->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Loading Modal --}}
    <dialog id="loading-modal" class="modal">
        <div class="modal-box flex flex-col items-center justify-center py-10">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <h3 class="font-bold text-lg mt-4">{{ __('Memproses Permintaan...') }}</h3>
            <p class="text-sm text-base-content/70 mt-2 text-center">{{ __('Harap tunggu sebentar, sistem sedang memproses pembubuhan tanda tangan secara otomatis.') }}</p>
        </div>
    </dialog>
</x-app-layout>
