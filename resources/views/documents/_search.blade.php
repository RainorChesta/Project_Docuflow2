<form method="GET" action="{{ route('documents.index') }}" class="mb-4 flex gap-2">
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or number..."
           class="input input-bordered input-sm flex-1">
    <button type="submit" class="btn btn-neutral btn-sm">Search</button>
    @if(request('search') || request('status') || request('division_id') || request('document_type_id'))
        <a href="{{ route('documents.index', ['type' => $type]) }}" class="btn btn-ghost btn-sm">Clear</a>
    @endif
</form>
