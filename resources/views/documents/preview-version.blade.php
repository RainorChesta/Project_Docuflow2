<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $document->title }} — v{{ $version->version_number }}</span>
            <span class="text-sm font-normal text-base-content/60">{{ $document->document_number }}</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4 pb-4 border-b border-base-300">
                        <div class="text-sm">
                            <div><span class="text-base-content/60">Version:</span> v{{ $version->version_number }}</div>
                            <div><span class="text-base-content/60">Author:</span> {{ $version->author_name }}</div>
                            <div><span class="text-base-content/60">Status:</span>
                                @if($version->id === $document->current_version_id)
                                    <span class="badge badge-success badge-sm">Active</span>
                                @elseif($version->status === 'inactive')
                                    <span class="badge badge-neutral badge-sm">Inactive</span>
                                @elseif($version->status === 'pending')
                                    <span class="badge badge-warning badge-sm">Pending</span>
                                @elseif($version->status === 'discarded' || $version->discarded_at)
                                    <span class="badge badge-neutral badge-sm">Discarded</span>
                                @elseif($version->status === 'rejected')
                                    <span class="badge badge-error badge-sm">Rejected</span>
                                @else
                                    <span class="badge badge-ghost badge-sm">{{ $version->status }}</span>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm">Back</a>
                    </div>

                    @include('documents._paper', [
    'content' => $version->content ?? '',
    'liveStorage' => 'doc-preview-' . $document->id,
    'paperSize' => $document->paper_size ?? 'A4',
    'paperMargin' => $document->paper_margin,
])
                </div>
            </div>
        </div>
    </div>
</x-app-layout>