<div class="mb-4 flex flex-wrap items-center justify-between gap-2">
    <h2 class="font-semibold text-base-content text-lg">{{ $title }}</h2>
    @if($showCreate ?? true)
        <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
            New Document
        </a>
    @endif
</div>
