<section class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-base-content flex items-center gap-2.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                {{ __('Tanda Tangan Digital (TTD)') }}
            </h2>
            <p class="mt-1 text-sm text-base-content/60">
                {{ __('Kelola tanda tangan original dan stempel perusahaan Anda.') }}
            </p>
        </div>
        <div id="ttd-status-badge">
            @if(auth()->user()->hasSignature())
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 text-white shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                    {{ __('TTD Original Aktif') }}
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-500 text-white shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    {{ __('Wajib Membuat TTD Original') }}
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

    <div id="ttd-flash" class="hidden"></div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        {{-- Left Form Panel --}}
        <div class="space-y-4 bg-base-100 p-6 rounded-2xl border border-base-300 shadow-sm relative">
            <h3 class="font-bold text-base text-base-content">{{ __('Tambah Tanda Tangan Baru') }}</h3>
            
            @php
                $hasSignature = auth()->user()->hasSignature();
                $contextService = app(\App\Services\CompanyContextService::class);
                $availableCompanies = $contextService->getAvailableCompanies(auth()->user());
                $activeCompanyId = (string) $contextService->getActiveCompanyId(auth()->user());
            @endphp

            <div class="form-control w-full space-y-1.5">
                <label class="text-xs font-semibold text-base-content/70">{{ __('Jenis Tanda Tangan') }}</label>
                <select id="signature-type-select" class="select select-bordered select-sm w-full font-medium rounded-lg text-sm bg-base-100">
                    @if($hasSignature)
                        <option value="company_stamp" selected>{{ __('Tanda Tangan + Stempel Perusahaan') }}</option>
                        <option value="original" disabled>{{ __('Tanda Tangan Original (Sudah Dibuat)') }}</option>
                    @else
                        <option value="original" selected>{{ __('Tanda Tangan Original') }}</option>
                    @endif
                </select>
            </div>

            <div class="form-control w-full space-y-1.5 {{ $hasSignature ? '' : 'hidden' }}" id="company-select-container">
                <label class="text-xs font-semibold text-base-content/70">{{ __('Pilih Perusahaan') }}</label>
                <select id="signature-company-select" class="select select-bordered select-sm w-full font-medium rounded-lg text-sm bg-base-100">
                    <option value="" disabled {{ empty($activeCompanyId) ? 'selected' : '' }}>{{ __('Pilih Perusahaan...') }}</option>
                    @foreach($availableCompanies as $company)
                        <option value="{{ $company->id }}" {{ (string)$company->id === $activeCompanyId ? 'selected' : '' }}>
                            {{ $company->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center justify-between pt-1">
                <label class="text-sm font-semibold text-base-content">{{ __('Pilih Metode') }}</label>
                <div class="flex items-center gap-4 text-sm font-medium" id="signature-tabs">
                    @if($hasSignature)
                        <button type="button" class="tab-btn pb-0.5 transition-colors opacity-40 cursor-not-allowed pointer-events-none text-base-content/40" data-target="draw" id="tab-draw" title="{{ __('Canvas dinonaktifkan karena TTD original sudah dibuat') }}">
                            {{ __('Gambar') }}
                        </button>
                        <button type="button" class="tab-btn font-semibold text-primary border-b-2 border-primary pb-0.5 transition-colors cursor-pointer" data-target="upload" id="tab-upload">
                            {{ __('Unggah') }}
                        </button>
                    @else
                        <button type="button" class="tab-btn font-semibold text-primary border-b-2 border-primary pb-0.5 transition-colors cursor-pointer" data-target="draw" id="tab-draw">
                            {{ __('Gambar') }}
                        </button>
                        <button type="button" class="tab-btn text-base-content/50 hover:text-base-content pb-0.5 transition-colors cursor-pointer" data-target="upload" id="tab-upload">
                            {{ __('Unggah') }}
                        </button>
                    @endif
                </div>
            </div>

            {{-- Draw Mode --}}
            <div id="signature-mode-draw" class="space-y-3 {{ $hasSignature ? 'hidden' : '' }}">
                <div class="relative border-2 border-dashed border-base-300 rounded-xl bg-base-100 p-2 shadow-inner hover:border-primary/50 transition-colors">
                    <canvas id="signature-canvas" class="w-full h-52 touch-none rounded-lg bg-white {{ $hasSignature ? 'pointer-events-none cursor-not-allowed opacity-60' : 'cursor-crosshair' }}"></canvas>
                    
                    @if($hasSignature)
                        <div id="canvas-locked-overlay" class="absolute inset-0 bg-base-100/90 backdrop-blur-xs flex flex-col items-center justify-center text-center p-6 rounded-xl z-20">
                            <div class="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center text-base-content/60 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-base-content/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h4 class="font-bold text-sm text-base-content">{{ __('Canvas Dinonaktifkan') }}</h4>
                            <p class="text-xs text-base-content/60 mt-1 max-w-xs leading-relaxed">
                                {{ __('Tanda tangan sudah tersimpan di profil Anda. Canvas tidak dapat digunakan lagi. Silakan hapus tanda tangan tersimpan di samping terlebih dahulu jika ingin menggambar ulang.') }}
                            </p>
                        </div>
                    @else
                        <div id="canvas-hint" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center text-base-content/30 transition-opacity duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span class="text-xs font-medium">{{ __('Goreskan tanda tangan Anda di sini') }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-2 pt-1">
                    <button type="button" id="btn-clear-canvas" class="btn btn-ghost btn-sm text-base-content/70 gap-1.5" {{ $hasSignature ? 'disabled' : '' }}>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        {{ __('Hapus Canvas') }}
                    </button>
                    <button type="button" id="btn-save-signature" class="btn btn-primary btn-sm gap-1.5 px-4 rounded-lg shadow-sm" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                        {{ __('Simpan TTD') }}
                    </button>
                </div>
            </div>

            {{-- Upload Mode --}}
            <div id="signature-mode-upload" class="space-y-3 {{ $hasSignature ? '' : 'hidden' }}">
                <div class="relative border-2 border-dashed border-base-300 rounded-xl bg-base-100 p-6 flex flex-col items-center justify-center hover:border-primary/50 transition-colors h-[230px]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-base-content/30 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <p class="text-sm text-base-content/70 mb-4 text-center max-w-xs leading-relaxed">{{ __('Pilih file gambar (PNG, JPG) dengan latar transparan jika memungkinkan.') }}</p>
                    <input type="file" id="signature-file-input" accept="image/png, image/jpeg, image/jpg" class="file-input file-input-bordered file-input-sm w-full max-w-xs rounded-lg" />
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" id="btn-save-upload-signature" class="btn btn-primary btn-sm gap-1.5 px-4 rounded-lg shadow-sm" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                        {{ __('Simpan TTD') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Right Saved Signatures List Panel --}}
        <div class="space-y-4">
            <h3 class="font-bold text-base text-base-content">{{ __('TTD Tersimpan Saat Ini') }}</h3>

            <div id="ttd-preview-panel"
                 class="space-y-4"
                 data-store-url="{{ route('profile.signature.store') }}"
                 data-destroy-url="{{ url('profile/signature') }}"
                 data-csrf="{{ csrf_token() }}">
                
                @foreach(auth()->user()->signatures()->with('company')->latest()->get() as $sig)
                    <div class="border border-base-300 rounded-2xl bg-base-100 p-4 relative flex flex-col items-center shadow-xs hover:border-base-content/20 transition-all">
                        <div class="w-full flex justify-end mb-2">
                            @if($sig->type === 'original')
                                <span class="px-2.5 py-0.5 text-xs font-semibold rounded border border-sky-400 text-sky-500 bg-sky-50/50 dark:bg-sky-950/20">
                                    {{ __('Original') }}
                                </span>
                            @else
                                <span class="px-2.5 py-0.5 text-xs font-semibold rounded border border-emerald-500 text-emerald-600 bg-emerald-50/50 dark:bg-emerald-950/20">
                                    {{ $sig->company?->name ?? __('Perusahaan') }}
                                </span>
                            @endif
                        </div>
                        <div class="bg-white p-3 rounded-lg border border-base-200 w-full flex items-center justify-center min-h-[96px]">
                            <img src="{{ asset('storage/' . $sig->file_path) }}" alt="TTD" class="max-h-20 max-w-full object-contain mx-auto">
                        </div>
                        <div class="flex justify-between items-center w-full mt-3 pt-1">
                            <p class="text-xs text-base-content/50 font-medium">{{ $sig->updated_at->format('d M Y, H:i') }}</p>
                            <button type="button" class="btn btn-outline btn-error btn-xs rounded-md px-3 btn-delete-sig" data-id="{{ $sig->id }}">
                                {{ __('Hapus') }}
                            </button>
                        </div>
                    </div>
                @endforeach

                @if(auth()->user()->signatures()->count() === 0)
                    <div id="ttd-empty-state" class="text-base-content/40 space-y-2 border border-dashed border-base-300 rounded-2xl bg-base-100/50 p-8 flex flex-col items-center justify-center text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto stroke-current" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Belum Ada Tanda Tangan') }}</p>
                        <p class="text-xs">{{ __('Gunakan panel sebelah kiri untuk membuat tanda tangan Anda.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Delete Modal --}}
    <x-modal name="confirm-delete-ttd" :show="false" maxWidth="sm">
        <div class="p-4 sm:p-6">
            <h3 class="text-lg font-semibold text-base-content">{{ __('Confirm Signature Deletion') }}</h3>
            <p class="mt-2 text-sm text-base-content/70">{{ __('Are you sure you want to delete this signature? This action cannot be undone.') }}</p>

            <div id="delete-modal-error" class="hidden mt-4 alert alert-error shadow-sm text-sm"></div>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <button type="button" class="btn btn-ghost" x-on:click="$dispatch('close-modal', 'confirm-delete-ttd')">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-error" id="btn-confirm-delete-ttd">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    {{ __('Delete') }}
                </button>
            </div>
        </div>
    </x-modal>

    {{-- Signature Size Alert Modal --}}
    <x-modal name="signature-size-modal" :show="false" maxWidth="sm">
        <div class="p-5 sm:p-6 text-center space-y-4">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-error/10 text-error border border-error/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-base-content">{{ __('Ukuran Tanda Tangan Terlalu Besar') }}</h3>
                <p class="mt-2 text-sm text-base-content/70 leading-relaxed" id="signature-size-modal-msg">
                    {{ __('Ukuran file tanda tangan melebihi batas maksimal 2MB. Silakan pilih atau unggah file dengan ukuran yang lebih kecil (maks. 2MB).') }}
                </p>
            </div>
            <div class="pt-2 flex justify-center">
                <button type="button" class="btn btn-primary btn-sm px-6 rounded-xl" x-on:click="$dispatch('close-modal', 'signature-size-modal')">
                    {{ __('Mengerti') }}
                </button>
            </div>
        </div>
    </x-modal>
</section>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<script>
(function () {
    'use strict';

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

    function showSizeAlertModal(msg) {
        const msgEl = document.getElementById('signature-size-modal-msg');
        if (msgEl && msg) {
            msgEl.textContent = msg;
        }
        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'signature-size-modal' }));
    }

    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('signature-canvas');
        if (!canvas) return;

        const hint = document.getElementById('canvas-hint');
        const clearBtn = document.getElementById('btn-clear-canvas');
        const saveBtn = document.getElementById('btn-save-signature');
        const panel = document.getElementById('ttd-preview-panel');

        const STORE_URL = panel.dataset.storeUrl;
        const DESTROY_URL = panel.dataset.destroyUrl;
        const CSRF = panel.dataset.csrf;

        const fileInput = document.getElementById('signature-file-input');
        const saveUploadBtn = document.getElementById('btn-save-upload-signature');
        
        const typeSelect = document.getElementById('signature-type-select');
        const companyContainer = document.getElementById('company-select-container');
        const companySelect = document.getElementById('signature-company-select');
        
        const MAX_FILE_SIZE = 2 * 1024 * 1024; // 2MB
        const hasOriginalSignature = @json(auth()->user()->hasSignature());

        let currentMode = hasOriginalSignature ? 'upload' : 'draw';

        const tabs = document.querySelectorAll('#signature-tabs .tab-btn');
        const modeDraw = document.getElementById('signature-mode-draw');
        const modeUpload = document.getElementById('signature-mode-upload');
        const tabDraw = document.getElementById('tab-draw');
        const tabUpload = document.getElementById('tab-upload');

        function setTab(target) {
            if (hasOriginalSignature && target === 'draw') return;
            currentMode = target;
            if (target === 'draw') {
                tabDraw.classList.add('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                tabDraw.classList.remove('text-base-content/50');
                tabUpload.classList.remove('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                tabUpload.classList.add('text-base-content/50');
                
                modeDraw.classList.remove('hidden');
                modeUpload.classList.add('hidden');
                setTimeout(() => resizeCanvas(true), 50);
            } else {
                tabUpload.classList.add('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                tabUpload.classList.remove('text-base-content/50');
                tabDraw.classList.remove('text-primary', 'border-b-2', 'border-primary', 'font-semibold');
                tabDraw.classList.add('text-base-content/50');

                modeDraw.classList.add('hidden');
                modeUpload.classList.remove('hidden');
            }
        }

        function updateFormUI() {
            if (hasOriginalSignature) {
                tabDraw.classList.add('opacity-40', 'pointer-events-none', 'cursor-not-allowed');
                if (clearBtn) clearBtn.disabled = true;
                if (saveBtn) saveBtn.disabled = true;
            }

            if (typeSelect.value === 'company_stamp') {
                if (companyContainer) companyContainer.classList.remove('hidden');
                setTab('upload');
                tabDraw.classList.add('opacity-40', 'pointer-events-none', 'cursor-not-allowed');
            } else {
                if (companyContainer) companyContainer.classList.add('hidden');
                if (!hasOriginalSignature) {
                    tabDraw.classList.remove('opacity-40', 'pointer-events-none', 'cursor-not-allowed');
                }
            }
        }

        typeSelect.addEventListener('change', updateFormUI);
        updateFormUI();

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                if (e.currentTarget.classList.contains('pointer-events-none')) return;
                setTab(e.currentTarget.dataset.target);
            });
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                if (file.size > MAX_FILE_SIZE) {
                    fileInput.value = '';
                    saveUploadBtn.disabled = true;
                    showSizeAlertModal(@json(__('Ukuran file tanda tangan melebihi batas maksimal 2MB. Silakan pilih atau unggah file dengan ukuran yang lebih kecil (maks. 2MB).')));
                    return;
                }
                saveUploadBtn.disabled = false;
            } else {
                saveUploadBtn.disabled = true;
            }
        });

        function resizeCanvas(preserveData = false) {
            if (hasOriginalSignature) return;
            if (!modeDraw || modeDraw.classList.contains('hidden')) return;
            const rect = canvas.getBoundingClientRect();
            if (!rect.width || !rect.height) return;

            let data = null;
            if (preserveData && signaturePad && !signaturePad.isEmpty()) {
                data = signaturePad.toData();
            }

            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width  = canvas.offsetWidth  * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            const ctx = canvas.getContext('2d');
            ctx.scale(ratio, ratio);

            if (data && signaturePad) {
                signaturePad.fromData(data);
            } else if (signaturePad) {
                signaturePad.clear();
                if (hint) hint.style.opacity = '1';
                if (saveBtn) saveBtn.disabled = true;
            }
        }

        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255,255,255,1)',
            penColor: '#000000',
            minWidth: 1.5,
            maxWidth: 3.5,
        });

        if (hasOriginalSignature) {
            signaturePad.off();
            canvas.classList.add('pointer-events-none', 'cursor-not-allowed', 'opacity-60');
            if (clearBtn) clearBtn.disabled = true;
            if (saveBtn) saveBtn.disabled = true;
        } else {
            window.addEventListener('resize', () => resizeCanvas(true));
            resizeCanvas(false);

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
        }

        async function saveSignature(isUpload) {
            const body = new FormData();
            body.append('_token', CSRF);
            body.append('type', typeSelect.value);

            if (typeSelect.value === 'company_stamp') {
                if (!companySelect.value) {
                    showFlash(@json(__('Silakan pilih perusahaan.')), 'warning');
                    return;
                }
                body.append('company_id', companySelect.value);
            }

            if (isUpload) {
                if (!fileInput.files.length) {
                    showFlash(@json(__('Silakan pilih file gambar terlebih dahulu.')), 'warning');
                    return;
                }
                const file = fileInput.files[0];
                if (file.size > MAX_FILE_SIZE) {
                    fileInput.value = '';
                    saveUploadBtn.disabled = true;
                    showSizeAlertModal(@json(__('Ukuran file tanda tangan melebihi batas maksimal 2MB. Silakan pilih atau unggah file dengan ukuran yang lebih kecil (maks. 2MB).')));
                    return;
                }
                body.append('signature_image', file);
            } else {
                if (signaturePad.isEmpty()) {
                    showFlash(@json(__('Canvas tanda tangan masih kosong.')), 'warning');
                    return;
                }
                const dataUrl = signaturePad.toDataURL('image/png');
                body.append('signature_data', dataUrl);
            }

            const activeBtn = isUpload ? saveUploadBtn : saveBtn;
            const origHtml = activeBtn.innerHTML;
            activeBtn.disabled = true;
            activeBtn.innerHTML = '<span class="loading loading-spinner loading-sm"></span> ' + @json(__('Menyimpan...'));

            try {
                const res  = await fetch(STORE_URL, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });

                if (res.status === 413) {
                    showSizeAlertModal(@json(__('Ukuran file tanda tangan melebihi batas maksimal yang diizinkan server.')));
                    activeBtn.disabled = false;
                    return;
                }

                const data = await res.json();

                if (data.success) {
                    showFlash(@json(__('Tanda tangan berhasil disimpan!')));
                    if (!isUpload) {
                        signaturePad.off();
                        canvas.classList.add('pointer-events-none', 'cursor-not-allowed', 'opacity-60');
                        if (clearBtn) clearBtn.disabled = true;
                        if (saveBtn) saveBtn.disabled = true;
                    }
                    setTimeout(() => window.location.reload(), 1000); // Reload to reflect new list & state
                } else {
                    const isSizeError = data.errors?.signature_image || 
                        (data.message && (
                            data.message.toLowerCase().includes('2048') || 
                            data.message.toLowerCase().includes('2mb') || 
                            data.message.toLowerCase().includes('terlalu besar') || 
                            data.message.toLowerCase().includes('lebih dari 2mb') || 
                            data.message.toLowerCase().includes('greater than') || 
                            data.message.toLowerCase().includes('too large')
                        ));

                    if (isSizeError) {
                        showSizeAlertModal(data.message || @json(__('Ukuran file tanda tangan melebihi batas maksimal 2MB. Silakan pilih atau unggah file dengan ukuran yang lebih kecil (maks. 2MB).')));
                    } else {
                        showFlash(data.message || @json(__('Gagal menyimpan tanda tangan.')), 'error');
                    }
                    activeBtn.disabled = false;
                }
            } catch (err) {
                console.error('Save signature error:', err);
                showFlash(@json(__('Terjadi kesalahan jaringan saat menyimpan.')), 'error');
                activeBtn.disabled = false;
            } finally {
                activeBtn.innerHTML = origHtml;
            }
        }

        saveBtn.addEventListener('click', () => saveSignature(false));
        saveUploadBtn.addEventListener('click', () => saveSignature(true));

        let currentDeleteId = null;

        document.querySelectorAll('.btn-delete-sig').forEach(btn => {
            btn.addEventListener('click', (e) => {
                currentDeleteId = e.target.dataset.id;
                const errorEl = document.getElementById('delete-modal-error');
                if (errorEl) {
                    errorEl.classList.add('hidden');
                    errorEl.innerHTML = '';
                }
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'confirm-delete-ttd' }));
            });
        });

        const confirmDeleteBtn = document.getElementById('btn-confirm-delete-ttd');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', async function() {
                if (!currentDeleteId) return;
                
                const errorEl = document.getElementById('delete-modal-error');
                const origBtnHtml = confirmDeleteBtn.innerHTML;
                
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> ' + @json(__('Deleting...'));
                if (errorEl) errorEl.classList.add('hidden');
                
                const body = new FormData();
                body.append('_token', CSRF);
                body.append('_method', 'DELETE');
                
                try {
                    const res = await fetch(DESTROY_URL + '/' + currentDeleteId, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body,
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        showFlash(@json(__('Tanda tangan berhasil dihapus.')));
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        if (errorEl) {
                            errorEl.innerHTML = data.message || @json(__('Gagal menghapus tanda tangan.'));
                            errorEl.classList.remove('hidden');
                        } else {
                            showFlash(data.message || @json(__('Gagal menghapus tanda tangan.')), 'error');
                        }
                    }
                } catch (err) {
                    console.error('Delete signature error:', err);
                    if (errorEl) {
                        errorEl.innerHTML = @json(__('Terjadi kesalahan jaringan saat menghapus.'));
                        errorEl.classList.remove('hidden');
                    } else {
                        showFlash(@json(__('Terjadi kesalahan jaringan saat menghapus.')), 'error');
                    }
                } finally {
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = origBtnHtml;
                }
            });
        }
    });
}());
</script>
