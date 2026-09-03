@php
    $hasDraft = $doc->versions->contains('status', 'draft');
    $hasPending = $doc->versions->contains('status', 'pending');
    $isEditorShare = isset($type) && $type === 'shared' && $doc->shares->first()?->role === 'editor';
@endphp
<a href="{{ route('documents.show', ['document' => $doc, 'type' => request('type')]) }}" class="group flex flex-col items-center gap-1 p-3 rounded-lg hover:bg-primary/5 transition-colors cursor-pointer relative" title="{{ $doc->title }}">

    {{-- Hover actions (top-right corner) --}}
    <div class="absolute top-1 right-1 flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity z-10">
        <span onclick="event.preventDefault(); event.stopPropagation(); window.location='{{ route('documents.preview', ['document' => $doc, 'type' => request('type')]) }}'" class="btn btn-ghost btn-xs btn-square" title="{{ __('Pratinjau') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </span>
        @if($doc->owner_id === auth()->id() || $isEditorShare)
            <span onclick="event.preventDefault(); event.stopPropagation(); window.location='{{ route('documents.edit', ['document' => $doc, 'type' => request('type')]) }}'" class="btn btn-ghost btn-xs btn-square" title="{{ __('Edit') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </span>
        @endif
        @if(auth()->user()->isAdmin())
            <button type="button" onclick="event.preventDefault(); event.stopPropagation(); document.getElementById('delete-doc-modal-{{ $doc->id }}').showModal()" class="btn btn-ghost btn-xs btn-square text-error" title="{{ __('Hapus') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </button>

            <dialog id="delete-doc-modal-{{ $doc->id }}" onclick="event.stopPropagation();" class="modal modal-bottom sm:modal-middle text-left whitespace-normal backdrop-blur-xs">
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
                                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Tindakan ini tidak bisa dibatalkan.') }}</p>
                                </div>
                            </div>
                            <button type="button" onclick="document.getElementById('delete-doc-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content hover:bg-base-200">
                                ✕
                            </button>
                        </div>
                        <p class="text-sm text-base-content/70 mt-3">
                            {{ __('Hapus dokumen :title beserta semua versinya?', ['title' => $doc->title]) }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.documents.destroy', $doc) }}">
                        @csrf @method('DELETE')
                        <div class="bg-base-200/40 px-6 py-4 border-t border-base-200 flex items-center justify-end gap-2.5">
                            <button type="button" onclick="document.getElementById('delete-doc-modal-{{ $doc->id }}').close()" class="btn btn-ghost btn-sm rounded-xl font-medium text-base-content/70 hover:text-base-content px-4">
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
        @endif
    </div>

    {{-- Document icon --}}
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 text-primary/70 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
            <path d="M14 2v6h6" fill="none" stroke="currentColor" stroke-width="1" opacity="0.3"/>
        </svg>
        {{-- Version badge --}}
        <div class="absolute -bottom-1 -right-2 flex flex-col items-end gap-0.5">
            @if($doc->hasPendingRename())
                <span class="badge badge-warning badge-xs text-[9px] px-1" title="{{ __('Menunggu Persetujuan Ubah Nama') }}">✎</span>
            @endif
            @if($doc->currentVersion)
                <span class="badge badge-success badge-xs text-[10px] px-1">v{{ $doc->currentVersion->version_number }}</span>
            @elseif($hasPending)
                <span class="badge badge-warning badge-xs text-[10px] px-1">{{ __('Tertunda') }}</span>
            @elseif($hasDraft)
                <span class="badge badge-warning badge-xs text-[10px] px-1">{{ __('Draf') }}</span>
            @endif
        </div>
    </div>

    {{-- Filename --}}
    <div class="w-full text-center">
        <span class="text-xs text-base-content/90 group-hover:text-primary transition-colors line-clamp-2 leading-tight font-medium" title="{{ $doc->title }}">
            {{ $doc->title }}
        </span>
        <div class="text-[10px] text-base-content/60 mt-1 line-clamp-2 leading-tight" title="{{ $doc->document_number }} · {{ $doc->branch?->name }} · {{ $doc->owner->name }}">
            {{ $doc->document_number }}
            @if($doc->branch)
                <span class="font-medium text-base-content/80">· {{ $doc->branch->name }}</span>
            @endif
            @if($doc->isGeneral()) <span class="text-success">· {{ __('Umum') }}</span>
            @elseif($doc->isPersonal()) <span class="text-info">· {{ __('Personal') }}</span>
            @else <span>· {{ $doc->division?->code ?? '—' }}</span>
            @endif
            · {{ $doc->owner->name }}
        </div>
    </div>

    {{-- Format and Document type badges --}}
    <div class="flex items-center justify-center gap-1 flex-wrap max-w-[95%]">
        @if($doc->format_choice === 'lama')
            <span class="badge badge-secondary badge-outline badge-xs shrink-0" title="{{ __('Format Penomoran Lama') }}">{{ __('Format Lama') }}</span>
        @else
            <span class="badge badge-primary badge-outline badge-xs shrink-0" title="{{ __('Format Penomoran Baru') }}">{{ __('Format Baru') }}</span>
        @endif
        @if($doc->documentType)
            <span class="badge badge-outline badge-xs opacity-60 max-w-[90px] inline-flex items-center" title="{{ $doc->documentType->name ?? $doc->documentType->code }}">
                <span class="truncate">{{ $doc->documentType->code }}</span>
            </span>
        @endif
    </div>

</a>
