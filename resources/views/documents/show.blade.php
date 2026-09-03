<x-app-layout>
    <x-slot name="header">{{ __('Detail Dokumen') }}</x-slot>

    @php
        $companies = $companies ?? \App\Models\Company::with('branches')->get();
        $divisions = $divisions ?? (auth()->user()?->isAdmin() ? \App\Models\Division::all() : \App\Models\Division::whereIn('id', auth()->user()?->allDivisionIds() ?? [])->get());
        $approvedSignatures = $approvedSignatures ?? [];
        $version = $version ?? $document->displayVersion();
    @endphp

    <x-confirm-modal
        name="confirm-discard-{{ $document->id }}"
        :title="__('Delete Document?')"
        :message="__('Are you sure you want to delete this document and all its changes?')"
        :action="route('documents.destroy', $document)"
        method="DELETE"
        :confirmLabel="__('Delete Document')"
        :cancelLabel="__('Batal')"
    />

    {{-- Konfirmasi approve rollback (banner pending rollback) --}}
    @if($document->hasPendingRollback() && auth()->user()->can('approve', $document))
        <x-confirm-modal
            name="confirm-approve-rollback"
            :title="__('Approve Rollback?')"
            :message="__('Rollback request will be submitted to the division head. If approved, all versions after v:version will be permanently deleted.', ['version' => $document->pendingRollbackVersion->version_number])"
            :action="route('approvals.rollback-request.approve', $document)"
            method="POST"
            :confirmLabel="__('Approve Rollback')"
            :cancelLabel="__('Batal')"
            confirmClass="btn-success"
        />
    @endif

    {{-- Konfirmasi ajukan rollback (modal Version History) --}}
    @foreach($document->versions as $version)
        @if($version->id !== $document->current_version_id
            && $version->status !== 'pending'
            && !($version->status === 'discarded' || $version->discarded_at)
            && !$document->hasPendingRollback()
            && auth()->user()->can('update', $document))
            <x-confirm-modal
                name="confirm-rollback-{{ $version->id }}"
                title="{{ __('Rollback to v:version?', ['version' => $version->version_number]) }}"
                message="{{ __('Rollback request will be submitted to the division head. If approved, all versions after v:version will be permanently deleted.', ['version' => $version->version_number]) }}"
                :action="route('approvals.rollback', [$document, $version])"
                method="POST"
                confirmLabel="{{ __('Submit Rollback') }}"
                reopenOnCancel="version-modal"
            />
        @endif
    @endforeach

    <div class="pb-6">
        <div class="max-w-7xl mx-auto w-full">
            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-error mb-4">
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Pending Rollback Approval Banner -->
            @if($document->hasPendingRollback())
                <div class="alert alert-warning mb-6 rounded-2xl shadow-xs print:hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 shrink-0 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">{{ __('Menunggu Persetujuan Rollback') }} ({{ __('ke') }} v{{ $document->pendingRollbackVersion->version_number }})</p>
                                <p class="text-xs text-base-content/70">
                                    {{ __('Diajukan oleh') }} {{ $document->rollbackRequestedBy?->name ?? '—' }}.
                                    {{ __('Versi setelah') }} v{{ $document->pendingRollbackVersion->version_number }} {{ __('akan dihapus permanen jika disetujui.') }}
                                </p>
                            </div>
                        </div>
                        @can('approve', $document)
                            <div class="flex flex-wrap gap-2 shrink-0">
                                <form method="POST" action="{{ route('approvals.rollback-request.approve', $document) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm rounded-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        {{ __('Approve Rollback') }}
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $document->id }}').showModal()" class="btn btn-outline btn-error btn-sm rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Reject') }}
                                </button>

                                {{-- Reject Rollback Modal --}}
                                <dialog id="reject-rollback-modal-{{ $document->id }}" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs">
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
                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Permintaan rollback ke versi sebelumnya akan ditolak.') }}</p>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $document->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
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
                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $document->title }}</span>
                                                        @if($document->pendingRollbackVersion)
                                                            <span class="badge badge-warning badge-sm font-semibold">Ke v{{ $document->pendingRollbackVersion->version_number }}</span>
                                                        @endif
                                                    </div>
                                                    @if($document->rollbackRequestedBy)
                                                        <p class="text-xs text-base-content/60 mt-1">
                                                            {{ __('Diajukan oleh') }}: <span class="font-medium text-base-content/80">{{ $document->rollbackRequestedBy->name }}</span>
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Form --}}
                                        <form method="POST" action="{{ route('approvals.rollback-request.reject', $document) }}">
                                            @csrf
                                            <div class="px-6 pb-5 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label for="reject-rollback-show-notes-{{ $document->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                        {{ __('Catatan / Alasan Penolakan') }}
                                                    </label>
                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                </div>
                                                <div class="relative">
                                                    <textarea 
                                                        id="reject-rollback-show-notes-{{ $document->id }}"
                                                        name="notes" 
                                                        maxlength="500"
                                                        class="textarea textarea-bordered w-full text-sm rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                        placeholder="{{ __('Tuliskan alasan penolakan untuk pemohon...') }}"></textarea>
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
                                                <button type="button" onclick="document.getElementById('reject-rollback-modal-{{ $document->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
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
                            </div>
                        @endcan
                    </div>
                </div>
            @endif

            <!-- Pending Banner (paling atas) -->
            @php $pendingVersion = $document->versions->firstWhere('status', 'pending'); @endphp
            @if($pendingVersion)
                <div class="alert alert-warning mb-6 rounded-2xl shadow-xs print:hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                        <div class="flex items-start sm:items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <p class="font-semibold text-sm">{{ __('Menunggu Persetujuan') }} (v{{ $pendingVersion->version_number }})</p>
                                <p class="text-xs text-base-content/70">{{ __('Versi menunggu review oleh kepala divisi.') }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 shrink-0">

                            @can('approve', $document)
                                <form method="POST" action="{{ route('approvals.approve', [$document, $pendingVersion]) }}" class="inline">
                                    @csrf
                                    <button class="btn btn-success btn-sm rounded-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        {{ __('Approve') }}
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('reject-version-modal-{{ $pendingVersion->id }}').showModal()" class="btn btn-error btn-sm rounded-xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Reject') }}
                                </button>

                                {{-- Reject Version Modal --}}
                                <dialog id="reject-version-modal-{{ $pendingVersion->id }}" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs">
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
                                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Tolak Dokumen') }}</h3>
                                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Pengajuan versi ini tidak akan dipublikasikan.') }}</p>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="document.getElementById('reject-version-modal-{{ $pendingVersion->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
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
                                                        <span class="font-semibold text-sm text-base-content break-words">{{ $document->title }}</span>
                                                        <span class="badge badge-warning badge-sm font-semibold">v{{ $pendingVersion->version_number }}</span>
                                                    </div>
                                                    @if($pendingVersion->author_name)
                                                        <p class="text-xs text-base-content/60 mt-1">
                                                            {{ __('Diajukan oleh') }}: <span class="font-medium text-base-content/80">{{ $pendingVersion->author_name }}</span>
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Form --}}
                                        <form method="POST" action="{{ route('approvals.reject', [$document, $pendingVersion]) }}">
                                            @csrf
                                            <div class="px-6 pb-5 space-y-2">
                                                <div class="flex items-center justify-between">
                                                    <label for="reject-version-notes-{{ $pendingVersion->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                        {{ __('Catatan / Alasan Penolakan') }}
                                                    </label>
                                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                </div>
                                                <div class="relative">
                                                    <textarea 
                                                        id="reject-version-notes-{{ $pendingVersion->id }}"
                                                        name="notes" 
                                                        maxlength="500"
                                                        class="textarea textarea-bordered w-full text-sm rounded-xl bg-base-200/30 border-base-300 focus:border-error focus:ring-2 focus:ring-error/20 focus:outline-hidden transition-all placeholder:text-base-content/40 leading-relaxed min-h-[95px] p-3" 
                                                        placeholder="{{ __('Tuliskan catatan atau masukan perbaikan untuk penulis...') }}"></textarea>
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
                                                <button type="button" onclick="document.getElementById('reject-version-modal-{{ $pendingVersion->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                                    {{ __('Batal') }}
                                                </button>
                                                <button type="submit" class="btn btn-error btn-sm sm:btn-md text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                    {{ __('Tolak Dokumen') }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <form method="dialog" class="modal-backdrop">
                                        <button>{{ __('Batal') }}</button>
                                    </form>
                                </dialog>
                            @endcan
                        </div>
                    </div>
                </div>
            @endif

            {{-- Rejection Notice Banner --}}
            @php
                $latestRejectedVersion = $document->versions->where('status', 'rejected')->sortByDesc('updated_at')->first();
            @endphp
            @if($latestRejectedVersion && (!$document->currentVersion || $latestRejectedVersion->version_number >= $document->currentVersion->version_number))
                <div id="rejection-notice-banner" class="mb-6 rounded-2xl border border-error/30 bg-error/10 p-4 sm:p-5 shadow-xs print:hidden">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 sm:gap-4">
                        <div class="flex items-start gap-3 min-w-0 flex-1">
                            <div class="p-2 rounded-xl bg-error/20 text-error shrink-0 mt-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="font-bold text-sm text-error uppercase tracking-wide">{{ __('Dokumen Ditolak') }} (v{{ $latestRejectedVersion->version_number }})</h4>
                                    <span class="text-xs text-base-content/50 font-medium">&bull; {{ $latestRejectedVersion->updated_at?->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-base-content/70 mt-0.5">
                                    {{ __('Pengajuan dokumen versi ini telah ditolak oleh peninjau.') }}
                                </p>
                            </div>
                        </div>
                        @can('update', $document)
                            <div class="shrink-0 flex items-center justify-end pl-10 sm:pl-0">
                                <a href="{{ route('documents.edit', $document) }}" class="btn btn-error btn-sm text-white font-semibold gap-2 shadow-xs rounded-xl whitespace-nowrap hover:brightness-95 transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <span>{{ __('Edit & Perbaiki') }}</span>
                                </a>
                            </div>
                        @endcan
                    </div>

                    @if($latestRejectedVersion->notes)
                        <div class="mt-3.5 sm:ml-10 p-3.5 rounded-xl bg-base-100/90 dark:bg-base-200/90 border border-error/20 text-xs text-base-content shadow-xs">
                            <div class="font-semibold text-error text-[11px] mb-1.5 flex items-center gap-1.5 uppercase tracking-wider">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                {{ __('Catatan Penolakan:') }}
                            </div>
                            <p class="text-base-content/85 text-xs leading-relaxed whitespace-pre-wrap break-words">{{ $latestRejectedVersion->notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Pending Rename Approval Banner (Executive Blue Palette) -->
            @if($document->hasPendingRename())
                <div class="mb-6 rounded-2xl border border-primary/30 dark:border-primary/40 bg-primary/10 dark:bg-primary/15 p-4 sm:p-4.5 shadow-sm print:hidden transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3.5">
                        {{-- Left: Icon, Proposed Title & Metadata --}}
                        <div class="flex items-start gap-3.5 min-w-0 flex-1">
                            <div class="w-9 h-9 rounded-xl bg-primary text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs ring-2 ring-primary/20">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm sm:text-base leading-snug">
                                    <span class="font-bold text-base-content">{{ __('Pengajuan Nama Baru') }}:</span>
                                    <span class="font-extrabold text-base-content break-words ml-1">
                                        "{{ $document->pending_title }}"
                                    </span>
                                </div>
                                <div class="text-xs text-base-content/80 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span>{{ __('Diajukan oleh') }} <strong class="text-base-content font-semibold">{{ $document->renameRequestedBy?->name ?? '—' }}</strong></span>
                                    @if($document->rename_requested_at)
                                        <span class="text-base-content/40">•</span>
                                        <span>{{ $document->rename_requested_at->diffForHumans() }}</span>
                                    @endif
                                    @if($document->rename_request_notes)
                                        <span class="text-base-content/40">•</span>
                                        <span class="italic text-base-content/70 break-words">"{{ $document->rename_request_notes }}"</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Right: Actions --}}
                        <div class="flex items-center gap-2 shrink-0 self-start md:self-center">
                            @can('approveRename', $document)
                                <form method="POST" action="{{ route('approvals.rename-request.approve', $document) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm rounded-xl text-white font-semibold gap-1.5 shadow-sm px-3.5 hover:shadow-md transition-all">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                                        {{ __('Setujui') }}
                                    </button>
                                </form>
                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $document->id }}').showModal()" class="btn btn-outline btn-error btn-sm rounded-xl font-semibold gap-1 px-3 bg-white dark:bg-transparent hover:text-white transition-all">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Tolak') }}
                                </button>

                                {{-- Reject Modal --}}
                                <dialog id="reject-rename-modal-{{ $document->id }}" class="modal modal-bottom sm:modal-middle text-left backdrop-blur-xs">
                                    <div class="modal-box p-0 overflow-hidden rounded-2xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                                        <div class="p-5 pb-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-xl bg-error/10 text-error flex items-center justify-center shrink-0">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <h3 class="font-bold text-base text-base-content leading-snug">{{ __('Tolak Perubahan Nama Dokumen') }}</h3>
                                                        <p class="text-xs text-base-content/60">{{ __('Permintaan perubahan nama akan dibatalkan.') }}</p>
                                                    </div>
                                                </div>
                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $document->id }}').close()" class="btn btn-ghost btn-xs btn-circle text-base-content/50 hover:text-base-content">
                                                    ✕
                                                </button>
                                            </div>

                                            <div class="mt-3.5 p-3.5 rounded-xl bg-base-200/60 border border-base-300/60 space-y-2 text-xs">
                                                <div>
                                                    <span class="text-base-content/60 block font-medium">{{ __('Nama Dokumen Saat Ini') }}:</span>
                                                    <p class="font-semibold text-base-content break-words mt-0.5 max-h-20 overflow-y-auto">{{ $document->title }}</p>
                                                </div>
                                                <div class="border-t border-base-300/40 pt-2">
                                                    <span class="text-base-content/60 block font-medium">{{ __('Nama Baru yang Ditolak') }}:</span>
                                                    <p class="font-bold text-primary break-words mt-0.5 max-h-20 overflow-y-auto">{{ $document->pending_title }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <form method="POST" action="{{ route('approvals.rename-request.reject', $document) }}">
                                            @csrf
                                            <div class="px-5 pb-4 space-y-1.5">
                                                <label for="reject-rename-notes-{{ $document->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                                    {{ __('Alasan Penolakan') }} <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                                </label>
                                                <textarea id="reject-rename-notes-{{ $document->id }}" name="notes" rows="2" class="textarea textarea-bordered w-full text-xs rounded-xl focus:textarea-primary leading-relaxed resize-none" placeholder="{{ __('Tuliskan alasan penolakan...') }}"></textarea>
                                            </div>

                                            <div class="bg-base-200/40 px-5 py-3 border-t border-base-200 flex items-center justify-end gap-2">
                                                <button type="button" onclick="document.getElementById('reject-rename-modal-{{ $document->id }}').close()" class="btn btn-ghost btn-sm rounded-lg font-medium text-base-content/70">
                                                    {{ __('Batal') }}
                                                </button>
                                                <button type="submit" class="btn btn-error btn-sm text-white font-semibold rounded-lg shadow-xs">
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
                                <span class="badge badge-primary text-xs font-medium py-2 px-3 shadow-xs">
                                    {{ __('Menunggu Persetujuan') }}
                                </span>
                            @endcan

                            @if($document->rename_requested_by_id === auth()->id() || $document->owner_id === auth()->id() || auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('documents.cancel-rename', $document) }}" class="inline" onsubmit="return confirm('{{ __('Batalkan permintaan perubahan nama dokumen ini?') }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm text-slate-500 hover:text-error hover:bg-error/10 rounded-xl font-normal transition-colors">
                                        {{ __('Batal') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Metadata -->
            @php
                $hasDraft = $document->versions->contains('status', 'draft');
            @endphp
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6 print:hidden">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4 border-b border-base-200 pb-4">
                        <div class="flex items-center flex-wrap gap-2 min-w-0 flex-1">
                            <h1 class="text-lg sm:text-xl font-bold text-base-content break-words">{{ $document->title }}</h1>
                            @if(auth()->user()->can('rename', $document) || auth()->user()->can('requestRename', $document))
                                <button type="button" onclick="document.getElementById('rename-document-modal-{{ $document->id }}').showModal()" class="btn btn-ghost btn-xs btn-circle text-base-content/50 hover:text-primary hover:bg-base-200 shrink-0" title="{{ auth()->user()->can('rename', $document) ? __('Ubah Nama Dokumen') : __('Ajukan Ubah Nama Dokumen') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                            @endif
                            @php
                                $contextService = app(\App\Services\CompanyContextService::class);
                                $activeBranchId = $contextService->getActiveBranchId(auth()->user());
                                $isCrossBranch = $document->branch_id && $activeBranchId && (int)$document->branch_id !== (int)$activeBranchId;
                            @endphp
                            @if($isCrossBranch)
                                <span class="badge badge-secondary badge-sm shrink-0" title="Dokumen Lintas Cabang">↗ Lintas Cabang</span>
                            @endif
                            @if($document->approver_role && $pendingVersion)
                                @php
                                    $roleColors = [
                                        'head' => 'badge-primary badge-outline',
                                        'admin' => 'badge-warning badge-outline',
                                        'direktur' => 'badge-error badge-outline'
                                    ];
                                    $roleLabels = [
                                        'head' => 'Head',
                                        'admin' => 'Admin (Fallback)',
                                        'direktur' => 'Direktur (Fallback)'
                                    ];
                                    $badgeColor = $roleColors[$document->approver_role] ?? 'badge-ghost';
                                    $badgeLabel = $roleLabels[$document->approver_role] ?? ucfirst($document->approver_role);
                                @endphp
                                <span class="badge {{ $badgeColor }} badge-sm shrink-0" title="{{ __('Jalur Persetujuan Saat Ini') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ __('Route: :role', ['role' => $badgeLabel]) }}
                                </span>
                            @endif
                        </div>
                        @if($document->document_number)
                            <div class="shrink-0 self-start sm:self-center">
                                <span class="badge badge-outline badge-sm font-mono">{{ $document->document_number }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-x-4 gap-y-5 pt-4 text-sm">
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">{{ __('Perusahaan / Cabang') }}</span>
                            <p class="font-medium mt-1">
                                {{ $document->company?->code ?? ($document->branch?->company?->code ?? '—') }} / 
                                {{ $document->branch?->name ?? '—' }}
                            </p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">{{ __('Divisi') }}</span>
                            <p class="font-medium mt-1">{{ $document->division?->code ?? '—' }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">{{ __('Pengguna') }}</span>
                            <p class="font-medium mt-1">{{ $document->owner->name }}</p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">{{ __('Status') }}</span>
                            <p class="font-medium mt-1">
                                @if($document->is_expired)
                                    <span class="badge badge-error badge-sm mb-1">{{ __('Kedaluwarsa') }}</span><br>
                                @endif
                                @if($document->currentVersion)
                                    {{ __('Aktif') }} (v{{ $document->currentVersion->version_number }})
                                @elseif($pendingVersion)
                                    <span class="text-warning">{{ __('Menunggu Persetujuan') }} (v{{ $pendingVersion->version_number }})</span>
                                @elseif($hasDraft)
                                    <span class="text-warning">{{ __('Draf') }}</span>
                                @else
                                    <span class="text-warning">{{ __('Menunggu Persetujuan Pertama') }}</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">{{ __('Visibilitas') }}</span>
                            <p class="font-medium mt-1">
                                @if($document->isGeneral())
                                    <span class="badge badge-success badge-sm">{{ __('Umum') }}</span>
                                @elseif($document->isPersonal())
                                    <span class="badge badge-info badge-sm">{{ __('Personal') }}</span>
                                @else
                                    <span class="badge badge-neutral badge-sm">{{ $document->division?->code ?? __('Divisi') }} {{ __('saja') }}</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <span class="text-xs uppercase tracking-wide text-base-content/50">{{ __('Kedaluwarsa') }}</span>
                            <p class="font-medium mt-1">
                                @if($document->expires_at)
                                    <span class="{{ $document->is_expired ? 'text-error font-semibold' : '' }}">
                                        {{ $document->expires_at->format('d M Y') }}
                                    </span>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Actions (di bawah keterangan, sejajar menyamping dalam satu baris, scrollable jika layar sempit) --}}
                    @php $isFileBased = $document->displayVersion()?->file_path; @endphp
                    <div class="mt-5 pt-4 border-t border-base-200 w-full overflow-x-auto scrollbar-hide touch-pan-x">
                        <div class="flex items-center justify-center sm:justify-start gap-2 flex-nowrap min-w-max py-1 px-1">
                            @can('update', $document)
                                @if(request('saving') == 1)
                                    <a href="{{ route('documents.edit', $document) }}" id="btn-edit-document" class="btn btn-primary btn-sm pointer-events-none opacity-50 gap-1.5 shrink-0" title="{{ __('Edit Dokumen') }}">
                                        <span id="spinner-edit-document" class="loading loading-spinner loading-xs"></span>
                                        <svg id="icon-edit-document" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 hidden shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span id="text-edit-document" class="hidden sm:inline">{{ __('Menyimpan...') }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('documents.edit', $document) }}" id="btn-edit-document" class="btn btn-primary btn-sm gap-1.5 shrink-0" title="{{ __('Edit Dokumen') }}">
                                        <span id="spinner-edit-document" class="loading loading-spinner loading-xs hidden"></span>
                                        <svg id="icon-edit-document" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span id="text-edit-document" class="hidden sm:inline">{{ __('Edit Dokumen') }}</span>
                                    </a>
                                @endif
                            @endcan
                            <a href="{{ route('documents.download', $document) }}" class="btn btn-outline btn-primary btn-sm gap-1.5 shrink-0" title="{{ __('Download DOCX') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span class="hidden sm:inline">{{ __('Download DOCX') }}</span>
                            </a>
                            @can('manageAccess', $document)
                                <button type="button" onclick="openShareModal()" class="btn btn-outline btn-primary btn-sm gap-1.5 shrink-0" title="{{ __('Bagikan') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 0a3 3 0 11-5.367 2.684 3 3 0 015.367-2.684z" /></svg>
                                    <span class="hidden sm:inline">{{ __('Bagikan') }}</span>
                                </button>
                            @endcan

                            <button
                                type="button"
                                class="btn btn-ghost btn-sm border border-base-300 gap-1.5 shrink-0"
                                onclick="openModal('version-modal')"
                                title="{{ __('Lihat Versi') }} ({{ $document->versions->count() }})"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="hidden sm:inline">{{ __('Lihat Versi') }}</span>
                                <span class="badge badge-ghost badge-xs">{{ $document->versions->count() }}</span>
                            </button>

                            @can('manageScope', $document)
                                <button type="button" class="btn btn-ghost btn-sm border border-base-300 gap-1.5 shrink-0" onclick="openModal('scope-modal')" title="{{ __('Ubah Cakupan') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="hidden sm:inline">{{ __('Ubah Cakupan') }}</span>
                                </button>
                            @endcan

                            @if(auth()->user()->can('rename', $document) || auth()->user()->can('requestRename', $document))
                                <button type="button" class="btn btn-ghost btn-sm border border-base-300 gap-1.5 shrink-0" onclick="document.getElementById('rename-document-modal-{{ $document->id }}').showModal()" title="{{ auth()->user()->can('rename', $document) ? __('Ubah Nama') : __('Ajukan Ubah Nama') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    <span class="hidden sm:inline">{{ auth()->user()->can('rename', $document) ? __('Ubah Nama') : __('Ajukan Ubah Nama') }}</span>
                                </button>
                            @endif

                            {{-- Export to PDF (hanya untuk dokumen hasil editor) --}}
                            @if(!$isFileBased)
                                <button type="button" class="btn btn-ghost btn-sm border border-base-300 gap-1.5 shrink-0"
                                        onclick="openModal('export-pdf-modal')"
                                        title="{{ __('Export PDF') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <span class="hidden sm:inline">{{ __('Export PDF') }}</span>
                                </button>
                            @endif

                            <button type="button" class="btn btn-ghost btn-sm border border-base-300 gap-1.5 shrink-0"
                                    onclick="loadSummary()"
                                    title="{{ __('Summarize Document') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span class="hidden sm:inline">{{ __('Summarize Document') }}</span>
                            </button>
                        </div>
                    </div>

                    @if(session('pdf_export'))
                        <div class="alert alert-success mt-3">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 w-full">
                                <span>{{ __('PDF successfully created.') }} <span class="font-medium">{{ session('pdf_export.filename') }}</span></span>
                                <a href="{{ session('pdf_export.url') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm shrink-0">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Content -->
            {{-- Ringkasan Dokumen (card, bukan modal) --}}
            @php
                $hasSummary = !empty($document->summary) && $document->summary_status === \App\Models\Document::SUMMARY_COMPLETED;
                $isProcessing = $document->summary_status === \App\Models\Document::SUMMARY_PROCESSING;
                $isFailed = $document->summary_status === \App\Models\Document::SUMMARY_FAILED;
            @endphp
            <div id="summary-card" class="card bg-base-100 border border-primary/20 shadow-md mb-6 print:hidden {{ (!$hasSummary && !$isProcessing && !$isFailed) ? 'hidden' : '' }}">
                <div class="card-body p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-base-200 pb-3 mb-4">
                        <div class="flex items-center gap-2.5">
                            <span class="p-2 rounded-xl bg-primary/10 text-primary">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-bold text-base text-base-content flex items-center gap-2">
                                    {{ __('AI Document Summary') }}
                                </h3>
                                <p class="text-xs text-base-content/60">{{ __('Automatic summary based on original document content') }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <div class="flex items-center gap-2">
                                <label for="summary-model" class="text-xs text-base-content/70">{{ __('AI Model:') }}</label>
                                <select id="summary-model" class="select select-bordered select-xs w-36">
                                    <option value="auto">Auto (Fallback)</option>
                                    <option value="groq">Groq (Llama)</option>
                                    <option value="deepseek">DeepSeek</option>
                                    <option value="ollama">Ollama (Custom AI)</option>
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <label for="summary-percentage" class="text-xs text-base-content/70">{{ __('Density:') }}</label>
                                <input type="range" id="summary-percentage" min="20" max="80" value="30" step="1" class="range range-xs range-primary w-24" oninput="document.getElementById('pct-val').textContent = this.value + '%'" />
                                <span id="pct-val" class="text-xs font-medium w-8">30%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="summary-copy-btn" class="btn btn-ghost btn-xs border border-base-300 text-xs {{ !$hasSummary ? 'hidden' : '' }}" onclick="copySummaryText()">
                                    <svg class="w-3.5 h-3.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span id="copy-btn-label">{{ __('Copy Summary') }}</span>
                                </button>
                                <button type="button" id="summary-regenerate" class="btn btn-primary btn-outline btn-xs text-xs" onclick="loadSummary(true)">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ __('Re-summarize') }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="summary-loading" class="{{ $isProcessing ? '' : 'hidden' }} my-3">
                        <div class="summary-loading-bar mb-3" aria-hidden="true">
                            <span class="summary-loading-shimmer"></span>
                        </div>
                        <p class="text-xs text-primary font-medium inline-flex items-center gap-2">
                            <span class="loading loading-spinner loading-xs"></span>
                            {{ __('AI is reading & summarizing the document... Please wait a moment.') }}
                        </p>
                    </div>

                    <div id="summary-body-wrapper" class="{{ !$hasSummary || $isProcessing ? 'hidden' : '' }}">
                        <div id="summary-body" class="bg-base-200/50 p-4 sm:p-5 rounded-xl border border-base-300/80 text-sm sm:text-base font-normal text-base-content leading-relaxed space-y-2">
                            @if($hasSummary)
                                {!! nl2br(e($document->summary)) !!}
                            @endif
                        </div>
                        <div class="mt-3 text-xs text-base-content/60 italic flex gap-2 p-3 bg-base-200/30 rounded-lg border border-base-200">
                            <svg class="w-4 h-4 shrink-0 text-primary mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <span class="font-semibold">{{ __('Dihasilkan oleh AI') }}:</span> 
                                {{ __('Ringkasan ini dihasilkan oleh kecerdasan buatan otomatis. AI mungkin membuat kesalahan, melewatkan konteks, atau menghilangkan detail penting. Selalu tinjau dokumen asli sebelum mengambil tindakan.') }}
                            </div>
                        </div>
                    </div>

                    <div id="summary-error" class="{{ $isFailed ? '' : 'hidden' }} alert alert-error text-sm mt-3">
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>{{ $document->summary_error ?? __('Failed to create summary. Please try again.') }}</span>
                    </div>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6 print:border-none print:shadow-none print:bg-transparent print:mb-0 print:rounded-none">
                <div class="card-body p-0">
                    @php $display = $document->displayVersion(); @endphp
                    @if($display && $display->file_path)
                        @include('documents._file-preview', ['document' => $document, 'version' => $display])
                    @elseif($display)
                        {{--
                            Halaman ini menampilkan versi dokumen yang SUDAH
                            DISETUJUI (bukan draft editor). Karena itu, include
                            ini TIDAK mengirim 'liveStorage'.

                            Alasannya: initPreviewPagination() (resources/js/
                            jodit.js) akan membaca localStorage[storageKey+':paper']
                            LEBIH DULU sebelum memakai paper_size/paper_margin
                            dari database, KALAU liveStorage dikirim. Key
                            localStorage itu ditulis LIVE oleh applyPaperSize()
                            setiap kali tombol "Ukuran Kertas" / "Margin
                            Halaman" disentuh di editor — bahkan SEBELUM form
                            disimpan (baru dibersihkan saat submit form
                            berhasil). Kalau di browser yang sama user pernah
                            membuka editor dan mengubah margin/ukuran kertas
                            tanpa menyimpan, halaman show/detail ini diam-diam
                            ikut memakai margin draft yang BUKAN margin resmi
                            dokumen — menyebabkan pagination (posisi list, dsb)
                            di show/detail berbeda dari editor walau kontennya
                            identik.

                            Karena halaman ini menampilkan versi resmi/approved,
                            dia HARUS selalu memakai paper_size/paper_margin dari
                            kolom dokumen di DB (lewat atribut data-paper-size /
                            data-paper-margin di scope), bukan draft localStorage
                            milik editor. Makanya 'liveStorage' TIDAK dikirim di
                            sini.

                            CATATAN: dropdown "Ukuran Kertas" pada partial
                            _paper.blade.php TIDAK lagi bergantung pada
                            $liveStorage (lihat fix di _paper.blade.php), jadi
                            walau liveStorage tidak dikirim di sini, dropdown
                            tetap tampil dan bisa dipakai untuk mengganti
                            tampilan ukuran kertas secara lokal di halaman ini
                            saja (tanpa menulis apa pun ke localStorage).
                        --}}
                        @include('documents._paper', [
                            'content' => $display->content,
                            'document' => $document,
                            'paperSize' => $document->paper_size ?? 'A4',
                            'paperMargin' => $document->paper_margin,
                        ])
                    @else
                        <p class="text-base-content/60 italic p-4 sm:p-6">No approved content yet.</p>
                    @endif
                </div>
            </div>

            @if($errors->has('export'))
                <div class="alert alert-error mb-6">
                    <span>{{ $errors->first('export') }} {{ __('Please try again.') }}</span>
                </div>
            @endif

            {{-- Unified Share & Access Modal --}}
            <dialog id="share-modal" class="modal">
                <div class="modal-box max-w-xl max-h-[85vh] overflow-y-auto">
                    <div class="flex flex-wrap items-center justify-between mb-4">
                        <h3 class="font-semibold text-base">{{ __('Bagikan ":title"', ['title' => $document->title]) }}</h3>
                        <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('share-modal').close()">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- Invite search --}}
                    <div class="form-control mb-2 relative">
                        <input id="share-search-input" type="text" placeholder="{{ __('Tambahkan orang atau divisi…') }}"
                               class="input input-bordered w-full" autocomplete="off">
                        <div id="share-search-results" class="hidden absolute top-full left-0 right-0 z-10 mt-1 bg-base-100 border border-base-300 rounded-box shadow-lg max-h-64 overflow-y-auto"></div>
                    </div>
                    <p id="share-search-hint" class="text-xs text-base-content/50 mb-4">{{ __('Ketik nama pengguna atau divisi untuk memberikan akses khusus.') }}</p>

                    {{-- People with access --}}
                    <div class="mb-5">
                        <h4 class="text-sm font-medium text-base-content/70 mb-2">{{ __('Orang & Divisi yang memiliki akses') }}</h4>
                        <div id="share-list" class="space-y-2 text-sm max-h-52 overflow-y-auto pr-1">
                            <div class="text-base-content/50 italic">Memuat&hellip;</div>
                        </div>
                    </div>

                    {{-- General access / Share Link --}}
                    <div class="border-t border-base-300 pt-4">
                        <h4 class="text-sm font-medium text-base-content/70 mb-3">{{ __('Akses umum (Share Link)') }}</h4>
                        <div class="space-y-3">
                            <div class="flex flex-col gap-2.5">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="radio" name="general_access" value="restricted" class="radio radio-sm" onchange="updateGeneralAccess()">
                                    <div>
                                        <span class="text-sm font-medium">{{ __('Dibatasi (Restricted)') }}</span>
                                        <p class="text-xs text-base-content/60">{{ __('Hanya orang dan divisi dengan akses khusus yang dapat membuka dokumen ini.') }}</p>
                                    </div>
                                </label>
                                <label class="flex items-start gap-2.5 cursor-pointer">
                                    <input type="radio" name="general_access" value="anyone_with_link" class="radio radio-sm mt-0.5" onchange="updateGeneralAccess()">
                                    <div class="flex-1">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-sm font-medium">{{ __('Siapa saja yang memiliki link') }}</span>
                                            <div id="link-role-container" class="inline-flex items-center gap-1.5">
                                                <select id="link-role-select" class="select select-bordered select-xs" onchange="updateGeneralAccess()">
                                                    <option value="viewer">{{ __('Viewer') }}</option>
                                                    <option value="editor">{{ __('Editor') }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <p class="text-xs text-base-content/60">{{ __('Siapa saja di internet yang memiliki link ini dapat melihat atau mengedit sesuai peran yang dipilih.') }}</p>
                                    </div>
                                </label>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-2">
                                <button type="button" class="btn btn-outline btn-primary btn-sm gap-1.5" onclick="copyShareUrl(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                    <span>{{ __('Salin Link') }}</span>
                                </button>
                                <button type="button" id="regenerate-token-btn" class="btn btn-ghost btn-sm gap-1.5" onclick="regenerateToken(this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    <span>{{ __('Buat link baru') }}</span>
                                </button>
                            </div>

                            {{-- Feedback: link disalin --}}
                            <div id="share-copied-feedback" class="hidden mt-3 p-3 bg-success/10 border border-success/20 rounded-lg transition-all">
                                <p class="text-xs font-semibold text-success flex items-center gap-1.5 mb-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ __('Link berhasil disalin') }}
                                </p>
                                <input id="share-copied-url" type="text" class="input input-bordered input-sm w-full text-xs bg-base-100" readonly onclick="this.select()" />
                            </div>
                            {{-- Feedback: link baru dibuat --}}
                            <div id="share-regenerated-feedback" class="hidden mt-3 p-3 rounded-lg transition-all"></div>
                        </div>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop">
                    <button>close</button>
                </form>
            </dialog>

            {{-- Export PDF Modal — pilih ukuran kertas HANYA untuk export ini
                 (tidak mengubah paper_size tersimpan di dokumen). Margin ikut
                 margin dokumen; kalau tidak muat di kertas yang dipilih,
                 PdfExportService akan meng-clamp-nya otomatis (lihat
                 clampMarginToPage() di resources/js/jodit.js — logikanya
                 sengaja dibuat identik dengan PdfExportService::buildHtml()). --}}
            @if(!$isFileBased)
                <dialog id="export-pdf-modal" class="modal">
                <div class="modal-box max-w-sm max-h-[85vh] overflow-y-auto">
                        <div class="flex flex-wrap items-center justify-between mb-4">
                            <h3 class="font-semibold">{{ __('Export to PDF') }}</h3>
                            <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('export-pdf-modal').close()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('documents.export-pdf', $document) }}"
                              onsubmit="this.querySelector('button[type=submit]').disabled = true;
                                        this.querySelector('button[type=submit]').classList.add('loading');
                                        this.querySelector('button[type=submit]').innerHTML = '{{ __('Creating PDF…') }}';
                                        return true;">
                            @csrf
                            <div class="form-control w-full mb-2">
                                <label class="label"><span class="label-text font-medium">Ukuran Kertas</span></label>
                                <select name="paper_size" class="select select-bordered w-full">
                                    @foreach(['A4','A5','A3','Letter','Legal'] as $size)
                                        <option value="{{ $size }}" {{ ($document->paper_size ?? 'A4') === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-base-content/50 mb-4">
                                {{ __('Margin follows current document margin; if it doesn\'t fit in the selected paper, the margin will be adjusted automatically.') }}
                            </p>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('export-pdf-modal').close()">{{ __('Batal') }}</button>
                                <button type="submit" class="btn btn-primary btn-sm">{{ __('Export') }}</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            @endif

        </div>
    </div>

    {{-- Ringkasan Dokumen: card di atas preview, bukan modal --}}
    <style>
        .summary-loading-bar {
            position: relative;
            height: 6px;
            border-radius: 9999px;
            background: var(--fallback-bc, oklch(0.278 0.033 256.848)) / 0.15;
            background: color-mix(in oklab, var(--fallback-bc, oklch(0.278 0.033 256.848)) 15%, transparent);
            overflow: hidden;
        }
        .summary-loading-shimmer {
            position: absolute;
            inset: 0;
            width: 40%;
            border-radius: 9999px;
            background: linear-gradient(90deg, transparent, var(--fallback-p, oklch(0.546 0.245 262.881)), transparent);
            animation: summary-shimmer 1.2s ease-in-out infinite;
        }
        @keyframes summary-shimmer {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(350%); }
        }
    </style>

    <script>
        const SUMMARY_KEY = 'dokuflow:summary:{{ $document->id }}';
        const SUMMARY_STATUS_URL = '{{ route('documents.summary-status', $document) }}';
        const SUMMARY_START_URL = '{{ route('documents.summarize', $document) }}';

        /**
         * Render teks ringkasan sebagai paragraf HTML sederhana.
         * Memecah berdasarkan baris kosong, lalu setiap blok jadi satu <p>.
         */
        function renderParagraphs(text) {
            if (!text) return '';
            return text
                .split(/\n\s*\n/)
                .map(p => p.trim())
                .filter(p => p.length > 0)
                .map(p => {
                    // Escape HTML
                    let safe = p.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
                    
                    // Render bold (**text**)
                    safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    
                    // Render bullet list (* item)
                    let lines = safe.split('\n');
                    let isList = false;
                    for (let i = 0; i < lines.length; i++) {
                        if (lines[i].match(/^(\*|-)\s+/)) {
                            lines[i] = '<li class="ml-5 list-disc">' + lines[i].replace(/^(\*|-)\s+/, '') + '</li>';
                            isList = true;
                        }
                    }
                    
                    if (isList) {
                        return '<ul class="mb-3 last:mb-0 leading-relaxed text-base-content space-y-1">' + lines.join('\n') + '</ul>';
                    }

                    // Biarkan newline dalam paragraf biasa jadi <br>
                    safe = safe.replace(/\n/g, '<br>');
                    return '<p class="mb-3 last:mb-0 leading-relaxed text-base-content">' + safe + '</p>';
                })
                .join('');
        }

        let summaryPollTimer = null;
        let summaryPollAttempts = 0;
        const MAX_POLL_ATTEMPTS = 60; // 60 x 2s = 120s max

        function loadSummary(force = false) {
            const card = document.getElementById('summary-card');
            const bodyWrapper = document.getElementById('summary-body-wrapper');
            const loading = document.getElementById('summary-loading');
            const error = document.getElementById('summary-error');
            const copyBtn = document.getElementById('summary-copy-btn');

            if (summaryPollTimer) {
                clearTimeout(summaryPollTimer);
                summaryPollTimer = null;
            }
            summaryPollAttempts = 0;

            if (force) localStorage.removeItem(SUMMARY_KEY);

            card.classList.remove('hidden');
            bodyWrapper.classList.add('hidden');
            error.classList.add('hidden');
            loading.classList.remove('hidden');
            if (copyBtn) copyBtn.classList.add('hidden');

            const percentage = parseInt(document.getElementById('summary-percentage')?.value || 30);
            const model = document.getElementById('summary-model')?.value || 'auto';

            fetch(SUMMARY_START_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ force: force, percentage: percentage, model: model }),
            })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    showError(data.error || 'Gagal memulai ringkasan.');
                    return;
                }
                if (data.status === 'completed' && data.summary) { finishSummary(data.summary); return; }
                if (data.status === 'failed') { showError(data.error); return; }
                pollSummary();
            })
            .catch(err => showError(err.message));
        }

        function pollSummary() {
            summaryPollAttempts++;
            if (summaryPollAttempts > MAX_POLL_ATTEMPTS) {
                showError('Pembuatan ringkasan melebihi batas waktu (timeout). Pastikan antrean `php artisan queue:work` berjalan.');
                return;
            }

            fetch(SUMMARY_STATUS_URL, { headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'completed' && data.summary) { finishSummary(data.summary); return; }
                if (data.status === 'failed') { showError(data.error); return; }
                summaryPollTimer = setTimeout(pollSummary, 2000);
            })
            .catch(() => {
                summaryPollTimer = setTimeout(pollSummary, 2000);
            });
        }

        function finishSummary(summary) {
            document.getElementById('summary-loading').classList.add('hidden');
            localStorage.setItem(SUMMARY_KEY, summary);
            const body = document.getElementById('summary-body');
            body.innerHTML = renderParagraphs(summary);
            document.getElementById('summary-body-wrapper').classList.remove('hidden');
            const copyBtn = document.getElementById('summary-copy-btn');
            if (copyBtn) copyBtn.classList.remove('hidden');
        }

        function showError(msg) {
            document.getElementById('summary-loading').classList.add('hidden');
            const error = document.getElementById('summary-error');
            const span = error.querySelector('span');
            if (span) span.textContent = msg || 'Ringkasan gagal dibuat. Silakan coba lagi.';
            error.classList.remove('hidden');
        }

        function copySummaryText() {
            const text = localStorage.getItem(SUMMARY_KEY) || document.getElementById('summary-body').innerText;
            const label = document.getElementById('copy-btn-label');
            navigator.clipboard.writeText(text).then(() => {
                label.textContent = 'Tersalin!';
                setTimeout(() => { label.textContent = 'Salin'; }, 2000);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const saved = {!! json_encode($document->summary) !!};
            if (saved) {
                const body = document.getElementById('summary-body');
                if (body) body.innerHTML = renderParagraphs(saved);
            }
            @if($document->summary_status === 'processing')
                pollSummary();
            @endif
        });
    </script>

    {{-- Share link modal (reusable) --}}
    <style>
        #share-link-modal::backdrop { background: rgba(0, 0, 0, 0.5); }
    </style>
    <script>
        @if($errors->has('file'))
            openModal('upload-version-modal');
        @endif

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            // Teleport dialog to body only if not inside an Alpine x-data component scope
            if (modal.parentElement !== document.body && !modal.closest('[x-data]')) {
                document.body.appendChild(modal);
            }
            modal.showModal();
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('dialog.modal').forEach(function(modal) {
                // DO NOT detach dialogs that belong to Alpine.js x-data components (e.g. switcher, search)
                if (modal.parentElement !== document.body && !modal.closest('[x-data]')) {
                    document.body.appendChild(modal);
                }
            });
        });

        // ---- Bagikan modal (Google Docs model) ----
        const shareDataUrl = @json(route('shares.data', $document));
        const shareStoreUrl = @json(route('shares.store', $document));
        const shareSearchUrl = @json(route('shares.search', $document));
        const shareGeneralUrl = @json(route('shares.general-access.update', $document));
        const shareRegenUrl = @json(route('shares.regenerate-token', $document));
        let shareState = null;

        async function openShareModal() {
            openModal('share-modal');
            await loadShareData();
        }

        // Backward compatibility
        function openAccessModal() { openShareModal(); }
        function openShareLinkModal() { openShareModal(); }

        async function loadShareData() {
            const list = document.getElementById('share-list');
            list.innerHTML = '<div class="text-base-content/50 italic">' + @json(__('Loading…')) + '</div>';
            try {
                const res = await fetch(shareDataUrl, { headers: { 'Accept': 'application/json' } });
                shareState = await res.json();
                renderShareList();
                renderGeneralAccess();
            } catch (e) {
                list.innerHTML = '<div class="text-error">' + @json(__('Failed to load access data.')) + '</div>';
            }
        }

        function renderShareList() {
            const list = document.getElementById('share-list');
            const rows = [];

            rows.push(`<div class="flex items-center justify-between gap-2 py-1">
                <div class="min-w-0">
                    <p class="font-medium truncate">${escapeHtml(shareState.owner.name)}</p>
                    <p class="text-xs text-base-content/50">Pemilik</p>
                </div>
                <span class="badge badge-primary badge-sm shrink-0">owner</span>
            </div>`);

            shareState.shares.forEach(s => {
                rows.push(`<div class="flex items-center justify-between gap-2 py-1">
                    <div class="min-w-0">
                        <p class="font-medium truncate">${escapeHtml(s.name)}</p>
                        <p class="text-xs text-base-content/50 truncate">${escapeHtml(s.email)}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <select class="select select-bordered select-xs" onchange="updateUserShare(${s.id}, this.value)">
                            <option value="viewer" ${s.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="editor" ${s.role === 'editor' ? 'selected' : ''}>Editor</option>
                        </select>
                        <button type="button" class="text-error hover:underline text-xs" onclick="removeUserShare(${s.id})">Hapus</button>
                    </div>
                </div>`);
            });

            shareState.division_shares.forEach(s => {
                rows.push(`<div class="flex items-center justify-between gap-2 py-1">
                    <div class="min-w-0">
                        <p class="font-medium truncate">${escapeHtml(s.name)}</p>
                        <p class="text-xs text-base-content/50">Divisi</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <select class="select select-bordered select-xs" onchange="updateDivisionShare(${s.id}, this.value)">
                            <option value="viewer" ${s.role === 'viewer' ? 'selected' : ''}>Viewer</option>
                            <option value="editor" ${s.role === 'editor' ? 'selected' : ''}>Editor</option>
                        </select>
                        <button type="button" class="text-error hover:underline text-xs" onclick="removeDivisionShare(${s.id})">Hapus</button>
                    </div>
                </div>`);
            });

            list.innerHTML = rows.join('') || '<div class="text-base-content/50 italic">' + @json(__('No other access yet.')) + '</div>';
        }

        function renderGeneralAccess() {
            const restricted = document.querySelector('input[name="general_access"][value="restricted"]');
            const anyone = document.querySelector('input[name="general_access"][value="anyone_with_link"]');
            const roleSelect = document.getElementById('link-role-select');

            if (shareState.general_access === 'anyone_with_link') {
                if (anyone) anyone.checked = true;
            } else {
                if (restricted) restricted.checked = true;
            }

            if (roleSelect && shareState.link_role) {
                roleSelect.value = shareState.link_role;
            }
        }

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[c]));
        }

        async function postForm(url, data) {
            const body = new URLSearchParams(data);
            body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                body,
            });
            if (!res.ok) throw new Error('Request failed');
        }

        async function updateUserShare(id, role) {
            const url = @json(route('shares.update', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'PATCH', role });
            await loadShareData();
        }

        async function removeUserShare(id) {
            const url = @json(route('shares.destroy', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'DELETE' });
            await loadShareData();
        }

        async function updateDivisionShare(id, role) {
            const url = @json(route('shares.division.update', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'PATCH', role });
            await loadShareData();
        }

        async function removeDivisionShare(id) {
            const url = @json(route('shares.division.destroy', [$document, '__id__'])).replace('__id__', id);
            await postForm(url, { _method: 'DELETE' });
            await loadShareData();
        }

        async function updateGeneralAccess() {
            const checkedInput = document.querySelector('input[name="general_access"]:checked');
            const access = checkedInput ? checkedInput.value : 'restricted';
            const roleSelect = document.getElementById('link-role-select');
            const linkRole = roleSelect ? roleSelect.value : 'viewer';

            await postForm(shareGeneralUrl, {
                _method: 'PATCH',
                general_access: access,
                link_role: linkRole
            });
            await loadShareData();
        }

        async function regenerateToken(btn) {
            if (!btn) btn = document.getElementById('regenerate-token-btn');
            const feedbackDiv = document.getElementById('share-regenerated-feedback');

            // Save original button content and show loading state
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> ' + @json(__('Processing…'));
            feedbackDiv.classList.add('hidden');

            try {
                const res = await fetch(shareRegenUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                });

                if (!res.ok) {
                    const errData = await res.json().catch(() => ({}));
                    throw new Error(errData.message || @json(__('Failed to create new link.')));
                }

                const data = await res.json();

                // Update the frontend state immediately so "Salin Link" copies the new URL
                if (shareState) {
                    shareState.share_token = data.share_token;
                    shareState.share_url = data.share_url;
                }

                // Show success feedback
                feedbackDiv.className = 'mt-3 p-3 bg-success/10 border border-success/20 rounded-lg transition-all';
                feedbackDiv.innerHTML = `
                    <p class="text-xs font-semibold text-success flex items-center gap-1.5 mb-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        ${@json(__('New link created successfully'))}
                    </p>
                    <input type="text" class="input input-bordered input-sm w-full text-xs bg-base-100" readonly value="${escapeHtml(data.share_url)}" onclick="this.select()" />
                `;
                feedbackDiv.classList.remove('hidden');

                // Auto-hide after 5 seconds
                setTimeout(() => { feedbackDiv.classList.add('hidden'); }, 5000);

                // Also refresh the full share data to stay in sync
                await loadShareData();
            } catch (err) {
                // Show error feedback
                feedbackDiv.className = 'mt-3 p-3 bg-error/10 border border-error/20 rounded-lg transition-all';
                feedbackDiv.innerHTML = `
                    <p class="text-xs font-semibold text-error flex items-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        ${escapeHtml(err.message)}
                    </p>
                `;
                feedbackDiv.classList.remove('hidden');
                setTimeout(() => { feedbackDiv.classList.add('hidden'); }, 5000);
            } finally {
                // Restore button
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        }

        function copyShareUrl(btn) {
            if (!shareState?.share_url) return;

            const fallbackCopy = () => {
                prompt(@json(__('Failed to copy automatically. Please copy the link below manually:')), shareState.share_url);
            };

            const showFeedback = () => {
                const feedbackDiv = document.getElementById('share-copied-feedback');
                const inputUrl = document.getElementById('share-copied-url');
                
                inputUrl.value = shareState.share_url;
                feedbackDiv.classList.remove('hidden');
                
                // Ubah state tombol
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> ' + @json(__('Copied'));
                btn.classList.add('btn-success', 'text-success-content');
                btn.classList.remove('btn-outline', 'btn-primary');

                setTimeout(() => {
                    feedbackDiv.classList.add('hidden');
                    btn.innerHTML = originalHtml;
                    btn.classList.remove('btn-success', 'text-success-content');
                    btn.classList.add('btn-outline', 'btn-primary');
                }, 3000);
            };

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(shareState.share_url)
                    .then(showFeedback)
                    .catch(fallbackCopy);
            } else {
                fallbackCopy();
            }
        }

        // Invite autocomplete
        const searchInput = document.getElementById('share-search-input');
        const searchResults = document.getElementById('share-search-results');
        let searchTimer = null;

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(async () => {
                const q = searchInput.value.trim();
                if (q.length < 1) { searchResults.classList.add('hidden'); return; }
                const res = await fetch(shareSearchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                renderSearchResults(data);
            }, 250);
        });

        function renderSearchResults(data) {
            const items = [];
            data.users.forEach(u => {
                items.push(`<button type="button" class="w-full text-left px-3 py-2 hover:bg-base-200 flex items-center justify-between gap-2"
                    onclick="inviteUser(${u.id}, '${escapeHtml(u.name).replace(/'/g, "\\'")}')">
                    <span class="min-w-0"><span class="font-medium">${escapeHtml(u.name)}</span>
                    <span class="text-xs text-base-content/50 block truncate">${escapeHtml(u.email)}</span></span>
                    <span class="badge badge-ghost badge-sm shrink-0">Pengguna</span>
                </button>`);
            });
            data.divisions.forEach(d => {
                items.push(`<button type="button" class="w-full text-left px-3 py-2 hover:bg-base-200 flex items-center justify-between gap-2"
                    onclick="inviteDivision(${d.id}, '${escapeHtml(d.name).replace(/'/g, "\\'")}')">
                    <span class="font-medium">${escapeHtml(d.name)}</span>
                    <span class="badge badge-ghost badge-sm shrink-0">Divisi</span>
                </button>`);
            });
            searchResults.innerHTML = items.join('') || '<div class="px-3 py-2 text-base-content/50">' + @json(__('Not found.')) + '</div>';
            searchResults.classList.remove('hidden');
        }

        async function inviteUser(id, name) {
            await postForm(shareStoreUrl, { type: 'user', user_id: id, role: 'viewer' });
            searchInput.value = '';
            searchResults.classList.add('hidden');
            await loadShareData();
        }

        async function inviteDivision(id, name) {
            await postForm(shareStoreUrl, { type: 'division', division_id: id, role: 'viewer' });
            searchInput.value = '';
            searchResults.classList.add('hidden');
            await loadShareData();
        }

        document.addEventListener('click', (e) => {
            if (!searchResults.contains(e.target) && e.target !== searchInput) {
                searchResults.classList.add('hidden');
            }
        });
    </script>

    {{-- Version History modal --}}
    <dialog id="version-modal" class="modal">
        <div class="modal-box max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Version History</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('version-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @if($document->hasPendingRollback())
                <div class="alert alert-warning alert-sm mb-3 text-xs">
                    Rollback ke v{{ $document->pendingRollbackVersion->version_number }} sedang menunggu approval — opsi rollback lain dinonaktifkan sementara.
                </div>
            @endif
            @forelse($document->versions->sortByDesc('version_number') as $version)
                <div class="flex flex-wrap items-center justify-between gap-2 py-2 border-b border-base-200 text-sm">
                    <div class="min-w-0">
                        <span class="font-medium">v{{ $version->version_number }}</span>
                        @if($version->file_path)
                            <span class="badge badge-ghost badge-sm ml-1">Berkas</span>
                        @endif
                        <span class="text-base-content/60">by {{ $version->author_name }}</span>
                        <span class="text-base-content/40">{{ $version->created_at->format('M d, Y H:i') }}</span>
                        @if($version->id === $document->current_version_id)
                            <span class="badge badge-success badge-sm ml-2">Active</span>
                        @elseif($version->status === 'inactive')
                            <span class="badge badge-neutral badge-sm ml-2">Inactive</span>
                        @elseif($version->status === 'pending')
                            <span class="badge badge-warning badge-sm ml-2">Pending</span>
                        @elseif($version->status === 'discarded' || $version->discarded_at)
                            <span class="badge badge-neutral badge-sm ml-2">Discarded</span>
                        @elseif($version->status === 'rejected')
                            <span class="badge badge-error badge-sm ml-2">Rejected</span>
                        @endif
                        @if($document->hasPendingRollback() && $document->pending_rollback_version_id === $version->id)
                            <span class="badge badge-warning badge-sm ml-2">Target Rollback</span>
                        @endif
                        @if($version->status === 'rejected' && $version->notes)
                            <div class="w-full mt-1.5 p-2 rounded-lg bg-error/10 border border-error/20 text-xs text-error font-medium flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                <div>
                                    <span class="font-bold">{{ __('Catatan Penolakan:') }}</span>
                                    <span>{{ $version->notes }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('documents.preview-version', [$document, $version]) }}"
                           class="btn btn-ghost btn-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Preview
                        </a>
                        @can('update', $document)
                            @if($version->id !== $document->current_version_id
                                && $version->status !== 'pending'
                                && !($version->status === 'discarded' || $version->discarded_at)
                                && !$document->hasPendingRollback())
                                <button type="button"
                                        class="btn btn-outline btn-warning btn-xs"
                                        onclick="document.getElementById('version-modal').close(); window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-rollback-{{ $version->id }}' }))">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    Rollback
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            @empty
                <p class="text-base-content/60 text-sm">No versions yet.</p>
            @endforelse
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    @can('manageScope', $document)
    {{-- Change scope modal --}}
    <dialog id="scope-modal" class="modal">
        <div class="modal-box max-w-xl max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">Change Scope</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('scope-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @php
                $currentDistributions = $document->distributions()->pluck('target_branch_id')->toArray();
                if (empty($currentDistributions)) {
                    if ($document->branch_id) {
                        $currentDistributions = [$document->branch_id];
                    } elseif ($document->company_id) {
                        $pusatBranch = \App\Models\Branch::where('company_id', $document->company_id)->where('is_pusat', true)->first();
                        if ($pusatBranch) {
                            $currentDistributions = [$pusatBranch->id];
                        }
                    }
                }
                $initialVisibility = $document->visibility;
            @endphp
            <form method="POST" action="{{ route('documents.update-visibility', $document) }}" class="space-y-4" x-data="{ visibility: '{{ $initialVisibility }}' }">
                @csrf
                @method('PATCH')
                
                <div class="space-y-3">
                    <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                        <input type="radio" name="visibility" value="general" class="radio radio-sm radio-primary"
                                x-model="visibility" {{ $document->isGeneral() ? 'checked' : '' }}>
                        <span class="block min-w-0">
                            <span class="block font-medium text-sm">General (public)</span>
                            <span class="block text-xs text-base-content/60">Terlihat oleh semua pengguna.</span>
                        </span>
                    </label>
                    <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                        <input type="radio" name="visibility" value="division" class="radio radio-sm radio-primary"
                                x-model="visibility" {{ $document->isDivision() ? 'checked' : '' }}>
                        <span class="block min-w-0">
                            <span class="block font-medium text-sm">Division only</span>
                            <span class="block text-xs text-base-content/60">Hanya divisi {{ $document->division?->code ?? '' }} yang bisa melihat.</span>
                        </span>
                    </label>
                    <label class="label cursor-pointer justify-start gap-3 rounded-lg border border-base-300 p-3 hover:bg-base-200/50">
                        <input type="radio" name="visibility" value="personal" class="radio radio-sm radio-primary"
                                x-model="visibility" {{ $document->isPersonal() ? 'checked' : '' }}>
                        <span class="block min-w-0">
                            <span class="block font-medium text-sm">Personal</span>
                            <span class="block text-xs text-base-content/60">Hanya kamu yang bisa melihat.</span>
                        </span>
                    </label>
                </div>

                {{-- Multi-Company & Branch Assignment (Sharing) - Only visible if general --}}
                <div x-show="visibility === 'general'" x-transition class="mt-6 border-t border-base-300 pt-4">
                    <h4 class="font-semibold text-sm mb-2">{{ __('Bagikan ke Cabang Lain (Opsional)') }}</h4>
                    <p class="text-xs text-base-content/60 mb-4">{{ __('Pilih perusahaan dan cabang yang diizinkan untuk melihat dokumen ini. Dokumen akan muncul di Dokumen Umum mereka.') }}</p>

                    <div x-data="{
                        search: '',
                        open: false,
                        selectedCompanies: [],
                        selectedBranches: {{ json_encode($currentDistributions) }}.map(String),
                        companies: [
                            @foreach($companies as $company)
                                { id: {{ $company->id }}, name: '{{ addslashes($company->name) }} ({{ addslashes($company->code) }})', branchIds: {{ $company->branches->pluck('id')->toJson() }} },
                            @endforeach
                        ],
                        get filteredCompanies() {
                            if (this.search === '') {
                                return this.companies.filter(c => !this.selectedCompanies.includes(String(c.id)));
                            }
                            return this.companies.filter(c => 
                                !this.selectedCompanies.includes(String(c.id)) && 
                                c.name.toLowerCase().includes(this.search.toLowerCase())
                            );
                        },
                        toggleCompany(id) {
                            id = String(id);
                            if (this.selectedCompanies.includes(id)) {
                                this.selectedCompanies = this.selectedCompanies.filter(c => c !== id);
                                let company = this.companies.find(c => String(c.id) === id);
                                if(company) {
                                    this.selectedBranches = this.selectedBranches.filter(b => !company.branchIds.map(String).includes(String(b)));
                                }
                            } else {
                                this.selectedCompanies.push(id);
                            }
                            this.search = '';
                            this.$refs.searchInput.focus();
                        },
                        init() {
                            // Pre-select companies if any of their branches are selected
                            this.companies.forEach(company => {
                                let hasBranch = company.branchIds.some(b => this.selectedBranches.includes(String(b)));
                                if (hasBranch) {
                                    this.selectedCompanies.push(String(company.id));
                                }
                            });
                        }
                    }" class="relative mb-6">
                        
                        <div class="border border-base-300 rounded-lg p-2 min-h-[3rem] flex flex-wrap gap-2 items-center bg-base-100 cursor-text"
                             @click="open = true; $refs.searchInput.focus()"
                             @click.away="open = false">
                            
                            <template x-for="id in selectedCompanies" :key="id">
                                <div class="badge badge-primary gap-1 p-3">
                                    <span x-text="companies.find(c => String(c.id) === String(id))?.name"></span>
                                    <button type="button" @click.stop="toggleCompany(id)" class="hover:bg-primary-focus rounded-full p-0.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            </template>
                            
                            <input type="text" x-ref="searchInput" x-model="search" @focus="open = true" @keydown.backspace="if(search === '' && selectedCompanies.length > 0) toggleCompany(selectedCompanies[selectedCompanies.length - 1])" class="flex-1 outline-none bg-transparent min-w-[150px] text-sm" placeholder="{{ __('Tambah perusahaan...') }}">
                        </div>
                        
                        <div x-show="open" 
                             x-transition
                             class="absolute z-10 mt-1 w-full bg-base-100 border border-base-300 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                            <template x-if="filteredCompanies.length === 0">
                                <div class="p-3 text-sm text-base-content/60 text-center">{{ __('Tidak ada perusahaan ditemukan') }}</div>
                            </template>
                            <template x-for="company in filteredCompanies" :key="company.id">
                                <div @click="toggleCompany(company.id)" class="p-3 hover:bg-base-200 cursor-pointer text-sm">
                                    <span x-text="company.name"></span>
                                </div>
                            </template>
                        </div>

                        <div class="space-y-4 mt-4">
                            @foreach($companies as $company)
                                <div class="border border-base-300 rounded-lg p-4 bg-base-200/30"
                                     x-show="selectedCompanies.includes(String({{ $company->id }}))"
                                     x-data="{ 
                                        branchIds: {{ $company->branches->pluck('id')->toJson() }}
                                     }">
                                    
                                    <div class="flex items-center justify-between border-b border-base-300 pb-2 mb-3">
                                        <span class="font-medium text-sm">{{ $company->name }} ({{ $company->code }}) - Cabang Tujuan</span>
                                        <label class="flex items-center gap-2 cursor-pointer text-xs">
                                            <input type="checkbox" 
                                                   :checked="branchIds.length > 0 && branchIds.every(b => selectedBranches.includes(String(b)))"
                                                   @change="
                                                        if ($el.checked) {
                                                            branchIds.forEach(b => { if (!selectedBranches.includes(String(b))) selectedBranches.push(String(b)); });
                                                        } else {
                                                            selectedBranches = selectedBranches.filter(b => !branchIds.map(String).includes(String(b)));
                                                        }
                                                   "
                                                   class="checkbox checkbox-xs checkbox-primary">
                                            <span>{{ __('Pilih Semua') }}</span>
                                        </label>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        @foreach($company->branches as $branch)
                                            <label class="flex items-center gap-2 cursor-pointer text-xs">
                                                <input type="checkbox" name="target_branch_ids[]" value="{{ $branch->id }}"
                                                       x-model="selectedBranches"
                                                       class="checkbox checkbox-xs checkbox-secondary">
                                                <span>{{ $branch->name }} @if($branch->is_pusat)<span class="text-primary font-semibold">({{ __('Pusat') }})</span>@else({{ $branch->code }})@endif</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-2 pt-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('scope-modal').close()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Save
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
    @endcan

    {{-- Edit Restricted Info Modal --}}
    <dialog id="edit-restricted-modal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">{{ __('Dokumen Tidak Dapat Diedit Langsung') }}</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('edit-restricted-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="text-sm text-base-content/70 mb-5">
                {{ __('Dokumen ini berasal dari berkas yang diunggah, bukan ditulis melalui editor, sehingga isinya tidak dapat diedit secara langsung. Terdapat dua cara untuk memperbarui dokumen:') }}
            </p>
            <ul class="text-sm space-y-2 mb-5 list-disc list-inside text-base-content/80">
                <li><span class="font-medium">{{ __('Rollback') }}</span> {{ __('ke versi sebelumnya yang masih tersimpan.') }}</li>
                <li><span class="font-medium">{{ __('Unggah versi terbaru') }}</span> {{ __('untuk menggantikan isi dokumen saat ini.') }}</li>
            </ul>
            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('edit-restricted-modal').close(); openModal('version-modal');">
                    {{ __('Lihat Versi') }}
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('edit-restricted-modal').close(); openModal('upload-version-modal');">
                    {{ __('Unggah Versi Terbaru') }}
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Upload new version modal --}}
    <dialog id="upload-version-modal" class="modal">
        <div class="modal-box max-w-md max-h-[85vh] overflow-y-auto">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h3 class="font-semibold">{{ __('Unggah Versi Terbaru') }}</h3>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('upload-version-modal').close()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST" action="{{ route('documents.upload-version', $document) }}" enctype="multipart/form-data">
                @csrf
                <div class="form-control w-full mb-4">
                    <label for="upload-version-file" class="label">
                        <span class="label-text font-medium">{{ __('Berkas Pengganti') }}</span>
                    </label>
                    <input type="file" name="file" id="upload-version-file" accept=".pdf,.docx" class="file-input file-input-bordered w-full" required>
                    <p class="text-xs text-base-content/50 mt-1">{{ __('Hanya PDF atau DOCX, maksimal 10MB. Versi baru akan menunggu approval kepala divisi.') }}</p>
                    @error('file') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('upload-version-modal').close()">{{ __('Batal') }}</button>
                    <button type="submit" class="btn btn-primary btn-sm">{{ __('Unggah') }}</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>

    {{-- Rename Document Modal --}}
    @if(auth()->user()->can('rename', $document) || auth()->user()->can('requestRename', $document))
        @php
            $canDirectRename = auth()->user()->can('rename', $document);
        @endphp
        <dialog id="rename-document-modal-{{ $document->id }}" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
            <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-lg">
                <div class="p-6 pb-4">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-primary/10 text-primary flex items-center justify-center shrink-0 ring-4 ring-primary/5 shadow-xs">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-lg text-base-content leading-snug">
                                    {{ $canDirectRename ? __('Ubah Nama Dokumen') : __('Ajukan Perubahan Nama Dokumen') }}
                                </h3>
                                <p class="text-xs text-base-content/60 mt-0.5">
                                    {{ $canDirectRename 
                                        ? __('Perbarui nama dokumen ini secara langsung.') 
                                        : __('Perubahan nama akan diajukan untuk disetujui.') }}
                                </p>
                            </div>
                        </div>
                        <button type="button" onclick="document.getElementById('rename-document-modal-{{ $document->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                            ✕
                        </button>
                    </div>

                    @if(!$canDirectRename)
                        <div class="mt-4 p-3.5 rounded-xl bg-info/10 border border-info/20 text-xs text-info flex items-start gap-2.5">
                            <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="leading-relaxed">{{ __('Permintaan perubahan nama dokumen akan dikirimkan untuk ditinjau dan disetujui.') }}</span>
                        </div>
                    @endif
                </div>

                <form method="POST" action="{{ $canDirectRename ? route('documents.rename', $document) : route('documents.request-rename', $document) }}">
                    @csrf
                    <div class="px-6 pb-5 space-y-4">
                        <div class="space-y-1.5">
                            <label for="rename-input-title-{{ $document->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                {{ __('Judul / Nama Dokumen') }} <span class="text-error">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="rename-input-title-{{ $document->id }}" 
                                name="title" 
                                value="{{ old('title', $document->title) }}" 
                                class="input input-bordered w-full text-sm rounded-xl focus:input-primary" 
                                required 
                                maxlength="255"
                                placeholder="{{ __('Masukkan nama baru dokumen...') }}"
                            />
                        </div>

                        @if(!$canDirectRename)
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label for="rename-input-notes-{{ $document->id }}" class="text-xs font-semibold text-base-content uppercase tracking-wider">
                                        {{ __('Alasan Perubahan') }}
                                    </label>
                                    <span class="text-[11px] text-base-content/50 font-normal">({{ __('Opsional') }})</span>
                                </div>
                                <textarea 
                                    id="rename-input-notes-{{ $document->id }}" 
                                    name="notes" 
                                    rows="3" 
                                    class="textarea textarea-bordered w-full text-xs rounded-xl focus:textarea-primary leading-relaxed resize-none" 
                                    placeholder="{{ __('Jelaskan alasan pengajuan perubahan nama dokumen...') }}"
                                ></textarea>
                            </div>
                        @endif
                    </div>

                    <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                        <button type="button" onclick="document.getElementById('rename-document-modal-{{ $document->id }}').close()" class="btn btn-ghost btn-sm sm:btn-md rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                            {{ __('Batal') }}
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm sm:btn-md font-semibold rounded-xl px-5 shadow-xs flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $canDirectRename ? __('Simpan Perubahan') : __('Ajukan Permintaan') }}
                        </button>
                    </div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>{{ __('Batal') }}</button>
            </form>
        </dialog>
    @endif

    <script>
        @if(request('saving') == 1)
        document.addEventListener('DOMContentLoaded', function() {
            const initialUpdatedAt = "{{ $document->displayVersion()?->updated_at?->timestamp }}";
            const btn = document.getElementById('btn-edit-document');
            const spinner = document.getElementById('spinner-edit-document');
            const icon = document.getElementById('icon-edit-document');
            const text = document.getElementById('text-edit-document');
            let pollTimer = null;
            let fallbackTimer = null;
            let timerSeconds = 0;
            let counterInterval = null;
            
            function unlockButton() {
                if (btn) {
                    btn.classList.remove('pointer-events-none', 'opacity-50');
                    if (spinner) spinner.classList.add('hidden');
                    if (icon) icon.classList.remove('hidden');
                    if (text) text.textContent = '{{ __('Edit Dokumen') }}';
                }
                
                if (pollTimer) clearTimeout(pollTimer);
                if (fallbackTimer) clearTimeout(fallbackTimer);
                if (counterInterval) clearInterval(counterInterval);

                const url = new URL(window.location);
                url.searchParams.delete('saving');
                window.location.replace(url.toString());
            }

            function pollStatus() {
                fetch("{{ route('documents.onlyoffice-status', $document) }}")
                    .then(r => r.json())
                    .then(data => {
                        // Safe to edit if NO active session OR the document was successfully updated
                        const isSafe = (!data.active) || (data.updated_at != initialUpdatedAt);
                        if (isSafe) {
                            unlockButton();
                        } else {
                            pollTimer = setTimeout(pollStatus, 2000);
                        }
                    })
                    .catch(() => {
                        pollTimer = setTimeout(pollStatus, 2000);
                    });
            }
            
            pollStatus();
            
            if (text) {
                counterInterval = setInterval(() => {
                    timerSeconds++;
                    text.textContent = '{{ __('Menyimpan...') }} (' + timerSeconds + 's)';
                }, 1000);
            }
            
            // Fallback unlock after 45 seconds just in case network fails
            fallbackTimer = setTimeout(unlockButton, 45000);
        });
        @endif
    </script>
</x-app-layout>
