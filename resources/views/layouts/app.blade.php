<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DokuFlow') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Flash-prevention: set data-theme before CSS renders. No stored choice = follow OS live --}}
        <script>(function(){var t=localStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light');m.addEventListener('change',function(){var s=localStorage.getItem('theme:v2');if(s!=='dark'&&s!=='light')document.documentElement.setAttribute('data-theme',m.matches?'dark':'light')})})()</script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        @stack('after-styles')
    </head>
    <body class="font-sans antialiased bg-base-200 text-base-content print:bg-white">
        <div class="flex h-screen overflow-hidden print:block print:h-auto print:overflow-visible"
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
            <div class="print:hidden">
                @include('layouts.navigation')
            </div>

            {{-- Mobile backdrop --}}
            <div x-show="open" class="fixed inset-0 z-40 bg-black/50 lg:hidden print:hidden" x-on:click="open = false"></div>

            <!-- Right Area -->
            <div class="flex flex-col flex-1 min-h-0 min-w-0 print:block print:h-auto print:overflow-visible">
                <!-- Topbar -->
                <header class="h-16 bg-base-100 border-b border-base-300 flex items-center justify-between px-3 sm:px-6 shrink-0 sticky top-0 z-30 print:hidden">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <button type="button"
                                class="btn btn-ghost btn-sm px-2 shrink-0 lg:hidden"
                                aria-label="Toggle sidebar"
                                x-on:click="toggle()">
                            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                        @isset($header)
                            <h1 class="text-base sm:text-lg font-semibold text-base-content min-w-0 truncate">{{ $header }}</h1>
                        @endisset
                    </div>
                    <div class="flex items-center gap-1">
                        {{-- Language Switcher (ID <-> EN) --}}
                        <x-language-toggle />

                        {{-- Theme Toggle: follows OS until user picks Light/Dark --}}
                        <x-theme-toggle />

                        {{-- Global Search --}}
                        <button type="button"
                                class="btn btn-ghost btn-sm btn-square"
                                title="{{ __('Cari Dokumen') }}"
                                aria-label="{{ __('Cari Dokumen') }}"
                                @click="$dispatch('open-search')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </button>

                        {{-- Notification Bell --}}
                        <x-notification-bell />
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 {{ request()->routeIs('documents.edit') ? 'p-0 flex flex-col min-h-0' : 'p-3 sm:p-6 overflow-y-auto' }} print:block print:h-auto print:overflow-visible print:p-0">
                    @if(!request()->routeIs('documents.edit'))
                    <div class="print:hidden">
                    @php
                        $crumbs = [];
                        $route = request()->route();
                        $name = $route ? $route->getName() : null;
                        $user = auth()->user();
                        $isAdmin = $user->isAdmin();
                        $isHead = $user->isHead();

                        $docType = request('type', 'general');
                        if ($route && in_array($name, ['documents.edit', 'documents.show', 'documents.preview', 'documents.preview-version'])) {
                            $docType = match (request()->route('document')?->visibility) {
                                'personal' => 'mine',
                                'division' => 'division',
                                default => 'general',
                            };
                        }
                        $docTypeLabel = match ($docType) {
                            'mine' => __('Dokumen Saya'),
                            'division' => __('Dokumen Divisi'),
                            default => __('Dokumen Umum'),
                        };
                        $docTypeRoute = route('documents.index', ['type' => $docType]);

                        if ($route) {
                            if (str_starts_with($name, 'documents.')) {
                                if (in_array($name, ['documents.create', 'documents.edit', 'documents.show', 'documents.preview', 'documents.preview-version'])) {
                                    $crumbs[] = ['label' => $docTypeLabel, 'url' => $docTypeRoute];
                                }
                                $crumbs[] = ['label' => match ($name) {
                                    'documents.create' => __('Buat'),
                                    'documents.edit' => __('Edit'),
                                    'documents.show' => __('Detail Dokumen'),
                                    'documents.preview' => __('Pratinjau'),
                                    'documents.preview-version' => __('Pratinjau'),
                                    default => $docTypeLabel,
                                }, 'url' => null];
                            } elseif ($name !== 'dashboard') {
                                $crumbs[] = ['label' => __('Dashboard'), 'url' => route('dashboard')];
                                if (str_starts_with($name, 'admin.')) {
                                    $section = match (true) {
                                        str_contains($name, 'divisions') => __('Divisi'),
                                        str_contains($name, 'document-types') => __('Tipe Dokumen'),
                                        str_contains($name, 'users') => __('Pengguna'),
                                        str_contains($name, 'retention') => __('Retensi'),
                                        default => __('Administrasi'),
                                    };
                                    $crumbs[] = ['label' => $section, 'url' => null];
                                    if (str_contains($name, '.create')) {
                                        $crumbs[] = ['label' => __('Buat'), 'url' => null];
                                    } elseif (str_contains($name, '.edit')) {
                                        $crumbs[] = ['label' => __('Edit'), 'url' => null];
                                    }
                                } elseif ($name === 'approvals.index') {
                                    $crumbs[] = ['label' => __('Persetujuan'), 'url' => null];
                                } elseif ($name === 'profile.edit') {
                                    $crumbs[] = ['label' => __('Profil'), 'url' => null];
                                }
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
        @stack('scripts')
    </body>
</html>
