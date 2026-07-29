<x-app-layout>
    <x-slot name="header">Shared Document</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-xl font-bold">{{ $document->title }}</h2>
                            <p class="text-sm text-gray-500">{{ $document->document_number }} · {{ $document->division->code }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded {{ $link->role === 'editor' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst($link->role) }} access
                        </span>
                    </div>

                    @if($document->currentVersion)
                        <div class="prose max-w-none">
                            {!! $document->currentVersion->content !!}
                        </div>
                    @else
                        <p class="text-gray-500 italic">No content yet.</p>
                    @endif
                </div>
            </div>

            @if($link->role === 'editor')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold mb-4">Edit Document</h3>
                        <form method="POST" action="{{ route('shared.documents.save', $link->token) }}">
                            @csrf
                            <textarea name="content" id="editor-shared" rows="15" class="w-full border rounded-md">{{ $document->currentVersion->content ?? '' }}</textarea>
                            <div class="mt-4 flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
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
