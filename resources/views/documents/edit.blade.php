<x-app-layout>

    @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
        <x-confirm-modal
            name="confirm-discard-{{ $document->id }}"
            title="Discard Document?"
            message="Are you sure you want to discard this document and all its changes?"
            :action="route('documents.destroy', $document)"
            method="DELETE"
            confirmLabel="Discard"
        />

        <x-confirm-modal
            name="confirm-discard-version-{{ $document->id }}"
            title="Discard Changes?"
            message="Are you sure you want to discard the pending changes? The approved version will remain intact."
            :action="route('documents.discard', $document)"
            method="POST"
            confirmLabel="Discard Changes"
        />
    @endif

    @php
        $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
        $hasDraftOnly = !$pending && !$document->currentVersion;
    @endphp

    <div class="h-full overflow-hidden bg-base-200/50 flex flex-col">

        {{-- Canvas / Dokumen --}}
        <div class="flex-1 flex flex-col min-h-0 py-4 px-2 sm:px-4">
            <div class="max-w-6xl mx-auto w-full flex-1 flex flex-col min-h-0">

                @if(session('success'))
                    <div class="mb-4">
                        <div class="alert alert-success shadow-sm">
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4">
                        <div class="alert alert-error py-2 text-sm">
                            <span>{{ $errors->first() }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Pending version warning: saving updates the pending version in place --}}
            @if($pending)
                <div x-data="{ show: true }" x-show="show" class="mb-4">
                    <div class="alert alert-warning shadow-sm">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 w-full">
                            <div class="flex items-start sm:items-center gap-2 text-sm min-w-0">
                                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>
                                    {{ __('There is a pending version (v:version) not yet reviewed. Save will update the pending version (no new version).', ['version' => $pending->version_number]) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="show = false" class="btn btn-ghost btn-sm btn-circle" aria-label="Close">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

                <form method="POST" action="{{ route('documents.save', $document) }}" id="editor-form" class="flex flex-col flex-1 min-h-0">
                    @csrf
                    @method('PUT')

                    {{-- Kotak gabungan: title bar + toolbar + editor Jodit jadi satu kotak --}}
                    <div id="jodit-merge-box" class="bg-base-100 rounded-xl shadow-md border border-base-300 flex flex-col flex-1 min-h-0">

                        {{-- Title/Action row --}}
                        <div class="bg-base-100 px-3 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3 rounded-t-xl">

                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-6 h-6 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h1 class="text-base sm:text-lg font-semibold truncate min-w-0">{{ $document->title }}</h1>
                                <span class="badge badge-ghost badge-sm hidden sm:inline-flex">
                                    {{ $document->document_number ?? '' }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 shrink-0">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('cancel-modal').showModal()">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    {{ __('Batal') }} 
                                </button>

                                @if($document->currentVersion)
                                    @can('update', $document)
                                        @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
                                            <button type="button" class="btn btn-outline btn-primary btn-sm" x-on:click="$dispatch('open-modal', 'confirm-discard-version-{{ $document->id }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                {{ __('Discard') }}
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('documents.discard', $document) }}" class="shrink-0">
                                                @csrf
                                                <button type="submit" class="btn btn-outline btn-primary btn-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    {{ __('Discard') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                @else
                                    @can('delete', $document)
                                        @if(!auth()->user()->isAdmin() && !auth()->user()->isHead())
                                            <button type="button" class="btn btn-outline btn-primary btn-sm" x-on:click="$dispatch('open-modal', 'confirm-discard-{{ $document->id }}')">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                {{ __('Discard') }}
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('documents.destroy', $document) }}" class="shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline btn-primary btn-sm">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    {{ __('Discard') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                @endif
                                
                                <button type="submit" form="editor-form" class="btn btn-primary btn-sm px-6">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                    {{ __('Simpan Perubahan') }}
                                </button>
                            </div>
                        </div>

                        <textarea
                            name="content"
                            id="jodit-editor"
                            data-upload-url="{{ route('jodit.upload') }}"
                            data-csrf-token="{{ csrf_token() }}"
                            data-live-storage="doc-preview-{{ $document->id }}"
                            data-qr-image-url="{{ route('documents.qrcode', $document) }}"
                            data-paper-size="{{ $document->paper_size ?? 'A4' }}"
                            data-paper-margin="{{ json_encode($document->paper_margin) }}"
                        >{{ $document->displayVersion()->content ?? '' }}</textarea>
                    </div>

                    <input type="hidden" name="paper_size" id="paper-size-input">
                    <input type="hidden" name="paper_margin" id="paper-margin-input">

                    <p class="text-center text-xs text-base-content/50 mt-4 px-2">
                        @if($hasDraftOnly)
                            {!! __('Save Changes submits the draft for approval (status becomes pending).') !!}
                        @else
                            {{ __('Save will create a new version awaiting Head approval.') }}
                        @endif
                        @if($pending ?? null)
                            <br>{{ __('The existing pending version will be updated (not a new version).') }}
                        @endif
                    </p>
                </form>

                @if($hasDraftOnly)
                    <form method="POST" action="{{ route('documents.save-draft', $document) }}" id="draft-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="content" id="draft-content">
                        <input type="hidden" name="paper_size" id="draft-paper-size">
                        <input type="hidden" name="paper_margin" id="draft-paper-margin">
                    </form>
                @endif

                
                     
                <!-- <div class="mt-10">
                    <div class="bg-base-100 rounded-xl shadow-md border border-base-300 overflow-hidden">
                        <div class="px-4 py-2 bg-base-200 border-b border-base-300 text-xs text-base-content/50 font-medium tracking-wide uppercase">
                            Preview
                        </div>
                        <div class="p-4">
                            <div id="live-preview-content">
                                {{-- Render awal: tampilkan konten yang tersimpan di DB dengan
                                     _paper partial — konsisten dengan show.blade.php. --}}
                                @php $display = $document->displayVersion(); @endphp
                                @if($display && $display->content)
                                    @include('documents._paper', [
                                        'content'     => $display->content,
                                        'document'    => $document,
                                        'liveStorage' => 'doc-preview-' . $document->id,
                                        'paperSize'   => $document->paper_size ?? 'A4',
                                        'paperMargin' => $document->paper_margin,
                                    ])
                                @endif
                            </div>
                        </div>
                    </div>
                </div> -->
            <!-- </div> -->

            
        </div>
    </div>

    <style>
        /* ── Jodit container: hapus border/shadow/radius bawaan, jadikan flex
           child yang mengisi sisa ruang #jodit-merge-box. ── */
        #jodit-merge-box .jodit-container {
            border: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            margin: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            flex: 1 1 auto !important;
            min-height: 0 !important;
        }

        /* ── Toolbar: rata tanpa radius, TIDAK BOLEH menyusut. ── */
        #jodit-merge-box .jodit-toolbar__box,
        #jodit-merge-box .jodit-toolbar_box {
            border-radius: 0 !important;
            margin: 0 !important;
            flex-shrink: 0 !important;        /* kunci: toolbar tidak boleh collapse */
            z-index: 20 !important;
        }

        /* ── Workplace: flex-grow mengisi sisa, tapi TIDAK scroll sendiri.
           Scroll terjadi di dalam <iframe> (lihat rule berikutnya). ── */
        #jodit-merge-box .jodit-workplace {
            flex: 1 1 auto !important;
            min-height: 0 !important;
            overflow: hidden !important;       /* workplace sendiri tidak scroll */
        }

        /* ── KUNCI UTAMA: paksa <iframe> editor tinggi 100% dari workplace,
           bukan auto-grow mengikuti dokumen. Scroll terjadi di DALAM iframe
           (browser otomatis scroll isi iframe kalau kontennya lebih tinggi
           dari frame-nya). ── */
        #jodit-merge-box .jodit-workplace iframe {
            height: 100% !important;
            min-height: 0 !important;
            max-height: none !important;
        }
    </style>

    <script>
        (function () {
            let isDirty = false;
            let intendedUrl = null;

            // Monitor changes in Jodit
            function hookDirty() {
                const ta = document.getElementById('jodit-editor');
                const inst = window.__joditInstances?.get(ta.id);
                if (!inst) {
                    requestAnimationFrame(hookDirty);
                    return;
                }
                inst.e.on('change', () => {
                    isDirty = true;
                });
            }
            hookDirty();

            // Clear dirty flag on any form submit (save, discard, draft)
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    isDirty = false;
                });
            });

            // Handle URL changes, tab closes, reload
            window.addEventListener('beforeunload', (e) => {
                if (isDirty) {
                    e.preventDefault();
                    e.returnValue = ''; // Native browser dialog
                }
            });

            // Handle browser back button via popstate hack
            history.pushState({ page: 'edit' }, null, location.href);
            window.addEventListener('popstate', (e) => {
                if (isDirty) {
                    // Restore state so URL doesn't change
                    history.pushState({ page: 'edit' }, null, location.href);
                    intendedUrl = 'back';
                    document.getElementById('cancel-modal').showModal();
                } else {
                    history.back();
                }
            });

            // Handle internal link clicks
            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (link && isDirty && !link.hasAttribute('target') && !e.ctrlKey && !e.metaKey && link.hostname === window.location.hostname) {
                    if (link.closest('#cancel-modal')) return;
                    e.preventDefault();
                    intendedUrl = link.href;
                    document.getElementById('cancel-modal').showModal();
                }
            });

            window.proceedCancel = function() {
                localStorage.removeItem('doc-preview-{{ $document->id }}');
                localStorage.removeItem('doc-preview-{{ $document->id }}:paper');
                isDirty = false;

                if (intendedUrl === 'back') {
                    history.go(-2);
                } else if (intendedUrl) {
                    window.location.href = intendedUrl;
                } else {
                    window.location.href = "{{ route('documents.show', $document) }}";
                }
            };

            const target = document.getElementById('live-preview-content');
            const storageKey = 'doc-preview-{{ $document->id }}';

            // Bungkus HTML konten editor ke dalam struktur .doku-paper-scope >
            // .doku-paper — SAMA PERSIS dengan yang dipakai preview.blade.php
            // untuk live-sync. Ini yang memastikan CSS dari _paper.blade.php
            // (dan document-shared.css .doku-paper scope) berlaku pada preview
            // di halaman edit, sehingga hasil yang dilihat di sini identik
            // dengan halaman preview/show sesungguhnya.
            function renderPaper(html) {
                if (!html || !html.trim().length) return;

                const scope = document.createElement('div');
                scope.className = 'doku-paper-scope';
                scope.dataset.liveStorage = storageKey;
                scope.dataset.paperSize = '{{ $document->paper_size ?? "A4" }}';
                scope.dataset.paperMargin = '{{ json_encode($document->paper_margin) }}';

                const paper = document.createElement('div');
                paper.className = 'doku-paper';
                paper.innerHTML = html;

                scope.appendChild(paper);
                target.innerHTML = '';
                target.appendChild(scope);

                // Terapkan batas antar halaman sesuai ukuran kertas aktif
                // (dibaca dari localStorage, di-set oleh applyPaperSize di jodit.js).
                if (window.__initPreviewPagination) {
                    window.__initPreviewPagination(scope);
                }
            }

            // FIX #3 (preview di halaman Edit tidak sinkron / beda posisi
            // elemen dari editor): dipakai untuk refresh panel Preview baik
            // karena konten berubah (ada draft baru di localStorage) maupun
            // karena HANYA margin/ukuran kertas yang berubah (kontennya sama,
            // cukup repaginate ulang scope yang sudah ada).
            function refreshPreview() {
                const draft = localStorage.getItem(storageKey);
                if (draft && draft.trim().length) {
                    renderPaper(draft);
                    return;
                }
                // Tidak ada draft konten baru — repaginate ulang scope yang
                // sudah ada memakai ukuran kertas & margin TERBARU (dibaca
                // window.__initPreviewPagination dari localStorage ':paper',
                // yang di-update applyPaperSize() setiap kali tombol Margin /
                // Ukuran Kertas dipakai di toolbar editor).
                const scope = target.querySelector('.doku-paper-scope');
                if (scope && window.__initPreviewPagination) {
                    window.__initPreviewPagination(scope);
                }
            }

            // Cross-tab: event bawaan browser 'storage' HANYA fire di tab
            // LAIN (bukan tab yang memanggil localStorage.setItem). Ini
            // dipertahankan untuk kasus user membuka tab preview terpisah.
            window.addEventListener('storage', (e) => {
                if (e.key === storageKey && e.newValue && e.newValue.trim().length) {
                    renderPaper(e.newValue);
                }
            });

            // Same-tab (perbaikan utama): jodit.js sekarang mem-broadcast
            // CustomEvent 'doku:draft-updated' setiap kali draft konten
            // ditulis ke localStorage (event 'storage' bawaan browser TIDAK
            // PERNAH fire di tab yang sama dengan yang menulis, sehingga
            // sebelumnya panel Preview DI HALAMAN EDIT ITU SENDIRI tidak
            // pernah ter-refresh saat mengetik/mengubah konten — inilah
            // salah satu penyebab preview terlihat "basi"/beda dari editor).
            window.addEventListener('doku:draft-updated', (e) => {
                if (e.detail?.storageKey === storageKey) {
                    refreshPreview();
                }
            });

            // Same-tab, khusus perubahan Margin Halaman / Ukuran Kertas:
            // tombol-tombol itu memanggil applyPaperSize() yang HANYA
            // fire event 'resize' / 'afterResize' pada instance Jodit
            // (bukan 'change'), jadi tidak lewat jalur draft-updated di
            // atas. Tanpa hook ini, preview di halaman edit tidak ikut
            // berubah saat margin/ukuran kertas diganti — persis gejala
            // "beda 1 baris peletakan elemen antara editor dan preview
            // saat ukuran kertas diubah", karena preview yang ditampilkan
            // masih pakai pagination lama.
            (function hookEditorInstance() {
                const ta = document.getElementById('jodit-editor');
                const inst = window.__joditInstances?.get(ta.id);
                if (!inst) {
                    requestAnimationFrame(hookEditorInstance);
                    return;
                }
                let raf = null;
                const scheduleRefresh = () => {
                    if (raf) return;
                    raf = requestAnimationFrame(() => {
                        raf = null;
                        refreshPreview();
                    });
                };
                inst.e.on('afterResize.livePreview', scheduleRefresh);
            })();

            // Isi hidden input draft-form dengan konten editor saat submit
