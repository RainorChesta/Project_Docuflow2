<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full overflow-hidden print:h-auto print:overflow-visible">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DokuFlow') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Flash-prevention: set data-theme before CSS renders. No stored choice = follow OS live --}}
        <script>(function(){var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light');m.addEventListener('change',function(){var s=sessionStorage.getItem('theme:v2');if(s!=='dark'&&s!=='light')document.documentElement.setAttribute('data-theme',m.matches?'dark':'light')})})()</script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        @stack('after-styles')
    </head>
    <body class="h-full overflow-hidden font-sans antialiased bg-base-200 text-base-content print:bg-white print:h-auto print:overflow-visible relative">
        
        <style>
            @keyframes blob {
                0% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.1); }
                66% { transform: translate(-20px, 20px) scale(0.9); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob {
                animation: blob 15s infinite alternate ease-in-out;
            }
            .animation-delay-2000 { animation-delay: 2s; }
            .animation-delay-4000 { animation-delay: 4s; }
        </style>

        <!-- Ambient Background Blobs for Glassmorphism Refraction -->
        <!-- Heavily dimmed and using a safe, harmonious cool palette (blue/purple) so it doesn't distract from main content -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none z-0 opacity-50 dark:opacity-30">
            <div class="absolute top-[-10%] left-[-5%] w-[40vw] h-[40vw] max-w-[600px] max-h-[600px] bg-blue-500/20 rounded-full blur-[120px] animate-blob"></div>
            <div class="absolute top-[20%] right-[-10%] w-[35vw] h-[35vw] max-w-[500px] max-h-[500px] bg-indigo-500/20 rounded-full blur-[120px] animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-[-10%] left-[20%] w-[45vw] h-[45vw] max-w-[700px] max-h-[700px] bg-purple-500/20 rounded-full blur-[140px] animate-blob animation-delay-4000"></div>
        </div>

        <div class="flex h-full w-full overflow-hidden relative z-10 print:block print:h-auto print:overflow-visible"
             x-data="{
                 open: localStorage.getItem('dokuflow:sidebar') === 'closed' ? false : window.innerWidth >= 1024,
                 toggle() {
                     this.open = !this.open;
                     localStorage.setItem('dokuflow:sidebar', this.open ? 'open' : 'closed');
                 }
             }"
             x-init="() => {
                 const mq = window.matchMedia('(min-width: 1024px)');
                 const closeOnMobile = (e) => { if (!e.matches) open = false; };
                 mq.addEventListener('change', closeOnMobile);
                 $el._closeOnMobile = closeOnMobile;
             }"
             x-effect="if (window.innerWidth >= 1024 && open === false) {
                 localStorage.setItem('dokuflow:sidebar', 'closed');
             }">
            <!-- Left Sidebar -->
            <div class="print:hidden h-full flex shrink-0">
                @include('layouts.navigation')
            </div>

            {{-- Mobile backdrop --}}
            <div x-show="open" class="fixed inset-0 z-40 bg-black/50 lg:hidden print:hidden" x-on:click="open = false"></div>

            <!-- Right Area -->
            <div class="flex flex-col flex-1 min-h-0 min-w-0 print:block print:h-auto print:overflow-visible">
                <!-- Topbar -->
                <header class="glass-panel h-[60px] flex items-center justify-between px-2.5 sm:px-6 shrink-0 z-30 lg:mt-4 lg:mr-4 lg:ml-2 mx-2 sm:mx-4 mt-2 sm:mt-4 rounded-[20px] transition-all duration-300 print:hidden">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <button type="button"
                                class="btn btn-ghost btn-circle btn-sm shrink-0 lg:hidden hover:bg-base-200"
                                aria-label="Toggle sidebar"
                                x-on:click="toggle()">
                            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        @isset($header)
                            {{-- Header title removed per user request, relying on breadcrumbs instead --}}
                        @endisset
                    </div>
                    <div class="flex items-center gap-1 sm:gap-1.5">
                        {{-- Company & Branch Switcher: far right on mobile, first on desktop --}}
                        @if(!auth()->user()->isAdmin())
                        <div class="order-last md:order-first">
                            <x-company-branch-switcher />
                        </div>
                        @endif

                        {{-- Language Switcher (ID <-> EN) --}}
                        <div class="hover:bg-base-200 rounded-full transition-colors">
                            <x-language-toggle />
                        </div>

                        {{-- Theme Toggle: follows OS until user picks Light/Dark --}}
                        <div class="hover:bg-base-200 rounded-full transition-colors">
                            <x-theme-toggle />
                        </div>

                        {{-- Global Search --}}
                        <button type="button"
                                class="btn btn-ghost btn-circle btn-sm hover:bg-base-200 transition-colors"
                                title="{{ __('Cari Dokumen') }}"
                                aria-label="{{ __('Cari Dokumen') }}"
                                @click="$dispatch('open-search')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        {{-- Notification Bell --}}
                        <div class="hover:bg-base-200 rounded-full transition-colors ml-0.5 sm:ml-1">
                            <x-notification-bell />
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 {{ request()->routeIs('documents.edit') ? 'p-0 flex flex-col min-h-0' : 'p-3 sm:p-6 overflow-y-auto' }} print:block print:h-auto print:overflow-visible print:p-0">
                    @if(!request()->routeIs('documents.edit'))
                    <div class="print:hidden max-w-7xl mx-auto w-full pt-2 sm:pt-4">
                    @php
                        $crumbs = [];
                        $route = request()->route();
                        $name = $route ? $route->getName() : null;
                        $user = auth()->user();
                        $isAdmin = $user->isAdmin();
                        $isHead = $user->isHead();

                        $docType = request('type');
                        if (!$docType && $route && in_array($name, ['documents.edit', 'documents.show', 'documents.preview', 'documents.preview-version'])) {
                            $docType = match (request()->route('document')?->visibility) {
                                'personal' => 'mine',
                                'division' => 'division',
                                default => 'general',
                            };
                        }
                        $docType = $docType ?: 'general';
                        $docTypeLabel = match ($docType) {
                            'mine' => __('Dokumen Saya'),
                            'division' => __('Dokumen Divisi'),
                            default => __('Dokumen Umum'),
                        };
                        $docTypeRoute = route('documents.index', ['type' => $docType]);

                        if ($route && $name !== 'dashboard') {
                            $crumbs[] = ['label' => __('Dashboard'), 'url' => route('dashboard')];

                            if (str_starts_with($name, 'documents.')) {
                                if (in_array($name, ['documents.choose', 'documents.create', 'documents.edit', 'documents.show', 'documents.preview', 'documents.preview-version'])) {
                                    if (!(request('from') === 'approvals' && in_array($name, ['documents.preview', 'documents.preview-version']))) {
                                        $crumbs[] = ['label' => $docTypeLabel, 'url' => $docTypeRoute];
                                    }
                                }
                                
                                if ($name === 'documents.choose') {
                                    $crumbs[] = ['label' => __('Pilih Template'), 'url' => null];
                                } elseif ($name === 'documents.create') {
                                    $crumbs[] = ['label' => __('Pilih Template'), 'url' => route('documents.choose', ['type' => $docType])];
                                    $crumbs[] = ['label' => __('Buat Dokumen'), 'url' => null];
                                } elseif ($name === 'documents.edit') {
                                    if ($doc = request()->route('document')) {
                                        $crumbs[] = ['label' => __('Detail Dokumen'), 'url' => route('documents.show', $doc)];
                                    }
                                    $crumbs[] = ['label' => __('Edit Dokumen'), 'url' => null];
                                } elseif ($name === 'documents.show') {
                                    $crumbs[] = ['label' => __('Detail Dokumen'), 'url' => null];
                                } elseif ($name === 'documents.preview' || $name === 'documents.preview-version') {
                                    if (request('from') === 'approvals') {
                                        $crumbs[] = ['label' => __('Persetujuan'), 'url' => route('approvals.index')];
                                    } else {
                                        if ($doc = request()->route('document')) {
                                            $crumbs[] = ['label' => __('Detail Dokumen'), 'url' => route('documents.show', $doc)];
                                        }
                                    }
                                    $crumbs[] = ['label' => __('Pratinjau Dokumen'), 'url' => null];
                                } elseif ($name === 'documents.index') {
                                    $crumbs[] = ['label' => $docTypeLabel, 'url' => null];
                                }
                            } elseif (str_starts_with($name, 'director.documents.')) {
                                $crumbs[] = ['label' => __('Semua Dokumen'), 'url' => null];
                            } elseif (str_starts_with($name, 'admin.')) {
                                $section = match (true) {
                                    str_contains($name, 'divisions') => __('Divisi'),
                                    str_contains($name, 'document-types') => __('Tipe Dokumen'),
                                    str_contains($name, 'users') => __('Pengguna'),
                                    str_contains($name, 'retention') => __('Retensi'),
                                    str_contains($name, 'templates') || str_contains($name, 'template-categories') => __('Template Dokumen'),
                                    str_contains($name, 'companies') => __('Perusahaan'),
                                    str_contains($name, 'branches') => __('Cabang'),
                                    default => __('Administrasi'),
                                };
                                // Try to determine the index route for the section
                                $indexRouteName = preg_replace('/\.(create|edit|show)$/', '.index', $name);
                                $indexUrl = (Route::has($indexRouteName) && $indexRouteName !== $name) ? route($indexRouteName) : null;
                                
                                $crumbs[] = ['label' => $section, 'url' => $indexUrl];
                                
                                if (str_contains($name, '.create')) {
                                    $crumbs[] = ['label' => __('Buat'), 'url' => null];
                                } elseif (str_contains($name, '.edit')) {
                                    $crumbs[] = ['label' => __('Edit'), 'url' => null];
                                } elseif (str_contains($name, '.show')) {
                                    $crumbs[] = ['label' => __('Detail'), 'url' => null];
                                }
                            } elseif (str_starts_with($name, 'approvals.')) {
                                $crumbs[] = ['label' => __('Persetujuan'), 'url' => null];
                            } elseif (str_starts_with($name, 'profile.')) {
                                $crumbs[] = ['label' => __('Profil'), 'url' => null];
                            } elseif (str_starts_with($name, 'signatures.requests.')) {
                                $crumbs[] = ['label' => __('Persetujuan Tanda Tangan'), 'url' => null];
                            }
                        }
                    @endphp

                    @if(count($crumbs) > 0)
                        <x-breadcrumbs :items="$crumbs" />
                    @endif
                    </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
        <x-mandatory-signature-modal />
        <x-search-modal />
        <x-navigation-guard />
        <x-toast-notification />
        @stack('scripts')
    </body>
</html>
