<x-app-layout>
    <x-slot name="header">{{ __('ONLYOFFICE Document Editor') }}</x-slot>

    @php
        $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
        $isPendingV1 = (!$document->currentVersion) && (!$pending || $pending->version_number === 1);
        $hasDraftOnly = !$pending && !$document->currentVersion;
        $isPdf = ($version && ($version->file_path && str_ends_with(strtolower($version->file_path), '.pdf'))) || ($version && $version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'));
    @endphp

    @if($isPendingV1)
        <x-confirm-modal
            name="confirm-discard-v1-{{ $document->id }}"
            :title="__('Discard Document?')"
            :message="__('Are you sure you want to discard this document? The pending v1 document will be moved to trash.')"
            :action="route('documents.discard', $document)"
            method="POST"
            :confirmLabel="__('Buang ke Trash')"
            :cancelLabel="__('Batal')"
            confirmClass="btn-error"
        />
    @else
        <x-confirm-modal
            name="confirm-discard-version-{{ $document->id }}"
            :title="__('Discard Changes?')"
            :message="__('Are you sure you want to discard recent unsaved changes? The document and its base version will remain intact.')"
            :action="route('documents.discard', $document)"
            method="POST"
            :confirmLabel="__('Discard Changes')"
            :cancelLabel="__('Batal')"
        />
    @endif

    <div class="pb-6">
        <div class="max-w-7xl mx-auto w-full">
            {{-- Pending Alert if exists --}}
            @if($pending)
                <div class="mb-4 px-4 py-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-500/30 rounded-xl text-xs text-amber-900 dark:text-amber-200 flex items-center justify-between shadow-xs">
                    <span>{{ __('Terdapat versi pending (v:version) yang menunggu review. Setiap perubahan yang Anda simpan akan memperbarui versi pending ini.', ['version' => $pending->version_number]) }}</span>
                </div>
            @endif



            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6 print:border-none print:shadow-none print:bg-transparent print:mb-0 print:rounded-none">
                <div class="card-body p-0">
                    <div class="p-3 sm:p-4">
                        {{-- Top Navigation & Action Bar inside Card Header (Responsive on mobile & tablet) --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3 mb-3 px-1 sm:px-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <a href="{{ route('documents.show', $document) }}" 
                                   class="btn btn-ghost btn-xs btn-square shrink-0 text-base-content/70 hover:text-base-content" 
                                   title="{{ __('Kembali ke Detail Dokumen') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                </a>
                                <div class="text-xs sm:text-sm text-base-content/60 flex items-center gap-1.5 sm:gap-2 flex-wrap min-w-0">
                                    <span class="font-medium text-base-content truncate max-w-[140px] sm:max-w-xs" title="{{ $version->file_original_name ?? ($document->title . '.docx') }}">
                                        {{ $version->file_original_name ?? ($document->title . '.docx') }}
                                    </span>
                                    @if($document->document_number)
                                        <span class="badge badge-ghost badge-xs font-mono shrink-0">{{ $document->document_number }}</span>
                                    @endif
                                    <span class="badge badge-ghost badge-xs shrink-0">v{{ $version->version_number }}</span>
                                    @if($pending)
                                        <span class="badge badge-warning badge-xs shrink-0">{{ __('Pending') }}</span>
                                    @elseif($hasDraftOnly)
                                        <span class="badge badge-info badge-xs shrink-0">{{ __('Draft') }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Action Buttons: Responsive Wrap with Unclipped Dropdown --}}
                            <div class="flex items-center gap-1.5 flex-wrap overflow-visible shrink-0">
                                {{-- Download DOCX --}}
                                <a href="{{ route('documents.download', [$document, 'version_id' => $version->id]) }}"
                                   class="btn btn-primary btn-xs gap-1 shrink-0"
                                   title="{{ __('Download DOCX') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    <span class="hidden sm:inline">{{ __('Unduh DOCX') }}</span>
                                    <span class="sm:hidden">{{ __('Unduh') }}</span>
                                </a>

                                {{-- Discard --}}
                                @can('update', $document)
                                    @if($isPendingV1)
                                        <button type="button"
                                                class="btn btn-outline btn-error btn-xs gap-1 shrink-0"
                                                x-on:click="$dispatch('open-modal', 'confirm-discard-v1-{{ $document->id }}')"
                                                title="{{ __('Buang Dokumen') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span class="hidden sm:inline">{{ __('Buang Dokumen') }}</span>
                                            <span class="sm:hidden">{{ __('Buang') }}</span>
                                        </button>
                                    @else
                                        <button type="button"
                                                class="btn btn-outline btn-error btn-xs gap-1 shrink-0"
                                                x-on:click="$dispatch('open-modal', 'confirm-discard-version-{{ $document->id }}')"
                                                title="{{ __('Discard Changes') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span class="hidden sm:inline">{{ __('Buang Perubahan') }}</span>
                                            <span class="sm:hidden">{{ __('Buang') }}</span>
                                        </button>
                                    @endif
                                @endcan

                                {{-- Selesai Edit --}}
                                <button type="button"
                                        id="btn-selesai-edit"
                                        onclick="finishEditingDocument()"
                                        class="btn btn-primary btn-xs gap-1 font-medium shadow-xs shrink-0"
                                        title="{{ __('Selesai Edit') }}">
                                    <svg id="icon-selesai-edit" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span id="spinner-selesai-edit" class="loading loading-spinner loading-xs hidden"></span>
                                    <span id="text-selesai-edit">{{ __('Selesai Edit') }}</span>
                                </button>

                                {{-- Quick Actions: Sisip QR Code --}}
                                <button type="button"
                                        onclick="insertQrCodeToEditor()"
                                        class="btn btn-xs btn-outline btn-primary gap-1 font-medium shrink-0"
                                        title="{{ __('Sisipkan QR Code Verifikasi Dokumen') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                    <span class="hidden sm:inline">{{ __('Sisip QR') }}</span>
                                    <span class="sm:hidden">{{ __('QR') }}</span>
                                </button>

                                {{-- Quick Actions: Sisip TTD --}}
                                <button type="button"
                                        onclick="openSignatureSelectorModal()"
                                        class="btn btn-xs btn-outline btn-secondary gap-1 font-medium shrink-0"
                                        title="{{ __('Sisipkan Tanda Tangan Digital') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                    </svg>
                                    <span class="hidden sm:inline">{{ __('Sisip TTD') }}</span>
                                    <span class="sm:hidden">{{ __('TTD') }}</span>
                                </button>

                                @if($isPdf)
                                    {{-- PDF Revert Last Signature Button --}}
                                    <button type="button"
                                            onclick="confirmRevertPdfSignature()"
                                            class="btn btn-xs btn-outline btn-error gap-1 font-medium shrink-0"
                                            title="{{ __('Batalkan TTD Terakhir') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        <span class="hidden sm:inline">{{ __('Batalkan TTD') }}</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- ONLYOFFICE Editor Viewport (Fluid & Responsive on Mobile/Tablet/Desktop) --}}
                        <div class="w-full border border-base-300 rounded-lg overflow-hidden shadow-xs bg-base-100 h-[calc(100vh-13.5rem)] min-h-[520px] sm:min-h-[600px]">
                            <div id="onlyoffice-editor-container" class="w-full h-full"></div>
                            
                            <div id="onlyoffice-fallback" class="hidden flex flex-col items-center justify-center p-6 bg-base-100/95 text-center h-full">
                                <div class="max-w-md p-6 bg-base-200 rounded-2xl border border-base-300 shadow-xl">
                                    <svg class="w-12 h-12 text-warning mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <h3 class="font-bold text-lg mb-2">{{ __('ONLYOFFICE Server Belum Aktif') }}</h3>
                                    <p class="text-sm text-base-content/70 mb-4">
                                        {{ __('Tidak dapat terhubung ke server ONLYOFFICE di') }} <code class="bg-base-300 px-1 py-0.5 rounded">{{ config('onlyoffice.url') }}</code>.
                                        <br>{{ __('Pastikan container Docker ONLYOFFICE sedang berjalan dengan:') }}
                                    </p>
                                    <pre class="bg-neutral text-neutral-content p-3 rounded-lg text-xs text-left mb-4 overflow-x-auto"><code>docker compose up -d</code></pre>
                                    <div class="flex justify-center gap-2">
                                        <button onclick="window.location.reload()" class="btn btn-primary btn-sm">{{ __('Muat Ulang Halaman') }}</button>
                                        <a href="{{ route('documents.download', [$document, 'version_id' => $version->id]) }}" class="btn btn-outline btn-sm">{{ __('Download DOCX') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Signature User Selector Modal --}}
    <dialog id="signature-users-modal" class="modal modal-bottom sm:modal-middle backdrop-blur-xs">
        <div class="modal-box p-6 rounded-3xl border border-base-300/80 shadow-2xl bg-base-100 max-w-xl w-full">
            {{-- Header --}}
            <div class="flex items-start justify-between border-b border-base-200 pb-4 mb-4">
                <div>
                    <h3 class="font-bold text-lg text-base-content leading-tight">{{ __('PILIH TANDA TANGAN / STEMPEL') }}</h3>
                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Pilih tanda tangan Anda untuk disisipkan, atau minta tanda tangan / stempel dari pengguna lain.') }}</p>
                </div>
            </div>

            {{-- Signature Search Input --}}
            <div class="flex gap-2 mb-3">
                <div class="relative flex-1">
                    <input type="text" id="signature-search-input" oninput="filterSignatureUsers(this.value)" onkeypress="if(event.key === 'Enter') filterSignatureUsers(this.value)" placeholder="{{ __('Ketik nama pengguna lain lalu klik Cari...') }}" class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary text-xs sm:text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button type="button" id="signature-search-clear" onclick="clearSignatureSearch()" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content text-xs p-1">✕</button>
                </div>
                <button type="button" onclick="filterSignatureUsers(document.getElementById('signature-search-input').value)" class="btn btn-primary btn-sm px-4 rounded-xl gap-1.5 font-bold uppercase">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    {{ __('Cari') }}
                </button>
            </div>

            {{-- User List --}}
            <div id="signature-users-list" class="space-y-2.5 max-h-72 overflow-y-auto overflow-x-hidden pr-1 min-w-0 max-w-full">
                <div class="flex justify-center py-6 text-sm text-base-content/60">
                    <span class="loading loading-spinner loading-sm mr-2"></span> {{ __('MEMUAT PENGGUNA...') }}
                </div>
            </div>

            {{-- Footer Action --}}
            <div class="modal-action border-t border-base-200 pt-3 mt-4 flex justify-end">
                <form method="dialog">
                    <button class="btn btn-ghost btn-sm rounded-xl">{{ __('TUTUP') }}</button>
                </form>
            </div>
        </div>
    </dialog>

    {{-- Signature Alert Modal --}}
    <dialog id="signature-alert-modal" class="modal modal-bottom sm:modal-middle backdrop-blur-xs">
        <div class="modal-box p-6 rounded-3xl border border-base-300/80 shadow-2xl bg-base-100 max-w-md w-full text-center">
            <div id="signature-alert-icon" class="mx-auto mb-3 flex items-center justify-center h-12 w-12 rounded-full bg-success/15 text-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <h3 id="signature-alert-title" class="font-bold text-lg text-base-content uppercase leading-tight mb-2">{{ __('PEMBERITAHUAN TANDA TANGAN') }}</h3>
            <p id="signature-alert-message" class="text-xs sm:text-sm text-base-content/70 leading-relaxed break-words mb-4"></p>
            <div class="modal-action border-t border-base-200 pt-3 flex justify-center">
                <form method="dialog">
                    <button class="btn btn-primary btn-sm px-6 rounded-xl font-bold uppercase">{{ __('MENGERTI') }}</button>
                </form>
            </div>
        </div>
    </dialog>

    {{-- Floating Live Toast Notification inside Editor --}}
    <div id="signature-live-toast" class="fixed top-6 right-6 z-50 transform transition-all duration-300 translate-y-[-150%] opacity-0 pointer-events-none max-w-sm w-full">
        <div id="signature-live-toast-box" class="flex items-start gap-3 p-4 rounded-2xl bg-base-100/95 backdrop-blur-md border border-success/30 shadow-2xl shadow-success/10 text-base-content pointer-events-auto">
            <div id="signature-live-toast-icon" class="h-8 w-8 rounded-full bg-success/20 text-success flex items-center justify-center shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <div class="flex-1 min-w-0 pr-1">
                <h4 id="signature-live-toast-title" class="font-bold text-xs uppercase text-success tracking-wide">PERMINTAAN DIKIRIM</h4>
                <p id="signature-live-toast-message" class="text-xs text-base-content/80 mt-0.5 leading-relaxed break-words"></p>
            </div>
            <button type="button" onclick="hideSignatureLiveToast()" class="text-base-content/40 hover:text-base-content text-xs p-1">✕</button>
        </div>
    </div>

    @if($isPdf)
        {{-- Interactive Visual PDF Signature Placement Modal --}}
        <dialog id="pdf-visual-signature-modal" class="modal backdrop-blur-sm">
            <div class="modal-box p-0 rounded-2xl border border-base-300 shadow-2xl bg-base-100 max-w-5xl w-11/12 h-[92vh] max-h-[920px] flex flex-col overflow-hidden">
                {{-- Modal Header & Controls --}}
                <div class="px-5 py-3 border-b border-base-200 bg-base-100 rounded-t-2xl flex items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold shadow-xs shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 id="pdf-visual-modal-title" class="font-bold text-sm sm:text-base text-base-content leading-tight truncate">Atur Posisi &amp; Ukuran Tanda Tangan</h3>
                            <p id="pdf-visual-modal-subtitle" class="text-xs text-base-content/60 truncate hidden sm:block">Geser kotak TTD dan tarik sudut kanan bawah untuk mengatur ukuran.</p>
                        </div>
                    </div>

                    {{-- Page Navigation & Metrics --}}
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="join border border-base-300 rounded-lg overflow-hidden shadow-xs">
                            <button type="button" id="pdf-visual-prev-page" onclick="changeVisualPdfPage(-1)" class="btn btn-xs join-item btn-ghost font-bold px-2">◀</button>
                            <span class="btn btn-xs join-item btn-ghost no-animation text-xs font-semibold px-2.5 pointer-events-none">
                                <span id="pdf-visual-current-page-num">1</span> / <span id="pdf-visual-total-page-num">1</span>
                            </span>
                            <button type="button" id="pdf-visual-next-page" onclick="changeVisualPdfPage(1)" class="btn btn-xs join-item btn-ghost font-bold px-2">▶</button>
                        </div>

                        <div class="badge badge-neutral badge-sm font-mono text-[11px] gap-1.5 py-2.5 px-3 rounded-lg shadow-xs hidden md:inline-flex" id="pdf-visual-coord-badge">
                            X: <span id="pdf-coord-x">0</span>mm | Y: <span id="pdf-coord-y">0</span>mm | <span id="pdf-coord-w">40</span>×<span id="pdf-coord-h">25</span>mm
                        </div>

                        <button type="button" onclick="closePdfVisualPlacementModal()" class="btn btn-ghost btn-sm btn-circle text-base-content/50 hover:text-base-content ml-1">
                            ✕
                        </button>
                    </div>
                </div>

                {{-- PDF Canvas Workspace Viewport --}}
                <div id="pdf-workspace-viewport" class="flex-1 overflow-auto bg-base-300/60 p-4 sm:p-6 flex items-start justify-center relative min-h-[350px]">
                    <div id="pdf-visual-loading" class="absolute inset-0 flex flex-col items-center justify-center bg-base-100/80 z-20">
                        <span class="loading loading-spinner loading-lg text-primary mb-2"></span>
                        <p class="text-xs font-semibold text-base-content/70 uppercase tracking-wider">{{ __('Memuat Dokumen PDF...') }}</p>
                    </div>

                    <div id="pdf-page-wrapper" class="relative shadow-2xl rounded-lg overflow-hidden bg-white border border-base-content/10 select-none my-auto transition-all">
                        <canvas id="pdf-render-canvas" class="block"></canvas>
                        
                        {{-- Interactive Signature Overlay Layer --}}
                        <div id="pdf-interactive-overlay" class="absolute inset-0 z-10 pointer-events-auto">
                            <div id="pdf-signature-drag-box"
                                 class="absolute border-2 border-primary/90 bg-primary/5 cursor-move shadow-lg select-none touch-none transition-shadow group hover:shadow-2xl hover:border-primary p-0 m-0"
                                 style="left: 40px; top: 40px; width: 140px; height: 85px;">
                                
                                {{-- Floating Header Tag positioned outside so it doesn't compress or offset image --}}
                                <div class="absolute -top-6 left-0 flex items-center gap-1.5 pointer-events-none z-20 whitespace-nowrap">
                                    <span id="pdf-box-signer-tag" class="badge badge-primary badge-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs px-2 py-0.5 text-[10px]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                        </svg>
                                        <span id="pdf-box-signer-tag-text">{{ __('Geser TTD') }}</span>
                                    </span>
                                    <span class="badge badge-neutral badge-xs font-mono font-bold text-[10px] shadow-xs px-2 py-0.5 opacity-90" id="pdf-box-dim-preview">40×25mm</span>
                                </div>

                                {{-- Exact Image Surface (Fills the entire boundary flush to borders with zero padding) --}}
                                <div id="pdf-box-preview-container" class="w-full h-full flex items-center justify-center pointer-events-none overflow-hidden p-0 m-0">
                                    @if($userSignatureClientUrl || $userSignatureDataUri)
                                        <img id="pdf-box-preview-img" src="{{ $userSignatureClientUrl ?: $userSignatureDataUri }}" alt="Signature" class="w-full h-full object-fill block m-0 p-0 pointer-events-none" />
                                    @else
                                        <span id="pdf-box-preview-text" class="text-xs font-bold text-primary/80 uppercase italic tracking-wide">[ {{ __('Tanda Tangan') }} ]</span>
                                    @endif
                                </div>

                                {{-- Bottom Resizer Handle (Bottom-Right corner) --}}
                                <div id="pdf-sig-resize-handle"
                                     class="absolute -right-3 -bottom-3 w-6 h-6 bg-primary text-primary-content rounded-full flex items-center justify-center cursor-nwse-resize shadow-lg hover:scale-110 active:scale-95 transition-transform z-30 ring-2 ring-white"
                                     title="{{ __('Tarik untuk mengubah ukuran') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer Actions --}}
                <div class="px-6 py-4 border-t border-base-200 bg-base-100 flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <div class="text-xs text-base-content/60 hidden sm:block">
                        💡 <span class="font-medium">{{ __('Tips:') }}</span> {{ __('Posisikan kotak di atas garis tanda tangan pada dokumen.') }}
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" onclick="closePdfVisualPlacementModal()" class="btn btn-ghost btn-sm rounded-xl">{{ __('Batal') }}</button>
                        <button type="button" id="pdf-visual-action-btn" onclick="submitActiveVisualAction()" class="btn btn-primary btn-sm gap-1.5 rounded-xl font-bold shadow-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span id="pdf-visual-action-btn-text">{{ __('Bubuhkan TTD Saya Di Sini') }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </dialog>
    @endif

    {{-- On-Screen Custom Notification Modal for Signature Approval & Alerts --}}
    <dialog id="signature-alert-modal" class="modal">
        <div class="modal-box max-w-sm text-center">
            <div id="signature-alert-icon" class="mx-auto mb-3 flex items-center justify-center h-12 w-12 rounded-full bg-success/15 text-success">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 id="signature-alert-title" class="font-bold text-base text-base-content mb-1 uppercase">PEMBERITAHUAN TANDA TANGAN</h3>
            <p id="signature-alert-message" class="text-xs text-base-content/70 mb-4 uppercase leading-relaxed"></p>
            <div class="modal-action justify-center">
                <button type="button" id="signature-alert-action-btn" class="btn btn-primary btn-sm px-6 uppercase" onclick="document.getElementById('signature-alert-modal').close()">
                    {{ __('OK') }}
                </button>
            </div>
        </div>
    </dialog>

    @push('scripts')
        @if($isPdf)
            <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
            <script>
                if (typeof pdfjsLib !== 'undefined') {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                }
            </script>
        @endif
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js?v=9.4.0-f4-v2"
                onerror="document.getElementById('onlyoffice-fallback').classList.remove('hidden');"></script>
        <script>
            const qrCodeUrl = @json($qrCodeUrl ?? null);
            const qrCodeToken = @json($qrCodeToken ?? null);
            const mySignatureUrl = @json($userSignatureUrl ?? null);
            const mySignatureToken = @json($userSignatureToken ?? null);
            const mySignatureClientUrl = @json($userSignatureClientUrl ?? ($userSignatureDataUri ?? null));
            const isPdfDocument = @json($isPdf);
            const pdfFileSourceUrl = @json(($isPdf && $version) ? route('documents.file', [$document, $version]) : null);

            let pdfDocInstance = null;
            let currentVisualPdfPage = 1;
            let totalVisualPdfPages = 1;
            let pdfPageScale = 1.0;
            let pdfViewport = null;
            let visualPlacementCoords = {
                page: 1,
                xMm: 20,
                yMm: 20,
                wMm: 40,
                hMm: 25,
                isCustom: false
            };

            const mainScrollContainer = document.querySelector('main') || document.documentElement;

            function preserveParentScroll(fn) {
                const currentScrollTop = mainScrollContainer ? mainScrollContainer.scrollTop : 0;
                let result = null;
                if (typeof fn === 'function') {
                    result = fn();
                }
                requestAnimationFrame(() => {
                    if (mainScrollContainer && mainScrollContainer.scrollTop !== currentScrollTop) {
                        mainScrollContainer.scrollTop = currentScrollTop;
                    }
                });
                setTimeout(() => {
                    if (mainScrollContainer && mainScrollContainer.scrollTop !== currentScrollTop) {
                        mainScrollContainer.scrollTop = currentScrollTop;
                    }
                }, 50);
                setTimeout(() => {
                    if (mainScrollContainer && mainScrollContainer.scrollTop !== currentScrollTop) {
                        mainScrollContainer.scrollTop = currentScrollTop;
                    }
                }, 150);
                return result;
            }

            function initOnlyOfficeEditor() {
                const container = document.getElementById("onlyoffice-editor-container");
                if (!container) return;

                if (typeof DocsAPI === 'undefined') {
                    console.error("DocsAPI is not defined. ONLYOFFICE script might have failed to load.");
                    document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                    return;
                }

                try {
                    const config = @json($onlyOfficeConfig);
                    if (!config) {
                        console.error("ONLYOFFICE config is empty or invalid.");
                        return;
                    }
                    const isMobileOrTablet = window.innerWidth < 1024;

                    // Always use desktop type to bypass ONLYOFFICE Community Edition mobile license restriction
                    config.type = 'desktop';
                    config.editorConfig = config.editorConfig || {};
                    config.editorConfig.mode = 'edit';
                    config.editorConfig.customization = config.editorConfig.customization || {};
                    config.editorConfig.compactToolbar = true;
                    config.editorConfig.customization.compactHeader = true;
                    config.editorConfig.customization.autoFocus = false;
                    config.editorConfig.customization.mobile = { force: false };

                    if (isMobileOrTablet) {
                        // Responsive mode for small/shrinking viewports:
                        config.editorConfig.customization.compactToolbar = true;
                        config.editorConfig.customization.leftMenu = false;
                        config.editorConfig.customization.rightMenu = false;
                        config.editorConfig.customization.ruler = false;
                        config.editorConfig.customization.toolbarHideFileName = true;
                        config.editorConfig.customization.zoom = -2;
                    } else {
                        // Desktop screen - preserve standard layout intact
                        config.editorConfig.customization.compactToolbar = false;
                        config.editorConfig.customization.leftMenu = true;
                        config.editorConfig.customization.rightMenu = true;
                        config.editorConfig.customization.ruler = true;
                        config.editorConfig.customization.toolbarHideFileName = false;
                        config.editorConfig.customization.zoom = 100;
                    }
                    
                    config.events = config.events || {};
                    config.events.onAppReady = function() {
                        console.log('ONLYOFFICE editor ready');
                        if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                    };
                    config.events.onDocumentReady = function() {
                        if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                        setTimeout(() => {
                            if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                        }, 50);
                    };
                    window._hasSessionChanges = false;
                    config.events.onDocumentStateChange = function(event) {
                        const isModified = event.data;
                        
                        if (isModified) {
                            window._hasSessionChanges = true;
                        }

                        // Keep navigation dirty if ANY modification was made during this session,
                        // even if ONLYOFFICE internally fires isModified=false after forcesave.
                        if (typeof window.setNavigationDirty === 'function') {
                            window.setNavigationDirty(window._hasSessionChanges || isModified);
                        }
                        
                        const btnSelesai = document.getElementById('btn-selesai-edit');
                        const textSelesai = document.getElementById('text-selesai-edit');
                        
                        if (btnSelesai && textSelesai) {
                            if (isModified) {
                                btnSelesai.disabled = true;
                                btnSelesai.classList.remove('btn-primary');
                                btnSelesai.classList.add('btn-disabled');
                                textSelesai.textContent = "{{ __('Belum Disimpan') }}";
                            } else {
                                btnSelesai.disabled = false;
                                btnSelesai.classList.remove('btn-disabled');
                                btnSelesai.classList.add('btn-primary');
                                textSelesai.textContent = "{{ __('Selesai Edit') }}";
                            }
                        }
                    };
                    config.events.onError = function(event) {
                        console.error('ONLYOFFICE error event:', event);
                    };

                    window.docEditor = new DocsAPI.DocEditor("onlyoffice-editor-container", config);
                } catch (e) {
                    console.error('ONLYOFFICE initialization error:', e);
                    document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initOnlyOfficeEditor);
            } else {
                initOnlyOfficeEditor();
            }



            let signatureToastTimer = null;
            function hideSignatureLiveToast() {
                const toast = document.getElementById('signature-live-toast');
                if (toast) {
                    toast.classList.add('translate-y-[-150%]', 'opacity-0');
                    toast.classList.remove('translate-y-0', 'opacity-100');
                }
                if (signatureToastTimer) {
                    clearTimeout(signatureToastTimer);
                    signatureToastTimer = null;
                }
            }

            function showSignatureScreenAlert(title, message, isSuccess = true) {
                const toast = document.getElementById('signature-live-toast');
                const toastTitle = document.getElementById('signature-live-toast-title');
                const toastMsg = document.getElementById('signature-live-toast-message');
                const toastIcon = document.getElementById('signature-live-toast-icon');
                const toastBox = document.getElementById('signature-live-toast-box');

                // Show floating Toast
                if (toast && toastTitle && toastMsg) {
                    toastTitle.textContent = (title || 'PEMBERITAHUAN').toUpperCase();
                    toastTitle.className = 'font-bold text-xs uppercase tracking-wide ' + (isSuccess ? 'text-success' : 'text-error');
                    toastMsg.textContent = message || '';

                    if (toastBox) {
                        toastBox.className = 'flex items-start gap-3 p-4 rounded-2xl bg-base-100/95 backdrop-blur-md border shadow-2xl text-base-content pointer-events-auto ' + 
                            (isSuccess ? 'border-success/30 shadow-success/10' : 'border-error/30 shadow-error/10');
                    }
                    if (toastIcon) {
                        toastIcon.className = 'h-8 w-8 rounded-full flex items-center justify-center shrink-0 mt-0.5 ' + 
                            (isSuccess ? 'bg-success/20 text-success' : 'bg-error/20 text-error');
                        toastIcon.innerHTML = isSuccess 
                            ? `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`
                            : `<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`;
                    }

                    toast.classList.remove('translate-y-[-150%]', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');

                    if (signatureToastTimer) clearTimeout(signatureToastTimer);
                    signatureToastTimer = setTimeout(() => {
                        hideSignatureLiveToast();
                    }, 5000);
                } else {
                    const modal = document.getElementById('signature-alert-modal');
                    const titleEl = document.getElementById('signature-alert-title');
                    const messageEl = document.getElementById('signature-alert-message');
                    if (titleEl) titleEl.textContent = (title || 'PEMBERITAHUAN').toUpperCase();
                    if (messageEl) messageEl.textContent = message || '';
                    if (modal) modal.showModal();
                }
            }

            /**
             * Insert image directly into ONLYOFFICE document editor via DocsAPI insertImage or Document Builder Connector
             */
            function insertImageIntoOnlyOffice(imageUrl, widthMm = 18, heightMm = 18, token = null) {
                if (!window.docEditor) {
                    showSignatureScreenAlert('PERINGATAN', 'EDITOR BELUM SELESAI DIMUAT. TUNGGU SEBENTAR...', false);
                    return;
                }

                if (!imageUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'URL GAMBAR TIDAK VALID.', false);
                    return;
                }

                preserveParentScroll(() => {
                    try {
                        const payload = {
                            fileType: "png",
                            url: imageUrl,
                            width: Math.round(widthMm * 3.78),
                            height: Math.round(heightMm * 3.78)
                        };

                        if (token) {
                            payload.token = token;
                        }

                        // Method 1: Use native DocsAPI insertImage (most compatible in all ONLYOFFICE editions)
                        if (typeof window.docEditor.insertImage === 'function') {
                            window.docEditor.insertImage(payload);
                            console.log("Image inserted successfully via docEditor.insertImage");
                            return;
                        }

                        // Method 2: Fallback to Document Builder Connector
                        if (typeof window.docEditor.createConnector === 'function') {
                            const connector = window.docEditor.createConnector();
                            const script = `
                                var oDocument = Api.GetDocument();
                                var oParagraph = Api.CreateParagraph();
                                var oImage = Api.CreateImage("${imageUrl}", ${widthMm} * 36000, ${heightMm} * 36000);
                                oParagraph.AddElement(oImage, 0);
                                try {
                                    oDocument.InsertContent([oParagraph]);
                                } catch(e) {
                                    oDocument.Push(oParagraph);
                                }
                            `;
                            connector.callCommand(new Function(script), function() {
                                console.log("Image inserted successfully via connector.");
                            });
                        }
                    } catch (err) {
                        console.warn('insertImage error:', err);
                    }
                });
            }

            function insertQrCodeToEditor() {
                if (isPdfDocument) {
                    openPdfVisualPlacementModal(null, null, 'qrcode');
                    return;
                }

                if (!qrCodeUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'QR CODE DOKUMEN TIDAK TERSEDIA.', false);
                    return;
                }
                insertImageIntoOnlyOffice(qrCodeUrl, 18, 18, qrCodeToken);
            }

            // --- PDF Visual Interactive Drag & Drop / Resizing Placement Tool ---
            let activeVisualType = 'signature'; // 'signature' | 'qrcode'
            let activeVisualTargetUserId = null;
            let activeVisualTargetUserName = null;
            let activeVisualSignatureId = null;
            let activeVisualSignatureType = 'original'; // 'original' | 'company_stamp'
            let activeVisualCompanyName = null;

            function openPdfVisualPlacementModal(targetUserId = null, targetUserName = null, visualType = 'signature', signatureId = null, signatureType = 'original', companyName = null) {
                const selectorModal = document.getElementById('signature-users-modal');
                if (selectorModal && selectorModal.open) {
                    selectorModal.close();
                }

                activeVisualType = visualType;
                activeVisualTargetUserId = targetUserId;
                activeVisualTargetUserName = targetUserName;
                activeVisualSignatureId = signatureId;
                activeVisualSignatureType = signatureType;
                activeVisualCompanyName = companyName;

                const titleEl = document.getElementById('pdf-visual-modal-title');
                const subTitleEl = document.getElementById('pdf-visual-modal-subtitle');
                const tagBadgeEl = document.getElementById('pdf-box-signer-tag');
                const tagTextEl = document.getElementById('pdf-box-signer-tag-text');
                const previewContainer = document.getElementById('pdf-box-preview-container');
                const actionBtn = document.getElementById('pdf-visual-action-btn');
                const actionBtnText = document.getElementById('pdf-visual-action-btn-text');
                const dragBox = document.getElementById('pdf-signature-drag-box');
                const resizeHandle = document.getElementById('pdf-sig-resize-handle');

                const isStamp = (signatureType === 'company_stamp');
                const isQr = (visualType === 'qrcode');

                // Reset position flag to let renderVisualPdfPage calculate optimal dimensions (e.g. square for stamp/QR vs rectangular for signature)
                if (dragBox) {
                    dragBox.dataset.positioned = "";
                }

                // Dynamic styling based on type (Primary for TTD, Secondary for Stempel, Accent for QR Code)
                if (dragBox) {
                    dragBox.className = isStamp
                        ? "absolute border-2 border-secondary/90 bg-secondary/5 cursor-move shadow-lg select-none touch-none transition-shadow group hover:shadow-2xl hover:border-secondary p-0 m-0 rounded-lg"
                        : (isQr
                            ? "absolute border-2 border-accent/90 bg-accent/5 cursor-move shadow-lg select-none touch-none transition-shadow group hover:shadow-2xl hover:border-accent p-0 m-0 rounded-lg"
                            : "absolute border-2 border-primary/90 bg-primary/5 cursor-move shadow-lg select-none touch-none transition-shadow group hover:shadow-2xl hover:border-primary p-0 m-0 rounded-lg");
                }
                if (tagBadgeEl) {
                    tagBadgeEl.className = isStamp
                        ? "badge badge-secondary badge-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs px-2 py-0.5 text-[10px]"
                        : (isQr
                            ? "badge badge-accent badge-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs px-2 py-0.5 text-[10px]"
                            : "badge badge-primary badge-xs font-bold uppercase tracking-wider flex items-center gap-1 shadow-xs px-2 py-0.5 text-[10px]");
                }
                if (resizeHandle) {
                    resizeHandle.className = isStamp
                        ? "absolute -right-3 -bottom-3 w-6 h-6 bg-secondary text-secondary-content rounded-full flex items-center justify-center cursor-nwse-resize shadow-lg hover:scale-110 active:scale-95 transition-transform z-30 ring-2 ring-white"
                        : (isQr
                            ? "absolute -right-3 -bottom-3 w-6 h-6 bg-accent text-accent-content rounded-full flex items-center justify-center cursor-nwse-resize shadow-lg hover:scale-110 active:scale-95 transition-transform z-30 ring-2 ring-white"
                            : "absolute -right-3 -bottom-3 w-6 h-6 bg-primary text-primary-content rounded-full flex items-center justify-center cursor-nwse-resize shadow-lg hover:scale-110 active:scale-95 transition-transform z-30 ring-2 ring-white");
                }
                if (actionBtn) {
                    actionBtn.className = isStamp
                        ? "btn btn-secondary btn-sm gap-1.5 rounded-xl font-bold shadow-xs"
                        : (isQr
                            ? "btn btn-accent btn-sm gap-1.5 rounded-xl font-bold shadow-xs"
                            : "btn btn-primary btn-sm gap-1.5 rounded-xl font-bold shadow-xs");
                }

                // Resolve preview image URL
                let resolvedPreviewUrl = null;
                if (isQr) {
                    resolvedPreviewUrl = @json($qrCodeDataUri ?? null) || qrCodeUrl;
                } else if (targetUserId) {
                    const userObj = (allSignatureUsersData || []).find(u => u.id == targetUserId);
                    if (userObj) {
                        if (signatureId) {
                            const sigObj = (userObj.signatures || []).find(s => s.id == signatureId);
                            if (sigObj) resolvedPreviewUrl = sigObj.data_uri || sigObj.preview_url;
                        } else if (userObj.signatures && userObj.signatures.length > 0) {
                            resolvedPreviewUrl = userObj.signatures[0].data_uri || userObj.signatures[0].preview_url;
                        }
                    }
                } else {
                    if (signatureId) {
                        const meObj = (allSignatureUsersData || []).find(u => u.is_me || u.id == {{ auth()->id() }});
                        if (meObj) {
                            const sigObj = (meObj.signatures || []).find(s => s.id == signatureId);
                            if (sigObj) resolvedPreviewUrl = sigObj.data_uri || sigObj.preview_url;
                        }
                    }
                    if (!resolvedPreviewUrl) {
                        resolvedPreviewUrl = mySignatureClientUrl || @json($userSignatureDataUri ?? null);
                    }
                }

                // Titles, Subtitles, Tags, and Action Buttons
                if (isQr) {
                    if (titleEl) titleEl.textContent = 'Atur Posisi & Ukuran QR Code';
                    if (subTitleEl) subTitleEl.textContent = 'Geser kotak QR Code ke posisi yang diinginkan dan tarik sudut kanan bawah untuk mengatur ukuran.';
                    if (tagTextEl) tagTextEl.textContent = 'QR Code';
                    if (actionBtnText) actionBtnText.textContent = '{{ __("Bubuhkan QR Code Di Sini") }}';
                } else if (targetUserId && targetUserName) {
                    if (isStamp) {
                        const compDisplay = companyName ? companyName.toUpperCase() : 'PERUSAHAAN';
                        if (titleEl) titleEl.textContent = 'Atur Posisi & Ukuran Stempel: ' + compDisplay;
                        if (subTitleEl) subTitleEl.textContent = 'Posisikan dan atur ukuran kotak stempel perusahaan (' + compDisplay + ') untuk ' + targetUserName.toUpperCase() + ' pada dokumen.';
                        if (tagTextEl) tagTextEl.textContent = 'STEMPEL: ' + compDisplay;
                        if (actionBtnText) actionBtnText.textContent = '{{ __("Kirim Permintaan Stempel") }}';
                    } else {
                        if (titleEl) titleEl.textContent = 'Atur Posisi & Ukuran TTD: ' + targetUserName.toUpperCase();
                        if (subTitleEl) subTitleEl.textContent = 'Posisikan dan atur ukuran kotak tanda tangan untuk ' + targetUserName.toUpperCase() + ' pada halaman dokumen.';
                        if (tagTextEl) tagTextEl.textContent = 'TTD: ' + targetUserName.toUpperCase();
                        if (actionBtnText) actionBtnText.textContent = '{{ __("Kirim Permintaan Tanda Tangan") }}';
                    }
                } else {
                    if (isStamp) {
                        const compDisplay = companyName ? companyName.toUpperCase() : 'PERUSAHAAN';
                        if (titleEl) titleEl.textContent = 'Atur Posisi & Ukuran Stempel: ' + compDisplay;
                        if (subTitleEl) subTitleEl.textContent = 'Geser kotak stempel ke posisi yang diinginkan dan tarik sudut kanan bawah untuk mengubah ukuran.';
                        if (tagTextEl) tagTextEl.textContent = 'STEMPEL: ' + compDisplay;
                        if (actionBtnText) actionBtnText.textContent = '{{ __("Bubuhkan Stempel Di Sini") }}';
                    } else {
                        if (titleEl) titleEl.textContent = signatureId ? 'Atur Posisi & Ukuran TTD' : 'Atur Posisi & Ukuran Tanda Tangan Saya';
                        if (subTitleEl) subTitleEl.textContent = 'Geser kotak TTD ke posisi yang diinginkan dan tarik sudut kanan bawah untuk mengubah ukuran.';
                        if (tagTextEl) tagTextEl.textContent = 'TTD SAYA';
                        if (actionBtnText) actionBtnText.textContent = signatureId ? '{{ __("Bubuhkan TTD Di Sini") }}' : '{{ __("Bubuhkan TTD Saya Di Sini") }}';
                    }
                }

                // Render image preview in previewContainer
                if (previewContainer) {
                    if (resolvedPreviewUrl) {
                        previewContainer.innerHTML = `<img id="pdf-box-preview-img" src="${resolvedPreviewUrl}" alt="Preview" class="w-full h-full object-fill block m-0 p-0 pointer-events-none select-none transition-none" draggable="false" />`;
                    } else {
                        // Show animated loading indicator while fetching preview in background
                        previewContainer.innerHTML = `
                            <div class="w-full h-full flex flex-col items-center justify-center p-2 text-center bg-base-200/40 border border-dashed ${isStamp ? 'border-secondary/40 text-secondary' : 'border-primary/40 text-primary'} rounded">
                                <span class="loading loading-spinner loading-xs mb-1"></span>
                                <span class="text-[9px] font-bold uppercase tracking-wider">${isStamp ? 'Memuat Stempel...' : 'Memuat TTD...'}</span>
                            </div>
                        `;

                        const fetchUrl = targetUserId
                            ? '{{ route("signatures.users") }}?document_id={{ $document->id }}'
                            : (signatureId ? `/profile/signature?signature_id=${signatureId}` : null);

                        if (fetchUrl) {
                            fetch(fetchUrl)
                                .then(res => res.json())
                                .then(data => {
                                    let fetchedSrc = null;
                                    if (data.users) {
                                        allSignatureUsersData = data.users || [];
                                        const uObj = allSignatureUsersData.find(u => u.id == targetUserId);
                                        if (uObj) {
                                            const sObj = signatureId ? (uObj.signatures || []).find(s => s.id == signatureId) : (uObj.signatures || [])[0];
                                            if (sObj) fetchedSrc = sObj.data_uri || sObj.preview_url;
                                        }
                                    } else if (data.client_url || data.data_uri || data.url) {
                                        fetchedSrc = data.client_url || data.data_uri || data.url;
                                    }

                                    if (fetchedSrc && previewContainer && (activeVisualSignatureId == signatureId || !activeVisualSignatureId)) {
                                        previewContainer.innerHTML = `<img id="pdf-box-preview-img" src="${fetchedSrc}" alt="Preview" class="w-full h-full object-fill block m-0 p-0 pointer-events-none select-none transition-none" draggable="false" />`;
                                    } else if (previewContainer && (activeVisualSignatureId == signatureId || !activeVisualSignatureId)) {
                                        previewContainer.innerHTML = `<span class="text-xs font-bold ${isStamp ? 'text-secondary/80' : 'text-primary/80'} uppercase italic tracking-wide">[ ${isStamp ? '{{ __("Stempel") }}' : '{{ __("Tanda Tangan") }}'} ]</span>`;
                                    }
                                })
                                .catch(() => {
                                    if (previewContainer && (activeVisualSignatureId == signatureId || !activeVisualSignatureId)) {
                                        previewContainer.innerHTML = `<span class="text-xs font-bold ${isStamp ? 'text-secondary/80' : 'text-primary/80'} uppercase italic tracking-wide">[ ${isStamp ? '{{ __("Stempel") }}' : '{{ __("Tanda Tangan") }}'} ]</span>`;
                                    }
                                });
                        }
                    }
                }

                const visualModal = document.getElementById('pdf-visual-signature-modal');
                if (!visualModal) return;
                visualModal.showModal();

                if (!pdfDocInstance && pdfFileSourceUrl) {
                    loadVisualPdfDocument(pdfFileSourceUrl);
                } else if (pdfDocInstance) {
                    setTimeout(() => renderVisualPdfPage(currentVisualPdfPage), 50);
                }
            }

            function closePdfVisualPlacementModal() {
                const visualModal = document.getElementById('pdf-visual-signature-modal');
                if (visualModal) visualModal.close();
                activeVisualType = 'signature';
                activeVisualTargetUserId = null;
                activeVisualTargetUserName = null;
                activeVisualSignatureId = null;
                activeVisualSignatureType = 'original';
                activeVisualCompanyName = null;
            }

            function loadVisualPdfDocument(url) {
                const loadingEl = document.getElementById('pdf-visual-loading');
                if (loadingEl) loadingEl.classList.remove('hidden');

                if (typeof pdfjsLib === 'undefined') {
                    alert('Library PDF.js belum selesai dimuat. Silakan muat ulang halaman.');
                    return;
                }

                pdfjsLib.getDocument(url).promise.then(doc => {
                    pdfDocInstance = doc;
                    totalVisualPdfPages = doc.numPages;
                    currentVisualPdfPage = totalVisualPdfPages; // Default to last page
                    
                    const totalEl = document.getElementById('pdf-visual-total-page-num');
                    if (totalEl) totalEl.textContent = totalVisualPdfPages;
                    
                    setTimeout(() => {
                        renderVisualPdfPage(currentVisualPdfPage);
                        initInteractiveBoxDragAndResize();
                    }, 50);
                }).catch(err => {
                    console.error('Error loading PDF in visual tool:', err);
                    if (loadingEl) {
                        loadingEl.innerHTML = '<p class="text-xs text-error font-bold uppercase">{{ __("Gagal memuat pratinjau PDF.") }}</p>';
                    }
                });
            }

            function renderVisualPdfPage(pageNumber) {
                if (!pdfDocInstance) return;
                const loadingEl = document.getElementById('pdf-visual-loading');
                if (loadingEl) loadingEl.classList.remove('hidden');

                currentVisualPdfPage = Math.max(1, Math.min(pageNumber, totalVisualPdfPages));
                const currentEl = document.getElementById('pdf-visual-current-page-num');
                if (currentEl) currentEl.textContent = currentVisualPdfPage;
                visualPlacementCoords.page = currentVisualPdfPage;

                pdfDocInstance.getPage(currentVisualPdfPage).then(page => {
                    const canvas = document.getElementById('pdf-render-canvas');
                    const wrapper = document.getElementById('pdf-page-wrapper');
                    const workspace = document.getElementById('pdf-workspace-viewport');
                    const context = canvas.getContext('2d');

                    const availWidth = workspace ? (workspace.clientWidth - 48) : (window.innerWidth - 60);
                    const unscaledViewport = page.getViewport({ scale: 1.0 });
                    const targetWidth = Math.min(Math.max(280, availWidth), 800);
                    pdfPageScale = targetWidth / unscaledViewport.width;
                    pdfViewport = page.getViewport({ scale: pdfPageScale });

                    canvas.width = pdfViewport.width;
                    canvas.height = pdfViewport.height;
                    wrapper.style.width = pdfViewport.width + 'px';
                    wrapper.style.height = pdfViewport.height + 'px';

                    const renderContext = {
                        canvasContext: context,
                        viewport: pdfViewport
                    };

                    page.render(renderContext).promise.then(() => {
                        if (loadingEl) loadingEl.classList.add('hidden');
                        
                        const dragBox = document.getElementById('pdf-signature-drag-box');
                        if (dragBox && (!dragBox.dataset.positioned || dragBox.dataset.page != currentVisualPdfPage)) {
                            const mmPerPt = 25.4 / 72;
                            const unscaledW = pdfViewport.width / pdfPageScale;
                            const unscaledH = pdfViewport.height / pdfPageScale;
                            const pdfWidthMm = unscaledW * mmPerPt;
                            const pdfHeightMm = unscaledH * mmPerPt;
                            const pxPerMmX = canvas.width / pdfWidthMm;
                            const pxPerMmY = canvas.height / pdfHeightMm;

                            const isStamp = (activeVisualSignatureType === 'company_stamp');
                            const isQr = (activeVisualType === 'qrcode');
                            let defaultWMm = 35;
                            let defaultHMm = 20;
                            if (isQr) {
                                defaultWMm = 18;
                                defaultHMm = 18;
                            } else if (isStamp) {
                                defaultWMm = 25;
                                defaultHMm = 25;
                            }

                            const boxW = Math.round(defaultWMm * pxPerMmX);
                            const boxH = Math.round(defaultHMm * pxPerMmY);
                            const initLeft = pdfViewport.width - boxW - Math.round(15 * pxPerMmX);
                            const initTop = pdfViewport.height - boxH - Math.round(20 * pxPerMmY);

                            dragBox.style.width = boxW + 'px';
                            dragBox.style.height = boxH + 'px';
                            dragBox.style.left = Math.max(15, initLeft) + 'px';
                            dragBox.style.top = Math.max(15, initTop) + 'px';
                            dragBox.dataset.positioned = "true";
                            dragBox.dataset.page = currentVisualPdfPage;
                        }
                        updateCoordinateMetrics();
                    });
                });
            }

            function changeVisualPdfPage(delta) {
                if (!pdfDocInstance) return;
                const next = currentVisualPdfPage + delta;
                if (next >= 1 && next <= totalVisualPdfPages) {
                    renderVisualPdfPage(next);
                }
            }

            function updateCoordinateMetrics() {
                const dragBox = document.getElementById('pdf-signature-drag-box');
                const canvas = document.getElementById('pdf-render-canvas');
                if (!dragBox || !canvas || !pdfViewport) return;

                const boxLeft = parseFloat(dragBox.style.left) || 0;
                const boxTop = parseFloat(dragBox.style.top) || 0;
                const boxWidth = parseFloat(dragBox.style.width) || 120;
                const boxHeight = parseFloat(dragBox.style.height) || 70;

                const mmPerPt = 25.4 / 72;
                const unscaledW = pdfViewport.width / pdfPageScale;
                const unscaledH = pdfViewport.height / pdfPageScale;
                const pdfWidthMm = unscaledW * mmPerPt;
                const pdfHeightMm = unscaledH * mmPerPt;

                const mmPerPxX = pdfWidthMm / canvas.width;
                const mmPerPxY = pdfHeightMm / canvas.height;

                const finalXMm = Math.round(boxLeft * mmPerPxX * 10) / 10;
                const finalYMm = Math.round(boxTop * mmPerPxY * 10) / 10;
                const finalWMm = Math.round(boxWidth * mmPerPxX * 10) / 10;
                const finalHMm = Math.round(boxHeight * mmPerPxY * 10) / 10;

                visualPlacementCoords = {
                    page: currentVisualPdfPage,
                    xMm: finalXMm,
                    yMm: finalYMm,
                    wMm: finalWMm,
                    hMm: finalHMm,
                    isCustom: true
                };

                const elX = document.getElementById('pdf-coord-x');
                const elY = document.getElementById('pdf-coord-y');
                const elW = document.getElementById('pdf-coord-w');
                const elH = document.getElementById('pdf-coord-h');
                if (elX) elX.textContent = finalXMm;
                if (elY) elY.textContent = finalYMm;
                if (elW) elW.textContent = finalWMm;
                if (elH) elH.textContent = finalHMm;
                
                const dimPreview = document.getElementById('pdf-box-dim-preview');
                if (dimPreview) dimPreview.textContent = `${finalWMm}×${finalHMm}mm`;
            }

            function initInteractiveBoxDragAndResize() {
                const dragBox = document.getElementById('pdf-signature-drag-box');
                const resizeHandle = document.getElementById('pdf-sig-resize-handle');
                const overlay = document.getElementById('pdf-interactive-overlay');
                if (!dragBox || !resizeHandle || !overlay) return;

                let isDragging = false;
                let isResizing = false;
                let startX = 0;
                let startY = 0;
                let startLeft = 0;
                let startTop = 0;
                let startWidth = 0;
                let startHeight = 0;

                dragBox.addEventListener('pointerdown', function(e) {
                    if (e.target === resizeHandle || resizeHandle.contains(e.target)) return;
                    isDragging = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startLeft = parseFloat(dragBox.style.left) || 0;
                    startTop = parseFloat(dragBox.style.top) || 0;
                    dragBox.setPointerCapture(e.pointerId);
                    dragBox.classList.add('ring-2', 'ring-primary', 'shadow-2xl');
                    e.preventDefault();
                });

                resizeHandle.addEventListener('pointerdown', function(e) {
                    isResizing = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startWidth = parseFloat(dragBox.style.width) || 120;
                    startHeight = parseFloat(dragBox.style.height) || 70;
                    resizeHandle.setPointerCapture(e.pointerId);
                    e.stopPropagation();
                    e.preventDefault();
                });

                window.addEventListener('pointermove', function(e) {
                    if (isDragging) {
                        const dx = e.clientX - startX;
                        const dy = e.clientY - startY;
                        const overlayW = overlay.clientWidth;
                        const overlayH = overlay.clientHeight;
                        const boxW = dragBox.offsetWidth;
                        const boxH = dragBox.offsetHeight;

                        let newLeft = startLeft + dx;
                        let newTop = startTop + dy;

                        newLeft = Math.max(0, Math.min(newLeft, overlayW - boxW));
                        newTop = Math.max(0, Math.min(newTop, overlayH - boxH));

                        dragBox.style.left = newLeft + 'px';
                        dragBox.style.top = newTop + 'px';
                        updateCoordinateMetrics();
                    } else if (isResizing) {
                        const dx = e.clientX - startX;
                        const dy = e.clientY - startY;
                        const overlayW = overlay.clientWidth;
                        const overlayH = overlay.clientHeight;
                        const boxLeft = parseFloat(dragBox.style.left) || 0;
                        const boxTop = parseFloat(dragBox.style.top) || 0;

                        let newW = Math.max(30, startWidth + dx);
                        let newH = Math.max(20, startHeight + dy);

                        newW = Math.min(newW, overlayW - boxLeft);
                        newH = Math.min(newH, overlayH - boxTop);

                        dragBox.style.width = newW + 'px';
                        dragBox.style.height = newH + 'px';
                        updateCoordinateMetrics();
                    }
                });

                window.addEventListener('pointerup', function(e) {
                    if (isDragging) {
                        isDragging = false;
                        dragBox.classList.remove('ring-2', 'ring-primary', 'shadow-2xl');
                    }
                    if (isResizing) {
                        isResizing = false;
                    }
                });

                let resizeDebounceTimer = null;
                window.addEventListener('resize', function() {
                    const visualModal = document.getElementById('pdf-visual-signature-modal');
                    if (visualModal && visualModal.open && pdfDocInstance) {
                        clearTimeout(resizeDebounceTimer);
                        resizeDebounceTimer = setTimeout(() => {
                            renderVisualPdfPage(currentVisualPdfPage);
                        }, 150);
                    }
                });
            }

            function submitActiveVisualAction() {
                if (!visualPlacementCoords.isCustom) {
                    updateCoordinateMetrics();
                }

                if (activeVisualType === 'qrcode') {
                    stampMyVisualQrCode();
                } else if (activeVisualTargetUserId) {
                    submitVisualSignatureRequest(activeVisualTargetUserId, activeVisualTargetUserName);
                } else {
                    stampMyVisualSignature();
                }
            }

            function stampMyVisualQrCode() {
                const payload = {
                    page_number: visualPlacementCoords.page,
                    pos_x: visualPlacementCoords.xMm,
                    pos_y: visualPlacementCoords.yMm,
                    width: visualPlacementCoords.wMm,
                    height: visualPlacementCoords.hMm,
                    preset_position: 'custom'
                };

                const btn = document.getElementById('pdf-visual-action-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading loading-spinner loading-xs mr-1"></span> {{ __("Memproses...") }}';
                }

                if (typeof window.showLoadingBlur === 'function') {
                    window.showLoadingBlur(@json(__('Membubuhkan QR Code...')), @json(__('Sedang menyematkan QR Code verifikasi ke dokumen PDF...')));
                }

                fetch('{{ route("documents.stamp-qrcode", $document) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json().then(data => ({ status: res.status, data: data })))
                .then(response => {
                    closePdfVisualPlacementModal();
                    if (response.status === 200 && response.data.success) {
                        showSignatureScreenAlert('BERHASIL', response.data.message || 'QR CODE VERIFIKASI BERHASIL DIBUBUHKAN SESUAI POSISI & UKURAN VISUAL.', true);
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                        showSignatureScreenAlert('GAGAL MEMBUBUHKAN QR CODE', response.data.message || 'GAGAL MEMPROSES QR CODE.', false);
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '{{ __("Bubuhkan QR Code Di Sini") }}';
                        }
                    }
                })
                .catch(() => {
                    if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                    closePdfVisualPlacementModal();
                    showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = '{{ __("Bubuhkan QR Code Di Sini") }}';
                    }
                });
            }

            function submitVisualSignatureRequest(userId, userName) {
                const btn = document.getElementById('pdf-visual-action-btn');
                const isStamp = (activeVisualSignatureType === 'company_stamp');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading loading-spinner loading-xs mr-1"></span> {{ __("Mengirim...") }}';
                }

                if (typeof window.showLoadingBlur === 'function') {
                    window.showLoadingBlur(
                        isStamp ? @json(__('Mengirim Permintaan Stempel...')) : @json(__('Mengirim Permintaan Tanda Tangan...')),
                        @json(__('Sedang memproses pengiriman permintaan ke penerima...'))
                    );
                }

                const sigParam = activeVisualSignatureId ? `&signature_id=${activeVisualSignatureId}` : '';
                const queryStr = `&page_number=${visualPlacementCoords.page}&preset_position=custom&pos_x=${visualPlacementCoords.xMm}&pos_y=${visualPlacementCoords.yMm}&width=${visualPlacementCoords.wMm}&height=${visualPlacementCoords.hMm}${sigParam}`;

                fetch(`/profile/signature?user_id=${userId}&document_id={{ $document->id }}${queryStr}`)
                    .then(res => res.json().then(data => ({ status: res.status, data: data })))
                    .then(response => {
                        if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                        closePdfVisualPlacementModal();
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = isStamp ? '{{ __("Kirim Permintaan Stempel") }}' : '{{ __("Kirim Permintaan Tanda Tangan") }}';
                        }
                        if (response.status === 200 && response.data.success) {
                            const title = isStamp ? 'PERMINTAAN STEMPEL DIKIRIM' : 'PERMINTAAN TANDA TANGAN DIKIRIM';
                            const defaultMsg = isStamp ? 'PERMINTAAN PENGGUNAAN STEMPEL PERUSAHAAN BERHASIL DIKIRIM.' : 'PERMINTAAN TANDA TANGAN BERHASIL DIKIRIM.';
                            const autoMsg = isStamp 
                                ? ' KETIKA DISETUJUI, STEMPEL AKAN OTOMATIS DIBUBUHKAN PADA DOKUMEN PDF SESUAI POSISI & UKURAN YANG TELAH DIATUR.'
                                : ' KETIKA DISETUJUI, TANDA TANGAN AKAN OTOMATIS DIBUBUHKAN PADA DOKUMEN PDF SESUAI POSISI & UKURAN YANG TELAH DIATUR.';
                            showSignatureScreenAlert(
                                title,
                                (response.data.message || defaultMsg) + autoMsg,
                                true
                            );
                        } else {
                            showSignatureScreenAlert('GAGAL MENGIRIM PERMINTAAN', response.data.message || (isStamp ? 'GAGAL MENGIRIM PERMINTAAN STEMPEL.' : 'GAGAL MENGIRIM PERMINTAAN TANDA TANGAN.'), false);
                        }
                    })
                    .catch(() => {
                        if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                        closePdfVisualPlacementModal();
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = isStamp ? '{{ __("Kirim Permintaan Stempel") }}' : '{{ __("Kirim Permintaan Tanda Tangan") }}';
                        }
                        showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                    });
            }

            function stampMyVisualSignature() {
                if (!visualPlacementCoords.isCustom) {
                    updateCoordinateMetrics();
                }

                const isStamp = (activeVisualSignatureType === 'company_stamp');

                const payload = {
                    page_number: visualPlacementCoords.page,
                    pos_x: visualPlacementCoords.xMm,
                    pos_y: visualPlacementCoords.yMm,
                    width: visualPlacementCoords.wMm,
                    height: visualPlacementCoords.hMm,
                    preset_position: 'custom',
                    signature_id: activeVisualSignatureId
                };

                const btn = document.getElementById('pdf-visual-action-btn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading loading-spinner loading-xs mr-1"></span> {{ __("Memproses...") }}';
                }

                if (typeof window.showLoadingBlur === 'function') {
                    window.showLoadingBlur(
                        isStamp ? @json(__('Membubuhkan Stempel...')) : @json(__('Membubuhkan Tanda Tangan...')),
                        @json(__('Sedang menyematkan tanda tangan ke dalam dokumen PDF...'))
                    );
                }

                fetch('{{ route("documents.stamp-signature", $document) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json().then(data => ({ status: res.status, data: data })))
                .then(response => {
                    closePdfVisualPlacementModal();
                    if (response.status === 200 && response.data.success) {
                        showSignatureScreenAlert('BERHASIL', isStamp ? 'STEMPEL PERUSAHAAN BERHASIL DIBUBUHKAN SESUAI POSISI & UKURAN VISUAL.' : 'TANDA TANGAN SAYA BERHASIL DIBUBUHKAN SESUAI POSISI & UKURAN VISUAL.', true);
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                        showSignatureScreenAlert(isStamp ? 'GAGAL MEMBUBUHKAN STEMPEL' : 'GAGAL MEMBUBUHKAN TTD', response.data.message || (isStamp ? 'GAGAL MEMPROSES STEMPEL.' : 'GAGAL MEMPROSES TANDA TANGAN.'), false);
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = isStamp ? '{{ __("Bubuhkan Stempel Di Sini") }}' : '{{ __("Bubuhkan TTD Saya Di Sini") }}';
                        }
                    }
                })
                .catch(() => {
                    if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                    closePdfVisualPlacementModal();
                    showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = isStamp ? '{{ __("Bubuhkan Stempel Di Sini") }}' : '{{ __("Bubuhkan TTD Saya Di Sini") }}';
                    }
                });
            }

            function confirmRevertPdfSignature() {
                if (!confirm('{{ __("Apakah Anda yakin ingin membatalkan tanda tangan yang paling terakhir ditambahkan pada dokumen PDF ini?") }}')) {
                    return;
                }

                if (typeof window.showLoadingBlur === 'function') {
                    window.showLoadingBlur(
                        @json(__('Membatalkan Tanda Tangan...')),
                        @json(__('Sedang mengembalikan dokumen PDF ke status sebelumnya...'))
                    );
                }

                fetch('{{ route("documents.revert-pdf-signature", $document) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json().then(data => ({ status: res.status, data: data })))
                .then(response => {
                    if (response.status === 200 && response.data.success) {
                        showSignatureScreenAlert('BERHASIL', response.data.message || 'TANDA TANGAN BERHASIL DIHAPUS.', true);
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                        showSignatureScreenAlert('GAGAL MENGHAPUS TTD', response.data.message || 'TIDAK DAPAT MENGHAPUS TANDA TANGAN.', false);
                    }
                })
                .catch(() => {
                    if (typeof window.hideLoadingBlur === 'function') window.hideLoadingBlur();
                    showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                });
            }

            function insertMySignature(signatureId = null, signatureType = 'original', companyName = null) {
                if (isPdfDocument) {
                    openPdfVisualPlacementModal(null, null, 'signature', signatureId, signatureType, companyName);
                    return;
                }

                fetchUserSignatureAndInsert({{ auth()->id() }}, '{{ addslashes(auth()->user()->name) }}', signatureId);
            }

            function insertSignatureImage(signatureUrl, userName, token = null) {
                if (!signatureUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'PENGGUNA ' + userName.toUpperCase() + ' BELUM MEMILIKI TANDA TANGAN TERSIMPAN.', false);
                    return;
                }

                insertImageIntoOnlyOffice(signatureUrl, 18, 18, token);
            }

            let allSignatureUsersData = [];

            function openSignatureSelectorModal() {
                const modal = document.getElementById('signature-users-modal');
                const list = document.getElementById('signature-users-list');
                const searchInput = document.getElementById('signature-search-input');
                if (searchInput) searchInput.value = '';
                const clearBtn = document.getElementById('signature-search-clear');
                if (clearBtn) clearBtn.classList.add('hidden');

                modal.showModal();

                fetch('{{ route("signatures.users") }}?document_id={{ $document->id }}')
                    .then(res => res.json())
                    .then(data => {
                        allSignatureUsersData = data.users || [];
                        filterSignatureUsers('');
                    })
                    .catch(err => {
                        list.innerHTML = '<p class="text-sm text-error text-center py-4 uppercase">{{ __("GAGAL MEMUAT PENGGUNA.") }}</p>';
                    });
            }

            function filterSignatureUsers(query) {
                const clearBtn = document.getElementById('signature-search-clear');
                const q = (query || '').toLowerCase().trim();
                if (clearBtn) {
                    clearBtn.classList.toggle('hidden', !q);
                }
                const filtered = allSignatureUsersData.filter(u => {
                    if (!q) {
                        // When not searching, only show the current user
                        return !!u.is_me;
                    }
                    
                    return (u.name && u.name.toLowerCase().includes(q)) ||
                           (u.email && u.email.toLowerCase().includes(q)) ||
                           (u.role && u.role.toLowerCase().includes(q)) ||
                           (u.division && u.division.toLowerCase().includes(q));
                });
                renderSignatureUsersList(filtered, !q);
            }

            function clearSignatureSearch() {
                const searchInput = document.getElementById('signature-search-input');
                if (searchInput) {
                    searchInput.value = '';
                    filterSignatureUsers('');
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function renderSignatureUsersList(users, isInitialEmpty = false) {
                const list = document.getElementById('signature-users-list');
                if (!list) return;

                if (!users || users.length === 0) {
                    list.innerHTML = '<p class="text-sm text-base-content/60 text-center py-6 uppercase font-medium">{{ __("TIDAK ADA PENGGUNA DITEMUKAN.") }}</p>';
                    return;
                }

                let html = users.map(u => {
                    const signaturesHtml = (u.signatures && u.signatures.length > 0)
                        ? '<div class="border-t border-base-200 pt-2 mt-2 space-y-2 min-w-0">' +
                            u.signatures.map(sig => {
                                const isStamp = (sig.type === 'company_stamp');
                                const typeLabel = isStamp ? ('STEMPEL: ' + (sig.company_name || 'PERUSAHAAN')) : 'TANDA TANGAN ORIGINAL';
                                const typeBadgeClass = isStamp ? 'badge-secondary' : 'badge-primary';
                                const safeName = (u.name || '').replace(/'/g, "\\'");
                                const safeComp = (sig.company_name || '').replace(/'/g, "\\'");
                                
                                let sigActionHtml = '';
                                if (u.is_me) {
                                    sigActionHtml = `<button type="button" onclick="insertMySignature(${sig.id}, '${sig.type}', '${safeComp}')" class="btn btn-xs ${isStamp ? 'btn-secondary' : 'btn-primary'} gap-1 uppercase font-bold shrink-0">{{ __("SISIPKAN") }}</button>`;
                                } else if (sig.request_status === 'pending') {
                                    sigActionHtml = `
                                        <span class="badge badge-warning badge-xs gap-1 py-1.5 px-2 font-bold uppercase shrink-0">
                                            ⏳ {{ __('MENUNGGU') }}
                                        </span>
                                    `;
                                } else if (sig.request_status === 'approved') {
                                    const reqAction = isPdfDocument 
                                        ? `openPdfVisualPlacementModal(${u.id}, '${safeName}', 'signature', ${sig.id}, '${sig.type}', '${safeComp}')`
                                        : `fetchUserSignatureAndInsert(${u.id}, &quot;${(u.name || '').replace(/"/g, '&quot;')}&quot;, ${sig.id})`;
                                    sigActionHtml = `
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="badge badge-success badge-xs gap-1 py-1 px-2 font-bold uppercase text-white">
                                                ✓ {{ __('DISETUJUI') }}
                                            </span>
                                            <button type="button" onclick="${reqAction}" class="btn btn-xs btn-outline ${isStamp ? 'btn-secondary' : 'btn-primary'} gap-1 uppercase font-bold">
                                                {{ __('MINTA ULANG') }}
                                            </button>
                                        </div>
                                    `;
                                } else if (sig.request_status === 'rejected') {
                                    const reqAction = isPdfDocument 
                                        ? `openPdfVisualPlacementModal(${u.id}, '${safeName}', 'signature', ${sig.id}, '${sig.type}', '${safeComp}')`
                                        : `fetchUserSignatureAndInsert(${u.id}, &quot;${(u.name || '').replace(/"/g, '&quot;')}&quot;, ${sig.id})`;
                                    sigActionHtml = `
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="badge badge-error badge-xs gap-1 py-1 px-2 font-bold uppercase text-white" title="${escapeHtml(sig.rejected_reason || '')}">
                                                ✕ {{ __('DITOLAK') }}
                                            </span>
                                            <button type="button" onclick="${reqAction}" class="btn btn-xs btn-outline btn-warning gap-1 uppercase font-bold">
                                                {{ __('MINTA ULANG') }}
                                            </button>
                                        </div>
                                    `;
                                } else if (sig.request_status === 'used') {
                                    const reqAction = isPdfDocument 
                                        ? `openPdfVisualPlacementModal(${u.id}, '${safeName}', 'signature', ${sig.id}, '${sig.type}', '${safeComp}')`
                                        : `fetchUserSignatureAndInsert(${u.id}, &quot;${(u.name || '').replace(/"/g, '&quot;')}&quot;, ${sig.id})`;
                                    sigActionHtml = `
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="badge badge-ghost badge-xs text-base-content/60 gap-1 py-1 px-2 font-bold uppercase">
                                                ✓ {{ __('DIGUNAKAN') }}
                                            </span>
                                            <button type="button" onclick="${reqAction}" class="btn btn-xs btn-outline ${isStamp ? 'btn-secondary' : 'btn-primary'} gap-1 uppercase font-bold">
                                                ${isStamp ? '{{ __("MINTA STEMPEL") }}' : '{{ __("MINTA TTD") }}'}
                                            </button>
                                        </div>
                                    `;
                                } else {
                                    const reqAction = isPdfDocument 
                                        ? `openPdfVisualPlacementModal(${u.id}, '${safeName}', 'signature', ${sig.id}, '${sig.type}', '${safeComp}')`
                                        : `fetchUserSignatureAndInsert(${u.id}, &quot;${(u.name || '').replace(/"/g, '&quot;')}&quot;, ${sig.id})`;
                                    sigActionHtml = `<button type="button" onclick="${reqAction}" class="btn btn-xs btn-outline ${isStamp ? 'btn-secondary' : 'btn-primary'} gap-1 uppercase font-bold shrink-0">${isStamp ? '{{ __("MINTA STEMPEL") }}' : '{{ __("MINTA TTD") }}'}</button>`;
                                }
                                
                                const sigReasonHtml = (sig.request_status === 'rejected' && sig.rejected_reason)
                                    ? `<div class="mt-2 p-2.5 rounded-xl bg-error/10 border border-error/20 text-xs text-error font-medium min-w-0 max-w-full overflow-hidden">
                                            <div class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider mb-1 text-error">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                                <span>{{ __('Alasan Ditolak:') }}</span>
                                            </div>
                                            <p class="text-base-content/85 dark:text-base-content/90 font-normal leading-relaxed break-words [overflow-wrap:anywhere] whitespace-pre-wrap text-[11px] max-h-36 overflow-y-auto pr-1">${escapeHtml(sig.rejected_reason)}</p>
                                       </div>`
                                    : '';

                                return `
                                    <div class="p-2.5 rounded-xl bg-base-100 border border-base-200 min-w-0 max-w-full overflow-hidden shadow-xs">
                                        <div class="flex items-center justify-between text-xs gap-2 flex-wrap sm:flex-nowrap">
                                            <div class="flex items-center gap-1.5 min-w-0 flex-1">
                                                <span class="badge ${typeBadgeClass} badge-outline badge-xs font-bold uppercase shrink-0">${isStamp ? 'Stempel' : 'TTD'}</span>
                                                <span class="font-bold truncate" title="${escapeHtml(typeLabel)}">${escapeHtml(typeLabel)}</span>
                                            </div>
                                            <div class="shrink-0 ml-auto">${sigActionHtml}</div>
                                        </div>
                                        ${sigReasonHtml}
                                    </div>
                                `;
                            }).join('') +
                          '</div>'
                        : (!u.has_signature ? '<p class="text-xs text-base-content/40 italic uppercase mt-1">{{ __("BELUM MEMILIKI TANDA TANGAN ATAU STEMPEL") }}</p>' : '');

                    const isMe = u.is_me;

                    return `
                        <div class="flex flex-col p-2.5 rounded-xl border border-base-200 hover:bg-base-200/40 transition-all ${isMe ? 'bg-primary/5 border-primary/20' : ''} min-w-0 max-w-full overflow-hidden">
                            <div class="flex items-center justify-between min-w-0">
                                <div class="pr-2 min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5 mb-0.5 min-w-0">
                                        <p class="text-sm font-semibold leading-tight text-base-content uppercase truncate" title="${escapeHtml(u.name || '')}">${escapeHtml(u.name || '')}</p>
                                        ${u.is_me ? '<span class="badge badge-primary badge-xs uppercase font-bold shrink-0">Saya</span>' : ''}
                                    </div>
                                    <p class="text-xs text-base-content/60 uppercase truncate">${escapeHtml(u.role || '')} &bull; ${escapeHtml(u.division || '')}</p>
                                </div>
                            </div>
                            ${signaturesHtml}
                        </div>
                    `;
                }).join('');

                if (isInitialEmpty) {
                    html += `
                        <div class="p-3 bg-base-200/40 rounded-xl text-center text-xs text-base-content/60 mt-3 border border-dashed border-base-300">
                            <span class="font-semibold text-base-content/80">{{ __('Tips:') }}</span>
                            {{ __('Ketik nama pengguna di kolom pencarian di atas lalu klik Cari untuk memilih tanda tangan atau stempel pengguna lain.') }}
                        </div>
                    `;
                }

                list.innerHTML = html;
            }

            function fetchUserSignatureAndInsert(userId, userName, signatureId = null) {
                document.getElementById('signature-users-modal').close();
                let url = `/profile/signature?user_id=${userId}&document_id={{ $document->id }}`;
                if (signatureId) {
                    url += `&signature_id=${signatureId}`;
                }
                if (isPdfDocument && typeof getPdfPlacementParams === 'function') {
                    const params = getPdfPlacementParams();
                    if (params && params.page_number) {
                        url += `&page_number=${params.page_number}&preset_position=${params.preset_position || 'bottom-right'}`;
                    }
                    if (params && params.pos_x !== undefined) {
                        url += `&pos_x=${params.pos_x}&pos_y=${params.pos_y}&width=${params.width}&height=${params.height}`;
                    }
                }

                fetch(url)
                    .then(res => res.json().then(data => ({ status: res.status, data: data })))
                    .then(response => {
                        const data = response.data;
                        if (response.status === 200 && data.success) {
                            if (!data.is_pending && data.url) {
                                if (!isPdfDocument) {
                                    insertSignatureImage(data.url, userName, data.token || null);
                                }
                            }
                            if (data.is_pending) {
                                if (!isPdfDocument) {
                                    if (data.url) {
                                        insertImageIntoOnlyOffice(data.url, 18, 18, data.token || null);
                                    }
                                }
                                showSignatureScreenAlert(
                                    isPdfDocument ? 'PERMINTAAN DIKIRIM' : 'PENANDA DISISIPKAN',
                                    (data.message || (isPdfDocument ? ('Permintaan penggunaan tanda tangan / stempel telah berhasil dikirim ke ' + userName.toUpperCase() + '.') : 'Penanda tanda tangan / stempel telah disisipkan.')) + (isPdfDocument ? ' KETIKA DISETUJUI, ITEM AKAN OTOMATIS DIBUBUHKAN PADA DOKUMEN PDF.' : ' PERMINTAAN AKAN DIKIRIM SECARA OTOMATIS KETIKA DOKUMEN DISIMPAN & SELESAI DIEDIT.'),
                                    true
                                );
                            } else if (data.message) {
                                setTimeout(() => {
                                    showSignatureScreenAlert(
                                        'BERHASIL',
                                        data.message + (isPdfDocument ? ' KETIKA DISETUJUI, ITEM AKAN OTOMATIS DIBUBUHKAN PADA DOKUMEN PDF.' : ''),
                                        true
                                    );
                                }, 400);
                            }
                        } else if (response.status === 403) {
                            showSignatureScreenAlert(
                                'AKSES DITOLAK',
                                data.message || 'ANDA TIDAK MEMILIKI IZIN UNTUK MENGGUNAKAN TANDA TANGAN / STEMPEL PENGGUNA INI.',
                                false
                            );
                        } else {
                            showSignatureScreenAlert(
                                'DATA TIDAK DITEMUKAN',
                                data.message || ('TANDA TANGAN / STEMPEL UNTUK ' + userName.toUpperCase() + ' TIDAK DITEMUKAN.'),
                                false
                            );
                        }
                    })
                    .catch(() => {
                        showSignatureScreenAlert(
                            'KESALAHAN SISTEM',
                            'GAGAL MENGAMBIL DATA TANDA TANGAN / STEMPEL.',
                            false
                        );
                    });
            }

            // Real-time Echo Listener for signature approval and rejection notifications on screen
            function initEchoSignatureListeners() {
                if (typeof window.Echo !== 'undefined') {
                    window.Echo.private('App.Models.User.{{ auth()->id() }}')
                        .notification((notification) => {
                            if (notification.type === 'signature_request_approved' && notification.document_id == {{ $document->id }}) {
                                showSignatureScreenAlert(
                                    'TANDA TANGAN TELAH DISETUJUI',
                                    (notification.message || 'TANDA TANGAN TELAH DISETUJUI DAN DITERAPKAN PADA DOKUMEN.').toUpperCase(),
                                    true
                                );
                                setTimeout(() => window.location.reload(), 1500);
                            } else if (notification.type === 'signature_request_rejected' && notification.document_id == {{ $document->id }}) {
                                showSignatureScreenAlert(
                                    'PERMINTAAN TANDA TANGAN DITOLAK',
                                    (notification.message || 'PERMINTAAN TANDA TANGAN TELAH DITOLAK OLEH PEMILIK TTD. KOTAK PENANDA AKAN OTOMATIS DIHAPUS.').toUpperCase(),
                                    false
                                );
                                setTimeout(() => window.location.reload(), 1500);
                            }
                        });
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initEchoSignatureListeners);
            } else {
                initEchoSignatureListeners();
            }

            /**
             * Navigation Guard Custom Leave Handler:
             * If user leaves the page without clicking "Selesai Edit", discard any changes made in this session.
             */
            window.onNavigationGuardLeave = function(pendingUrl) {
                if (window._hasSessionChanges) {
                    const discardUrl = "{{ route('documents.discard', $document) }}";
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    fetch(discardUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        keepalive: true
                    }).catch((e) => {
                        console.warn('Discard request error:', e);
                    }).finally(() => {
                        if (pendingUrl === 'history_back') {
                            history.go(-2);
                        } else if (pendingUrl) {
                            window.location.href = pendingUrl;
                        }
                    });
                } else {
                    if (pendingUrl === 'history_back') {
                        history.go(-2);
                    } else if (pendingUrl) {
                        window.location.href = pendingUrl;
                    }
                }
            };

            /**
             * "Selesai Edit" action: saves document changes, notifies approver/requester, and redirects to show page.
             */
            function finishEditingDocument() {
                window._hasSessionChanges = false;
                if (typeof window.allowIntentionalLeave === 'function') {
                    window.allowIntentionalLeave();
                }

                const btn = document.getElementById('btn-selesai-edit');
                const spinner = document.getElementById('spinner-selesai-edit');
                const icon = document.getElementById('icon-selesai-edit');
                const text = document.getElementById('text-selesai-edit');

                if (btn) btn.disabled = true;
                if (icon) icon.classList.add('hidden');
                if (spinner) spinner.classList.remove('hidden');
                if (text) text.textContent = "{{ __('MENYIMPAN...') }}";

                if (typeof window.showLoadingBlur === 'function') {
                    window.showLoadingBlur(
                        @json(__('Menyimpan Dokumen...')),
                        @json(__('Menyelesaikan pengeditan dan meneruskan ke approver...'))
                    );
                }

                if (window.docEditor) {
                    try {
                        window.docEditor.destroyEditor();
                    } catch (e) {
                        console.warn('destroyEditor error:', e);
                    }
                }

                fetch("{{ route('documents.finish-editing', $document) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    window.location.href = data.redirect_url || "{{ route('documents.show', $document) }}";
                })
                .catch(err => {
                    console.warn('finish-editing request error:', err);
                    window.location.href = "{{ route('documents.show', $document) }}";
                });
            }
        </script>
    @endpush

</x-app-layout>