<x-app-layout>
    <x-slot name="header">Documents</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="mb-4 flex items-center justify-between gap-2">
                <h2 class="font-semibold text-base-content text-lg">
                    @if($type === 'general') General Dokumen
                    @elseif($type === 'mine') My Documents
                    @else Dokumen Divisi
                    @endif
                </h2>
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

            <form method="GET" action="{{ route('documents.index') }}" class="mb-4 flex gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search title or number..."
                       class="input input-bordered input-sm flex-1">
                <button type="submit" class="btn btn-neutral btn-sm">Search</button>
                @if(request('search') || request('status') || request('division_id'))
                    <a href="{{ route('documents.index', ['type' => $type]) }}" class="btn btn-ghost btn-sm">Clear</a>
                @endif
            </form>

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="divide-y divide-base-200">
                        @forelse($documents as $doc)
                            <div class="px-6 py-4 flex items-center justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('documents.show', $doc) }}" class="link link-primary font-medium">
                                            {{ $doc->title }}
                                        </a>
                                        @if($doc->documentType)
                                            <span class="badge badge-outline badge-sm">{{ $doc->documentType->code }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-base-content/60">
                                        {{ $doc->document_number }}
                                        @if($doc->isGeneral()) <span class="text-success">· General</span>
                                        @elseif($doc->isPersonal()) <span class="text-info">· Personal</span>
                                        @else <span>· {{ $doc->division?->code ?? '—' }}</span>
                                        @endif
                                        · {{ $doc->owner->name }}
                                    </p>
                                </div>
                                <div class="text-sm text-base-content/60 flex items-center gap-2">
                                    @php
                                        $hasDraft = $doc->versions->contains('status', 'draft');
                                        $hasPending = $doc->versions->contains('status', 'pending');
                                    @endphp
                                    @if($doc->currentVersion)
                                        <span class="badge badge-success badge-sm">v{{ $doc->currentVersion->version_number }}</span>
                                    @elseif($hasPending)
                                        <span class="badge badge-warning badge-sm">Pending</span>
                                    @elseif($hasDraft)
                                        <span class="badge badge-warning badge-sm">Draft</span>
                                    @else
                                        <span class="badge badge-ghost badge-sm">No version</span>
                                    @endif
                                    @if($doc->owner_id === auth()->id() && $hasDraft && !$hasPending && !$doc->currentVersion)
                                        <a href="{{ route('documents.edit', $doc) }}" class="btn btn-ghost btn-xs">Edit</a>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-base-content/60">No documents found.</div>
                        @endforelse
                    </div>
                    @if($documents->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $documents->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
