<form method="GET" action="{{ route('documents.index') }}" class="mb-4 flex gap-2">
    <input type="hidden" name="type" value="{{ $type }}">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or number..."
           class="input input-bordered input-sm flex-1">
    <select name="document_type_id" class="select select-bordered select-sm" onchange="this.form.submit()">
        <option value="">Semua tipe</option>
        @foreach($documentTypes as $dt)
            <option value="{{ $dt->id }}" {{ request('document_type_id') == $dt->id ? 'selected' : '' }}>
                {{ $dt->code }} - {{ $dt->name }}
            </option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-neutral btn-sm">Search</button>
    @if(request('search') || request('status') || request('division_id') || request('document_type_id'))
        <a href="{{ route('documents.index', ['type' => $type]) }}" class="btn btn-ghost btn-sm">Clear</a>
    @endif
</form>
