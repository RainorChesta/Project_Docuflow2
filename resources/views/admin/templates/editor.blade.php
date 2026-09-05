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
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 ml-auto sm:ml-0">
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

    @push('scripts')
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"
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
                        console.log('ONLYOFFICE editor ready');
                        if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                    };
                    config.events.onDocumentReady = function() {
                        if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                        setTimeout(() => {
                            if (mainScrollContainer) mainScrollContainer.scrollTop = 0;
                        }, 50);
                    };
                    config.events.onDocumentStateChange = function(event) {
                        const isModified = event.data;
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

            function finishEditingTemplate() {
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

                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 1000);
            }
        </script>
    @endpush
</x-app-layout>
