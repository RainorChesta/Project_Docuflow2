<x-app-layout>
    <x-slot name="header">Shared Document</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold">{{ $document->title }}</h2>
                            <p class="text-sm text-base-content/60">{{ $document->document_number }} · {{ $document->division->code }}</p>
                        </div>
                        <span class="badge {{ $link->role === 'editor' ? 'badge-primary' : 'badge-ghost' }}">
                            {{ ucfirst($link->role) }} access
                        </span>
                    </div>

                    @if($document->currentVersion)
                        <div class="prose max-w-none">
                            {!! $document->currentVersion->content !!}
                        </div>
                    @else
                        <p class="text-base-content/60 italic">No content yet.</p>
                    @endif
                </div>
            </div>

            @if($link->role === 'editor')
                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body">
                        <h3 class="font-semibold mb-4">Edit Document</h3>
                        <form method="POST" action="{{ route('shared.documents.save', $link->token) }}">
                            @csrf
                            <textarea name="content" id="editor-shared" rows="15" class="textarea textarea-bordered w-full">{{ $document->currentVersion->content ?? '' }}</textarea>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="btn btn-primary">
                                    Save & Submit for Approval
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    @if($link->role === 'editor')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jodit/4.0.32/jodit.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jodit/4.0.32/jodit.min.css" />
    <script>
        Jodit.make('#editor-shared', {
            height: 400,
            buttons: ['bold', 'italic', 'underline', '|', 'ul', 'ol', '|', 'font', 'fontsize', 'brush', '|', 'link', '|', 'paragraph', 'align', '|', 'undo', 'redo'],
            uploader: { insertImageAsBase64URI: true },
            disablePlugins: ['speech', 'video', 'file'],
        });
    </script>
    @endif
    @endpush
</x-app-layout>
</x-app-layout>
