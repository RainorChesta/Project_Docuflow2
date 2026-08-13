<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Divisi') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            @include('documents._header', ['title' => __('Dokumen Divisi'), 'showCreate' => false])

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
                            <div class="p-4 sm:p-6 text-base-content/60">{{ __('Tidak ada dokumen divisi.') }}</div>
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
