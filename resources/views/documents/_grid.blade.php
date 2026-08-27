@php
    $hasDraft = $doc->versions->contains('status', 'draft');
    $hasPending = $doc->versions->contains('status', 'pending');
    $isEditorShare = isset($type) && $type === 'shared' && $doc->shares->first()?->role === 'editor';
@endphp
<a href="{{ route('documents.show', $doc) }}" class="group flex flex-col items-center gap-1 p-3 rounded-lg hover:bg-primary/5 transition-colors cursor-pointer relative" title="{{ $doc->title }}">

    {{-- Hover actions (top-right corner) --}}
    <div class="absolute top-1 right-1 flex gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity z-10">
        <span onclick="event.preventDefault(); event.stopPropagation(); window.location='{{ route('documents.preview', $doc) }}'" class="btn btn-ghost btn-xs btn-square" title="{{ __('Pratinjau') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </span>
        @if($doc->owner_id === auth()->id() || $isEditorShare)
            <span onclick="event.preventDefault(); event.stopPropagation(); window.location='{{ route('documents.edit', $doc) }}'" class="btn btn-ghost btn-xs btn-square" title="{{ __('Edit') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            </span>
        @endif
    </div>

    {{-- Document icon --}}
    <div class="relative">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 text-primary/70 group-hover:text-primary transition-colors" viewBox="0 0 24 24" fill="currentColor">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6z"/>
            <path d="M14 2v6h6" fill="none" stroke="currentColor" stroke-width="1" opacity="0.3"/>
        </svg>
        {{-- Version badge --}}
        <div class="absolute -bottom-1 -right-2">
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
                <span class="font-medium text-base-content/80">· {{ $doc->branch->name }} @if($doc->branch->is_pusat)<span class="text-primary font-semibold">(Pusat)</span>@endif</span>
            @endif
            @if($doc->isGeneral()) <span class="text-success">· {{ __('Umum') }}</span>
            @elseif($doc->isPersonal()) <span class="text-info">· {{ __('Personal') }}</span>
            @else <span>· {{ $doc->division?->code ?? '—' }}</span>
            @endif
            · {{ $doc->owner->name }}
        </div>
    </div>

    {{-- Document type badge --}}
    @if($doc->documentType)
        <span class="badge badge-outline badge-xs opacity-60">{{ $doc->documentType->code }}</span>
    @endif
</a>
