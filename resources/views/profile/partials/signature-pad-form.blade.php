<section class="space-y-6">
    <header class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-medium text-base-content flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Tanda Tangan Digital (TTD)
            </h2>
            <p class="mt-1 text-sm text-base-content/60">
                Gambar dan simpan tanda tangan digital Anda untuk menandatangani dokumen di DokuFlow.
            </p>
        </div>
        {{-- Status badge: updated dynamically by JS after save/delete --}}
        <div id="ttd-status-badge">
            @if(auth()->user()->hasSignature())
                <span class="badge badge-success gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    TTD Aktif
                </span>
            @else
                <span class="badge badge-warning gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    Wajib Membuat TTD
                </span>
            @endif
        </div>
    </header>

    @if(session('warning'))
        <div class="alert alert-warning shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    {{-- Flash notification area --}}
    <div id="ttd-flash" class="hidden"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        {{-- ─── Left panel: Input Options (Canvas / Upload) ─── --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-base-content">Pilih Metode</label>
                <div class="tabs tabs-boxed p-1" id="signature-tabs">
                    <a class="tab tab-active tab-sm font-medium" data-target="draw">Gambar</a>
                    <a class="tab tab-sm font-medium" data-target="upload">Unggah</a>
                </div>
            </div>

            {{-- Draw Mode --}}
            <div id="signature-mode-draw" class="space-y-3">
                <div class="relative border-2 border-dashed border-base-300 rounded-xl bg-base-100 p-2 shadow-inner hover:border-primary/50 transition-colors">
                    <canvas id="signature-canvas" class="w-full h-52 touch-none cursor-crosshair rounded-lg bg-white"></canvas>
                    <div id="canvas-hint" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-base-content/30 transition-opacity duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span class="text-xs font-medium">Goreskan tanda tangan Anda di sini</span>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <button type="button" id="btn-clear-canvas" class="btn btn-ghost btn-sm text-base-content/70">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        Hapus Canvas
                    </button>
                    <button type="button" id="btn-save-signature" class="btn btn-primary btn-sm" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                        Simpan TTD
                    </button>
                </div>
            </div>

            {{-- Upload Mode --}}
            <div id="signature-mode-upload" class="space-y-3 hidden">
                <div class="relative border-2 border-dashed border-base-300 rounded-xl bg-base-100 p-6 flex flex-col items-center justify-center hover:border-primary/50 transition-colors h-[230px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-base-content/30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <p class="text-sm text-base-content/70 mb-4 text-center">Pilih file gambar (PNG, JPG) dengan latar transparan jika memungkinkan.</p>
                    <input type="file" id="signature-file-input" accept="image/png, image/jpeg, image/jpg" class="file-input file-input-bordered file-input-sm w-full max-w-xs" />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" id="btn-save-upload-signature" class="btn btn-primary btn-sm" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                        Simpan TTD
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Right panel: Saved signature preview ─── --}}
        <div class="space-y-3">
            <label class="block text-sm font-medium text-base-content">TTD Tersimpan Saat Ini</label>

            {{-- Container is pre-rendered by Blade; JS enriches it after load if needed --}}
            <div id="ttd-preview-panel"
                 class="border border-base-300 rounded-xl bg-base-200/50 p-4 min-h-[220px] flex flex-col items-center justify-center text-center relative"
                 data-store-url="{{ route('profile.signature.store') }}"
                 data-destroy-url="{{ route('profile.signature.destroy') }}"
                 data-show-url="{{ route('profile.signature.show') }}"
                 data-csrf="{{ csrf_token() }}">

                @if(auth()->user()->hasSignature())
                    {{-- Server-rendered on initial page load --}}
                    <div class="bg-white p-4 rounded-lg shadow-sm border border-base-300 w-full">
                        <img src="{{ asset('storage/' . auth()->user()->signature->file_path) }}"

                             alt="Tanda tangan {{ auth()->user()->name }}"
                             class="max-h-36 max-w-full object-contain mx-auto">
                    </div>
                    <p class="text-xs text-base-content/50 mt-3">
                        Disimpan pada: {{ auth()->user()->signature->updated_at->format('d M Y, H:i') }}
                    </p>
                    <form method="POST"
                          action="{{ route('profile.signature.destroy') }}"
                          class="mt-4 ttd-delete-form"
                          data-confirm="Apakah Anda yakin ingin menghapus tanda tangan ini?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-error btn-xs">
                            Hapus TTD Saat Ini
                        </button>
                    </form>
                @else
                    {{-- Empty state: server-rendered when no signature exists yet --}}
                    <div id="ttd-empty-state" class="text-base-content/40 space-y-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto stroke-current" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-sm font-medium">Belum Ada Tanda Tangan</p>
                        <p class="text-xs">Gunakan canvas di sebelah kiri untuk menggambar tanda tangan Anda.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Confirm Delete Modal --}}
    <x-modal name="confirm-delete-ttd" :show="false" maxWidth="sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-base-content">Confirm Signature Deletion</h3>
            <p class="mt-2 text-sm text-base-content/70">Are you sure you want to delete this signature? This action cannot be undone.</p>

            <div id="delete-modal-error" class="hidden mt-4 alert alert-error shadow-sm text-sm"></div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="btn btn-ghost" x-on:click="$dispatch('close-modal', 'confirm-delete-ttd')">
                    Cancel
                </button>
                <button type="button" class="btn btn-error" id="btn-confirm-delete-ttd">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    Delete
                </button>
            </div>
        </div>
    </x-modal>
