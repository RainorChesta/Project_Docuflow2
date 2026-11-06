@php
    $hasDraft = $doc->hasDraft();
    $hasPending = $doc->hasPending();
@endphp
<div class="px-4 sm:px-6 py-3.5 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-base-200/40 transition-colors">
    <div class="min-w-0 w-full sm:w-auto sm:flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('documents.show', ['document' => $doc, 'type' => request('type')]) }}" class="font-medium break-words text-base-content no-underline hover:text-primary transition-colors">
                {{ $doc->title }}
            </a>
            @if($doc->hasPendingRename())
                <span class="badge badge-warning badge-outline badge-xs shrink-0" title="{{ __('Menunggu Persetujuan Ubah Nama') }}">{{ __('Ubah Nama') }}</span>
            @endif
            @if($doc->isLockedForEditing())
                <span class="badge badge-warning badge-outline badge-xs px-1.5 py-0.5 shrink-0" title="{{ __('Dokumen sedang dalam alur peninjauan/tanda tangan dan terkunci dari pengeditan') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                </span>
            @endif
            @if(isset($type) && $type === 'shared')
                @php $shareRole = $doc->shares->first()?->role; @endphp
                @if($shareRole)
                    <span class="badge badge-sm {{ $shareRole === 'editor' ? 'badge-info' : 'badge-ghost' }} shrink-0 capitalize">{{ $shareRole }}</span>
                @endif
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-base-content/60 mt-1.5">
            <span class="font-mono font-medium text-base-content/80">{{ $doc->document_number }}</span>
            @if($doc->documentType)
                <span class="badge badge-ghost badge-xs border-base-300/60 font-medium shrink-0" title="{{ $doc->documentType->name ?? $doc->documentType->code }}">
                    {{ $doc->documentType->code }}
                </span>
            @endif
            @if($doc->branch)
                <span class="text-base-content/30 hidden sm:inline">•</span>
                <span class="truncate max-w-[200px] sm:max-w-none text-base-content/70" title="{{ $doc->branch->name }}">{{ $doc->branch->name }}</span>
            @endif
            <span class="text-base-content/30 hidden sm:inline">•</span>
            <span class="truncate font-medium text-base-content/80">{{ $doc->owner->name }}</span>
        </div>
    </div>
    <div class="flex items-center justify-between sm:justify-end gap-2 sm:gap-3 w-full sm:w-auto pt-2 sm:pt-0 border-t sm:border-t-0 border-base-200/70 shrink-0">
        @php
            $isEditorShare = isset($type) && $type === 'shared' && $doc->shares->first()?->role === 'editor';
        @endphp
        <div class="flex items-center gap-1.5 sm:gap-2">
            @if(($doc->owner_id === auth()->id() || $isEditorShare) && !$doc->isLockedForEditing())
                <a href="{{ route('documents.edit', ['document' => $doc, 'type' => request('type')]) }}" class="btn btn-ghost btn-xs rounded-lg gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                    {{ __('Edit') }}
                </a>
            @endif
            <a
                href="{{ route('documents.preview', ['document' => $doc, 'type' => request('type')]) }}"
                class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-base-content/60 hover:text-base-content hover:bg-base-200 transition-colors"
                title="{{ __('Pratinjau') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </a>
            {{-- Archive Action Button in List --}}
            @if($doc->isArchived())
                @php $listArchive = $doc->latestArchiveEntry; @endphp
                @if($listArchive)
                    <a
                        href="{{ route('archives.show', $listArchive) }}"
                        onclick="event.stopPropagation();"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-success hover:text-success hover:bg-success/10 transition-colors"
                        title="{{ __('Lihat Arsip: ') . $listArchive->archive_number }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                    </a>
                @endif
            @elseif($doc->hasPendingArchiveRequest())
                @php $listPendingArchive = $doc->pendingArchiveRequest(); @endphp
                @if($listPendingArchive)
                    <a
                        href="{{ route('archive-requests.show', $listPendingArchive) }}"
                        onclick="event.stopPropagation();"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-warning hover:text-warning hover:bg-warning/10 transition-colors"
                        title="{{ __('Permohonan pendaftaran arsip sedang diproses') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </a>
                @endif
            @elseif($doc->hasActiveVersion() && !$doc->trashed())
                <a
                    href="{{ route('archive-requests.create', ['document_id' => $doc->id]) }}"
                    onclick="event.stopPropagation();"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-amber-600 dark:text-amber-400 hover:text-amber-700 hover:bg-amber-500/10 transition-colors"
                    title="{{ __('Arsipkan Dokumen') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </a>
            @endif
            @if(auth()->user()->can('delete', $doc))
                <button
                    type="button"
                    onclick="event.preventDefault(); event.stopPropagation(); document.getElementById('delete-doc-modal-list-{{ $doc->id }}').showModal()"
                    class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-error/70 hover:text-error hover:bg-error/10 transition-colors"
                    title="{{ __('Hapus Dokumen') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>

                <dialog id="delete-doc-modal-list-{{ $doc->id }}" onclick="event.stopPropagation();" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                    <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-sm">
                        <div class="p-6 pb-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Hapus Dokumen') }}</h3>
                                        <p class="text-xs text-base-content/60 mt-0.5">{{ __('Dokumen akan dipindahkan ke tempat sampah.') }}</p>
                                    </div>
                                </div>
                                <button type="button" onclick="document.getElementById('delete-doc-modal-list-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                    ✕
                                </button>
                            </div>
                            <p class="text-sm text-base-content/70 mt-3">
                                {{ __('Pindahkan dokumen \":title\" ke tempat sampah?', ['title' => $doc->title]) }}
                            </p>
                        </div>
                        <form method="POST" action="{{ route('documents.destroy', $doc) }}">
                            @csrf @method('DELETE')
                            <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                <button type="button" onclick="document.getElementById('delete-doc-modal-list-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                    {{ __('Batal') }}
                                </button>
                                <button type="submit" class="btn btn-error btn-sm text-white font-semibold rounded-xl px-5 shadow-xs hover:shadow-md hover:shadow-error/20 transition-all flex items-center gap-1.5">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    {{ __('Hapus') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>{{ __('Batal') }}</button>
                    </form>
                </dialog>
            @elseif($doc->owner_id === auth()->id() && $doc->versions()->where('status', 'active')->exists())
                @if($doc->hasPendingDeletion())
                    <span onclick="event.preventDefault(); event.stopPropagation();" class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-error/50 cursor-not-allowed" title="{{ __('Menunggu persetujuan hapus') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                @else
                    <button
                        type="button"
                        onclick="event.preventDefault(); event.stopPropagation(); document.getElementById('request-delete-doc-modal-list-{{ $doc->id }}').showModal()"
                        class="inline-flex items-center justify-center w-7 h-7 rounded-full shrink-0 text-error/70 hover:text-error hover:bg-error/10 transition-colors"
                        title="{{ __('Ajukan Hapus Dokumen') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>

                    <dialog id="request-delete-doc-modal-list-{{ $doc->id }}" onclick="event.stopPropagation();" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
                        <div class="modal-box p-0 overflow-hidden rounded-2xl sm:rounded-3xl border border-base-content/10 shadow-2xl bg-base-100 max-w-md">
                            <form method="POST" action="{{ route('documents.request-deletion', $doc) }}">
                                @csrf
                                <div class="p-6 pb-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-center gap-3.5">
                                            <div class="w-11 h-11 rounded-2xl bg-error/10 text-error flex items-center justify-center shrink-0 ring-4 ring-error/5 shadow-xs">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-bold text-lg text-base-content leading-snug">{{ __('Penghapusan Dokumen Aktif') }}</h3>
                                                <p class="text-xs text-base-content/60 mt-0.5">{{ __('Memerlukan persetujuan Administrator') }}</p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="document.getElementById('request-delete-doc-modal-list-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                            ✕
                                        </button>
                                    </div>

                                    <div class="mt-3 p-3 rounded-xl bg-base-200/60 border border-base-300/60 text-xs text-base-content/80 space-y-1.5">
                                        <p class="leading-relaxed">
                                            {{ __('Dokumen aktif \":title\" (:number) telah memiliki pengesahan resmi. Sesuai tata kelola, penghapusan memerlukan permohonan ke Administrator.', ['title' => $doc->title, 'number' => $doc->document_number ?: 'Resmi']) }}
                                        </p>
                                    </div>

                                    <div class="mt-3.5">
                                        <label class="label text-xs font-semibold text-base-content/80">
                                            <span>{{ __('Alasan Penghapusan') }} <span class="text-error">*</span></span>
                                        </label>
                                        <textarea name="notes" rows="2" required class="textarea textarea-bordered w-full text-xs rounded-xl bg-base-200/50 focus:bg-base-100" placeholder="{{ __('Tuliskan alasan mengapa dokumen ini perlu dihapus...') }}"></textarea>
                                    </div>
                                </div>
                                <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                                    <button type="button" onclick="document.getElementById('request-delete-doc-modal-list-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
                                        {{ __('Batal') }}
                                    </button>
                                    <button type="submit" class="btn btn-error btn-sm text-white font-semibold rounded-xl px-5 shadow-xs flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        {{ __('Ajukan Hapus') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <form method="dialog" class="modal-backdrop">
                            <button>{{ __('Batal') }}</button>
                        </form>
                    </dialog>
                @endif
            @endif
        </div>
        <div class="text-sm text-base-content/60 flex items-center gap-1.5 flex-wrap justify-end">
            @if($doc->isExpired() || $doc->is_expired)
                <span class="badge badge-error badge-sm text-white font-medium gap-1.5 shadow-2xs leading-none">
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                    {{ __('Kedaluwarsa') }}
                </span>
            @elseif($hasPending && $doc->currentVersion)
                <span class="badge badge-warning badge-sm w-auto px-2 justify-center font-medium" title="{{ __('Versi aktif v:active, menunggu persetujuan v:pending', ['active' => $doc->currentVersion->version_number, 'pending' => $doc->displayVersion()?->version_number]) }}">
                    v{{ $doc->currentVersion->version_number }} (v{{ $doc->displayVersion()?->version_number }} {{ __('Pending') }})
                </span>
            @elseif($hasDraft && $doc->currentVersion)
                @php $dVer = $doc->draftVersion(); @endphp
                <span class="badge badge-warning badge-sm w-auto px-2 justify-center font-semibold" title="{{ __('Versi aktif v:active, draf revisi v:draft', ['active' => $doc->currentVersion->version_number, 'draft' => $dVer?->version_number]) }}">
                    {{ __('Draf') }} (v{{ $dVer?->version_number }})
                </span>
            @elseif($doc->currentVersion)
                <span class="badge badge-success badge-sm min-w-[36px] justify-center text-white font-semibold">v{{ $doc->currentVersion->version_number }}</span>
            @elseif($hasPending)
                <span class="badge badge-warning badge-sm px-2 justify-center">{{ __('Tertunda') }}</span>
            @elseif($hasDraft)
                <span class="badge badge-warning badge-sm px-2 justify-center font-semibold">{{ __('Draf') }} (v{{ $doc->draftVersion()?->version_number ?? 1 }})</span>
            @else
                <span class="badge badge-ghost badge-sm px-2 justify-center">{{ __('Tanpa versi') }}</span>
            @endif
            @if($doc->isArchived())
                <span class="badge badge-success badge-sm gap-1 text-white font-semibold shrink-0" title="{{ __('Terdaftar di Arsip: ') . ($doc->latestArchiveEntry?->archive_number ?? '') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    {{ __('Arsip') }}
                </span>
            @elseif($doc->hasPendingArchiveRequest())
                <span class="badge badge-warning badge-sm gap-1 font-medium shrink-0" title="{{ __('Permohonan pendaftaran arsip sedang diproses') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    {{ __('Proses Arsip') }}
                </span>
            @endif
            @if($doc->isDirectorRead())
                <span class="badge badge-success badge-sm gap-1 text-white font-semibold shrink-0" title="{{ __('Ditinjau oleh Direktur') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    {{ __('Ditinjau Direktur') }}
                </span>
            @endif
        </div>
    </div>
</div>
