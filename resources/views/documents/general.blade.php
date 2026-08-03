<x-app-layout>
    <x-slot name="header">General Documents</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            @include('documents._header', ['title' => 'General Dokumen'])

            @include('documents._search')

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="divide-y divide-base-200">
                        @forelse($documents as $doc)
                            @include('documents._list', ['doc' => $doc])
                        @empty
                            <div class="p-6 text-base-content/60">Tidak ada dokumen general.</div>
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

    @include('documents._preview_modal')
</x-app-layout>