</section>

{{-- Signature pad library (CDN) --}}
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
(function () {
    'use strict';

    /* ── Helpers ── */
    function formatDate(isoStr) {
        const d = new Date(isoStr);
        const dd  = String(d.getDate()).padStart(2, '0');
        // Use English short month names for consistency with requirement
        const mon = d.toLocaleString('en-US', { month: 'short' });
        const yr  = d.getFullYear();
        const hh  = String(d.getHours()).padStart(2, '0');
        const mm  = String(d.getMinutes()).padStart(2, '0');
        return `${dd} ${mon} ${yr}, ${hh}:${mm}`;
    }

    function showFlash(message, type = 'success') {
        const el = document.getElementById('ttd-flash');
        if (!el) return;
        el.innerHTML = `
            <div class="alert alert-${type} shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="${type === 'success'
                            ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
                            : 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'}" />
                </svg>
                <span>${message}</span>
            </div>`;
        el.classList.remove('hidden');
        clearTimeout(el._timer);
        el._timer = setTimeout(() => { el.classList.add('hidden'); el.innerHTML = ''; }, 4000);
    }

    /* ── Render helpers for the right panel ── */
    function renderSavedSignature(panel, url, updatedAtISO) {
        const destroyUrl = panel.dataset.destroyUrl;
        const csrf       = panel.dataset.csrf;
        const dateStr    = formatDate(updatedAtISO);

        panel.innerHTML = `
            <div class="bg-white p-4 rounded-lg shadow-sm border border-base-300 w-full">
                <img src="${url}?cb=${Date.now()}"
                     alt="Tanda tangan tersimpan"
                     class="max-h-36 max-w-full object-contain mx-auto">
            </div>
            <p class="text-xs text-base-content/50 mt-3">Disimpan pada: ${dateStr}</p>
            <form method="POST" action="${destroyUrl}"
                  class="mt-4 ttd-delete-form"
                  data-confirm="Apakah Anda yakin ingin menghapus tanda tangan ini?">
                <input type="hidden" name="_token"  value="${csrf}">
                <input type="hidden" name="_method" value="DELETE">
                <button type="submit" class="btn btn-outline btn-error btn-xs">
                    Hapus TTD Saat Ini
                </button>
            </form>`;
    }

    function renderEmptyState(panel) {
        panel.innerHTML = `
            <div class="text-base-content/40 space-y-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto stroke-current" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <p class="text-sm font-medium">Belum Ada Tanda Tangan</p>
                <p class="text-xs">Gunakan canvas di sebelah kiri untuk menggambar tanda tangan Anda.</p>
            </div>`;
    }

    function setBadge(hasSignature) {
        const badge = document.getElementById('ttd-status-badge');
        if (!badge) return;
        if (hasSignature) {
            badge.innerHTML = `
                <span class="badge badge-success gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    TTD Aktif
                </span>`;
        } else {
            badge.innerHTML = `
                <span class="badge badge-warning gap-1.5 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Wajib Membuat TTD
                </span>`;
        }
    }

    /* ── Main init ── */
    document.addEventListener('DOMContentLoaded', function () {

        const canvas  = document.getElementById('signature-canvas');
        if (!canvas) return;

        const hint       = document.getElementById('canvas-hint');
        const clearBtn   = document.getElementById('btn-clear-canvas');
        const saveBtn    = document.getElementById('btn-save-signature');
        const panel      = document.getElementById('ttd-preview-panel');

        const STORE_URL  = panel.dataset.storeUrl;
        const SHOW_URL   = panel.dataset.showUrl;
        const CSRF       = panel.dataset.csrf;

        const fileInput      = document.getElementById('signature-file-input');
        const saveUploadBtn  = document.getElementById('btn-save-upload-signature');
        let currentMode      = 'draw'; // 'draw' or 'upload'

        /* ── Tabs logic ── */
        const tabs = document.querySelectorAll('#signature-tabs .tab');
        const modeDraw = document.getElementById('signature-mode-draw');
        const modeUpload = document.getElementById('signature-mode-upload');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                tabs.forEach(t => t.classList.remove('tab-active'));
                e.target.classList.add('tab-active');
                currentMode = e.target.dataset.target;
                
                if (currentMode === 'draw') {
                    modeDraw.classList.remove('hidden');
                    modeUpload.classList.add('hidden');
                } else {
                    modeDraw.classList.add('hidden');
                    modeUpload.classList.remove('hidden');
                }
            });
        });

        /* ── Upload Input Logic ── */
        fileInput.addEventListener('change', () => {
            saveUploadBtn.disabled = !fileInput.files.length;
        });

        /* ── Canvas / SignaturePad setup ── */
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width  = canvas.offsetWidth  * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            signaturePad.clear();
        }

        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255,255,255,1)',
            penColor: '#000000',
            minWidth: 1.5,
            maxWidth: 3.5,
        });

        window.addEventListener('resize', resizeCanvas);
        resizeCanvas();

        signaturePad.addEventListener('beginStroke', () => {
            if (hint) hint.style.opacity = '0';
        });

        signaturePad.addEventListener('endStroke', () => {
            saveBtn.disabled = signaturePad.isEmpty();
        });

        clearBtn.addEventListener('click', () => {
            signaturePad.clear();
            if (hint) hint.style.opacity = '1';
            saveBtn.disabled = true;
        });

        /* ── Save signature via AJAX ── */
        async function saveSignature(isUpload) {
            const body = new FormData();
            body.append('_token', CSRF);

            if (isUpload) {
                if (!fileInput.files.length) {
                    showFlash('Silakan pilih file gambar terlebih dahulu.', 'warning');
                    return;
                }
                body.append('signature_image', fileInput.files[0]);
            } else {
                if (signaturePad.isEmpty()) {
                    showFlash('Canvas tanda tangan masih kosong.', 'warning');
                    return;
                }
                const dataUrl = signaturePad.toDataURL('image/png');
                body.append('signature_data', dataUrl);
            }

            const activeBtn = isUpload ? saveUploadBtn : saveBtn;
            const origHtml = activeBtn.innerHTML;
            activeBtn.disabled = true;
            activeBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> Menyimpan...';

            try {
                const res  = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const data = await res.json();

                if (data.success) {
                    renderSavedSignature(panel, data.url, data.updated_at);
                    setBadge(true);
                    if (!isUpload) {
                        signaturePad.clear();
                        if (hint) hint.style.opacity = '1';
                    } else {
                        fileInput.value = ''; // clear input
                    }
                    activeBtn.disabled = true;
                    showFlash('Tanda tangan berhasil disimpan!');
                } else {
                    showFlash(data.message || 'Gagal menyimpan tanda tangan.', 'error');
                    activeBtn.disabled = false;
                }
            } catch (err) {
                console.error('Save signature error:', err);
                showFlash('Terjadi kesalahan jaringan saat menyimpan.', 'error');
                activeBtn.disabled = false;
            } finally {
                activeBtn.innerHTML = origHtml;
            }
        }

        saveBtn.addEventListener('click', () => saveSignature(false));
        saveUploadBtn.addEventListener('click', () => saveSignature(true));

        /* ── Delete signature via AJAX (with Custom Modal) ── */
        let formToSubmit = null;

        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.classList.contains('ttd-delete-form')) return;
            e.preventDefault();

            formToSubmit = form;
            
            // Hide any previous errors
            const errorEl = document.getElementById('delete-modal-error');
            if (errorEl) {
                errorEl.classList.add('hidden');
                errorEl.innerHTML = '';
            }

            // Open the custom modal
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-delete-ttd' }));
        });

        const confirmDeleteBtn = document.getElementById('btn-confirm-delete-ttd');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', async function() {
                if (!formToSubmit) return;
                
                const form = formToSubmit;
                const errorEl = document.getElementById('delete-modal-error');
                const origBtnHtml = confirmDeleteBtn.innerHTML;
                
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Deleting...';
                if (errorEl) errorEl.classList.add('hidden');
                
                const body = new FormData(form);
                
                try {
                    const res = await fetch(form.action, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body,
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        renderEmptyState(panel);
                        setBadge(false);
                        showFlash('Tanda tangan berhasil dihapus.');
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'confirm-delete-ttd' }));
                    } else {
                        if (errorEl) {
                            errorEl.innerHTML = data.message || 'Gagal menghapus tanda tangan.';
                            errorEl.classList.remove('hidden');
                        } else {
                            showFlash(data.message || 'Gagal menghapus tanda tangan.', 'error');
                        }
                    }
                } catch (err) {
                    console.error('Delete signature error:', err);
                    if (errorEl) {
                        errorEl.innerHTML = 'Terjadi kesalahan jaringan saat menghapus.';
                        errorEl.classList.remove('hidden');
                    } else {
                        showFlash('Terjadi kesalahan jaringan saat menghapus.', 'error');
                    }
                } finally {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = origBtnHtml;
                }
            });
        }

        /* ── On page load: if the server already rendered a signature, nothing extra needed.
               But if the panel shows empty state, we still do a quick AJAX fetch to confirm. ── */
        if (panel.querySelector('#ttd-empty-state')) {
            fetch(SHOW_URL, { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (data && data.success && data.url) {
                        renderSavedSignature(panel, data.url, data.updated_at);
                        setBadge(true);
                    }
                })
                .catch(() => { /* silent — server-rendered state is the fallback */ });
        }
    });
}());
</script>
