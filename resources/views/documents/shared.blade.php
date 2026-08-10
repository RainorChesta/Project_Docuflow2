<x-app-layout>
    <x-slot name="header">Shared Document</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto w-full">
            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-bold break-words">{{ $document->title }}</h2>
                            <p class="text-sm text-base-content/60">{{ $document->document_number }} · {{ $document->division?->code ?? '—' }}</p>
                            <p class="text-sm text-base-content/60">Author: <span class="font-medium text-base-content">{{ $document->owner->name }}</span></p>
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
                @php
                    $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
                @endphp
                @if($pending)
                    <div class="alert alert-warning mb-4 shadow-sm">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
                            <div class="flex items-start sm:items-center gap-2 text-sm">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>
                                    Ada versi pending (v{{ $pending->version_number }}) yang belum di-review.
                                    <strong>Save akan memperbarui versi pending tersebut (tanpa versi baru).</strong>
                                </span>
                            </div>
                            <form method="POST" action="{{ route('shared.documents.discard', $link->token) }}" class="shrink-0">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-warning btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    Discard pending (v{{ $pending->version_number }})
                                </button>
                            </form>
                        </div>
                    </div>
                @endif

                <div class="card bg-base-100 border border-base-300 shadow-sm">
                    <div class="card-body">
                        <h3 class="font-semibold mb-4">Edit Document</h3>
                        <form method="POST" action="{{ route('shared.documents.save', $link->token) }}">
                            @csrf
                            <textarea
                                name="content"
                                id="editor-shared"
                                rows="15"
                                class="textarea textarea-bordered w-full"
                                data-upload-url="{{ route('shared.documents.upload', $link->token) }}"
                                data-csrf-token="{{ csrf_token() }}"
                            >{{ $pending->content ?? $document->currentVersion->content ?? '' }}</textarea>                            <div class="mt-4 flex flex-wrap justify-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    Save & Submit for Approval
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

