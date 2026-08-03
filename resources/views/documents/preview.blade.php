<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <span>{{ $document->title }}</span>
            <span class="text-sm font-normal text-base-content/60">{{ $document->document_number }}</span>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto">
            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4 pb-4 border-b border-base-300">
                        <div class="text-sm">
                            <div><span class="text-base-content/60">Division:</span> {{ $document->division?->code ?? '—' }}</div>
                            <div><span class="text-base-content/60">Owner:</span> {{ $document->owner->name }}</div>
                        </div>
                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">Back to Edit</a>
                    </div>

                    <div id="live-preview-content">
                        @if($document->displayVersion())
                            <div class="prose max-w-none">
                                {!! $document->displayVersion()->content !!}
                            </div>
                        @else
                            <p class="text-base-content/60 italic">No approved content yet.</p>
                        @endif
                    </div>

                    {{-- Live sync: konten dari tab editor via localStorage --}}
                    <script>
                        (function () {
                            const key = 'doc-preview-{{ $document->id }}';
                            const target = document.getElementById('live-preview-content');
                            if (!target) return;

                            function render(html) {
                                if (html && html.trim().length) {
                                    target.innerHTML = '<div class="prose max-w-none">' + html + '</div>';
                                }
                            }

                            // Hanya render draft kalau benar-benar ada konten (bukan cuma <p><br></p>)
                            function hasRealContent(html) {
                                if (!html) return false;
                                const el = document.createElement('div');
                                el.innerHTML = html;
                                return el.textContent.trim().length > 0 || el.querySelector('img, table, iframe');
                            }

                            // Konten terakhir yang tersimpan di editor (kalau ada)
                            const draft = localStorage.getItem(key);
                            if (hasRealContent(draft)) render(draft);

                            // Tab editor menulis → storage event → update realtime
                            window.addEventListener('storage', (e) => {
                                if (e.key === key && hasRealContent(e.newValue)) {
                                    render(e.newValue);
                                }
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
