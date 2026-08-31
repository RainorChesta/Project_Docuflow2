<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 sm:gap-2">
            <span class="min-w-0 font-bold break-words">{{ $document->title }} — v{{ $version->version_number }}</span>
            @if($document->document_number)
                <span class="text-xs sm:text-sm font-normal text-base-content/60 shrink-0 font-mono">{{ $document->document_number }}</span>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto w-full px-0">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <div class="flex flex-wrap justify-between items-center gap-3 mb-4 pb-4 border-b border-base-300">
                        <div class="text-sm">
                            <div><span class="text-base-content/60">{{ __('Versi') }}:</span> v{{ $version->version_number }}</div>
                            <div><span class="text-base-content/60">{{ __('Penulis') }}:</span> {{ $version->author_name }}</div>
                            <div><span class="text-base-content/60">{{ __('Status') }}:</span>
                                @if($version->id === $document->current_version_id)
                                    <span class="badge badge-success badge-sm">{{ __('Aktif') }}</span>
                                @elseif($version->status === 'inactive')
                                    <span class="badge badge-neutral badge-sm">{{ __('Tidak Aktif') }}</span>
                                @elseif($version->status === 'pending')
                                    <span class="badge badge-warning badge-sm">{{ __('Tertunda') }}</span>
                                @elseif($version->status === 'discarded' || $version->discarded_at)
                                    <span class="badge badge-neutral badge-sm">{{ __('Dibuang') }}</span>
                                @elseif($version->status === 'rejected')
                                    <span class="badge badge-error badge-sm">{{ __('Ditolak') }}</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">{{ $version->status }}</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                            {{ __('Kembali') }}
                        </a>
                    </div>

                    @if($version->file_path)
                        @include('documents._file-preview', ['document' => $document, 'version' => $version])
                    @else
                        @include('documents._paper', [
                            'content'     => $version->content ?? '',
                            'document'    => $document,
                            'paperSize'   => $document->paper_size ?? 'A4',
                            'paperMargin' => $document->paper_margin,
                        ])
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>