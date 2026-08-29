<x-app-layout>
    <x-slot name="header">{{ __('Dokumen Umum') }}</x-slot>

    <div class="pt-0 pb-6">
        <div class="max-w-7xl mx-auto w-full" x-data="{ viewMode: localStorage.getItem('docViewMode') || 'list' }" x-init="$watch('viewMode', val => localStorage.setItem('docViewMode', val))">
            @include('documents._header', ['title' => __('Dokumen Umum'), 'showCreate' => false])

            {{-- Filter toolbar: always visible on the General Documents page --}}
            @include('documents._search')

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($showDocuments)
            <!-- List Layout -->
            <div x-show="viewMode === 'list'" class="card bg-base-100 border border-base-300 shadow-sm mb-4">
                <div class="card-body p-0">
                    <div class="divide-y divide-base-200">
                        @forelse($documents as $doc)
                            @include('documents._list', ['doc' => $doc])
                        @empty
                            @if(request('search') || request('document_type_id') || request('year'))
                                <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-base-content/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="font-semibold text-base-content/70">{{ __('Dokumen tidak ditemukan') }}</p>
                                    <p class="text-sm text-base-content/50 mt-1">{{ __('Tidak ada dokumen yang cocok dengan filter saat ini.') }}</p>
                                    <a href="{{ route('documents.index', ['type' => 'general', 'folder' => $folder]) }}" class="btn btn-ghost btn-sm mt-4">
                                        {{ __('Bersihkan Filter') }}
                                    </a>
                                </div>
                            @else
                                <div class="p-4 sm:p-6 text-base-content/60">{{ __('Tidak ada dokumen.') }}</div>
                            @endif
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Grid Layout -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-1 mb-4 bg-base-100 border border-base-300 rounded-xl shadow-sm p-3" style="display: none;">
                @forelse($documents as $doc)
                    @include('documents._grid', ['doc' => $doc])
                @empty
                    @if(request('search') || request('document_type_id') || request('year'))
                        <div class="col-span-full flex flex-col items-center justify-center py-16 px-4 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 text-base-content/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <p class="font-semibold text-base-content/70">{{ __('Dokumen tidak ditemukan') }}</p>
                            <p class="text-sm text-base-content/50 mt-1">{{ __('Tidak ada dokumen yang cocok dengan filter saat ini.') }}</p>
                            <a href="{{ route('documents.index', ['type' => 'general', 'folder' => $folder]) }}" class="btn btn-ghost btn-sm mt-4">
                                        {{ __('Bersihkan Filter') }}
                                    </a>
                        </div>
                    @else
                        <div class="col-span-full p-4 sm:p-6 text-base-content/60 bg-base-100 rounded-xl border border-base-300 shadow-sm">
                            {{ __('Tidak ada dokumen.') }}
                        </div>
                    @endif
                @endforelse
            </div>

            @if($documents->hasPages())
                <div class="p-4 bg-base-100 border border-base-300 rounded-xl shadow-sm">
                    {{ $documents->links() }}
                </div>
            @endif
            @endif
        </div>
    </div>
</x-app-layout>
