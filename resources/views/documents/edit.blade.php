<x-app-layout>

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

    @php
        $pending = $document->versions->first(fn($v) => $v->status === 'pending' && !$v->discarded_at);
        $hasDraftOnly = !$pending && !$document->currentVersion;
    @endphp

    <div class="h-[calc(100vh-4rem)] flex flex-col bg-base-200/50">

        {{-- Top Navigation & Action Bar --}}
        <div class="bg-base-100 border-b border-base-300 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('documents.show', $document) }}" class="btn btn-ghost btn-sm btn-square" title="{{ __('Kembali') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold truncate">{{ $document->title }}</h1>
                        @if($document->document_number)
                            <span class="badge badge-ghost badge-sm hidden sm:inline-flex">
                                {{ $document->document_number }}
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-base-content/60">
                        {{ __('ONLYOFFICE Document Editor') }} &bull; v{{ $version->version_number }}
                        @if($pending)
                            <span class="badge badge-warning badge-xs ml-1">{{ __('Pending Review') }}</span>
                        @elseif($hasDraftOnly)
                            <span class="badge badge-info badge-xs ml-1">{{ __('Draft') }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Quick Actions: QR Code & Signature --}}
                <button type="button"
                        onclick="insertQrCodeToEditor()"
                        class="btn btn-sm btn-outline btn-primary gap-1.5"
                        title="{{ __('Sisipkan QR Code Verifikasi Dokumen') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <span>{{ __('Sisip QR Code') }}</span>
                </button>

                <div class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-sm btn-outline btn-secondary gap-1.5 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>{{ __('Sisip TTD') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </label>
                    <ul tabindex="0" class="dropdown-content z-30 menu p-2 shadow-lg bg-base-100 rounded-box w-64 border border-base-300 mt-1">
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

                <div class="h-4 w-px bg-base-300 mx-1 hidden sm:block"></div>

                <a href="{{ route('documents.download', $document) }}" class="btn btn-ghost btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    {{ __('Download DOCX') }}
                </a>

                @if($document->currentVersion)
                    @can('update', $document)
                        <button type="button" class="btn btn-outline btn-error btn-sm" x-on:click="$dispatch('open-modal', 'confirm-discard-version-{{ $document->id }}')">
                            {{ __('Discard Changes') }}
                        </button>
                    @endcan
                @else
                    @can('delete', $document)
                        <button type="button" class="btn btn-outline btn-error btn-sm" x-on:click="$dispatch('open-modal', 'confirm-discard-{{ $document->id }}')">
                            {{ __('Discard') }}
                        </button>
                    @endcan
                @endif

                <button type="button"
                        id="btn-selesai-edit"
                        onclick="finishEditingDocument()"
                        class="btn btn-primary btn-sm px-5 gap-2">
                    <svg id="icon-selesai-edit" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="spinner-selesai-edit" class="loading loading-spinner loading-xs hidden"></span>
                    <span id="text-selesai-edit">{{ __('Selesai Edit') }}</span>
                </button>
            </div>
        </div>

        {{-- Pending Alert if exists --}}
        @if($pending)
            <div class="px-4 py-2 bg-warning/10 border-b border-warning/20 text-xs text-warning-content flex items-center justify-between">
                <span>{{ __('Terdapat versi pending (v:version) yang menunggu review. Setiap perubahan yang Anda simpan akan memperbarui versi pending ini.', ['version' => $pending->version_number]) }}</span>
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
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-base mb-3">{{ __('Pilih Tanda Tangan Pengguna') }}</h3>
            <div id="signature-users-list" class="space-y-2 max-h-64 overflow-y-auto pr-1">
                <div class="flex justify-center py-6 text-sm text-base-content/60">
                    <span class="loading loading-spinner loading-sm mr-2"></span> {{ __('Memuat pengguna...') }}
                </div>
            </div>
            <div class="modal-action">
                <form method="dialog">
                    <button class="btn btn-ghost btn-sm">{{ __('Tutup') }}</button>
                </form>
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
                    
                    config.events = config.events || {};
                    config.events.onAppReady = function() {
                        console.log('ONLYOFFICE editor ready');
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

            /**
             * Insert image directly into ONLYOFFICE document editor via DocsAPI insertImage
             */
            function insertImageIntoOnlyOffice(imageUrl, widthPx = 140, heightPx = 140, token = null) {
                if (!window.docEditor) {
                    alert('{{ __("Editor belum selesai dimuat. Tunggu sebentar...") }}');
                    return;
                }

                if (!imageUrl) {
                    alert('{{ __("URL gambar tidak valid.") }}');
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
                    alert('{{ __("Tidak dapat menyisipkan gambar secara otomatis. Silakan gunakan menu Insert -> Picture pada toolbar ONLYOFFICE.") }}');
                }
            }

            function insertQrCodeToEditor() {
                if (!qrCodeUrl) {
                    alert('{{ __("QR Code dokumen tidak tersedia.") }}');
                    return;
                }
                insertImageIntoOnlyOffice(qrCodeUrl, 140, 140, qrCodeToken);
            }

            function insertMySignature() {
                if (!mySignatureUrl) {
                    alert('{{ __("Anda belum memiliki tanda tangan tersimpan.") }}');
                    return;
                }
                insertImageIntoOnlyOffice(mySignatureUrl, 140, 140, mySignatureToken);
            }

            function insertSignatureImage(signatureUrl, userName, token = null) {
                if (!signatureUrl) {
                    alert('Pengguna ' + userName + ' belum memiliki tanda tangan tersimpan.');
                    return;
                }
                insertImageIntoOnlyOffice(signatureUrl, 140, 140, token);
            }

            function openSignatureSelectorModal() {
                const modal = document.getElementById('signature-users-modal');
                const list = document.getElementById('signature-users-list');
                modal.showModal();

                fetch('{{ route("signatures.users") }}')
                    .then(res => res.json())
                    .then(data => {
                        const users = data.users || [];
                        if (users.length === 0) {
                            list.innerHTML = '<p class="text-sm text-base-content/60 text-center py-4">{{ __("Tidak ada pengguna ditemukan.") }}</p>';
                            return;
                        }

                        list.innerHTML = users.map(u => `
                            <div class="flex items-center justify-between p-2 rounded-lg border border-base-200 hover:bg-base-200/50 transition-colors">
                                <div>
                                    <p class="text-sm font-medium leading-none mb-1">${u.name} ${u.is_me ? '<span class="badge badge-primary badge-xs">Saya</span>' : ''}</p>
                                    <p class="text-xs text-base-content/60">${u.role} &bull; ${u.division}</p>
                                </div>
                                <div>
                                    ${u.has_signature 
                                        ? `<button type="button" onclick="fetchUserSignatureAndInsert(${u.id}, '${u.name}')" class="btn btn-xs btn-primary">{{ __('Sisipkan') }}</button>`
                                        : `<span class="text-xs text-base-content/40 italic">{{ __('Belum ada TTD') }}</span>`
                                    }
                                </div>
                            </div>
                        `).join('');
                    })
                    .catch(err => {
                        list.innerHTML = '<p class="text-sm text-error text-center py-4">{{ __("Gagal memuat pengguna.") }}</p>';
                    });
            }

            function fetchUserSignatureAndInsert(userId, userName) {
                document.getElementById('signature-users-modal').close();
                fetch(`/profile/signature?user_id=${userId}&document_id={{ $document->id }}`)
                    .then(res => res.json().then(data => ({ status: res.status, data: data })))
                    .then(response => {
                        const data = response.data;
                        if (response.status === 200 && data.success && data.url) {
                            insertSignatureImage(data.url, userName, data.token || null);
                            if (data.message) {
                                setTimeout(() => alert(data.message), 500);
                            }
                        } else if (response.status === 403) {
                            alert(data.message || 'Anda tidak memiliki izin untuk menggunakan tanda tangan pengguna ini.');
                        } else {
                            alert(data.message || 'Tanda tangan untuk ' + userName + ' tidak ditemukan.');
                        }
                    })
                    .catch(() => {
                        alert('Gagal mengambil data tanda tangan.');
                    });
            }

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
                if (text) text.textContent = "{{ __('Menyimpan...') }}";

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