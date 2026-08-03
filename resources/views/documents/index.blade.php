<x-app-layout>
    <x-slot name="header">My Documents</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="mb-4 flex items-center justify-between gap-2">
                <a href="{{ route('documents.create') }}" class="btn btn-primary">
                    + New Document
                </a>

                <form method="GET" class="flex gap-2">
                    <select name="document_type_id" class="select select-bordered select-sm" onchange="this.form.submit()">
                        <option value="">Semua tipe</option>
                        @foreach($documentTypes as $type)
                            <option value="{{ $type->id }}" {{ request('document_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->code }} - {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

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
                                        @if($doc->is_public) <span class="text-success">· Public</span> @endif
                                        · {{ $doc->division->code }}
                                        · {{ $doc->owner->name }}
                                    </p>
                                </div>
                                <div class="text-sm text-base-content/60">
                                    @if($doc->currentVersion)
                                        v{{ $doc->currentVersion->version_number }}
                                    @else
                                        <span class="text-warning">Pending</span>
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
