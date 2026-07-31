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

        {{-- Flash-prevention: set data-theme before CSS renders. Default = follow OS, user toggle overrides --}}
        <script>(function(){var t=localStorage.getItem('theme'),d=t?t==='dark':window.matchMedia('(prefers-color-scheme: dark)').matches;document.documentElement.setAttribute('data-theme',d?'dark':'light')})()</script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-base-200 text-base-content">
        <div class="flex min-h-screen">
            <!-- Left Sidebar -->
            @include('layouts.navigation')

            <!-- Right Area -->
            <div class="flex flex-col flex-1 min-w-0">
                <!-- Topbar -->
                <header class="h-16 bg-base-100 border-b border-base-300 flex items-center justify-between px-6 shrink-0">
                    <div class="flex items-center gap-3">
                        @isset($header)
                            <h1 class="text-lg font-semibold text-base-content">{{ $header }}</h1>
                        @endisset
                    </div>
                    <div class="flex items-center gap-1">
                        {{-- Theme Toggle: Light/Dark -- default auto follow OS --}}
                        <div class="dropdown dropdown-end">
                            <button tabindex="0" role="button" class="btn btn-ghost btn-sm btn-square rounded-lg" id="themeToggleBtn" title="Switch theme">
                                <svg id="themeIconSun" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                <svg id="themeIconMoon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            </button>
                            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-300 w-36 mt-2 p-2 z-50">
                                <li><button data-theme-value="light" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm rounded-md hover:bg-base-200 transition-colors"><span>☀️</span> Light</button></li>
                                <li><button data-theme-value="dark" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm rounded-md hover:bg-base-200 transition-colors"><span>🌙</span> Dark</button></li>
                            </ul>
                        </div>

                        <div class="dropdown dropdown-end">
                            <div tabindex="0" role="button" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-base-200 transition-colors">
                                <div class="w-7 h-7 rounded-full bg-primary text-primary-content flex items-center justify-center text-xs font-bold">
                                    {{ substr(Auth::user()->name, 0, 1) }}
                                </div>
                                <span class="text-sm font-medium text-base-content hidden sm:block">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4 text-base-content/40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-300 w-48 mt-2 p-2">
                                <li><a href="{{ route('profile.edit') }}" class="text-sm">Profile</a></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="text-sm text-error">Log Out</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 p-6 overflow-y-auto">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
