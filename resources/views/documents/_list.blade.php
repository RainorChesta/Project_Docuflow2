@php
    $hasDraft = $doc->versions->contains('status', 'draft');
    $hasPending = $doc->versions->contains('status', 'pending');
@endphp
<div class="px-6 py-4 flex items-center justify-between gap-4">
    <div class="min-w-0">
        <div class="flex items-center gap-2">
            <a href="{{ route('documents.show', $doc) }}" class="link link-primary font-medium truncate">
                {{ $doc->title }}
            </a>
            @if($doc->documentType)
                <span class="badge badge-outline badge-sm shrink-0">{{ $doc->documentType->code }}</span>
            @endif
        </div>
        <p class="text-sm text-base-content/60 truncate">
            {{ $doc->document_number }}
            @if($doc->isGeneral()) <span class="text-success">· General</span>
            @elseif($doc->isPersonal()) <span class="text-info">· Personal</span>
            @else <span>· {{ $doc->division?->code ?? '—' }}</span>
            @endif
            · {{ $doc->owner->name }}
        </p>
    </div>
    <div class="flex items-center gap-3 shrink-0">
        @if($doc->owner_id === auth()->id() && $hasDraft && !$hasPending && !$doc->currentVersion)
            <a href="{{ route('documents.edit', $doc) }}" class="btn btn-ghost btn-xs">Edit</a>
        @endif
        <a
            href="{{ route('documents.preview', $doc) }}"
            class="inline-flex items-center justify-center w-6 h-6 rounded-full shrink-0 text-base-content/60 hover:text-base-content hover:bg-base-200"
            title="Preview Dokumen"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </a>
        <div class="text-sm text-base-content/60">
            @if($doc->currentVersion)
                <span class="badge badge-success badge-sm">v{{ $doc->currentVersion->version_number }}</span>
            @elseif($hasPending)
                <span class="badge badge-warning badge-sm w-16 justify-center">Pending</span>
            @elseif($hasDraft)
                <span class="badge badge-warning badge-sm w-16 justify-center">Draft</span>
            @else
                <span class="badge badge-ghost badge-sm">No version</span>
            @endif
        </div>
    </div>
</div>
