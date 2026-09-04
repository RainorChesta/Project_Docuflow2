@props(['title' => '', 'description' => '', 'heading' => '', 'size' => 'md'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DokuFlow') }} — {{ $title ?? __('Authentication') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        {{-- Flash-prevention: set data-theme before CSS renders. No stored choice = follow OS live --}}
        <script>(function(){var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light');document.documentElement.classList.toggle('dark',d);m.addEventListener('change',function(){var s=sessionStorage.getItem('theme:v2');var isDark=s==='dark'||(s!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',isDark?'dark':'light');document.documentElement.classList.toggle('dark',isDark)})})()</script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen min-h-[100dvh] bg-gradient-to-br from-primary/10 via-base-200 to-secondary/10 font-sans antialiased flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="card bg-base-100 w-full {{ $size === 'sm' ? 'max-w-sm' : 'max-w-md' }} shadow-2xl border border-base-200 overflow-hidden my-auto">
            <div class="card-body {{ $size === 'sm' ? 'p-4 sm:p-5' : 'p-4 sm:p-8' }}">
                <div class="text-center {{ $size === 'sm' ? 'mb-4' : 'mb-6' }} flex flex-col items-center {{ $size === 'sm' ? 'gap-2' : 'gap-3' }}">
                    <div class="self-end -mb-2 flex items-center gap-2">
                        <x-language-toggle />
                        <x-theme-toggle />
                    </div>
                    <a href="/" class="inline-block">
                        <x-application-logo class="mx-auto {{ $size === 'sm' ? 'h-10 w-10' : 'h-14 w-14' }} text-primary" />
                    </a>
                    <h1 class="{{ $size === 'sm' ? 'text-lg' : 'text-xl' }} font-bold text-base-content mt-2 break-words text-center">{{ $heading ?? config('app.name', 'DokuFlow') }}</h1>
                    <p class="text-base-content/50 {{ $size === 'sm' ? 'text-xs' : 'text-sm' }} mt-1">{{ $description ?? __('Silakan masuk ke akun Anda') }}</p>
                </div>

                {{ $slot }}

                <p class="text-center text-xs text-base-content/30 {{ $size === 'sm' ? 'mt-4 scale-90' : 'mt-6' }}">
                    &copy; {{ date('Y') }} {{ config('app.name', 'DokuFlow') }}
                </p>
            </div>
        </div>
    </body>
</html>
