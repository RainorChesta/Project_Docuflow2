<x-app-layout>
    <x-slot name="header">My Documents</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('documents.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                    + New Document
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @forelse($documents as $doc)
                        <div class="border-b py-3 flex items-center justify-between">
                            <div>
                                <a href="{{ route('documents.show', $doc) }}" class="text-blue-600 hover:underline font-medium">
                                    {{ $doc->title }}
                                </a>
                                <p class="text-sm text-gray-500">
                                    {{ $doc->document_number }}
                                    @if($doc->is_public) <span class="text-green-600">· Public</span> @endif
                                    · {{ $doc->division->code }}
                                    · {{ $doc->owner->name }}
                                </p>
                            </div>
                            <div class="text-sm text-gray-500">
                                @if($doc->currentVersion)
                                    v{{ $doc->currentVersion->version_number }}
                                @else
                                    <span class="text-yellow-600">Pending</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">No documents found.</p>
                    @endforelse

                    <div class="mt-4">
                        {{ $documents->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