// Isi hidden input draft-form dengan konten editor saat submit
const draftForm = document.getElementById('draft-form');
if (draftForm) {
    draftForm.addEventListener('submit', () => {
        const ta = document.getElementById('jodit-editor');
        const inst = window.__joditInstances?.get(ta.id);
        document.getElementById('draft-content').value = inst ? inst.value : ta.value;

        const sizeKey = inst && window.__findPaperKey
            ? (window.__findPaperKey(inst.currentPaperSize) || 'A4')
            : 'A4';
        document.getElementById('draft-paper-size').value = sizeKey;
        document.getElementById('draft-paper-margin').value = inst
            ? JSON.stringify(inst.currentMargin || {})
            : '';

        // FIX: konten sudah resmi tersimpan ke DB — draft lokal tidak
        // perlu lagi, hapus supaya tidak menimpa data baru saat edit dibuka lagi.
        localStorage.removeItem(storageKey);
        localStorage.removeItem(storageKey + ':paper');
    });
}
        })();
    </script>

    <dialog id="cancel-modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Batal Edit Dokumen?</h3>
            <p class="py-4">
                @if($hasDraftOnly)
                    Apa yang ingin Anda lakukan dengan perubahan pada dokumen draft ini?
                @else
                    Perubahan yang belum Anda simpan akan dibatalkan. Lanjutkan?
                @endif
            </p>
            <div class="modal-action flex justify-between w-full">
                <form method="dialog">
                    <button class="btn btn-ghost">Keep Editing</button>
                </form>
                <div class="flex gap-2">
                    @if($hasDraftOnly)
                        <button type="submit" form="draft-form" class="btn btn-neutral" onclick="isDirty = false;">
                            Save Draft
                        </button>
                        <form action="{{ route('documents.destroy', $document) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-error" onclick="localStorage.removeItem('doc-preview-{{ $document->id }}'); localStorage.removeItem('doc-preview-{{ $document->id }}:paper'); isDirty = false;">
                                Discard
                            </button>
                        </form>
                    @else
                        <button type="button" class="btn btn-error" onclick="proceedCancel()">
                            Cancel
                        </button>
                    @endif
                </div>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>tutup</button>
        </form>
    </dialog>
</x-app-layout>