<div class="mb-4 flex items-center justify-between gap-2">
    <h2 class="font-semibold text-base-content text-lg">{{ $title }}</h2>
    @if($showCreate ?? true)
        <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">
            + New Document
        </a>
    @endif
</div>
