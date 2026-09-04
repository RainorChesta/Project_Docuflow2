<x-app-layout>
    <x-slot name="header">{{ __('ONLYOFFICE Document Editor') }}</x-slot>

    <x-confirm-modal
        name="confirm-discard-{{ $document->id }}"
        :title="__('Discard Document?')"
        :message="__('Are you sure you want to discard this document and all its changes?')"
        :action="route('documents.destroy', $document)"
        method="DELETE"
        :confirmLabel="__('Discard')"
        :cancelLabel="__('Batal')"
    />

    <x-confirm-modal
        name="confirm-discard-version-{{ $document->id }}"
        :title="__('Discard Changes?')"
        :message="__('Are you sure you want to discard the pending changes? The approved version will remain intact.')"
        :action="route('documents.discard', $document)"
        method="POST"
        :confirmLabel="__('Discard Changes')"
        :cancelLabel="__('Batal')"
    />

    @php
        $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
        $hasDraftOnly = !$pending && !$document->currentVersion;
        $isPdf = ($version && ($version->file_path && str_ends_with(strtolower($version->file_path), '.pdf'))) || ($version && $version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'));
    @endphp

    <div class="pb-6">
        <div class="max-w-7xl mx-auto w-full">
            {{-- Pending Alert if exists --}}
            @if($pending)
                <div class="mb-4 px-4 py-3 bg-warning/10 border border-warning/20 rounded-xl text-xs text-warning-content flex items-center justify-between shadow-xs">
                    <span>{{ __('Terdapat versi pending (v:version) yang menunggu review. Setiap perubahan yang Anda simpan akan memperbarui versi pending ini.', ['version' => $pending->version_number]) }}</span>
                </div>
            @endif

            {{-- Signature Approval Banner (server-side rendered on page load) --}}
            @if(!empty($pendingApprovalBanner))
                <div id="signature-banner-alert" class="mb-4 px-4 py-3 bg-success/20 border border-success/30 rounded-xl text-xs text-success-content flex flex-wrap sm:flex-nowrap items-center justify-between gap-3 z-10 shadow-xs transition-all duration-300">
                    <div class="flex items-center gap-2.5">
                        <div class="h-6 w-6 rounded-full bg-success/30 flex flex-shrink-0 items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span id="signature-banner-message" class="font-bold uppercase tracking-wide leading-tight">
                            TANDA TANGAN DARI {{ strtoupper(implode(', ', $pendingApprovalBanner)) }} TELAH DISETUJUI. SILAKAN KLIK "GANTI TTD" UNTUK MENGGANTI PLACEHOLDER DENGAN TANDA TANGAN RESMI.
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('signature-banner-alert').classList.add('hidden')" class="btn btn-ghost btn-xs">
                            {{ __('TUTUP') }}
                        </button>
                        <button type="button" onclick="openSignatureSelectorModal()" class="btn btn-success btn-xs text-white uppercase font-bold">
                            {{ __('GANTI TTD') }}
                        </button>
                    </div>
                </div>
            @else
                <div id="signature-banner-alert" class="hidden mb-4 px-4 py-3 bg-success/20 border border-success/30 rounded-xl text-xs text-success-content flex flex-wrap sm:flex-nowrap items-center justify-between gap-3 z-10 shadow-xs transition-all duration-300">
                    <div class="flex items-center gap-2.5">
                        <div class="h-6 w-6 rounded-full bg-success/30 flex flex-shrink-0 items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span id="signature-banner-message" class="font-bold uppercase tracking-wide leading-tight"></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="document.getElementById('signature-banner-alert').classList.add('hidden')" class="btn btn-ghost btn-xs">
                            {{ __('TUTUP') }}
                        </button>
                        <button type="button" onclick="openSignatureSelectorModal()" class="btn btn-success btn-xs text-white uppercase font-bold">
                            {{ __('GANTI TTD') }}
                        </button>
                    </div>
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

                                {{-- Discard Changes --}}
                                @if($document->currentVersion)
                                    @can('update', $document)
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
                                    @endcan
                                @else
                                    @can('delete', $document)
                                        <button type="button"
                                                class="btn btn-outline btn-error btn-xs gap-1 shrink-0"
                                                x-on:click="$dispatch('open-modal', 'confirm-discard-{{ $document->id }}')"
                                                title="{{ __('Discard') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span class="hidden sm:inline">{{ __('Buang') }}</span>
                                            <span class="sm:hidden">{{ __('Buang') }}</span>
                                        </button>
                                    @endcan
                                @endif

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

                        {{-- ONLYOFFICE Editor Viewport (Fluid & Responsive on Mobile/Tablet, 1150px on Desktop) --}}
                        <div class="w-full border border-base-300 rounded-lg overflow-hidden shadow-xs bg-base-100 h-[72vh] sm:h-[80vh] lg:h-[1150px] min-h-[520px] sm:min-h-[650px] lg:min-h-[1123px]">
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
                    <h3 class="font-bold text-lg text-base-content leading-tight">{{ __('PILIH & GANTI TANDA TANGAN') }}</h3>
                    <p class="text-xs text-base-content/60 mt-0.5">{{ __('Kelola permintaan dan pembubuhan tanda tangan digital pada dokumen ini.') }}</p>
                </div>
                <div id="signature-available-count-badge" class="badge badge-success badge-sm gap-1 hidden font-bold shrink-0">
                    <span id="signature-available-count-text">0</span> {{ __('TERSEDIA') }}
                </div>
            </div>

            {{-- Signature Search Input --}}
            <div class="flex gap-2 mb-3">
                <div class="relative flex-1">
                    <input type="text" id="signature-search-input" onkeypress="if(event.key === 'Enter') filterSignatureUsers(this.value)" placeholder="{{ __('Ketik nama pengguna lain lalu klik Cari...') }}" class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 rounded-xl focus:border-primary focus:ring-1 focus:ring-primary text-xs sm:text-sm">
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
            <div id="signature-users-list" class="space-y-2.5 max-h-72 overflow-y-auto pr-1">
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
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"
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

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof DocsAPI === 'undefined') {
                    document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                    return;
                }

                try {
                    const config = @json($onlyOfficeConfig);
                    const isMobileOrTablet = window.innerWidth < 1024;

                    // Always use desktop type to bypass ONLYOFFICE Community Edition mobile license restriction
                    config.type = 'desktop';
                    config.editorConfig = config.editorConfig || {};
                    config.editorConfig.mode = 'edit';
                    config.editorConfig.customization = config.editorConfig.customization || {};
                    config.editorConfig.customization.compactHeader = true;
                    config.editorConfig.customization.autoFocus = false;
                    config.editorConfig.customization.mobile = { force: false };

                    if (isMobileOrTablet) {
                        // Responsive mode for small/shrinking viewports:
                        // - Compact single-row toolbar instead of bulky tabs
                        config.editorConfig.customization.compactToolbar = true;
                        // - Hide left & right sidebar panels to maximize document width
                        config.editorConfig.customization.leftMenu = false;
                        config.editorConfig.customization.rightMenu = false;
                        // - Hide rulers that take up margins
                        config.editorConfig.customization.ruler = false;
                        // - Hide duplicate file name in toolbar
                        config.editorConfig.customization.toolbarHideFileName = true;
                        // - Fit to Width (-2) scales document page to fill 100% available viewport width
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
                        replacePendingSignatures();
                    };
                    config.events.onDocumentStateChange = function(event) {
                        const isModified = event.data;
                        
                        if (typeof window.setNavigationDirty === 'function') {
                            window.setNavigationDirty(isModified);
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
            });

            function replacePendingSignatures() {
                const approvedSignatures = @json($approvedSignatures ?? []);
                if (!approvedSignatures || approvedSignatures.length === 0 || !window.docEditor || !window.docEditor.createConnector) return;

                const connector = window.docEditor.createConnector();
                const script = `
                    var oDocument = Api.GetDocument();
                    var aContentControls = oDocument.GetAllContentControls();
                    var approved = ${JSON.stringify(approvedSignatures)};
                    
                    for (var i = 0; i < aContentControls.length; i++) {
                        var label = aContentControls[i].GetLabel();
                        if (label && label.indexOf("pending_sig_") === 0) {
                            var reqId = parseInt(label.split("_")[2]);
                            var match = null;
                            for (var j = 0; j < approved.length; j++) {
                                if (approved[j].request_id === reqId) {
                                    match = approved[j];
                                    break;
                                }
                            }
                            if (match && match.url) {
                                aContentControls[i].RemoveAllElements();
                                var oImage = Api.CreateImage(match.url, 140 * 36000, 140 * 36000);
                                var oParagraph = Api.CreateParagraph();
                                oParagraph.AddElement(oImage, 0);
                                try {
                                    aContentControls[i].AddElement(oParagraph, 0);
                                } catch (e) {
                                    aContentControls[i].AddElement(oImage, 0);
                                }
                                aContentControls[i].SetLabel("resolved_sig_" + reqId);
                            }
                        }
                    }
                `;
                connector.callCommand(new Function(script), function() {
                    console.log("Pending signatures replaced automatically.");
                });
            }

            function showSignatureScreenAlert(title, message, isSuccess = true) {
                const modal = document.getElementById('signature-alert-modal');
                const titleEl = document.getElementById('signature-alert-title');
                const messageEl = document.getElementById('signature-alert-message');
                const iconEl = document.getElementById('signature-alert-icon');
                const banner = document.getElementById('signature-banner-alert');
                const bannerMsg = document.getElementById('signature-banner-message');

                // Update modal contents
                if (titleEl) titleEl.textContent = (title || 'PEMBERITAHUAN TANDA TANGAN').toUpperCase();
                if (messageEl) messageEl.textContent = (message || '').toUpperCase();

                if (iconEl) {
                    if (isSuccess) {
                        iconEl.className = 'mx-auto mb-3 flex items-center justify-center h-12 w-12 rounded-full bg-success/15 text-success';
                        iconEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
                    } else {
                        iconEl.className = 'mx-auto mb-3 flex items-center justify-center h-12 w-12 rounded-full bg-warning/15 text-warning';
                        iconEl.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
                    }
                }

                // If it's the approval notification, we show it in the banner too
                if (title === 'TANDA TANGAN TELAH DISETUJUI' && banner && bannerMsg) {
                    bannerMsg.textContent = (message || '').toUpperCase();
                    banner.classList.remove('hidden');
                } else if (modal) {
                    modal.showModal();
                } else {
                    alert((title + '\n' + message).toUpperCase());
                }
            }

            /**
             * Insert image directly into ONLYOFFICE document editor via Document Builder Connector or DocsAPI insertImage
             */
            function insertImageIntoOnlyOffice(imageUrl, widthPx = 140, heightPx = 140, token = null) {
                if (!window.docEditor) {
                    showSignatureScreenAlert('PERINGATAN', 'EDITOR BELUM SELESAI DIMUAT. TUNGGU SEBENTAR...', false);
                    return;
                }

                if (!imageUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'URL GAMBAR TIDAK VALID.', false);
                    return;
                }

                try {
                    // Method 1: Use Document Builder Connector (Native and most reliable in ONLYOFFICE)
                    if (typeof window.docEditor.createConnector === 'function') {
                        try {
                            const connector = window.docEditor.createConnector();
                            const script = `
                                var oDocument = Api.GetDocument();
                                var oParagraph = Api.CreateParagraph();
                                var oImage = Api.CreateImage("${imageUrl}", ${widthPx} * 36000, ${heightPx} * 36000);
                                oParagraph.AddElement(oImage, 0);
                                oDocument.InsertContent([oParagraph]);
                            `;
                            connector.callCommand(new Function(script), function() {
                                console.log("Image inserted successfully via connector.");
                            });
                            return;
                        } catch (connErr) {
                            console.warn("Connector callCommand failed, falling back to DocsAPI insertImage:", connErr);
                        }
                    }

                    // Method 2: Fallback to DocsAPI insertImage
                    const payload = {
                        fileType: "png",
                        url: imageUrl,
                        width: widthPx,
                        height: heightPx
                    };

                    if (token) {
                        payload.token = token;
                    }

                    if (typeof window.docEditor.insertImage === 'function') {
                        window.docEditor.insertImage(payload);
                    }
                } catch (err) {
                    console.warn('insertImage error:', err);
                    showSignatureScreenAlert('PERINGATAN', 'TIDAK DAPAT MENYISIPKAN GAMBAR SECARA OTOMATIS. SILAKAN GUNAKAN MENU INSERT -> PICTURE PADA TOOLBAR ONLYOFFICE.', false);
                }
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
                insertImageIntoOnlyOffice(qrCodeUrl, 140, 140, qrCodeToken);
            }

            // --- PDF Visual Interactive Drag & Drop / Resizing Placement Tool ---
            let activeVisualType = 'signature'; // 'signature' | 'qrcode'
            let activeVisualTargetUserId = null;
            let activeVisualTargetUserName = null;
            let activeVisualSignatureId = null;

            function openPdfVisualPlacementModal(targetUserId = null, targetUserName = null, visualType = 'signature', signatureId = null) {
                const selectorModal = document.getElementById('signature-users-modal');
                if (selectorModal && selectorModal.open) {
                    selectorModal.close();
                }

                activeVisualType = visualType;
                activeVisualTargetUserId = targetUserId;
                activeVisualTargetUserName = targetUserName;
                activeVisualSignatureId = signatureId;

                const titleEl = document.getElementById('pdf-visual-modal-title');
                const subTitleEl = document.getElementById('pdf-visual-modal-subtitle');
                const tagTextEl = document.getElementById('pdf-box-signer-tag-text');
                const previewContainer = document.getElementById('pdf-box-preview-container');
                const actionBtnText = document.getElementById('pdf-visual-action-btn-text');

                if (activeVisualType === 'qrcode') {
                    if (titleEl) titleEl.textContent = 'Atur Posisi & Ukuran QR Code';
                    if (subTitleEl) subTitleEl.textContent = 'Geser kotak QR Code ke posisi yang diinginkan dan tarik sudut kanan bawah untuk mengatur ukuran.';
                    if (tagTextEl) tagTextEl.textContent = 'QR Code';
                    if (previewContainer) {
                        const qrSrc = @json($qrCodeDataUri ?? null) || qrCodeUrl;
                        if (qrSrc) {
                            previewContainer.innerHTML = `<img src="${qrSrc}" alt="QR Code" class="w-full h-full object-fill block m-0 p-0 pointer-events-none" />`;
                        } else {
                            previewContainer.innerHTML = `<span class="text-xs font-bold text-primary uppercase">[ QR Code ]</span>`;
                        }
                    }
                    if (actionBtnText) actionBtnText.textContent = '{{ __("Bubuhkan QR Code Di Sini") }}';
                } else if (targetUserId && targetUserName) {
                    if (titleEl) titleEl.textContent = 'Atur Posisi & Ukuran TTD: ' + targetUserName.toUpperCase();
                    if (subTitleEl) subTitleEl.textContent = 'Posisikan dan atur ukuran kotak tanda tangan untuk ' + targetUserName.toUpperCase() + ' pada halaman dokumen.';
                    if (tagTextEl) tagTextEl.textContent = 'TTD: ' + targetUserName.toUpperCase();
                    if (previewContainer) previewContainer.innerHTML = `<span class="text-xs font-bold text-primary uppercase tracking-wide">[ TTD: ${targetUserName} ]</span>`;
                    if (actionBtnText) actionBtnText.textContent = '{{ __("Kirim Permintaan Tanda Tangan") }}';
                } else {
                    if (titleEl) titleEl.textContent = signatureId ? 'Atur Posisi & Ukuran Stempel / TTD' : 'Atur Posisi & Ukuran Tanda Tangan Saya';
                    if (subTitleEl) subTitleEl.textContent = 'Geser kotak TTD ke posisi yang diinginkan dan tarik sudut kanan bawah untuk mengubah ukuran.';
                    if (tagTextEl) tagTextEl.textContent = signatureId ? 'Geser Stempel/TTD' : 'Geser TTD';
                    if (previewContainer) {
                        if (signatureId) {
                            previewContainer.innerHTML = `<span class="loading loading-spinner loading-xs text-primary"></span>`;
                            fetch(`/profile/signature?signature_id=${signatureId}`)
                                .then(res => res.json())
                                .then(data => {
                                    const src = data.client_url || data.data_uri || data.url;
                                    if (src) {
                                        previewContainer.innerHTML = `<img src="${src}" alt="Signature" class="w-full h-full object-fill block m-0 p-0 pointer-events-none" />`;
                                    } else {
                                        previewContainer.innerHTML = `<span class="text-xs font-bold text-primary/80 uppercase italic tracking-wide">[ {{ __("Tanda Tangan") }} ]</span>`;
                                    }
                                })
                                .catch(() => {
                                    previewContainer.innerHTML = `<span class="text-xs font-bold text-primary/80 uppercase italic tracking-wide">[ {{ __("Tanda Tangan") }} ]</span>`;
                                });
                        } else if (mySignatureClientUrl) {
                            previewContainer.innerHTML = `<img src="${mySignatureClientUrl}" alt="Signature" class="w-full h-full object-fill block m-0 p-0 pointer-events-none" />`;
                        } else {
                            previewContainer.innerHTML = `<span class="text-xs font-bold text-primary/80 uppercase italic tracking-wide">[ {{ __("Tanda Tangan") }} ]</span>`;
                        }
                    }
                    if (actionBtnText) actionBtnText.textContent = signatureId ? '{{ __("Bubuhkan Stempel / TTD Di Sini") }}' : '{{ __("Bubuhkan TTD Saya Di Sini") }}';
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

                            const defaultWMm = activeVisualType === 'qrcode' ? 30 : 40;
                            const defaultHMm = activeVisualType === 'qrcode' ? 30 : 25;
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
                        showSignatureScreenAlert('GAGAL MEMBUBUHKAN QR CODE', response.data.message || 'GAGAL MEMPROSES QR CODE.', false);
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '{{ __("Bubuhkan QR Code Di Sini") }}';
                        }
                    }
                })
                .catch(() => {
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
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<span class="loading loading-spinner loading-xs mr-1"></span> {{ __("Mengirim...") }}';
                }

                const sigParam = activeVisualSignatureId ? `&signature_id=${activeVisualSignatureId}` : '';
                const queryStr = `&page_number=${visualPlacementCoords.page}&preset_position=custom&pos_x=${visualPlacementCoords.xMm}&pos_y=${visualPlacementCoords.yMm}&width=${visualPlacementCoords.wMm}&height=${visualPlacementCoords.hMm}${sigParam}`;

                fetch(`/profile/signature?user_id=${userId}&document_id={{ $document->id }}${queryStr}`)
                    .then(res => res.json().then(data => ({ status: res.status, data: data })))
                    .then(response => {
                        closePdfVisualPlacementModal();
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '{{ __("Kirim Permintaan Tanda Tangan") }}';
                        }
                        if (response.status === 200 && response.data.success) {
                            showSignatureScreenAlert(
                                'PERMINTAAN TANDA TANGAN DIKIRIM',
                                (response.data.message || 'PERMINTAAN TANDA TANGAN BERHASIL DIKIRIM.') + ' KETIKA DISETUJUI, TANDA TANGAN AKAN OTOMATIS DIBUBUHKAN PADA DOKUMEN PDF SESUAI POSISI & UKURAN YANG TELAH DIATUR.',
                                true
                            );
                        } else {
                            showSignatureScreenAlert('GAGAL MENGIRIM PERMINTAAN', response.data.message || 'GAGAL MENGIRIM PERMINTAAN TANDA TANGAN.', false);
                        }
                    })
                    .catch(() => {
                        closePdfVisualPlacementModal();
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '{{ __("Kirim Permintaan Tanda Tangan") }}';
                        }
                        showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                    });
            }

            function stampMyVisualSignature() {
                if (!visualPlacementCoords.isCustom) {
                    updateCoordinateMetrics();
                }

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
                        showSignatureScreenAlert('BERHASIL', 'TANDA TANGAN SAYA BERHASIL DIBUBUHKAN SESUAI POSISI & UKURAN VISUAL.', true);
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        showSignatureScreenAlert('GAGAL MEMBUBUHKAN TTD', response.data.message || 'GAGAL MEMPROSES TANDA TANGAN.', false);
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = '{{ __("Bubuhkan TTD Saya Di Sini") }}';
                        }
                    }
                })
                .catch(() => {
                    closePdfVisualPlacementModal();
                    showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = '{{ __("Bubuhkan TTD Saya Di Sini") }}';
                    }
                });
            }

            function confirmRevertPdfSignature() {
                if (!confirm('{{ __("Apakah Anda yakin ingin membatalkan tanda tangan yang paling terakhir ditambahkan pada dokumen PDF ini?") }}')) {
                    return;
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
                        showSignatureScreenAlert('GAGAL MENGHAPUS TTD', response.data.message || 'TIDAK DAPAT MENGHAPUS TANDA TANGAN.', false);
                    }
                })
                .catch(() => {
                    showSignatureScreenAlert('KESALAHAN SISTEM', 'GAGAL MENGHUBUNGI SERVER.', false);
                });
            }

            function insertMySignature(signatureId = null) {
                if (isPdfDocument) {
                    openPdfVisualPlacementModal(null, null, 'signature', signatureId);
                    return;
                }

                if (signatureId) {
                    fetchUserSignatureAndInsert({{ auth()->id() }}, 'SAYA', signatureId);
                    return;
                }

                if (!mySignatureUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'ANDA BELUM MEMILIKI TANDA TANGAN TERSIMPAN.', false);
                    return;
                }

                insertImageIntoOnlyOffice(mySignatureUrl, 140, 140, mySignatureToken);
                showSignatureScreenAlert('BERHASIL', 'TANDA TANGAN SAYA BERHASIL DISISIPKAN KE DALAM DOKUMEN.', true);
            }

            function insertSignatureImage(signatureUrl, userName, token = null, isPending = false, requestId = null) {
                if (!signatureUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'PENGGUNA ' + userName.toUpperCase() + ' BELUM MEMILIKI TANDA TANGAN TERSIMPAN.', false);
                    return;
                }

                if (isPending && requestId && window.docEditor && window.docEditor.createConnector) {
                    try {
                        var connector = window.docEditor.createConnector();
                        var script = `
                            var oDocument = Api.GetDocument();
                            var oBlock = Api.CreateBlockLvlSdt();
                            var oParagraph = Api.CreateParagraph();
                            oParagraph.AddText("\${PENDING_SIG_${requestId}}");
                            oBlock.AddElement(oParagraph, 0);
                            oBlock.SetLabel("pending_sig_${requestId}");
                            oDocument.InsertContent([oBlock]);
                        `;
                        connector.callCommand(new Function(script), function() {
                            console.log("Pending signature block content control inserted for request " + "${requestId}");
                        });
                    } catch (e) {
                        console.warn("Failed to insert block content control, falling back to basic image insertion.", e);
                        insertImageIntoOnlyOffice(signatureUrl, 140, 140, token);
                    }
                } else {
                    insertImageIntoOnlyOffice(signatureUrl, 140, 140, token);
                }
            }

            let allSignatureUsersData = [];

            function openSignatureSelectorModal() {
                const modal = document.getElementById('signature-users-modal');
                const list = document.getElementById('signature-users-list');
                const badge = document.getElementById('signature-available-count-badge');
                const badgeText = document.getElementById('signature-available-count-text');
                const searchInput = document.getElementById('signature-search-input');
                if (searchInput) searchInput.value = '';
                const clearBtn = document.getElementById('signature-search-clear');
                if (clearBtn) clearBtn.classList.add('hidden');

                modal.showModal();

                fetch('{{ route("signatures.users") }}?document_id={{ $document->id }}')
                    .then(res => res.json())
                    .then(data => {
                        allSignatureUsersData = data.users || [];
                        const availableCount = data.available_to_replace_count || 0;

                        if (badge && badgeText) {
                            if (availableCount > 0) {
                                badgeText.textContent = availableCount;
                                badge.classList.remove('hidden');
                            } else {
                                badge.classList.add('hidden');
                            }
                        }

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
                        return u.is_me || u.is_available_to_replace || u.request_status === 'pending';
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

            function renderSignatureUsersList(users, isInitialEmpty = false) {
                const list = document.getElementById('signature-users-list');
                if (!list) return;

                if (!users || users.length === 0) {
                    list.innerHTML = '<p class="text-sm text-base-content/60 text-center py-6 uppercase font-medium">{{ __("TIDAK ADA PENGGUNA DITEMUKAN.") }}</p>';
                    return;
                }

                let html = users.map(u => {
                    let actionHtml = '';

                    if (!u.has_signature) {
                        if (u.is_me) {
                            actionHtml = `<a href="{{ route('profile.signature.show') }}" class="btn btn-xs btn-outline btn-warning gap-1 uppercase font-bold">{{ __('BUAT TTD') }}</a>`;
                        } else {
                            actionHtml = `<span class="text-xs text-base-content/40 italic uppercase">{{ __('BELUM ADA TTD') }}</span>`;
                        }
                    } else if (u.is_me) {
                        actionHtml = `<span class="badge badge-primary badge-xs uppercase font-bold">{{ __('Tanda Tangan Saya') }}</span>`;
                    } else if (u.is_available_to_replace) {
                        const creditLabel = u.available_credits > 1 ? ` (${u.available_credits}X)` : '';
                        actionHtml = `
                            <div class="flex items-center gap-1.5">
                                <span class="badge badge-success badge-xs font-bold uppercase">{{ __('DISETUJUI') }}${creditLabel}</span>
                                <button type="button" onclick="consumeSignatureReplacement(${u.request_id}, '${u.name}')" class="btn btn-xs btn-success text-white gap-1 shadow-sm font-bold uppercase">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    {{ __('GANTI TTD') }}
                                </button>
                            </div>
                        `;
                    } else if (u.request_status === 'pending') {
                        actionHtml = `
                            <span class="badge badge-warning badge-xs gap-1 py-2 px-2.5 font-bold uppercase">
                                ⏳ {{ __('MENUNGGU PERSETUJUAN') }}
                            </span>
                        `;
                    } else if (u.request_status === 'rejected') {
                        actionHtml = `
                            <div class="flex items-center gap-1.5">
                                <span class="badge badge-error badge-xs gap-1 py-1 px-2 font-bold uppercase text-white">
                                    ✕ {{ __('DITOLAK') }}
                                </span>
                            </div>
                        `;
                    } else if (u.request_status === 'used') {
                        actionHtml = `
                            <div class="flex items-center gap-1.5">
                                <span class="badge badge-ghost badge-xs text-base-content/60 gap-1 py-2 px-2 font-bold uppercase">
                                    ✓ {{ __('SUDAH DIGUNAKAN') }}
                                </span>
                            </div>
                        `;
                    } else {
                        actionHtml = `
                            <span class="text-xs text-base-content/60 font-semibold uppercase">{{ __('PILIH TTD') }}</span>
                        `;
                    }

                    const reasonHtml = (u.request_status === 'rejected' && u.rejected_reason)
                        ? `<div class="mt-1.5 p-2 rounded-lg bg-error/10 border border-error/20 text-xs text-error font-medium flex items-start gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                <div>
                                    <span class="font-bold">{{ __('Alasan:') }}</span>
                                    <span>${u.rejected_reason}</span>
                                </div>
                           </div>`
                        : '';

                    const signaturesHtml = (u.signatures && u.signatures.length > 0)
                        ? '<div class="border-t border-base-200 pt-2 mt-2 space-y-2">' +
                            u.signatures.map(sig => {
                                let sigActionHtml = '';
                                if (u.is_me) {
                                    sigActionHtml = '<button type="button" onclick="insertMySignature(' + sig.id + ')" class="btn btn-xs btn-primary gap-1 uppercase font-bold">{{ __("SISIPKAN") }}</button>';
                                } else {
                                    sigActionHtml = '<button type="button" onclick="' + (isPdfDocument ? `openPdfVisualPlacementModal(${u.id}, '${u.name}', 'signature', ${sig.id})` : `fetchUserSignatureAndInsert(${u.id}, &quot;${u.name}&quot;, ${sig.id})`) + '" class="btn btn-xs btn-outline btn-primary gap-1 uppercase font-bold">{{ __("MINTA TTD") }}</button>';
                                }
                                
                                const typeLabel = sig.type === 'original' ? 'ORIGINAL' : 'STEMPEL: ' + (sig.company_name || 'PERUSAHAAN');
                                return '<div class="flex items-center justify-between text-xs bg-base-100 p-2 rounded-lg border border-base-200">' +
                                    '<div><span class="font-bold">' + typeLabel + '</span></div>' +
                                    '<div>' + sigActionHtml + '</div>' +
                                '</div>';
                            }).join('') +
                          '</div>'
                        : '';

                    return `
                        <div class="flex flex-col p-2.5 rounded-xl border border-base-200 hover:bg-base-200/40 transition-all ${u.is_available_to_replace ? 'bg-success/5 border-success/30' : (u.request_status === 'rejected' ? 'bg-error/5 border-error/20' : '')}">
                            <div class="flex items-center justify-between">
                                <div class="pr-2">
                                    <div class="flex items-center gap-1.5 mb-0.5">
                                        <p class="text-sm font-semibold leading-tight text-base-content uppercase">${u.name}</p>
                                        ${u.is_me ? '<span class="badge badge-primary badge-xs uppercase font-bold">Saya</span>' : ''}
                                    </div>
                                    <p class="text-xs text-base-content/60 uppercase">${u.role} &bull; ${u.division}</p>
                                </div>
                                <div class="shrink-0">
                                    ${actionHtml}
                                </div>
                            </div>
                            ${reasonHtml}
                            ${signaturesHtml}
                        </div>
                    `;
                }).join('');

                if (isInitialEmpty) {
                    html += `
                        <div class="p-3 bg-base-200/40 rounded-xl text-center text-xs text-base-content/60 mt-3 border border-dashed border-base-300">
                            <span class="font-semibold text-base-content/80">{{ __('Tips:') }}</span>
                            {{ __('Ketik nama pengguna di kolom pencarian di atas lalu klik Cari untuk memilih tanda tangan pengguna lain.') }}
                        </div>
                    `;
                }

                list.innerHTML = html;
            }

            function consumeSignatureReplacement(requestId, userName) {
                document.getElementById('signature-users-modal').close();
                document.getElementById('signature-banner-alert').classList.add('hidden'); // Hide banner if replacing

                fetch(`/signature-requests/${requestId}/consume`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                .then(res => res.json().then(data => ({ status: res.status, data: data })))
                .then(response => {
                    const data = response.data;
                    if (response.status === 200 && data.success && data.url) {
                        if (data.is_pdf || isPdfDocument) {
                            showSignatureScreenAlert(
                                'TANDA TANGAN DIBUBUHKAN PADA PDF',
                                'TANDA TANGAN RESMI DARI ' + userName.toUpperCase() + ' TELAH BERHASIL DIBUBUHKAN PADA DOKUMEN PDF.',
                                true
                            );
                            setTimeout(() => window.location.reload(), 1200);
                            return;
                        }
                        
                        if (window.docEditor && window.docEditor.createConnector) {
                            try {
                                var connector = window.docEditor.createConnector();
                                var script = `
                                    var oDocument = Api.GetDocument();
                                    var aContentControls = oDocument.GetAllContentControls();
                                    var found = false;
                                    for (var i = 0; i < aContentControls.length; i++) {
                                        var label = aContentControls[i].GetLabel();
                                        if (label === "pending_sig_${requestId}") {
                                            aContentControls[i].RemoveAllElements();
                                            var oImage = Api.CreateImage("${data.url}", 140 * 36000, 140 * 36000);
                                            var oParagraph = Api.CreateParagraph();
                                            oParagraph.AddElement(oImage, 0);
                                            try {
                                                aContentControls[i].AddElement(oParagraph, 0);
                                            } catch(e) {
                                                aContentControls[i].AddElement(oImage, 0);
                                            }
                                            aContentControls[i].SetLabel("resolved_sig_${requestId}");
                                            found = true;
                                            break;
                                        }
                                    }
                                    return found;
                                `;
                                connector.callCommand(new Function(script), function(found) {
                                    if (found) {
                                        console.log("Replaced signature directly in existing control.");
                                    } else {
                                        console.log("Content control not found. Inserting at cursor.");
                                        insertImageIntoOnlyOffice(data.url, 140, 140, data.token || null);
                                    }
                                });
                            } catch(e) {
                                insertImageIntoOnlyOffice(data.url, 140, 140, data.token || null);
                            }
                        } else {
                            insertImageIntoOnlyOffice(data.url, 140, 140, data.token || null);
                        }
                        
                        showSignatureScreenAlert(
                            'TANDA TANGAN DISETUJUI & DISISIPKAN',
                            'TANDA TANGAN RESMI DARI ' + userName.toUpperCase() + ' TELAH BERHASIL DIMUAT DAN DISISIPKAN KE DALAM DOKUMEN.',
                            true
                        );
                    } else {
                        showSignatureScreenAlert(
                            'GAGAL MENGGANTI TANDA TANGAN',
                            data.message || 'GAGAL MEMPROSES PENGGANTIAN TANDA TANGAN.',
                            false
                        );
                    }
                })
                .catch(() => {
                    showSignatureScreenAlert(
                        'KESALAHAN SISTEM',
                        'GAGAL MENGHUBUNGI SERVER UNTUK MEMPROSES TANDA TANGAN.',
                        false
                    );
                });
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
                        if (response.status === 200 && data.success && data.url) {
                            if (!isPdfDocument) {
                                insertSignatureImage(data.url, userName, data.token || null, data.is_pending || false, data.request_id || null);
                            }
                            if (data.message) {
                                setTimeout(() => {
                                    showSignatureScreenAlert(
                                        'PERMINTAAN TANDA TANGAN DIKIRIM',
                                        data.message + (isPdfDocument ? ' KETIKA DISETUJUI, TANDA TANGAN AKAN OTOMATIS DIBUBUHKAN PADA DOKUMEN PDF.' : ''),
                                        true
                                    );
                                }, 400);
                            }
                        } else if (response.status === 403) {
                            showSignatureScreenAlert(
                                'AKSES DITOLAK',
                                data.message || 'ANDA TIDAK MEMILIKI IZIN UNTUK MENGGUNAKAN TANDA TANGAN PENGGUNA INI.',
                                false
                            );
                        } else {
                            showSignatureScreenAlert(
                                'TANDA TANGAN TIDAK DITEMUKAN',
                                data.message || ('TANDA TANGAN UNTUK ' + userName.toUpperCase() + ' TIDAK DITEMUKAN.'),
                                false
                            );
                        }
                    })
                    .catch(() => {
                        showSignatureScreenAlert(
                            'KESALAHAN SISTEM',
                            'GAGAL MENGAMBIL DATA TANDA TANGAN.',
                            false
                        );
                    });
            }

            // Real-time Echo Listener for signature approval and rejection notifications on screen
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof window.Echo !== 'undefined') {
                    window.Echo.private('App.Models.User.{{ auth()->id() }}')
                        .notification((notification) => {
                            if (notification.type === 'signature_request_approved' && notification.document_id == {{ $document->id }}) {
                                showSignatureScreenAlert(
                                    'TANDA TANGAN TELAH DISETUJUI',
                                    (notification.message || 'TANDA TANGAN TELAH DISETUJUI OLEH PEMILIK TTD. SILAKAN BUKA MENU TANDA TANGAN UNTUK MELAKUKAN REPLACE SIGNATURE.').toUpperCase(),
                                    true
                                );
                            } else if (notification.type === 'signature_request_rejected' && notification.document_id == {{ $document->id }}) {
                                showSignatureScreenAlert(
                                    'PERMINTAAN TANDA TANGAN DITOLAK',
                                    (notification.message || 'PERMINTAAN TANDA TANGAN TELAH DITOLAK OLEH PEMILIK TTD.').toUpperCase(),
                                    false
                                );
                            }
                        });
                }

                // Polling fallback: check for newly approved signatures every 15s
                // This works even without Reverb/Echo running
                setInterval(function() {
                    fetch('{{ route("signatures.users") }}?document_id={{ $document->id }}', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        const count = data.available_to_replace_count || 0;
                        const banner = document.getElementById('signature-banner-alert');
                        const bannerMsg = document.getElementById('signature-banner-message');
                        if (count > 0 && banner && bannerMsg) {
                            // Find the names of users with approved signatures
                            const names = (data.users || []).filter(u => u.is_available_to_replace).map(u => u.name.toUpperCase()).join(', ');
                            bannerMsg.textContent = 'TANDA TANGAN DARI ' + names + ' TELAH DISETUJUI. SILAKAN KLIK "GANTI TTD" UNTUK MENGGANTI PLACEHOLDER DENGAN TANDA TANGAN RESMI.';
                            banner.classList.remove('hidden');
                        }
                    })
                    .catch(() => {});
                }, 15000);
            });

            /**
             * "Selesai Edit" action: saves document changes from ONLYOFFICE and redirects to show page.
             */
            function finishEditingDocument() {
                if (typeof window.allowIntentionalLeave === 'function') {
                    window.allowIntentionalLeave();
                }

                const btn = document.getElementById('btn-selesai-edit');
                const spinner = document.getElementById('spinner-selesai-edit');
                const icon = document.getElementById('icon-selesai-edit');
                const text = document.getElementById('text-selesai-edit');
                const targetUrl = "{{ route('documents.show', ['document' => $document->id, 'saving' => 1]) }}";

                if (btn) btn.disabled = true;
                if (icon) icon.classList.add('hidden');
                if (spinner) spinner.classList.remove('hidden');
                if (text) text.textContent = "{{ __('MENYIMPAN...') }}";

                if (window.docEditor) {
                    try {
                        window.docEditor.destroyEditor();
                    } catch (e) {
                        console.warn('destroyEditor error:', e);
                    }
                }

                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 1000);
            }
        </script>
    @endpush

</x-app-layout>