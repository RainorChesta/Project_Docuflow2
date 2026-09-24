@php
    $hasDraft = $doc->versions->contains('status', 'draft');
    $hasPending = $doc->versions->contains('status', 'pending');
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
        </div>
        <div class="text-sm text-base-content/60 flex items-center gap-1.5 flex-wrap justify-end">
            @if($hasPending && $doc->currentVersion)
                <span class="badge badge-warning badge-sm w-auto px-2 justify-center font-medium" title="{{ __('Versi aktif v:active, menunggu persetujuan v:pending', ['active' => $doc->currentVersion->version_number, 'pending' => $doc->displayVersion()?->version_number]) }}">
                    v{{ $doc->currentVersion->version_number }} (v{{ $doc->displayVersion()?->version_number }} {{ __('Pending') }})
                </span>
            @elseif($doc->currentVersion)
                <span class="badge badge-success badge-sm min-w-[36px] justify-center text-white font-semibold">v{{ $doc->currentVersion->version_number }}</span>
            @elseif($hasPending)
                <span class="badge badge-warning badge-sm px-2 justify-center">{{ __('Tertunda') }}</span>
            @elseif($hasDraft)
                <span class="badge badge-warning badge-sm px-2 justify-center">{{ __('Draf') }}</span>
            @else
                <span class="badge badge-ghost badge-sm px-2 justify-center">{{ __('Tanpa versi') }}</span>
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
