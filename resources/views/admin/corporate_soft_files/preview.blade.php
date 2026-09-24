<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dokuflow">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $corporateSoftFile->title }} - {{ __('Preview Soft File Korporat') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-base-200 text-base-content min-h-screen flex flex-col antialiased">

    {{-- Top Navigation Bar --}}
    <header class="bg-base-100 border-b border-base-300 sticky top-0 z-30 shadow-2xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('admin.corporate-soft-files.index') }}" class="btn btn-ghost btn-xs sm:btn-sm gap-1.5 rounded-xl text-base-content/70 hover:text-base-content">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Kembali') }}</span>
                </a>
                <div class="h-5 w-px bg-base-300"></div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h1 class="font-bold text-sm sm:text-base truncate">{{ $corporateSoftFile->title }}</h1>
                        <span class="badge {{ $corporateSoftFile->isPdf() ? 'badge-error' : ($corporateSoftFile->isImage() ? 'badge-secondary' : 'badge-primary') }} badge-xs font-mono font-bold">{{ $corporateSoftFile->file_type }}</span>
                        <span class="badge badge-neutral badge-xs font-bold gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ __('Hanya Lihat') }}
                        </span>
                        @if($corporateSoftFile->isActive())
                            <span class="badge badge-success badge-xs hidden sm:inline-flex">{{ __('Aktif') }}</span>
                        @else
                            <span class="badge badge-ghost badge-xs hidden sm:inline-flex">{{ __('Diarsipkan') }}</span>
                        @endif
                    </div>
                    <p class="text-[11px] text-base-content/50 truncate hidden sm:block">
                        {{ $corporateSoftFile->file_original_name }}
                        @if($corporateSoftFile->file_size)
                            • {{ number_format($corporateSoftFile->file_size / 1024, 1) }} KB
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.corporate-soft-files.download', $corporateSoftFile) }}" class="btn btn-primary btn-xs sm:btn-sm gap-1.5 rounded-xl shadow-xs">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Unduh') }}</span>
                </a>
                <a href="{{ route('admin.corporate-soft-files.edit', $corporateSoftFile) }}" class="btn btn-outline btn-xs sm:btn-sm gap-1.5 rounded-xl" title="{{ __('Edit Informasi & Hak Akses Soft File') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="hidden sm:inline">{{ __('Pengaturan') }}</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Main Viewport --}}
    <main class="flex-1 w-full flex flex-col p-1 sm:p-2 h-[calc(100vh-3.5rem)]">
        @if($corporateSoftFile->isImage())
            {{-- Image Preview Viewport --}}
            <div class="flex-1 bg-slate-900/5 dark:bg-base-300/30 rounded-2xl border border-base-300 shadow-sm overflow-hidden flex flex-col relative">
                {{-- Image Controls Floating Toolbar --}}
                <div class="absolute top-4 right-4 z-20 flex items-center gap-1.5 bg-base-100/90 backdrop-blur-md px-3 py-1.5 rounded-2xl shadow-lg border border-base-300">
                    <button type="button" onclick="zoomImage(-0.2)" class="btn btn-ghost btn-xs btn-square" title="{{ __('Perkecil') }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                    </button>
                    <span id="zoom-val" class="text-xs font-mono font-bold px-1.5 min-w-[45px] text-center">100%</span>
                    <button type="button" onclick="zoomImage(0.2)" class="btn btn-ghost btn-xs btn-square" title="{{ __('Perbesar') }}">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>
                    <div class="h-4 w-px bg-base-300 mx-0.5"></div>
                    <button type="button" onclick="resetZoom()" class="btn btn-ghost btn-xs rounded-xl px-2 text-xs font-semibold" title="{{ __('Reset') }}">
                        {{ __('Reset (100%)') }}
                    </button>
                </div>

                <div class="flex-1 w-full h-full overflow-auto flex items-center justify-center p-4 sm:p-8">
                    <div id="img-wrapper" class="my-auto w-full max-w-6xl flex flex-col items-center justify-center transition-transform duration-150">
                        <img id="preview-img" src="{{ route('admin.corporate-soft-files.preview-content', $corporateSoftFile) }}" 
                             alt="{{ $corporateSoftFile->title }}" 
                             class="w-full max-h-none object-contain rounded-2xl shadow-2xl border border-base-300 bg-white">
                        <div class="mt-4 text-center max-w-lg">
                            <p class="font-bold text-sm text-base-content">{{ $corporateSoftFile->title }}</p>
                            <p class="text-xs text-base-content/60 mt-0.5">{{ $corporateSoftFile->file_original_name }} • {{ number_format($corporateSoftFile->file_size / 1024, 1) }} KB</p>
                            @if($corporateSoftFile->description)
                                <p class="text-xs text-base-content/70 mt-2 bg-base-100/80 p-2.5 rounded-xl border border-base-200">{{ $corporateSoftFile->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <script>
                let currentZoom = 1.0;
                function zoomImage(delta) {
                    currentZoom = Math.max(0.3, Math.min(3.5, currentZoom + delta));
                    updateZoom();
                }
                function resetZoom() {
                    currentZoom = 1.0;
                    updateZoom();
                }
                function updateZoom() {
                    const img = document.getElementById('preview-img');
                    const val = document.getElementById('zoom-val');
                    if (img) {
                        img.style.transform = `scale(${currentZoom})`;
                        img.style.transformOrigin = 'center top';
                    }
                    if (val) {
                        val.textContent = Math.round(currentZoom * 100) + '%';
                    }
                }
            </script>
        @else
            {{-- ONLYOFFICE / Document Preview Viewport --}}
            <div class="flex-1 bg-base-100 rounded-2xl border border-base-300 shadow-sm overflow-hidden relative">
                <div id="onlyoffice-editor-container" class="w-full h-full"></div>

                <div id="onlyoffice-fallback" class="hidden absolute inset-0 bg-base-100/95 backdrop-blur-xs flex flex-col items-center justify-center p-6 text-center z-20">
                    <div class="max-w-md p-6 bg-base-200 rounded-2xl border border-base-300 shadow-xl">
                        <div class="w-12 h-12 rounded-full bg-warning/20 text-warning flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-base mb-1">{{ __('ONLYOFFICE Server Tidak Terhubung') }}</h3>
                        <p class="text-xs text-base-content/70 mb-4 leading-relaxed">
                            {{ __('Pratinjau interaktif membutuhkan container ONLYOFFICE Docs. Anda tetap dapat mengunduh berkas ini secara langsung.') }}
                        </p>
                        <div class="flex justify-center gap-2">
                            <a href="{{ route('admin.corporate-soft-files.download', $corporateSoftFile) }}" class="btn btn-primary btn-sm rounded-xl">
                                {{ __('Unduh Berkas') }}
                            </a>
                            <a href="{{ route('admin.corporate-soft-files.index') }}" class="btn btn-ghost btn-sm rounded-xl">
                                {{ __('Kembali') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <script src="{{ rtrim(config('onlyoffice.url'), '/') }}/web-apps/apps/api/documents/api.js?v=9.4.0-f4-v2"
                    onerror="document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof DocsAPI === 'undefined') {
                        document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                        return;
                    }

                    try {
                        const config = @json($onlyOfficeConfig);
                        if (!config) return;

                        config.type = 'embedded';
                        config.document = config.document || {};
                        config.document.permissions = config.document.permissions || {};
                        config.document.permissions.edit = false;
                        config.document.permissions.review = false;
                        config.document.permissions.comment = false;
                        config.document.permissions.fillForms = false;
                        config.document.permissions.modifyFilter = false;
                        config.document.permissions.modifyContentControl = false;

                        config.editorConfig = config.editorConfig || {};
                        config.editorConfig.mode = 'view';
                        config.editorConfig.canEdit = false;
                        config.editorConfig.customization = config.editorConfig.customization || {};
                        config.editorConfig.customization.compactHeader = true;
                        config.editorConfig.customization.toolbarNoTabs = true;
                        config.editorConfig.customization.toolbarHideFileName = false;
                        config.editorConfig.customization.autosave = false;
                        config.editorConfig.customization.forcesave = false;
                        config.editorConfig.customization.leftMenu = false;
                        config.editorConfig.customization.rightMenu = false;
                        config.editorConfig.customization.embedded = config.editorConfig.customization.embedded || { toolbarDockPosition: 'bottom' };
                        config.editorConfig.customization.zoom = -2; // Fit to Width

                        new DocsAPI.DocEditor("onlyoffice-editor-container", config);
                    } catch (e) {
                        console.error("ONLYOFFICE initialization error:", e);
                        document.getElementById('onlyoffice-fallback')?.classList.remove('hidden');
                    }
                });
            </script>
        @endif
    </main>
</body>
</html>
