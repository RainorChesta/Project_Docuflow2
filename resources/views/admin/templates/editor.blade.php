<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <span>{{ __('Edit Template: ') }}</span>
            <span class="font-normal text-base-content/70">{{ $template->title }}</span>
        </div>
    </x-slot>

    <div class="w-full h-[calc(100vh-4rem)] flex flex-col bg-base-200">
        {{-- Top Navigation & Action Bar --}}
        <div class="bg-base-100 border-b border-base-300 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0 shadow-sm">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.templates.index') }}" class="btn btn-ghost btn-sm btn-square" title="{{ __('Kembali') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-bold truncate">{{ $template->title }}</h1>
                    </div>
                    <p class="text-xs text-base-content/60">
                        {{ __('ONLYOFFICE Template Editor') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        id="btn-selesai-edit"
                        onclick="finishEditingTemplate()"
                        class="btn btn-primary btn-sm px-5 gap-2">
                    <svg id="icon-selesai-edit" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span id="spinner-selesai-edit" class="loading loading-spinner loading-xs hidden"></span>
                    <span id="text-selesai-edit">{{ __('Selesai Edit') }}</span>
                </button>
            </div>
        </div>

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

    @push('scripts')
        <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js"
                onerror="document.getElementById('onlyoffice-fallback').classList.remove('hidden');"></script>
        <script>
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
