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
                        @php $isFileBased = $document->displayVersion()?->file_path; @endphp
                        <div class="flex flex-wrap items-center gap-2">
                            @if(!$isFileBased)
                                <form method="POST" action="{{ route('documents.export-pdf', $document) }}" class="inline"
                                      onsubmit="this.querySelector('button').disabled = true;
                                                this.querySelector('button').classList.add('loading');
                                                this.querySelector('button').innerHTML = 'Membuat PDF&hellip;';
                                                return true;">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-sm border border-base-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Export PDF
                                    </button>
                                </form>
                            @endif
                            @can('update', $document)
                                <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    Back to Edit
                                </a>
                            @else
                                <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                                    Back
                                </a>
                            @endcan
                        </div>
                    </div>

                    @if(session('pdf_export'))
                        <div class="alert alert-success mt-3">
                            <div class="flex items-center justify-between gap-3 w-full">
                                <span>PDF berhasil dibuat. <span class="font-medium">{{ session('pdf_export.filename') }}</span></span>
                                <a href="{{ session('pdf_export.url') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm shrink-0">
                                    Download PDF
                                </a>
                            </div>
                        </div>
                    @endif

                    @if($errors->has('export'))
                        <div class="alert alert-error mb-6">
                            <span>{{ $errors->first('export') }} Silakan coba lagi.</span>
                        </div>
                    @endif

                    <div id="live-preview-content">
                        @php $display = $document->displayVersion(); @endphp
                        @if($display && $display->file_path)
                            @include('documents._file-preview', ['document' => $document, 'version' => $display])
                        @elseif($display)
                            @include('documents._paper', [
                                'content' => $display->content,
                                'liveStorage' => 'doc-preview-' . $document->id,
                                'paperSize' => $document->paper_size ?? 'A4',
                                'paperMargin' => $document->paper_margin,
                            ])
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
                                    // Bungkus dengan struktur yang SAMA PERSIS seperti partial _paper:
                                    // .doku-paper-scope > .doku-paper, karena semua CSS kertas A4
                                    // (garis pembatas halaman, font, padding) di-scope lewat
                                    // selector ".doku-paper-scope .doku-paper". Kalau wrapper
                                    // scope ini hilang, style-nya nggak ke-apply sama sekali.
                                    const scope = document.createElement('div');
                                    scope.className = 'doku-paper-scope';
                                    scope.dataset.liveStorage = 'doc-preview-{{ $document->id }}';
                                    scope.dataset.paperSize = '{{ $document->paper_size ?? "A4" }}';
                                    scope.dataset.paperMargin = '{{ json_encode($document->paper_margin) }}';

                                    const paper = document.createElement('div');
                                    paper.className = 'doku-paper';
                                    paper.innerHTML = html;

                                    scope.appendChild(paper);
                                    target.innerHTML = '';
                                    target.appendChild(scope);

                                    // Terapkan batas antar halaman sesuai ukuran kertas yang
                                    // aktif di editor (dibaca dari localStorage).
                                    if (window.__initPreviewPagination) {
                                        window.__initPreviewPagination(scope);
                                    }
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