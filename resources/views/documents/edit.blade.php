<x-app-layout>
    <x-slot name="header">Edit: {{ $document->title }}</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            @if($errors->any())
                <div class="alert alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('documents.save', $document) }}" id="editor-form">
                        @csrf
                        @method('PUT')

                        <div class="form-control w-full mb-4">
                            <label class="label">
                                <span class="label-text font-medium">Content</span>
                            </label>
                            <textarea name="content" id="editor" rows="20" class="textarea textarea-bordered w-full">{{ $document->currentVersion->content ?? '' }}</textarea>
                            @error('content') <p class="text-sm text-error mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-between items-center">
                            <p class="text-sm text-base-content/60">Save will create a pending version requiring Head approval.</p>
                            <button type="submit" class="btn btn-primary">
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
