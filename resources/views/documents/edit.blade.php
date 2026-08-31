<x-app-layout>

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
    @endphp

    <div class="w-full flex-1 flex flex-col min-h-0 bg-base-200/50">

        {{-- Top Navigation & Action Bar --}}
        <div class="bg-base-100 border-b border-base-300 px-3 sm:px-4 py-2.5 flex flex-col md:flex-row md:items-center md:justify-between gap-2.5 sm:gap-3 shrink-0 shadow-xs">
            {{-- Left on desktop / Top on mobile: Back & Full Document Information (NEVER CUT) --}}
            <div class="flex items-start sm:items-center gap-2.5 min-w-0 flex-1">
                <a href="{{ route('documents.show', $document) }}" 
                   class="btn btn-ghost btn-sm btn-square shrink-0 text-base-content/70 hover:text-base-content mt-0.5 sm:mt-0" 
                   title="{{ __('Kembali ke Detail Dokumen') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2 min-w-0">
                        <h1 class="text-sm sm:text-base font-bold text-base-content break-words" title="{{ $document->title }}">
                            {{ $document->title }}
                        </h1>
                        @if($document->document_number)
                            <span class="badge badge-ghost badge-xs sm:badge-sm shrink-0 font-medium self-start sm:self-auto font-mono">
                                {{ $document->document_number }}
                            </span>
                        @endif
                    </div>
                    <div class="flex items-center flex-wrap gap-1.5 text-[11px] text-base-content/60 leading-none mt-1">
                        <span>{{ __('ONLYOFFICE Document Editor') }} &bull;</span>
                        <span class="font-medium">v{{ $version->version_number }}</span>
                        @if($pending)
                            <span class="badge badge-warning badge-xs">{{ __('Pending') }}</span>
                        @elseif($hasDraftOnly)
                            <span class="badge badge-info badge-xs">{{ __('Draft') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right on desktop / Below document info on mobile: Action Icons & Buttons --}}
            <div class="flex items-center gap-1 sm:gap-1.5 self-end md:self-center ml-auto shrink-0 w-full md:w-auto justify-end pt-1 md:pt-0 border-t border-base-200/60 md:border-t-0">
                {{-- Download DOCX --}}
                <a href="{{ route('documents.download', $document) }}"
                   class="btn btn-ghost btn-sm gap-1.5 px-2.5 sm:px-3 text-base-content/70 hover:text-base-content"
                   title="{{ __('Download DOCX') }}"
                   aria-label="{{ __('Download DOCX') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden xl:inline">{{ __('Download DOCX') }}</span>
                </a>

                {{-- Discard Changes --}}
                @if($document->currentVersion)
                    @can('update', $document)
                        <button type="button"
                                class="btn btn-outline btn-error btn-sm gap-1.5 px-2.5 sm:px-3"
                                x-on:click="$dispatch('open-modal', 'confirm-discard-version-{{ $document->id }}')"
                                title="{{ __('Discard Changes') }}"
                                aria-label="{{ __('Discard Changes') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span class="hidden lg:inline">{{ __('Discard Changes') }}</span>
                        </button>
                    @endcan
                @else
                    @can('delete', $document)
                        <button type="button"
                                class="btn btn-outline btn-error btn-sm gap-1.5 px-2.5 sm:px-3"
                                x-on:click="$dispatch('open-modal', 'confirm-discard-{{ $document->id }}')"
                                title="{{ __('Discard') }}"
                                aria-label="{{ __('Discard') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span class="hidden lg:inline">{{ __('Discard') }}</span>
                        </button>
                    @endcan
                @endif

                {{-- Selesai Edit --}}
                <button type="button"
                        id="btn-selesai-edit"
                        onclick="finishEditingDocument()"
                        class="btn btn-primary btn-sm px-3 sm:px-4 gap-1.5 font-medium shadow-xs"
                        title="{{ __('Selesai Edit') }}"
                        aria-label="{{ __('Selesai Edit') }}">
                    <svg id="icon-selesai-edit" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="spinner-selesai-edit" class="loading loading-spinner loading-xs hidden"></span>
                    <span id="text-selesai-edit" class="hidden sm:inline">{{ __('Selesai Edit') }}</span>
                </button>

                <div class="h-4 w-px bg-base-300 mx-0.5 sm:mx-1"></div>

                {{-- Quick Actions: Sisip QR Code --}}
                <button type="button"
                        onclick="insertQrCodeToEditor()"
                        class="btn btn-sm btn-outline btn-primary gap-1.5 px-2.5 sm:px-3 font-medium"
                        title="{{ __('Sisipkan QR Code Verifikasi Dokumen') }}"
                        aria-label="{{ __('Sisip QR Code') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <span class="hidden lg:inline">{{ __('Sisip QR Code') }}</span>
                </button>

                {{-- Quick Actions: Sisip TTD --}}
                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-sm btn-outline btn-secondary gap-1.5 px-2.5 sm:px-3 font-medium cursor-pointer" title="{{ __('Sisipkan Tanda Tangan Digital') }}" aria-label="{{ __('Sisip TTD') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span class="hidden lg:inline">{{ __('Sisip TTD') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-60 hidden lg:inline shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </label>
                    <ul tabindex="0" class="dropdown-content z-30 menu p-2 shadow-xl bg-base-100 rounded-2xl w-64 border border-base-300 mt-1">
                        <li class="menu-title text-xs font-semibold px-2 py-1 text-base-content/60">{{ __('Pilih Tanda Tangan') }}</li>
                        @if($userSignatureUrl || $userSignatureDataUri)
                            <li>
                                <button type="button" onclick="insertMySignature()" class="flex items-center justify-between text-sm py-2">
                                    <span class="font-medium text-primary">{{ __('Tanda Tangan Saya') }}</span>
                                    <span class="badge badge-primary badge-xs">{{ __('Tersimpan') }}</span>
                                </button>
                            </li>
                        @else
                            <li>
                                <a href="{{ route('profile.signature.show') }}" class="text-xs text-warning flex items-center gap-1.5 py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>{{ __('Buat TTD Saya di Profil') }}</span>
                                </a>
                            </li>
                        @endif
                        <div class="divider my-1"></div>
                        <li>
                            <button type="button" onclick="openSignatureSelectorModal()" class="text-xs text-base-content/80 flex items-center gap-1.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <span>{{ __('Pilih Pengguna Lain...') }}</span>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Pending Alert if exists --}}
        @if($pending)
            <div class="px-4 py-2 bg-warning/10 border-b border-warning/20 text-xs text-warning-content flex items-center justify-between">
                <span>{{ __('Terdapat versi pending (v:version) yang menunggu review. Setiap perubahan yang Anda simpan akan memperbarui versi pending ini.', ['version' => $pending->version_number]) }}</span>
            </div>
        @endif

        {{-- Signature Approval Banner (server-side rendered on page load) --}}
        @if(!empty($pendingApprovalBanner))
            <div id="signature-banner-alert" class="px-4 py-3 bg-success/20 border-b border-success/30 text-xs text-success-content flex flex-wrap sm:flex-nowrap items-center justify-between gap-3 z-10 transition-all duration-300">
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
            <div id="signature-banner-alert" class="hidden px-4 py-3 bg-success/20 border-b border-success/30 text-xs text-success-content flex flex-wrap sm:flex-nowrap items-center justify-between gap-3 z-10 transition-all duration-300">
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

        {{-- ONLYOFFICE Editor Viewport --}}
        <div class="flex-1 w-full h-full relative overflow-hidden bg-base-100">
            <div id="onlyoffice-editor-container" class="w-full h-full"></div>
            
            <div id="onlyoffice-fallback" class="hidden absolute inset-0 flex flex-col items-center justify-center p-6 bg-base-100/95 z-20 text-center">
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
                        <a href="{{ route('documents.download', $document) }}" class="btn btn-outline btn-sm">{{ __('Download DOCX') }}</a>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Signature User Selector Modal --}}
    <dialog id="signature-users-modal" class="modal">
        <div class="modal-box max-w-lg">
            <div class="flex items-center justify-between border-b border-base-200 pb-3 mb-3">
                <div>
                    <h3 class="font-bold text-base text-base-content">{{ __('PILIH & GANTI TANDA TANGAN') }}</h3>
                    <p class="text-xs text-base-content/60">{{ __('KELOLA PERMINTAAN DAN PENGGANTIAN TANDA TANGAN PENGGUNA PADA DOKUMEN INI.') }}</p>
                </div>
                <div id="signature-available-count-badge" class="badge badge-success badge-sm gap-1 hidden font-bold">
                    <span id="signature-available-count-text">0</span> {{ __('TERSEDIA') }}
                </div>
            </div>

            {{-- Signature Search Input --}}
            <div class="relative mb-3">
                <input type="text" id="signature-search-input" oninput="filterSignatureUsers(this.value)" placeholder="{{ __('Cari tanda tangan (nama, peran, divisi)...') }}" class="input input-bordered input-sm w-full pl-9 pr-8 bg-base-100 focus:border-primary focus:ring-1 focus:ring-primary text-xs sm:text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <button type="button" id="signature-search-clear" onclick="clearSignatureSearch()" class="hidden absolute right-2.5 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content text-xs p-1">✕</button>
            </div>

            <div id="signature-users-list" class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
                <div class="flex justify-center py-6 text-sm text-base-content/60">
                    <span class="loading loading-spinner loading-sm mr-2"></span> {{ __('MEMUAT PENGGUNA...') }}
                </div>
            </div>

            <div class="modal-action border-t border-base-200 pt-3 mt-3">
                <form method="dialog">
                    <button class="btn btn-ghost btn-sm">{{ __('TUTUP') }}</button>
                </form>
            </div>
        </div>
    </dialog>

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
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"
                onerror="document.getElementById('onlyoffice-fallback').classList.remove('hidden');"></script>
        <script>
            const qrCodeUrl = @json($qrCodeUrl ?? null);
            const qrCodeToken = @json($qrCodeToken ?? null);
            const mySignatureUrl = @json($userSignatureUrl ?? null);
            const mySignatureToken = @json($userSignatureToken ?? null);

            document.addEventListener('DOMContentLoaded', function() {
                if (typeof DocsAPI === 'undefined') {
                    document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                    return;
                }

                try {
                    const config = @json($onlyOfficeConfig);
                    // Always use desktop type to bypass ONLYOFFICE Community Edition mobile license restriction
                    config.type = 'desktop';
                    config.editorConfig = config.editorConfig || {};
                    config.editorConfig.mode = 'edit';
                    config.editorConfig.customization = config.editorConfig.customization || {};
                    config.editorConfig.customization.zoom = 100;
                    config.editorConfig.customization.compactHeader = true;
                    config.editorConfig.customization.mobile = { force: false };
                    
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
             * Insert image directly into ONLYOFFICE document editor via DocsAPI insertImage
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
                    const payload = {
                        c: "add",
                        images: [
                            {
                                fileType: "png",
                                url: imageUrl,
                                width: widthPx,
                                height: heightPx
                            }
                        ],
                        fileType: "png",
                        url: imageUrl,
                        width: widthPx,
                        height: heightPx
                    };

                    if (token) {
                        payload.token = token;
                    }

                    window.docEditor.insertImage(payload);
                } catch (err) {
                    console.warn('insertImage error:', err);
                    showSignatureScreenAlert('PERINGATAN', 'TIDAK DAPAT MENYISIPKAN GAMBAR SECARA OTOMATIS. SILAKAN GUNAKAN MENU INSERT -> PICTURE PADA TOOLBAR ONLYOFFICE.', false);
                }
            }

            function insertQrCodeToEditor() {
                if (!qrCodeUrl) {
                    showSignatureScreenAlert('PERINGATAN', 'QR CODE DOKUMEN TIDAK TERSEDIA.', false);
                    return;
                }
                insertImageIntoOnlyOffice(qrCodeUrl, 140, 140, qrCodeToken);
            }

            function insertMySignature() {
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

                        renderSignatureUsersList(allSignatureUsersData);
                    })
                    .catch(err => {
                        list.innerHTML = '<p class="text-sm text-error text-center py-4 uppercase">{{ __("GAGAL MEMUAT PENGGUNA.") }}</p>';
                    });
            }

            function filterSignatureUsers(query) {
                const clearBtn = document.getElementById('signature-search-clear');
                if (clearBtn) {
                    clearBtn.classList.toggle('hidden', !query);
                }
                const q = (query || '').toLowerCase().trim();
                const filtered = allSignatureUsersData.filter(u => {
                    if (!q) return true;
                    return (u.name && u.name.toLowerCase().includes(q)) ||
                           (u.email && u.email.toLowerCase().includes(q)) ||
                           (u.role && u.role.toLowerCase().includes(q)) ||
                           (u.division && u.division.toLowerCase().includes(q)) ||
                           (u.rejected_reason && u.rejected_reason.toLowerCase().includes(q));
                });
                renderSignatureUsersList(filtered);
            }

            function clearSignatureSearch() {
                const searchInput = document.getElementById('signature-search-input');
                if (searchInput) {
                    searchInput.value = '';
                    filterSignatureUsers('');
                }
            }

            function renderSignatureUsersList(users) {
                const list = document.getElementById('signature-users-list');
                if (!list) return;

                if (!users || users.length === 0) {
                    list.innerHTML = '<p class="text-sm text-base-content/60 text-center py-6 uppercase">{{ __("TIDAK ADA PENGGUNA DITEMUKAN.") }}</p>';
                    return;
                }

                list.innerHTML = users.map(u => {
                    let actionHtml = '';

                    if (!u.has_signature) {
                        actionHtml = `<span class="text-xs text-base-content/40 italic uppercase">{{ __('BELUM ADA TTD') }}</span>`;
                    } else if (u.is_me) {
                        actionHtml = `<button type="button" onclick="insertMySignature()" class="btn btn-xs btn-primary gap-1 uppercase font-bold">{{ __('SISIPKAN TTD SAYA') }}</button>`;
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
                                    ✕ {{ __('Ditolak') }}
                                </span>
                                <button type="button" onclick="fetchUserSignatureAndInsert(${u.id}, '${u.name}')" class="btn btn-xs btn-outline btn-error gap-1 uppercase font-bold">
                                    {{ __('MINTA LAGI') }}
                                </button>
                            </div>
                        `;
                    } else if (u.request_status === 'used') {
                        actionHtml = `
                            <div class="flex items-center gap-1.5">
                                <span class="badge badge-ghost badge-xs text-base-content/60 gap-1 py-2 px-2 font-bold uppercase">
                                    ✓ {{ __('SUDAH DIGUNAKAN') }}
                                </span>
                                <button type="button" onclick="fetchUserSignatureAndInsert(${u.id}, '${u.name}')" class="btn btn-xs btn-outline btn-primary gap-1 uppercase font-bold">
                                    {{ __('MINTA LAGI') }}
                                </button>
                            </div>
                        `;
                    } else {
                        actionHtml = `
                            <button type="button" onclick="fetchUserSignatureAndInsert(${u.id}, '${u.name}')" class="btn btn-xs btn-outline btn-primary gap-1 uppercase font-bold">
                                {{ __('MINTA TTD') }}
                            </button>
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
                        </div>
                    `;
                }).join('');
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

            function fetchUserSignatureAndInsert(userId, userName) {
                document.getElementById('signature-users-modal').close();
                fetch(`/profile/signature?user_id=${userId}&document_id={{ $document->id }}`)
                    .then(res => res.json().then(data => ({ status: res.status, data: data })))
                    .then(response => {
                        const data = response.data;
                        if (response.status === 200 && data.success && data.url) {
                            insertSignatureImage(data.url, userName, data.token || null, data.is_pending || false, data.request_id || null);
                            if (data.message) {
                                setTimeout(() => {
                                    showSignatureScreenAlert(
                                        'PERMINTAAN TANDA TANGAN DIKIRIM',
                                        data.message,
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