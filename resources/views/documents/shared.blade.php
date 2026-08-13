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
                </div>
            </div>

            {{-- Export to PDF — same flow as the document show page. Paper size
                 picked in the modal overrides only this export; margin follows
                 the document's saved margin (clamped if needed). --}}
            @if(!($document->displayVersion()?->file_path))
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
                    <div class="flex-1 min-w-0">
                        @if(session('pdf_export'))
                            <div class="alert alert-success shadow-sm">
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 w-full">
                                    <span>PDF berhasil dibuat. <span class="font-medium">{{ session('pdf_export.filename') }}</span></span>
                                    <a href="{{ session('pdf_export.url') }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm shrink-0">
                                        Download PDF
                                    </a>
                                </div>
                            </div>
                        @elseif($errors->has('export'))
                            <div class="alert alert-error shadow-sm">
                                <span>{{ $errors->first('export') }} Silakan coba lagi.</span>
                            </div>
                        @endif
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm border border-base-300 shrink-0"
                            onclick="document.getElementById('shared-export-pdf-modal').showModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Export PDF
                    </button>
                </div>

                <dialog id="shared-export-pdf-modal" class="modal">
                    <div class="modal-box max-w-sm max-h-[85vh] overflow-y-auto">
                        <div class="flex flex-wrap items-center justify-between mb-4">
                            <h3 class="font-semibold">Export ke PDF</h3>
                            <button type="button" class="btn btn-ghost btn-sm btn-circle" onclick="document.getElementById('shared-export-pdf-modal').close()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        <form method="POST" action="{{ route('shared.documents.export-pdf', $link->token) }}"
                              onsubmit="this.querySelector('button[type=submit]').disabled = true;
                                        this.querySelector('button[type=submit]').classList.add('loading');
                                        this.querySelector('button[type=submit]').innerHTML = 'Membuat PDF&hellip;';
                                        return true;">
                            @csrf
                            <div class="form-control w-full mb-2">
                                <label class="label"><span class="label-text font-medium">Ukuran Kertas</span></label>
                                <select name="paper_size" class="select select-bordered w-full">
                                    @foreach(['A4','A5','A3','Letter','Legal'] as $size)
                                        <option value="{{ $size }}" {{ ($document->paper_size ?? 'A4') === $size ? 'selected' : '' }}>{{ $size }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-base-content/50 mb-4">
                                Margin tetap mengikuti margin dokumen saat ini; kalau tidak muat di kertas yang dipilih, margin akan disesuaikan otomatis.
                            </p>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('shared-export-pdf-modal').close()">Batal</button>
                                <button type="submit" class="btn btn-primary btn-sm">Export</button>
                            </div>
                        </form>
                    </div>
                    <form method="dialog" class="modal-backdrop">
                        <button>close</button>
                    </form>
                </dialog>
            @endif

            @if($link->role === 'viewer')
                <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                    <div class="card-body p-0">
                        @php $display = $document->displayVersion(); @endphp
                        @if($display && $display->file_path)
                            @include('documents._file-preview', ['document' => $document, 'version' => $display])
                        @elseif($display)
                            @include('documents._paper', [
                                'content' => $display->content,
                                'document' => $document,
                                'liveStorage' => 'doc-preview-' . $document->id,
                                'paperSize' => $document->paper_size ?? 'A4',
                                'paperMargin' => $document->paper_margin,
                            ])
                        @else
                            <p class="text-base-content/60 italic p-4 sm:p-6">No approved content yet.</p>
                        @endif
                    </div>
                </div>
            @endif

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
                                data-live-storage="doc-preview-{{ $document->id }}"
                                data-qr-image-url="{{ route('documents.qrcode', $document) }}"
                            >{{ $pending->content ?? $document->displayVersion()->content ?? '' }}</textarea>                            <div class="mt-4 flex flex-wrap justify-end gap-2">
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

