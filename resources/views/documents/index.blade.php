<x-app-layout>
    <x-slot name="header">My Documents</x-slot>

    <div
        x-data="previewModal()"
        x-init="$watch('open', value => { document.body.classList.toggle('overflow-hidden', value) })"
    >
    <div class="py-6">
        <div class="max-w-7xl mx-auto">
            <div class="mb-4 flex gap-2">
                <a href="{{ route('documents.create') }}" class="btn btn-primary">
                    + New Document
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success mb-4">
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body p-0">
                    <div class="divide-y divide-base-200">
                        @forelse($documents as $doc)
                            <div class="px-6 py-4 flex items-center justify-between">
                                <div>
                                    <a href="{{ route('documents.show', $doc) }}" class="link link-primary font-medium">
                                        {{ $doc->title }}
                                    </a>
                                    <p class="text-sm text-base-content/60">
                                        {{ $doc->document_number }}
                                        @if($doc->is_public) <span class="text-success">· Public</span> @endif
                                        · {{ $doc->division->code }}
                                        · {{ $doc->owner->name }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-sm text-base-content/60">
                                        @if($doc->currentVersion)
                                            v{{ $doc->currentVersion->version_number }}
                                        @else
                                            <span class="text-warning">Pending</span>
                                        @endif
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm btn-circle"
                                        title="Preview"
                                        x-on:click="previewDoc(
                                            '{{ route('documents.preview-content', $doc) }}',
                                            '{{ $doc->title }}',
                                            '{{ $doc->document_number }} · {{ $doc->division->code }}'
                                        )"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="p-6 text-base-content/60">No documents found.</div>
                        @endforelse
                    </div>
                    @if($documents->hasPages())
                        <div class="p-4 border-t border-base-200">
                            {{ $documents->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Preview modal --}}
    <div
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false"
        class="fixed inset-0 z-50 flex items-start justify-center px-4 py-6 sm:px-0"
    >
        <div class="fixed inset-0 bg-base-content/40 backdrop-blur-sm" x-on:click="open = false"></div>

        <div class="relative bg-base-100 rounded-box shadow-lg border border-base-300 w-full max-w-4xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-base-300 shrink-0">
                <div>
                    <div class="font-semibold text-base-content" x-text="title"></div>
                    <div class="text-sm text-base-content/60" x-text="subtitle"></div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm btn-circle" x-on:click="open = false">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto px-6 py-4" x-show="!loading">
                <div x-html="content"></div>
            </div>
            <div class="px-6 py-8 text-center text-base-content/60" x-show="loading">Memuat preview...</div>
        </div>
    </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('previewModal', () => ({
                open: false,
                loading: false,
                content: '',
                title: '',
                subtitle: '',
                async previewDoc(url, docTitle, docSubtitle) {
                    this.title = docTitle;
                    this.subtitle = docSubtitle;
                    this.open = true;
                    this.loading = true;
                    this.content = '';
                    try {
                        const res = await fetch(url);
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        this.content = await res.text();
                    } catch (e) {
                        this.content = '<p class="text-error">Gagal memuat preview.</p>';
                    } finally {
                        this.loading = false;
                    }
                },
            }));
        });
    </script>
</x-app-layout>
