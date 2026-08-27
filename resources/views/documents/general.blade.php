<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Umum') }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full" x-data="{ viewMode: localStorage.getItem('docViewMode') || 'list' }" x-init="$watch('viewMode', val => localStorage.setItem('docViewMode', val))">
            @include('documents._header', ['title' => __('Dokumen Umum'), 'showCreate' => false])

            @include('documents._search')

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- List Layout -->
            <div x-show="viewMode === 'list'" class="card bg-base-100 border border-base-300 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="divide-y divide-base-200">
                        @forelse($documents as $doc)
                            @include('documents._list', ['doc' => $doc])
                        @empty
                            <div class="p-4 sm:p-6 text-base-content/60">{{ __('Tidak ada dokumen general.') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Grid Layout -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-1 mb-4 bg-base-100 border border-base-300 rounded-xl shadow-sm p-3" style="display: none;">
                @forelse($documents as $doc)
                    @include('documents._grid', ['doc' => $doc])
                @empty
                    <div class="col-span-full p-4 sm:p-6 text-base-content/60 bg-base-100 rounded-xl border border-base-300 shadow-sm">
                        {{ __('Tidak ada dokumen general.') }}
                    </div>
                @endforelse
            </div>

            @if($documents->hasPages())
                <div class="p-4 bg-base-100 border border-base-300 rounded-xl shadow-sm">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
