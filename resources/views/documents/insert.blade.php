@php
    // Dokumen baru = belum pernah dikirim ke approval (cuma versi draft).
    $isNewDoc = $document->versions->contains(fn($v) => $v->status !== 'draft') === false;
@endphp

<x-app-layout>
    <div class="min-h-screen bg-base-200/50">

        {{-- Top Bar ala Word/Docs --}}
        <div class="sticky top-0 z-20 bg-base-100 border-b border-base-300 shadow-sm">
            <div class="max-w-6xl mx-auto px-3 sm:px-6 py-3 flex flex-wrap items-center justify-between gap-3">

                <div class="flex items-center gap-3 min-w-0">
                    <svg class="w-6 h-6 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h1 class="text-base sm:text-lg font-semibold truncate min-w-0">{{ $document->title }}</h1>
                    <span class="badge badge-ghost badge-sm hidden sm:inline-flex">
                        {{ $document->document_number ?? '' }}
                    </span>
                    @if($document->versions()->where('status', 'draft')->exists())
                        <span class="badge badge-warning badge-sm">Draft</span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <div class="hidden md:flex items-center gap-2 pr-3 mr-1 border-r border-base-300">
                        <div class="avatar placeholder">
                            <div class="bg-neutral text-neutral-content rounded-full w-8">
                                <span class="text-xs">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        </div>
                        <span class="text-sm font-medium">{{ auth()->user()->name }}</span>
                    </div>

                    <button type="button" onclick="document.getElementById('discard-modal').showModal()" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Cancel
                    </button>

                    <!-- @if(Route::has('documents.preview'))
                        <a href="{{ route('documents.preview', $document) }}" target="_blank" class="btn btn-ghost btn-sm gap-1">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview
                        </a>
                    @endif -->

                    <button type="submit" form="editor-form" class="btn btn-primary btn-sm px-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Save Changes
                    </button>
                </div>
            </div>

            @if($errors->any())
                <div class="max-w-6xl mx-auto px-3 sm:px-6 pb-3">
                    <div class="alert alert-error py-2 text-sm">
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Canvas / Dokumen --}}
        <div class="py-10 px-2 sm:px-4">
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
                            data-live-storage="doc-preview-{{ $document->id }}"
                        >{{ $document->displayVersion()->content ?? '' }}</textarea>
                    </div>

                    {{-- Pengaturan kertas: diisi JS dari editor sebelum submit,
                         biar pilihan ukuran kertas & margin ikut tersimpan. --}}
                    <input type="hidden" name="paper_size" id="paper-size-input" value="{{ $document->paper_size ?? 'A4' }}">
                    <input type="hidden" name="paper_margin" id="paper-margin-input" value="{{ $document->paper_margin ? json_encode($document->paper_margin) : '' }}">

                    <p class="text-center text-xs text-base-content/50 mt-4 px-2">
                        Save akan membuat versi baru yang menunggu approval Head.
                    </p>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Discard / Save as Draft --}}
    <x-confirm-modal
        name="confirm-discard-insert"
        title="Discard this document permanently?"
        message="Dokumen ini akan dihapus secara permanen dan tidak bisa dikembalikan."
        :action="route('documents.destroy', $document)"
        method="DELETE"
        confirmLabel="Discard"
        cancelLabel="Cancel"
    />

    <dialog id="discard-modal" class="modal">
        <div class="modal-box">
            <h3 class="font-semibold text-lg mb-1">Unsaved changes</h3>
            @if($isNewDoc)
                <p class="text-sm text-base-content/60">Save as draft or discard this document?</p>
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('discard-modal').close()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Keep Editing
                    </button>
                    <form method="POST" action="{{ route('documents.save-draft', $document) }}" class="inline">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="content" id="draft-content">
                        {{-- Pengaturan kertas wajib ikut terkirim — kalau tidak,
                             saveDraft() menulis paper_margin/paper_size null dan
                             margin yang baru di-set editor hilang. --}}
                        <input type="hidden" name="paper_size" id="draft-paper-size-input" value="{{ $document->paper_size ?? 'A4' }}">
                        <input type="hidden" name="paper_margin" id="draft-paper-margin-input" value="{{ $document->paper_margin ? json_encode($document->paper_margin) : '' }}">
                        <button type="submit" class="btn btn-neutral">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                            Save as Draft
                        </button>
                    </form>
                    <form method="POST" action="{{ route('documents.destroy', $document) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                                class="btn btn-error"
                                onclick="document.getElementById('discard-modal').close()"
                                x-on:click="$dispatch('open-modal', 'confirm-discard-insert')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            Discard
                        </button>
                    </form>
                </div>
            @else
                <p class="text-sm text-base-content/60">Keluar tanpa menyimpan perubahan?</p>
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('discard-modal').close()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        Keep Editing
                    </button>
                    <a href="{{ url()->previous() }}" class="btn btn-neutral">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back
                    </a>
                </div>
            @endif
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</x-app-layout>

<script>
    // Isi hidden input dengan konten editor saat Save as Draft diklik
    document.getElementById('discard-modal').addEventListener('click', function (e) {
        const saveDraftForm = e.target.closest('form[action*="save-draft"]');
        if (saveDraftForm) {
            const joditEl = document.getElementById('jodit-editor');
            const editor = window.__joditInstances?.get(joditEl.id);
            // Buang elemen jeda pagination (data-page-spacer) — setara
            // getCleanValue() di jodit.js — supaya spacer tidak ikut
            // tersimpan ke database sebagai bagian dari konten dokumen.
            const div = document.createElement('div');
            div.innerHTML = editor ? editor.value : joditEl.value;
            div.querySelectorAll('[data-page-spacer]').forEach(el => el.remove());
            document.getElementById('draft-content').value = div.innerHTML;
        }
    });

    // Sinkronkan pengaturan kertas (ukuran + margin) dari editor ke hidden
    // input sebelum form disubmit, supaya ikut tersimpan ke database.
    // Editor dicari LAZILY (saat submit), karena instance Jodit baru terdaftar
    // di window.__joditInstances setelah halaman selesai dimuat — kalau dicari
    // di awal script (parse time), belum ada.
    (function () {
        function syncPaper(editor, sizeInput, marginInput) {
            const size = editor?.currentPaperSize;
            const margin = editor?.currentMargin;
            if (!size || !margin) return;
            const key = Object.keys(window.__paperSizes || {})
                .find(k => window.__paperSizes[k] === size);
            if (sizeInput && key) sizeInput.value = key;
            if (marginInput) marginInput.value = JSON.stringify(margin);
        }

        // Form utama "Save Changes"
        const form = document.getElementById('editor-form');
        if (form) {
            form.addEventListener('submit', function () {
                syncPaper(
                    window.__joditInstances?.get('jodit-editor'),
                    document.getElementById('paper-size-input'),
                    document.getElementById('paper-margin-input')
                );
            });
        }

        // Form "Save as Draft" (modal discard) — pengaturan kertas wajib ikut
        // tersimpan; tanpa ini margin/ukuran kertas yang baru di-set editor
        // hilang (fallback ke nilai lama) saat save draft.
        const draftForm = document.querySelector('form[action*="save-draft"]');
        if (draftForm) {
            draftForm.addEventListener('submit', function () {
                syncPaper(
                    window.__joditInstances?.get('jodit-editor'),
                    document.getElementById('draft-paper-size-input'),
                    document.getElementById('draft-paper-margin-input')
                );
            });
        }
    })();
</script>
