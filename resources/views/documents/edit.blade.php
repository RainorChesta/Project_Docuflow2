<x-app-layout>
    <div class="min-h-screen bg-base-200/50">

        {{-- Top Bar ala Word/Docs --}}
        <div class="sticky top-0 z-20 bg-base-100 border-b border-base-300 shadow-sm">
            <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between gap-4">

                <div class="flex items-center gap-3 min-w-0">
                    <svg class="w-6 h-6 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h1 class="text-lg font-semibold truncate">{{ $document->title }}</h1>
                    <span class="badge badge-ghost badge-sm hidden sm:inline-flex">
                        {{ $document->document_number ?? '' }}
                    </span>
                </div>

                <div class="flex items-center gap-3 shrink-0">
                    <div class="hidden md:flex items-center gap-2 pr-3 mr-1 border-r border-base-300">
                        <div class="avatar placeholder">
                            <div class="bg-neutral text-neutral-content rounded-full w-8">
                                <span class="text-xs">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium">{{ auth()->user()->name }}</span>
                    </div>

                    <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm">
                        Cancel
                    </a>

                    @if(Route::has('documents.preview'))
                        <a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-ghost btn-sm gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </a>
                    @endif

                    <button type="submit" form="editor-form" class="btn btn-primary btn-sm px-6">
                        Save Changes
                    </button>
                </div>
            </div>

            @if($errors->any())
                <div class="max-w-6xl mx-auto px-6 pb-3">
                    <div class="alert alert-error py-2 text-sm">
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif

            {{-- Pending version warning: saving updates the pending version in place --}}
            @php
                $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
            @endphp
            @if($pending)
                <div class="max-w-6xl mx-auto px-6 pb-3">
                    <div class="alert alert-warning shadow-sm">
                        <div class="flex items-center justify-between gap-4 w-full">
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>
                                    Ada versi pending (v{{ $pending->version_number }}) yang belum di-review.
                                    <strong>Save akan memperbarui versi pending tersebut (tanpa versi baru).</strong>
                                </span>
                            </div>
                            <form method="POST" action="{{ route('documents.discard', $document) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-warning btn-sm">Discard pending (v{{ $pending->version_number }})</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Canvas / Dokumen --}}
        <div class="py-10 px-4">
            <form method="POST" action="{{ route('documents.save', $document) }}" id="editor-form">
                @csrf
                @method('PUT')

                <div>
                    <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                        <textarea
                            name="content"
                            id="jodit-editor"
                            data-upload-url="{{ route('jodit.upload') }}"
                            data-csrf-token="{{ csrf_token() }}"
                        >{{ $document->currentVersion->content ?? '' }}</textarea>
                    </div>

                    <p class="text-center text-xs text-base-content/50 mt-4">
                        Save akan membuat versi baru yang menunggu approval Head.
                        @if($pending ?? null)
                            Versi pending yang ada akan diperbarui (bukan versi baru).
                        @endif
                    </p>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>