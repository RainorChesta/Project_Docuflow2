@php
    $hasDraft = $doc->versions->contains('status', 'draft');
    $hasPending = $doc->versions->contains('status', 'pending');
@endphp
<div class="px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-3">
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('documents.show', ['document' => $doc, 'type' => request('type')]) }}" class="font-medium truncate min-w-0 text-base-content no-underline hover:text-primary transition-colors">
                {{ $doc->title }}
            </a>
            @if($doc->documentType)
                <span class="badge badge-outline badge-sm shrink-0">{{ $doc->documentType->code }}</span>
            @endif
            @if(isset($type) && $type === 'shared')
                @php $shareRole = $doc->shares->first()?->role; @endphp
                @if($shareRole)
                    <span class="badge badge-sm {{ $shareRole === 'editor' ? 'badge-info' : 'badge-ghost' }} shrink-0 capitalize">{{ $shareRole }}</span>
                @endif
            @endif
        </div>
        <p class="text-sm text-base-content/60 truncate">
            {{ $doc->document_number }}
            @if($doc->branch)
                <span class="font-medium text-base-content/80">· {{ $doc->branch->name }}</span>
            @endif
            @if($doc->isGeneral()) <span class="text-success">· {{ __('Umum') }}</span>
            @elseif($doc->isPersonal()) <span class="text-info">· {{ __('Personal') }}</span>
            @else <span>· {{ $doc->division?->code ?? '—' }}</span>
            @endif
            · {{ $doc->owner->name }}
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2 sm:gap-3 shrink-0">
        @php
            $isEditorShare = isset($type) && $type === 'shared' && $doc->shares->first()?->role === 'editor';
        @endphp
        @if($doc->owner_id === auth()->id() || $isEditorShare)
            <a href="{{ route('documents.edit', ['document' => $doc, 'type' => request('type')]) }}" class="btn btn-ghost btn-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                {{ __('Edit') }}
            </a>
        @endif
        <a
            href="{{ route('documents.preview', ['document' => $doc, 'type' => request('type')]) }}"
            class="inline-flex items-center justify-center w-6 h-6 rounded-full shrink-0 text-base-content/60 hover:text-base-content hover:bg-base-200"
            title="{{ __('Pratinjau') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </a>
        <div class="text-sm text-base-content/60">
            @if($doc->currentVersion)
                <span class="badge badge-success badge-sm w-16 justify-center">v{{ $doc->currentVersion->version_number }}</span>
            @elseif($hasPending)
                <span class="badge badge-warning badge-sm w-16 justify-center">{{ __('Tertunda') }}</span>
            @elseif($hasDraft)
                <span class="badge badge-warning badge-sm w-16 justify-center">{{ __('Draf') }}</span>
            @else
                <span class="badge badge-ghost badge-sm w-16 justify-center">{{ __('Tanpa versi') }}</span>
            @endif
        </div>
    </div>
</div>
