<div class="mb-4 flex items-center justify-between gap-2">
    <h2 class="font-semibold text-base-content text-lg">{{ $title }}</h2>
    <div class="flex items-center gap-2">
        <form method="GET">
            <input type="hidden" name="type" value="{{ $type }}">
            <select name="document_type_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                <option value="">Semua tipe</option>
                @foreach($documentTypes as $dt)
                    <option value="{{ $dt->id }}" {{ request('document_type_id') == $dt->id ? 'selected' : '' }}>
                        {{ $dt->code }} - {{ $dt->name }}
                    </option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">
            + New Document
        </a>
    </div>
</div>
