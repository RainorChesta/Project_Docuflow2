<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h2 class="text-xl font-bold text-base-content leading-tight">
                    {{ __('Persetujuan Dokumen') }}
                </h2>
                <p class="text-xs text-base-content/60 mt-0.5">
                    {{ __('Kelola permintaan persetujuan versi dokumen dan rollback divisi') }}
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="badge badge-primary badge-outline gap-1 text-xs py-2.5 px-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ $pendingVersions->count() + $pendingRollbacks->count() + ($pendingRenames->count() ?? 0) }} {{ __('Menunggu') }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
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

        {{-- Pending Document Rename Requests Section --}}
        @if(isset($pendingRenames) && $pendingRenames->count())
            <div class="card bg-base-100 border border-primary/30 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-primary/10 border-b border-primary/20 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-primary/20 text-primary flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                                {{ __('Permintaan Ubah Nama Dokumen') }}
                                <span class="badge badge-primary badge-sm font-semibold">{{ $pendingRenames->count() }}</span>
                            </h3>
                            <p class="text-xs text-base-content/60">
                                {{ __('Permintaan dari staf untuk memperbarui judul / nama dokumen.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Nama Baru yang Diajukan') }}</th>
                                <th class="py-3 px-4">{{ __('Diajukan Oleh') }}</th>
                                <th class="py-3 px-4">{{ __('Alasan') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($pendingRenames as $doc)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $doc) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $doc->title }}
                                        </a>
                                        @if($doc->document_number)
                                            <span class="text-xs font-mono text-base-content/50 block mt-0.5">{{ $doc->document_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        <div class="p-2 rounded-lg bg-primary/10 border border-primary/20 text-primary font-semibold text-xs break-words leading-relaxed max-h-24 overflow-y-auto" title="{{ $doc->pending_title }}">
                                            {{ $doc->pending_title }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $doc->renameRequestedBy?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $doc->renameRequestedBy?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/70 max-w-xs break-words">
                                        {{ $doc->rename_request_notes ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $doc->rename_requested_at ? $doc->rename_requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @can('approveRename', $doc)
                                                <button type="button" onclick="document.getElementById('approve-rename-modal-{{ $doc->id }}').showModal()" class="btn btn-success btn-xs gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                    {{ __('Approve') }}
                                                </button>

                                                {{-- Approve Rename Modal --}}
                                                <dialog id="approve-rename-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
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
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Perubahan Nama') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Ubah judul dokumen ini secara resmi.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('approve-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 space-y-2 text-xs">
                                                                <div>
                                                                    <span class="text-base-content/60 block">{{ __('Nama Saat Ini') }}:</span>
                                                                    <p class="font-medium text-sm text-base-content break-words mt-0.5 max-h-24 overflow-y-auto">{{ $doc->title }}</p>
                                                                </div>
                                                                <div class="border-t border-base-300/40 pt-2">
                                                                    <span class="text-base-content/60 block">{{ __('Nama Baru yang Disetujui') }}:</span>
                                                                    <p class="font-bold text-sm text-success break-words mt-0.5 max-h-24 overflow-y-auto">{{ $doc->pending_title }}</p>
                                                                </div>
                                                                @if($doc->renameRequestedBy)
                                                                    <div class="border-t border-base-300/40 pt-2">
                                                                        <span class="text-base-content/60">{{ __('Diajukan oleh') }}:</span>
                                                                        <span class="font-medium text-base-content ml-1">{{ $doc->renameRequestedBy->name }}</span>
                                                                    </div>
                                                                @endif
                                                                @if($doc->rename_request_notes)
                                                                    <div class="border-t border-base-300/40 pt-2">
                                                                        <span class="text-base-content/60">{{ __('Alasan') }}:</span>
                                                                        <p class="text-base-content/80 mt-0.5 italic">{{ $doc->rename_request_notes }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('approvals.rename-request.approve', $doc) }}">
                                                            @csrf
                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('approve-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-success btn-sm font-semibold rounded-xl px-5 shadow-xs flex items-center gap-1.5">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                                    {{ __('Setujui Perubahan Nama') }}
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                    <form method="dialog" class="modal-backdrop">
                                                        <button>{{ __('Batal') }}</button>
                                                    </form>
                                                </dialog>

                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $doc->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                    {{ __('Tolak') }}
                                                </button>

                                                {{-- Reject Rename Modal --}}
                                                <dialog id="reject-rename-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                        <div class="p-6 pb-4">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div class="flex items-center gap-3.5">
                                                                    <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                        </svg>
                                                                    </div>
                                                                    <div>
                                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Perubahan Nama Dokumen') }}</h3>
                                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Permintaan perubahan nama akan dibatalkan.') }}</p>
                                                                    </div>
                                                                </div>
                                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                                    ✕
                                                                </button>
                                                            </div>

                                                            <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 space-y-2 text-xs">
                                                                <div>
                                                                    <span class="text-base-content/60 block">{{ __('Nama Saat Ini') }}:</span>
                                                                    <p class="font-medium text-base-content break-words mt-0.5 max-h-24 overflow-y-auto">{{ $doc->title }}</p>
                                                                </div>
                                                                <div class="border-t border-base-300/40 pt-2">
                                                                    <span class="text-base-content/60 block">{{ __('Nama yang Diajukan') }}:</span>
                                                                    <p class="font-semibold text-warning break-words mt-0.5 max-h-24 overflow-y-auto">{{ $doc->pending_title }}</p>
                                                                </div>
                                                                @if($doc->renameRequestedBy)
                                                                    <div class="flex items-center justify-between">
                                                                        <span class="text-base-content/60">{{ __('Diajukan oleh') }}:</span>
                                                                        <span class="font-medium text-base-content">{{ $doc->renameRequestedBy->name }}</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route('approvals.rename-request.reject', $doc) }}">
                                                            @csrf
                                                            <div class="px-6 pb-5 space-y-2">
                                                                <div class="flex items-center justify-between">
                                                                    <label for="reject-notes-{{ $doc->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                                        {{ __('Catatan / Alasan Penolakan') }}
                                                                    </label>
                                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                                </div>
                                                                <textarea id="reject-notes-{{ $doc->id }}" name="notes" rows="3" class="textarea textarea-bordered w-full text-xs rounded-xl focus:textarea-primary leading-relaxed resize-none" placeholder="{{ __('Tuliskan alasan penolakan perubahan nama...') }}"></textarea>
                                                            </div>

                                                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                    {{ __('Batal') }}
                                                                </button>
                                                                <button type="submit" class="btn btn-error btn-sm text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
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
                                            @else
                                                <span class="badge badge-warning/15 text-warning border-warning/20 badge-sm font-medium py-2">
                                                    {{ __('Menunggu Persetujuan Atasan') }}
                                                </span>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pending Rollback Requests Section --}}
        @if($pendingRollbacks->count())
            <div class="card bg-base-100 border border-warning/30 shadow-sm overflow-hidden">
                <div class="px-5 py-4 bg-warning/10 border-b border-warning/20 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-warning/20 text-warning flex items-center justify-center shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                                {{ __('Permintaan Rollback Dokumen') }}
                                <span class="badge badge-warning badge-sm font-semibold">{{ $pendingRollbacks->count() }}</span>
                            </h3>
                            <p class="text-xs text-base-content/60">
                                {{ __('Permintaan dari staf untuk mengembalikan dokumen ke versi sebelumnya.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Target Versi') }}</th>
                                <th class="py-3 px-4">{{ __('Diajukan Oleh') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($pendingRollbacks as $doc)
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $doc) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $doc->title }}
                                        </a>
                                        @if($doc->document_number)
                                            <span class="text-xs font-mono text-base-content/50 block mt-0.5">{{ $doc->document_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="badge badge-warning badge-sm font-semibold">v{{ $doc->pendingRollbackVersion->version_number }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $doc->rollbackRequestedBy?->name ?? '—' }}</div>
                                        <div class="text-xs text-base-content/50">{{ $doc->rollbackRequestedBy?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $doc->rollback_requested_at ? $doc->rollback_requested_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" onclick="document.getElementById('approve-rollback-modal-{{ $doc->id }}').showModal()" class="btn btn-success btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Approve') }}
                                            </button>

                                            {{-- Custom Approve Rollback Modal --}}
                                            <dialog id="approve-rollback-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                                                <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                    <div class="p-6 pb-4">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="flex items-center gap-3.5">
                                                                <div class="w-11 h-11 rounded-2xl bg-success/10 text-success flex items-center justify-center shrink-0 ring-4 ring-success/5 shadow-xs">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                                    </svg>
                                                                </div>
                                                                <div>
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Permintaan Rollback') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Kembalikan dokumen ke versi v:version.', ['version' => $doc->pendingRollbackVersion->version_number]) }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('approve-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
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
                                                                <span class="font-semibold text-sm text-base-content break-words">{{ $doc->title }}</span>
                                                                <p class="text-xs text-base-content/60 mt-1">
                                                                    {{ __('Pemohon') }}: <span class="font-medium text-base-content/80">{{ $doc->rollbackRequestedBy?->name ?? '—' }}</span>
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div class="mt-3 p-3 rounded-xl bg-warning/10 border border-warning/20 text-xs text-warning-content flex items-start gap-2">
                                                            <svg class="w-4 h-4 text-warning shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                            <span>{{ __('Semua versi dokumen setelah versi v:version akan dihapus permanen dari sistem.', ['version' => $doc->pendingRollbackVersion->version_number]) }}</span>
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('approvals.rollback-request.approve', $doc) }}">
                                                        @csrf
                                                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                            <button type="button" onclick="document.getElementById('approve-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                {{ __('Batal') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                {{ __('Ya, Setujui Rollback') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>{{ __('Batal') }}</button>
                                                </form>
                                            </dialog>

                                            <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                {{ __('Reject') }}
                                            </button>
                                        </div>

                                        {{-- Reject Rollback Modal --}}
                                        <dialog id="reject-rollback-modal-{{ $doc->id }}" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs">
                                            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                {{-- Header --}}
                                                <div class="p-6 pb-4">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div class="flex items-center gap-3.5">
                                                            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Permintaan Rollback') }}</h3>
                                                                <p class="text-xs text-base-content/60 mt-0.5">{{ __('Permintaan rollback versi ini akan dibatalkan.') }}</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                            ✕
                                                        </button>
                                                    </div>

                                                    {{-- Target Info Box --}}
                                                    <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                        <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                                            </svg>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-semibold text-sm text-base-content break-words">{{ $doc->title }}</span>
                                                                @if($doc->pendingRollbackVersion)
                                                                    <span class="badge badge-warning badge-sm font-semibold">Ke v{{ $doc->pendingRollbackVersion->version_number }}</span>
                                                                @endif
                                                            </div>
                                                            @if($doc->rollbackRequestedBy)
                                                                <p class="text-xs text-base-content/60 mt-1">
                                                                    {{ __('Diajukan oleh') }}: <span class="font-medium text-base-content/80">{{ $doc->rollbackRequestedBy->name }}</span>
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Form --}}
                                                <form method="POST" action="{{ route('approvals.rollback-request.reject', $doc) }}">
                                                    @csrf
                                                    <div class="px-6 pb-5 space-y-2">
                                                        <div class="flex items-center justify-between">
                                                            <label for="reject-rollback-notes-{{ $doc->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                                {{ __('Catatan / Alasan Penolakan') }}
                                                            </label>
                                                            <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                        </div>
                                                        <div class="relative">
                                                            <textarea 
                                                                id="reject-rollback-notes-{{ $doc->id }}"
                                                                name="notes" 
                                                                maxlength="500"
                                                                class="textarea textarea-bordered w-full text-sm rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                                placeholder="{{ __('Tuliskan alasan penolakan rollback...') }}"></textarea>
                                                        </div>
                                                        <p class="text-[11px] text-base-content/50 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            {{ __('Catatan ini akan dikirimkan ke pemohon rollback.') }}
                                                        </p>
                                                    </div>

                                                    {{-- Modal Action Footer --}}
                                                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                        <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                            {{ __('Batal') }}
                                                        </button>
                                                        <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            {{ __('Tolak Rollback') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>{{ __('Batal') }}</button>
                                            </form>
                                        </dialog>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Pending Document Versions Section --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-base-300 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                            {{ __('Menunggu Persetujuan Versi Dokumen') }}
                            <span class="badge badge-primary badge-sm font-semibold">{{ $pendingVersions->count() }}</span>
                        </h3>
                        <p class="text-xs text-base-content/60">
                            {{ __('Daftar pembaruan konten dan draf revisi yang diajukan untuk disetujui.') }}
                        </p>
                    </div>
                </div>
            </div>

            @if($pendingVersions->isEmpty())
                <div class="py-12 text-center text-base-content/50 space-y-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium">{{ __('Semua dokumen telah ditinjau') }}</p>
                    <p class="text-xs text-base-content/40">{{ __('Tidak ada versi dokumen yang sedang menunggu persetujuan.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="table w-full min-w-[640px]">
                        <thead>
                            <tr class="bg-base-200/40 text-xs font-semibold uppercase tracking-wider text-base-content/70">
                                <th class="py-3 px-5">{{ __('Dokumen') }}</th>
                                <th class="py-3 px-4">{{ __('Versi') }}</th>
                                <th class="py-3 px-4">{{ __('Penulis') }}</th>
                                <th class="py-3 px-4">{{ __('Waktu Pengajuan') }}</th>
                                <th class="py-3 px-5 text-right">{{ __('Aksi') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-200">
                            @foreach($pendingVersions as $version)
                                @if(!$version->document)
                                    @continue
                                @endif
                                <tr class="hover:bg-base-200/40 transition-colors">
                                    <td class="px-5 py-4">
                                        <a href="{{ route('documents.show', $version->document) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors block break-words">
                                            {{ $version->document->title }}
                                        </a>
                                        @if($version->document->document_number)
                                            <span class="text-xs font-mono text-base-content/50 block mt-0.5">{{ $version->document->document_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="badge badge-warning badge-sm font-semibold">v{{ $version->version_number }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-medium text-sm text-base-content">{{ $version->author_name }}</div>
                                        <div class="text-xs text-base-content/50">{{ $version->author?->email ?? '' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-base-content/60 whitespace-nowrap">
                                        {{ $version->created_at ? $version->created_at->diffForHumans() : '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button" onclick="document.getElementById('approve-doc-modal-{{ $version->id }}').showModal()" class="btn btn-success btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                                {{ __('Approve') }}
                                            </button>

                                            {{-- Custom Approve Version Modal --}}
                                            <dialog id="approve-doc-modal-{{ $version->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
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
                                                                    <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Setujui Versi Dokumen') }}</h3>
                                                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Setujui versi ini (v:version) agar resmi dipublikasikan.', ['version' => $version->version_number]) }}</p>
                                                                </div>
                                                            </div>
                                                            <button type="button" onclick="document.getElementById('approve-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
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
                                                                <span class="font-semibold text-sm text-base-content break-words">{{ $version->document->title }}</span>
                                                                <p class="text-xs text-base-content/60 mt-1">
                                                                    {{ __('Penulis Versi') }}: <span class="font-medium text-base-content/80">{{ $version->author_name }}</span> &bull; <span class="badge badge-sm badge-ghost font-mono">v{{ $version->version_number }}</span>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <form method="POST" action="{{ route('approvals.approve', [$version->document, $version]) }}">
                                                        @csrf
                                                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                            <button type="button" onclick="document.getElementById('approve-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                                {{ __('Batal') }}
                                                            </button>
                                                            <button type="submit" class="btn btn-success btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-success/20 transition-all flex items-center gap-1.5">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                {{ __('Ya, Setujui Versi') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <form method="dialog" class="modal-backdrop">
                                                    <button>{{ __('Batal') }}</button>
                                                </form>
                                            </dialog>

                                            <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').showModal()" class="btn btn-error btn-outline btn-xs gap-1 font-medium">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                {{ __('Reject') }}
                                            </button>
                                        </div>

                                        {{-- Reject Reason Modal --}}
                                        <dialog id="reject-doc-modal-{{ $version->id }}" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs">
                                            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                                {{-- Header --}}
                                                <div class="p-6 pb-4">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div class="flex items-center gap-3.5">
                                                            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Versi Dokumen') }}</h3>
                                                                <p class="text-xs text-base-content/60 mt-0.5">{{ __('Pengajuan versi ini tidak akan dipublikasikan.') }}</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                                            ✕
                                                        </button>
                                                    </div>

                                                    {{-- Target Document Info Box --}}
                                                    <div class="mt-4 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 flex items-start gap-3">
                                                        <div class="p-2 rounded-lg bg-base-100 text-base-content/70 shrink-0 shadow-xs">
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </div>
                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2 flex-wrap">
                                                                <span class="font-semibold text-sm text-base-content break-words">{{ $version->document->title }}</span>
                                                                <span class="badge badge-warning badge-sm font-semibold">v{{ $version->version_number }}</span>
                                                            </div>
                                                            @if($version->author_name)
                                                                <p class="text-xs text-base-content/60 mt-1">
                                                                    {{ __('Diajukan oleh') }}: <span class="font-medium text-base-content/80">{{ $version->author_name }}</span>
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- Form --}}
                                                <form method="POST" action="{{ route('approvals.reject', [$version->document, $version]) }}">
                                                    @csrf
                                                    <div class="px-6 pb-5 space-y-2">
                                                        <div class="flex items-center justify-between">
                                                            <label for="reject-notes-{{ $version->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                                {{ __('Catatan / Alasan Penolakan') }}
                                                            </label>
                                                            <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                        </div>
                                                        <div class="relative">
                                                            <textarea 
                                                                id="reject-notes-{{ $version->id }}"
                                                                name="notes" 
                                                                maxlength="500"
                                                                class="textarea textarea-bordered w-full text-sm rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                                placeholder="{{ __('Tuliskan alasan penolakan atau catatan revisi untuk penulis...') }}"></textarea>
                                                        </div>
                                                        <p class="text-[11px] text-base-content/50 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                            {{ __('Catatan ini akan dikirimkan ke pengaju dokumen sebagai panduan perbaikan.') }}
                                                        </p>
                                                    </div>

                                                    {{-- Modal Action Footer --}}
                                                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                                        <button type="button" onclick="document.getElementById('reject-doc-modal-{{ $version->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                            {{ __('Batal') }}
                                                        </button>
                                                        <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                            </svg>
                                                            {{ __('Tolak Versi') }}
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                            <form method="dialog" class="modal-backdrop">
                                                <button>{{ __('Batal') }}</button>
                                            </form>
                                        </dialog>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
