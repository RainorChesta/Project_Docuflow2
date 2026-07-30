@props(['title' => '', 'description' => '', 'heading' => ''])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DokuFlow') }} — {{ $title ?? __('Authentication') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gradient-to-br from-primary/10 via-base-200 to-secondary/10 font-sans antialiased flex items-center justify-center p-4">
        <div class="card bg-base-100 w-full max-w-md shadow-2xl border border-base-200">
            <div class="card-body p-8">
                <div class="text-center mb-6">
                    <a href="/" class="inline-block">
                        <x-application-logo class="mx-auto h-14 w-14 text-primary" />
                    </a>
                    <h1 class="text-xl font-bold text-base-content mt-3">{{ $heading ?? config('app.name', 'DokuFlow') }}</h1>
                    <p class="text-base-content/50 text-sm mt-1">{{ $description ?? __('Silakan masuk ke akun Anda') }}</p>
                </div>

                {{ $slot }}

                <p class="text-center text-xs text-base-content/30 mt-6">
                    &copy; {{ date('Y') }} {{ config('app.name', 'DokuFlow') }}
                </p>
            </div>
        </div>
    </body>
</html>
