<x-app-layout>
    <x-slot name="header">Edit: {{ $document->title }}</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('documents.save', $document) }}" id="editor-form">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                            <textarea name="content" id="editor" rows="20" class="w-full border rounded-md">{{ $document->currentVersion->content ?? '' }}</textarea>
                            @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-between items-center">
                            <p class="text-sm text-gray-500">Save will create a pending version requiring Head approval.</p>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                Save & Submit for Approval
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jodit/4.0.32/jodit.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jodit/4.0.32/jodit.min.css" />
    <script>
        const editor = Jodit.make('#editor', {
            height: 500,
            buttons: ['bold', 'italic', 'underline', '|', 'ul', 'ol', '|', 'font', 'fontsize', 'brush', '|', 'link', 'image', '|', 'paragraph', 'align', '|', 'undo', 'redo'],
            uploader: { insertImageAsBase64URI: true },
            disablePlugins: ['speech', 'video', 'file'],
        });
    </script>
    @endpush
</x-app-layout>
