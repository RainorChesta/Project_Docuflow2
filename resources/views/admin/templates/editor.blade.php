<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <span>{{ __('Edit Template: ') }}</span>
            <span class="font-normal text-base-content/70">{{ $template->title }}</span>
        </div>
    </x-slot>

    <div class="pb-6">
        <div class="max-w-7xl mx-auto w-full">
            <div class="card bg-base-100 border border-base-300 shadow-sm mb-6">
                <div class="card-body p-0">
                    <div class="p-3 sm:p-4">
                        {{-- Top Navigation & Action Bar inside Card Header (Responsive on mobile & tablet) --}}
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3 mb-3 px-1 sm:px-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost btn-xs btn-square shrink-0 text-base-content/70 hover:text-base-content" title="{{ __('Kembali') }}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                </a>
                                <div class="text-xs sm:text-sm text-base-content/60 flex items-center gap-1.5 sm:gap-2 flex-wrap min-w-0">
                                    <span class="font-medium text-base-content truncate max-w-[150px] sm:max-w-xs" title="{{ $template->title }}">{{ $template->title }}</span>
                                    <span class="badge badge-ghost badge-xs shrink-0">{{ __('Template') }}</span>
                                     <span class="hidden" id="applied-corp-softfile-badge"><strong id="applied-corp-softfile-name">{{ $template->corporateSoftFile?->title }}</strong></span>
                                 </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 ml-auto sm:ml-0">
                                {{-- Corporate Soft File Dropdown Selector for Admin --}}
                                <div class="dropdown dropdown-end dropdown-bottom z-30" id="admin-template-sf-dropdown">
                                    <label tabindex="0" class="btn btn-xs {{ $template->corporateSoftFile ? 'btn-accent text-accent-content font-bold shadow-xs' : 'btn-outline btn-accent font-medium' }} gap-1.5 shrink-0 cursor-pointer" title="{{ __('Pilih Soft File Korporat') }}" id="template-softfile-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                        <span class="hidden sm:inline" id="template-softfile-btn-text">{{ $template->corporateSoftFile ? 'Kop: ' . \Illuminate\Support\Str::limit($template->corporateSoftFile->title, 14) : __('Soft File Korporat') }}</span>
                                        <span class="sm:hidden" id="template-softfile-btn-mobile-text">{{ $template->corporateSoftFile ? 'Kop: ' . \Illuminate\Support\Str::limit($template->corporateSoftFile->title, 8) : __('Kop') }}</span>
                                        @if($template->corporateSoftFile)
                                            <span class="badge badge-xs bg-white text-accent font-extrabold px-1.5 py-0 shadow-2xs" id="template-softfile-btn-badge">✓ Terpilih</span>
                                        @else
                                            <span class="badge badge-xs bg-white text-accent font-extrabold px-1.5 py-0 shadow-2xs hidden" id="template-softfile-btn-badge">✓ Terpilih</span>
                                        @endif
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </label>
                                    <div tabindex="0" class="dropdown-content z-40 p-3 shadow-2xl bg-base-100/98 backdrop-blur-md border border-base-300 rounded-2xl w-80 sm:w-[420px] max-h-[480px] flex flex-col gap-2 mt-2">
                                            <div class="px-1 py-1 border-b border-base-200/80 flex items-center justify-between shrink-0">
                                                <div>
                                                    <span class="text-xs font-bold text-base-content uppercase tracking-wider block">{{ __('Soft File Korporat') }}</span>
                                                    <span class="text-[11px] text-base-content/50 block">{{ __('Pilih kop surat resmi untuk template ini') }}</span>
                                                </div>
                                                <span class="badge badge-accent badge-xs font-bold">{{ isset($corporateSoftFiles) ? $corporateSoftFiles->count() : 0 }}</span>
                                            </div>

                                            {{-- Banner Active Soft File Indicator --}}
                                            <div id="applied-template-sf-banner" class="px-3 py-2 rounded-xl bg-accent/10 border border-accent/30 text-accent flex items-center justify-between gap-2 shrink-0 {{ $template->corporateSoftFile ? '' : 'hidden' }}">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <div class="w-6 h-6 rounded-md bg-accent text-accent-content flex items-center justify-center shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <span class="text-[9px] uppercase font-extrabold tracking-wider opacity-70 block">{{ __('Kop Surat Aktif:') }}</span>
                                                        <span class="text-xs font-bold truncate block text-base-content" id="applied-template-sf-banner-title">{{ $template->corporateSoftFile?->title }}</span>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" 
                                                            onclick="event.stopPropagation(); openRemoveTemplateSoftFileModal();" 
                                                            class="btn btn-ghost btn-xs text-error hover:bg-error/15 px-2 py-1 h-auto min-h-0 rounded-lg flex items-center gap-1 font-bold border border-error/20" 
                                                            title="{{ __('Batalkan / Hapus Pilihan Kop Surat') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                        <span class="text-[10px]">{{ __('Hapus Kop') }}</span>
                                                    </button>
                                                </div>
                                            </div>

                                            @if(isset($corporateSoftFiles) && $corporateSoftFiles->isNotEmpty())
                                                @if($corporateSoftFiles->count() > 3)
                                                    <div class="relative shrink-0">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-base-content/40 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                                        </svg>
                                                        <input type="text" 
                                                               oninput="filterTemplateSoftFileItems(this.value)" 
                                                               placeholder="{{ __('Cari soft file...') }}" 
                                                               class="input input-bordered input-xs w-full pl-8 pr-2.5 rounded-lg text-xs bg-base-200/50 focus:bg-base-100">
                                                    </div>
                                                @endif
                                                <ul class="flex-1 overflow-y-auto overflow-x-hidden space-y-1.5 pr-1 max-h-[260px] custom-scrollbar" id="corporate-softfile-list-items">
                                                    @foreach($corporateSoftFiles as $sf)
                                                        @php
                                                            $isApplied = ($template->corporate_soft_file_id == $sf->id);
                                                            $hasAnyApplied = !empty($template->corporate_soft_file_id);
                                                        @endphp
                                                        <li class="corp-softfile-item" data-id="{{ $sf->id }}" data-raw-title="{{ $sf->title }}" data-title="{{ strtolower($sf->title . ' ' . $sf->file_original_name) }}">
                                                            <div class="corp-sf-container flex items-center justify-between gap-2.5 p-2 rounded-xl transition-all {{ $isApplied ? 'bg-accent/15 border border-accent/40 text-accent shadow-2xs ring-1 ring-accent/20' : 'bg-base-200/40 hover:bg-base-200/80 border border-base-200/60' }}">
                                                                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                                                    <div class="corp-sf-icon w-8 h-8 rounded-lg {{ $isApplied ? 'bg-accent text-accent-content font-bold shadow-xs' : 'bg-base-300 text-base-content/70' }} flex items-center justify-center shrink-0">
                                                                        @if($isApplied)
                                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                                            </svg>
                                                                        @else
                                                                            @if($sf->isPdf())
                                                                                <span class="text-[9px] font-extrabold text-error">PDF</span>
                                                                            @elseif($sf->isImage())
                                                                                <span class="text-[9px] font-extrabold text-warning">IMG</span>
                                                                            @else
                                                                                <span class="text-[9px] font-extrabold text-primary">DOCX</span>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                    <div class="min-w-0 flex-1 cursor-pointer" 
                                                                          ondblclick="event.stopPropagation(); executeApplyTemplateSoftFileDirect({{ $sf->id }}, @json($sf->title));"
                                                                          onclick="openTemplateSoftFileConfirmModal({{ $sf->id }}, @json($sf->title), @json(app(\App\Services\OnlyOfficeService::class)->getCorporateSoftFileUrl($sf)), {{ $sf->isImage() ? 'true' : 'false' }})">
                                                                         <div class="flex items-center gap-1.5">
                                                                             <span class="corp-sf-title font-bold text-xs {{ $isApplied ? 'text-accent' : 'text-base-content' }} truncate">{{ $sf->title }}</span>
                                                                             <span class="badge badge-outline badge-xs text-[9px] opacity-70 shrink-0 uppercase">{{ $sf->file_type ?? 'DOCX' }}</span>
                                                                         </div>
                                                                         @if($sf->description)
                                                                             <div class="text-[11px] text-base-content/60 truncate mt-0.5">{{ $sf->description }}</div>
                                                                         @endif
                                                                         <div class="text-[10px] text-base-content/40 truncate mt-0.5">
                                                                             {{ $sf->file_original_name }}
                                                                         </div>
                                                                    </div>
                                                                </div>
                                                                <div class="corp-sf-action shrink-0">
                                                                    @if($isApplied)
                                                                        <span class="badge badge-accent badge-sm font-bold text-[10px] gap-1 px-2.5 py-1 shadow-2xs cursor-default">
                                                                            ✓ Digunakan
                                                                        </span>
                                                                    @else
                                                                        <button type="button" 
                                                                                data-id="{{ $sf->id }}"
                                                                                data-title="{{ $sf->title }}"
                                                                                onclick="event.stopPropagation(); executeApplyTemplateSoftFileDirect({{ $sf->id }}, @json($sf->title));" 
                                                                                class="btn-apply-template-sf-direct btn {{ $hasAnyApplied ? 'btn-outline btn-accent' : 'btn-accent' }} btn-xs rounded-lg font-bold shadow-xs px-2.5"
                                                                                title="{{ $hasAnyApplied ? __('Ganti kop aktif template dengan kop surat ini') : __('Langsung terapkan kop surat ini ke template') }}">
                                                                            {{ $hasAnyApplied ? __('Ganti Kop') : __('Terapkan') }}
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <div class="py-4 text-center text-xs text-base-content/60 space-y-2">
                                                    <p>{{ __('Belum ada soft file korporat aktif.') }}</p>
                                                    <a href="{{ route('admin.corporate-soft-files.create') }}" class="btn btn-accent btn-xs rounded-lg gap-1">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                                        {{ __('Tambah Soft File') }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <button type="button"
                                        id="btn-selesai-edit"
                                        onclick="finishEditingTemplate()"
                                        class="btn btn-primary btn-xs gap-1 font-medium shadow-xs shrink-0"
                                        title="{{ __('Selesai Edit') }}">
                                    <svg id="icon-selesai-edit" xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span id="spinner-selesai-edit" class="loading loading-spinner loading-xs hidden"></span>
                                    <span id="text-selesai-edit">{{ __('Selesai Edit') }}</span>
                                </button>
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
                                        <br>{{ __('Pastikan container Docker ONLYOFFICE sedang berjalan.') }}
                                    </p>
                                    <div class="flex justify-center gap-2">
                                        <button onclick="window.location.reload()" class="btn btn-primary btn-sm">{{ __('Muat Ulang Halaman') }}</button>
                                        <a href="{{ route('admin.templates.index') }}" class="btn btn-outline btn-sm">{{ __('Kembali') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Corporate Soft File Confirmation Modal for Admin Template Editor --}}
    <dialog id="template-softfile-modal" class="modal modal-bottom sm:modal-middle backdrop-blur-xs">
        <div class="modal-box p-6 rounded-3xl border border-base-300/80 shadow-2xl bg-base-100 max-w-md w-full text-center">
            <div class="mx-auto mb-3 flex items-center justify-center h-12 w-12 rounded-full bg-accent/20 text-accent">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h3 id="template-sf-modal-title" class="font-bold text-lg text-base-content uppercase leading-tight mb-2">{{ __('TERAPKAN SOFT FILE KORPORAT') }}</h3>
            <p id="template-sf-modal-message" class="text-xs sm:text-sm text-base-content/70 leading-relaxed break-words mb-4"></p>
            
            <div class="flex flex-col gap-2 pt-3 border-t border-base-200">
                <button type="button" id="btn-apply-template-sf-master" onclick="executeApplyTemplateSoftFileMaster()" class="btn btn-accent btn-sm rounded-xl font-bold uppercase gap-1.5 shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    <span>{{ __('Terapkan Sebagai Master Template') }}</span>
                </button>
                <button type="button" id="btn-insert-template-sf-connector" onclick="executeInsertTemplateSoftFileConnector()" class="btn btn-outline btn-accent btn-sm rounded-xl font-semibold gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    <span>{{ __('Sisipkan Konten / Kop Dokumen') }}</span>
                </button>
                <form method="dialog" class="mt-1">
                    <button class="btn btn-ghost btn-xs text-base-content/50 hover:text-base-content">{{ __('Batal') }}</button>
                </form>
            </div>
        </div>
    </dialog>

    {{-- Corporate Soft File Remove Confirmation Modal for Admin Template Editor --}}
    <dialog id="remove-template-softfile-modal" class="modal modal-bottom sm:modal-middle backdrop-blur-xs">
        <div class="modal-box p-6 rounded-3xl border border-base-300/80 shadow-2xl bg-base-100 max-w-md w-full text-center">
            <div class="mx-auto mb-3 flex items-center justify-center h-12 w-12 rounded-full bg-error/20 text-error">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="font-bold text-lg text-base-content uppercase leading-tight mb-2">{{ __('BATALKAN PILIHAN KOP SURAT') }}</h3>
            <p id="remove-template-sf-modal-message" class="text-xs sm:text-sm text-base-content/70 leading-relaxed break-words mb-4">
                {{ __('Apakah Anda yakin ingin membatalkan dan melepas penggunaan kop surat dari template ini?') }}
            </p>
            
            <div class="flex flex-col gap-2 pt-3 border-t border-base-200">
                <button type="button" id="btn-confirm-remove-template-sf" onclick="executeRemoveTemplateSoftFile()" class="btn btn-error btn-sm rounded-xl font-bold uppercase gap-1.5 shadow-xs text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    <span>{{ __('Ya, Batalkan / Hapus Kop') }}</span>
                </button>
                <form method="dialog" class="mt-1">
                    <button class="btn btn-ghost btn-xs text-base-content/50 hover:text-base-content">{{ __('Kembali') }}</button>
                </form>
            </div>
        </div>
    </dialog>

    {{-- Floating Live Toast Notification inside Template Editor --}}
    <div id="template-live-toast" class="fixed top-6 right-6 z-50 transform transition-all duration-300 translate-y-[-150%] opacity-0 pointer-events-none max-w-sm w-full">
        <div id="template-live-toast-box" class="flex items-start gap-3 p-4 rounded-2xl bg-base-100/95 backdrop-blur-md border border-success/30 shadow-2xl shadow-success/10 text-base-content pointer-events-auto">
            <div id="template-live-toast-icon" class="h-8 w-8 rounded-full bg-success/20 text-success flex items-center justify-center shrink-0 mt-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <div class="flex-1 min-w-0 pr-1">
                <h4 id="template-live-toast-title" class="font-bold text-xs uppercase text-success tracking-wide">PEMBERITAHUAN</h4>
                <p id="template-live-toast-message" class="text-xs text-base-content/80 mt-0.5 leading-relaxed break-words"></p>
            </div>
            <button type="button" onclick="hideTemplateLiveToast()" class="text-base-content/40 hover:text-base-content text-xs p-1">✕</button>
        </div>
    </div>

    @push('scripts')
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js?v=9.4.0-f4-v2"
                onerror="document.getElementById('onlyoffice-fallback').classList.remove('hidden');"></script>
        <script>
            const mainScrollContainer = document.querySelector('main') || document.documentElement;

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
                    config.editorConfig.compactHeader = true;
                    config.editorConfig.customization.autoFocus = false;
                    config.editorConfig.customization.mobile = { force: false };

                    if (isMobileOrTablet) {
                        // Responsive mode for small/shrinking viewports:
                        config.editorConfig.customization.compactToolbar = true;
                        config.editorConfig.customization.leftMenu = false;
                        config.editorConfig.customization.rightMenu = false;
                        config.editorConfig.customization.ruler = false;
                        config.editorConfig.customization.toolbarHideFileName = true;
                        config.editorConfig.customization.zoom = -2; // Fit to Width
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
                        if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                        setTimeout(() => {
                            if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                        }, 50);
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

                        const btnSelesai = document.getElementById('btn-selesai-edit');
                        const textSelesai = document.getElementById('text-selesai-edit');
                        
                        if (btnSelesai && textSelesai) {
                            textSelesai.textContent = "{{ __('Selesai Edit') }}";
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

            // --- Template Corporate Soft File Logic ---
            let activeTemplateSoftFile = null;
            let templateToastTimer = null;

            function hideTemplateLiveToast() {
                const toast = document.getElementById('template-live-toast');
                if (toast) {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('translate-y-[-150%]', 'opacity-0');
                }
                if (templateToastTimer) {
                    clearTimeout(templateToastTimer);
                    templateToastTimer = null;
                }
            }

            function showTemplateScreenAlert(title, message, isSuccess = true) {
                const toast = document.getElementById('template-live-toast');
                const toastTitle = document.getElementById('template-live-toast-title');
                const toastMsg = document.getElementById('template-live-toast-message');
                const toastIcon = document.getElementById('template-live-toast-icon');
                const toastBox = document.getElementById('template-live-toast-box');

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

                    if (templateToastTimer) clearTimeout(templateToastTimer);
                    templateToastTimer = setTimeout(() => {
                        hideTemplateLiveToast();
                    }, 5000);
                }
            }

            // --- Corporate Soft File Selection & Application Logic ---
            let activeTemplateSoftFile = null;
            let currentAppliedTemplateSoftFileId = {{ $template->corporate_soft_file_id ? $template->corporate_soft_file_id : 'null' }};
            let currentAppliedTemplateSoftFileTitle = @json($template->corporateSoftFile?->title);

            function escapeHtml(str) {
                if (!str) return '';
                return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }

            function openTemplateSoftFileConfirmModal(id, title, fileUrl, isImage = false) {
                activeTemplateSoftFile = { id: id, title: title, fileUrl: fileUrl, isImage: isImage };
                const modal = document.getElementById('template-softfile-modal');
                const titleEl = document.getElementById('template-sf-modal-title');
                const msgEl = document.getElementById('template-sf-modal-message');
                const btnMaster = document.getElementById('btn-apply-template-sf-master');

                const isSwitching = currentAppliedTemplateSoftFileId && currentAppliedTemplateSoftFileId != id;

                if (titleEl) {
                    titleEl.textContent = isSwitching ? 'GANTI KOP SURAT' : 'TERAPKAN SOFT FILE KORPORAT';
                }
                if (msgEl) {
                    if (isSwitching) {
                        msgEl.innerHTML = `Template saat ini menggunakan kop <strong>${escapeHtml(currentAppliedTemplateSoftFileTitle)}</strong>.<br>Apakah Anda ingin menggantinya dengan kop <strong>${escapeHtml(title)}</strong>?`;
                    } else {
                        msgEl.innerHTML = `Anda memilih <strong>${escapeHtml(title)}</strong>.<br><span class="text-xs text-base-content/60 mt-1 block">Pilih metode penerapan soft file korporat ini ke template:</span>`;
                    }
                }
                if (btnMaster) {
                    const span = btnMaster.querySelector('span');
                    if (span) {
                        span.textContent = isSwitching ? 'Ganti Sebagai Master Template' : 'Terapkan Sebagai Master Template';
                    }
                }

                if (modal) modal.showModal();
            }

            function openRemoveTemplateSoftFileModal() {
                const modal = document.getElementById('remove-template-softfile-modal');
                const msgEl = document.getElementById('remove-template-sf-modal-message');
                if (msgEl) {
                    msgEl.innerHTML = `Apakah Anda yakin ingin membatalkan/melepas penggunaan kop surat <strong>${escapeHtml(currentAppliedTemplateSoftFileTitle || '')}</strong> dari template ini?`;
                }
                if (modal && typeof modal.showModal === 'function') modal.showModal();
            }

            function executeRemoveTemplateSoftFile() {
                const modal = document.getElementById('remove-template-softfile-modal');
                if (modal && typeof modal.close === 'function') modal.close();

                showTemplateScreenAlert('SEDANG MEMBATALKAN', 'Membatalkan pilihan kop surat dari template...', true);

                const removeUrl = "{{ route('admin.templates.corporate-soft-files.remove', $template) }}";
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                fetch(removeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        resetUsedTemplateSoftFileUI();
                        showTemplateScreenAlert('BERHASIL DIBATALKAN', data.message || 'Pilihan soft file korporat berhasil dibatalkan.', true);

                        window._hasSessionChanges = false;
                        if (typeof window.allowIntentionalLeave === 'function') {
                            window.allowIntentionalLeave();
                        }
                        if (window.docEditor) {
                            try { window.docEditor.destroyEditor(); } catch(e) {}
                        }

                        setTimeout(() => {
                            const target = data.redirect_url || window.location.href.split('?')[0];
                            window.location.href = target + (target.includes('?') ? '&' : '?') + 't=' + Date.now();
                        }, 600);
                    } else {
                        showTemplateScreenAlert('GAGAL MEMBATALKAN', data.error || 'Gagal membatalkan kop surat dari template.', false);
                    }
                })
                .catch(err => {
                    console.error('remove template soft file error:', err);
                    showTemplateScreenAlert('KESALAHAN SISTEM', 'Terjadi kesalahan saat membatalkan kop surat.', false);
                });
            }

            function resetUsedTemplateSoftFileUI() {
                currentAppliedTemplateSoftFileId = null;
                currentAppliedTemplateSoftFileTitle = null;

                // 1. Header info badge
                const badge = document.getElementById('applied-corp-softfile-badge');
                if (badge) badge.classList.add('hidden');

                // 2. Dropdown button at top
                const btn = document.getElementById('template-softfile-btn');
                const btnText = document.getElementById('template-softfile-btn-text');
                const btnMobileText = document.getElementById('template-softfile-btn-mobile-text');
                const btnBadge = document.getElementById('template-softfile-btn-badge');
                if (btn) {
                    btn.className = "btn btn-xs btn-outline btn-accent font-medium gap-1.5 shrink-0 cursor-pointer";
                }
                if (btnText) {
                    btnText.innerText = "{{ __('Soft File Korporat') }}";
                }
                if (btnMobileText) {
                    btnMobileText.innerText = "{{ __('Kop') }}";
                }
                if (btnBadge) {
                    btnBadge.classList.add('hidden');
                }

                // 3. Dropdown banner
                const banner = document.getElementById('applied-template-sf-banner');
                if (banner) {
                    banner.classList.add('hidden');
                }

                // 4. Dropdown items list
                document.querySelectorAll('#admin-template-sf-dropdown .corp-softfile-item').forEach(item => {
                    const itemId = item.getAttribute('data-id');
                    const itemRawTitle = item.getAttribute('data-raw-title') || '';
                    const container = item.querySelector('.corp-sf-container') || item.querySelector('div.flex');
                    const iconBox = item.querySelector('.corp-sf-icon') || item.querySelector('.w-7.h-7') || item.querySelector('.shrink-0');
                    const titleEl = item.querySelector('.corp-sf-title') || item.querySelector('span.font-bold');
                    const badgeEl = item.querySelector('.corp-sf-badge') || item.querySelector('.badge-xs');
                    const actionCol = item.querySelector('.corp-sf-action') || item.querySelector('div.shrink-0:last-child') || item.lastElementChild;

                    if (container) {
                        container.className = "corp-sf-container flex items-center justify-between gap-2.5 p-2 rounded-xl transition-all bg-base-200/40 hover:bg-base-200/80 border border-base-200/60";
                    }
                    if (iconBox) {
                        iconBox.className = "corp-sf-icon w-8 h-8 rounded-lg bg-base-300 text-base-content/70 flex items-center justify-center shrink-0";
                    }
                    if (titleEl) {
                        titleEl.className = "corp-sf-title font-bold text-xs text-base-content truncate";
                    }
                    if (badgeEl) {
                        badgeEl.classList.add('hidden');
                    }
                    if (actionCol) {
                        actionCol.innerHTML = `<button type="button" data-id="${itemId}" data-title="${encodeURIComponent(itemRawTitle)}" onclick="event.stopPropagation(); executeApplyTemplateSoftFileDirect(${itemId}, ${JSON.stringify(itemRawTitle)})" class="btn-apply-template-sf-direct btn btn-accent btn-xs rounded-lg font-bold shadow-xs px-2.5" title="Langsung terapkan kop surat ini ke template">Terapkan</button>`;
                    }
                });
            }

            function executeApplyTemplateSoftFileDirect(id, title) {
                activeTemplateSoftFile = { id: id, title: title };
                const isSwitching = currentAppliedTemplateSoftFileId && currentAppliedTemplateSoftFileId != id;
                updateUsedTemplateSoftFileUI(id, title);
                showTemplateScreenAlert('SEDANG MENERAPKAN', (isSwitching ? 'Mengganti ke Kop Surat "' : 'Menerapkan Kop Surat "') + title + '" ke template...', true);
                executeApplyTemplateSoftFileMaster();
            }

            function handleTemplateSoftFileDblClick(id, title, fileUrl, isImage = false) {
                activeTemplateSoftFile = { id: id, title: title, fileUrl: fileUrl, isImage: isImage };
                executeApplyTemplateSoftFileDirect(id, title);
            }

            function executeApplyTemplateSoftFileMaster() {
                if (!activeTemplateSoftFile || !activeTemplateSoftFile.id) return;

                const selectedId = activeTemplateSoftFile.id;
                const selectedTitle = activeTemplateSoftFile.title;

                // 1. Immediately reflect UI changes
                updateUsedTemplateSoftFileUI(selectedId, selectedTitle);

                const modal = document.getElementById('template-softfile-modal');
                if (modal && typeof modal.close === 'function') modal.close();

                const applyUrl = "{{ url('/admin/templates/' . $template->id . '/corporate-soft-files') }}/" + selectedId + "/apply";
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

                fetch(applyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        updateUsedTemplateSoftFileUI(selectedId, selectedTitle);
                        showTemplateScreenAlert('BERHASIL DITERAPKAN', data.message || ('Kop Surat "' + selectedTitle + '" berhasil diterapkan.'), true);
                        window._hasSessionChanges = false;
                        if (typeof window.allowIntentionalLeave === 'function') {
                            window.allowIntentionalLeave();
                        }
                        if (window.docEditor) {
                            try { window.docEditor.destroyEditor(); } catch(e) {}
                        }
                        setTimeout(() => {
                            const target = data.redirect_url || window.location.href.split('?')[0];
                            window.location.href = target + (target.includes('?') ? '&' : '?') + 't=' + Date.now();
                        }, 600);
                    } else {
                        showTemplateScreenAlert('GAGAL MENERAPKAN', data.error || 'Gagal menerapkan kop surat.', false);
                    }
                })
                .catch(err => {
                    console.error('apply template soft file error:', err);
                    showTemplateScreenAlert('KESALAHAN SISTEM', 'Terjadi kesalahan saat menerapkan kop surat.', false);
                });
            }

            function executeInsertTemplateSoftFileConnector() {
                if (!activeTemplateSoftFile) return;

                const modal = document.getElementById('template-softfile-modal');
                if (modal && typeof modal.close === 'function') modal.close();

                const isImg = activeTemplateSoftFile.isImage || (activeTemplateSoftFile.fileUrl && /\.(jpe?g|png|webp|gif)(\?.*)?$/i.test(activeTemplateSoftFile.fileUrl));

                if (isImg && window.docEditor && typeof window.docEditor.createConnector === 'function') {
                    try {
                        const connector = window.docEditor.createConnector();
                        const softFileUrl = activeTemplateSoftFile.fileUrl;
                        const script = `
                            var oDocument = Api.GetDocument();
                            try {
                                var oParagraph = Api.CreateParagraph();
                                var oDrawing = Api.CreateImage("${softFileUrl}", 170 * 36000, 45 * 36000);
                                oParagraph.AddDrawing(oDrawing);
                                oParagraph.SetJc("center");
                                oDocument.InsertContent([oParagraph], 0);
                            } catch(e) {
                                console.warn("InsertImage script error:", e);
                            }
                        `;
                        connector.callCommand(new Function(script), function() {
                            showTemplateScreenAlert('BERHASIL', 'Kop surat berhasil disisipkan ke template.', true);
                            updateUsedTemplateSoftFileUI(activeTemplateSoftFile.id, activeTemplateSoftFile.title);
                        });
                    } catch (err) {
                        console.warn('insert template soft file error:', err);
                        executeApplyTemplateSoftFileMaster();
                    }
                } else {
                    // For DOCX and PDF, apply as template master with all header letterhead graphics and F4 page size
                    showTemplateScreenAlert('SEDANG MENERAPKAN', 'Menerapkan Kop Surat "' + (activeTemplateSoftFile.title || '') + '" ke template...', true);
                    executeApplyTemplateSoftFileMaster();
                }
            }

            function updateUsedTemplateSoftFileUI(id, title) {
                currentAppliedTemplateSoftFileId = id;
                currentAppliedTemplateSoftFileTitle = title;

                const badge = document.getElementById('applied-corp-softfile-badge');
                const nameEl = document.getElementById('applied-corp-softfile-name');
                if (badge && nameEl) {
                    nameEl.innerText = title;
                    badge.classList.remove('hidden');
                    badge.title = 'Kop Surat yang digunakan: ' + title;
                }

                const btn = document.getElementById('template-softfile-btn');
                const btnText = document.getElementById('template-softfile-btn-text');
                const btnMobileText = document.getElementById('template-softfile-btn-mobile-text');
                const btnBadge = document.getElementById('template-softfile-btn-badge');
                if (btn) {
                    btn.className = "btn btn-xs btn-accent text-accent-content font-bold shadow-xs gap-1.5 shrink-0 cursor-pointer";
                }
                if (btnText) {
                    btnText.innerText = "Kop: " + (title.length > 14 ? title.substring(0, 14) + '...' : title);
                }
                if (btnMobileText) {
                    btnMobileText.innerText = "Kop: " + (title.length > 8 ? title.substring(0, 8) + '...' : title);
                }
                if (btnBadge) {
                    btnBadge.classList.remove('hidden');
                    btnBadge.textContent = "✓ Terpilih";
                }

                // Banner
                const banner = document.getElementById('applied-template-sf-banner');
                const bannerTitle = document.getElementById('applied-template-sf-banner-title');
                if (banner && bannerTitle) {
                    bannerTitle.innerText = title;
                    banner.classList.remove('hidden');
                }

                document.querySelectorAll('#admin-template-sf-dropdown .corp-softfile-item').forEach(item => {
                    const itemId = item.getAttribute('data-id');
                    const itemRawTitle = item.getAttribute('data-raw-title') || '';
                    const isTarget = (itemId == id);
                    const container = item.querySelector('.corp-sf-container') || item.querySelector('div.flex');
                    const iconBox = item.querySelector('.corp-sf-icon') || item.querySelector('.w-7.h-7') || item.querySelector('.shrink-0');
                    const titleEl = item.querySelector('.corp-sf-title') || item.querySelector('span.font-bold');
                    const badgeEl = item.querySelector('.corp-sf-badge') || item.querySelector('.badge-xs');
                    const actionCol = item.querySelector('.corp-sf-action') || item.querySelector('div.shrink-0:last-child') || item.lastElementChild;

                    if (isTarget) {
                        if (container) {
                            container.className = "corp-sf-container flex items-center justify-between gap-2.5 p-2 rounded-xl transition-all bg-accent/15 border border-accent/40 text-accent shadow-2xs ring-1 ring-accent/20";
                        }
                        if (iconBox) {
                            iconBox.className = "corp-sf-icon w-8 h-8 rounded-lg bg-accent text-accent-content font-bold shadow-xs flex items-center justify-center shrink-0";
                            iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>';
                        }
                        if (titleEl) {
                            titleEl.className = "corp-sf-title font-bold text-xs text-accent truncate";
                        }
                        if (actionCol) {
                            actionCol.innerHTML = '<span class="badge badge-accent badge-sm font-bold text-[10px] gap-1 px-2.5 py-1 shadow-2xs cursor-default">✓ Digunakan</span>';
                        }
                    } else {
                        if (container) {
                            container.className = "corp-sf-container flex items-center justify-between gap-2.5 p-2 rounded-xl transition-all bg-base-200/40 hover:bg-base-200/80 border border-base-200/60";
                        }
                        if (iconBox) {
                            iconBox.className = "corp-sf-icon w-8 h-8 rounded-lg bg-base-300 text-base-content/70 flex items-center justify-center shrink-0";
                        }
                        if (titleEl) {
                            titleEl.className = "corp-sf-title font-bold text-xs text-base-content truncate";
                        }
                        if (actionCol) {
                            actionCol.innerHTML = `<button type="button" data-id="${itemId}" data-title="${encodeURIComponent(itemRawTitle)}" onclick="event.stopPropagation(); executeApplyTemplateSoftFileDirect(${itemId}, ${JSON.stringify(itemRawTitle)})" class="btn-apply-template-sf-direct btn btn-outline btn-accent btn-xs rounded-lg font-bold shadow-xs px-2.5" title="Ganti kop aktif template dengan kop surat ini">Ganti Kop</button>`;
                        }
                    }
                });
            }

            // Global click listener for template soft file apply buttons
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-apply-template-sf-direct');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const sfId = btn.getAttribute('data-id');
                    let sfTitle = btn.getAttribute('data-title') || '';
                    try { sfTitle = decodeURIComponent(sfTitle); } catch(err) {}
                    if (sfId) {
                        executeApplyTemplateSoftFileDirect(sfId, sfTitle);
                    }
                }
            });

            function finishEditingTemplate() {
                window._hasSessionChanges = false;
                const btn = document.getElementById('btn-selesai-edit');
                const spinner = document.getElementById('spinner-selesai-edit');
                const icon = document.getElementById('icon-selesai-edit');
                const text = document.getElementById('text-selesai-edit');
                const targetUrl = "{{ route('admin.templates.index') }}";

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

                fetch("{{ route('admin.templates.finish-editing', $template) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    window.location.href = data.redirect_url || targetUrl;
                })
                .catch(err => {
                    console.warn('finish-editing template request error:', err);
                    window.location.href = targetUrl;
                });
            }
        </script>
    @endpush

    {{-- Modal Konfirmasi Penerapan Soft File Korporat ke Template --}}
    <dialog id="template-softfile-modal" class="modal modal-bottom sm:modal-middle">
        <div class="modal-box rounded-2xl p-6 bg-base-100 border border-base-300 shadow-2xl max-w-md">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-accent/15 text-accent flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-base text-base-content" id="template-sf-modal-title">{{ __('Terapkan Soft File Korporat') }}</h3>
                    <p class="text-xs text-base-content/60">{{ __('Pilih metode penerapan ke template') }}</p>
                </div>
            </div>

            <div id="template-sf-modal-message" class="text-xs text-base-content/80 p-3 bg-base-200/60 rounded-xl mb-5 leading-relaxed border border-base-300">
                <!-- Dynamic Content -->
            </div>

            <div class="space-y-2.5">
                <button type="button" onclick="executeApplyTemplateSoftFileMaster()" class="btn btn-primary btn-sm w-full rounded-xl gap-2 font-bold shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    {{ __('Gunakan Sebagai Format Template Master') }}
                </button>
                <button type="button" onclick="executeInsertTemplateSoftFileConnector()" class="btn btn-outline btn-accent btn-sm w-full rounded-xl gap-2 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Sisipkan Konten ke Editor Saat Ini') }}
                </button>
            </div>

            <div class="modal-action mt-5 pt-3 border-t border-base-200 flex justify-end">
                <form method="dialog">
                    <button class="btn btn-ghost btn-xs rounded-lg">{{ __('Batal') }}</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>{{ __('Tutup') }}</button>
        </form>
    </dialog>

</x-app-layout>
