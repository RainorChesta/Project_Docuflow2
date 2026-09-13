<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ config('app.name', 'DokuFlow') }} — Document Management System</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo.png') }}">

    <!-- New Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script>(function(){var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light');document.documentElement.classList.toggle('dark',d)})()</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
        }
        
        /* Minimalist Grid Pattern */
        .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(0,0,0,0.1) 1px, transparent 1px), linear-gradient(to bottom, rgba(0,0,0,0.1) 1px, transparent 1px);
            background-size: 32px 32px;
        }
        @media (min-width: 640px) {
            .bg-grid-pattern {
                background-size: 40px 40px;
            }
        }
        [data-theme='dark'] .bg-grid-pattern {
            background-image: linear-gradient(to right, rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(to bottom, rgba(255,255,255,0.06) 1px, transparent 1px);
        }
        
        /* Typing Animation - Fluid and Adaptive on all screen sizes */
        .typing-text {
            display: inline;
            border-right: 2px solid currentColor;
            padding-right: 3px;
            animation: blink-caret 0.75s step-end infinite;
            word-break: normal;
            overflow-wrap: break-word;
        }
        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: currentColor; }
        }
    </style>
</head>
<body class="min-h-screen bg-base-100 text-base-content antialiased flex flex-col selection:bg-primary selection:text-primary-content overflow-x-hidden">

    <!-- Nav -->
    @if (Route::has('login'))
    <nav class="sticky top-0 z-50 w-full border-b border-base-200 bg-base-100/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center gap-1.5 sm:gap-4">
                <div class="flex items-center gap-2 shrink-0">
                    <x-application-logo class="h-7 w-7 sm:h-8 sm:w-8 text-primary shrink-0" />
                    <span class="text-base sm:text-xl font-bold tracking-tight text-base-content">{{ config('app.name', 'DokuFlow') }}</span>
                </div>
                <div class="flex items-center gap-1 sm:gap-3 shrink-0">
                    <div class="flex items-center gap-0.5 sm:gap-1.5">
                        <x-language-toggle />
                        <x-theme-toggle />
                    </div>
                    <div class="flex items-center gap-1 sm:gap-2">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-xs min-[400px]:text-sm font-semibold hover:text-primary transition-colors px-2 sm:px-3 py-1.5 whitespace-nowrap">{{ __('Dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="text-xs min-[400px]:text-sm font-semibold hover:text-primary transition-colors px-2 sm:px-3 py-1.5 whitespace-nowrap">{{ __('Log in') }}</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="text-xs min-[400px]:text-sm font-semibold bg-primary text-primary-content px-3 sm:px-4 py-1.5 sm:py-2 rounded-full hover:bg-primary/90 transition-colors shadow-sm whitespace-nowrap">{{ __('Sign up') }}</a>
                            @endif
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>
    @endif

    <!-- Hero Section -->
    <main class="flex-1 relative flex flex-col items-center justify-center py-8 sm:py-16 md:py-20 lg:py-24 px-4 sm:px-6 lg:px-8 overflow-hidden bg-grid-pattern">
        <!-- Radial gradient to highlight the center and fade the grid -->
        <div class="absolute inset-0 bg-base-100 [mask-image:radial-gradient(ellipse_at_center,transparent_20%,black_70%)] pointer-events-none"></div>
        
        <div class="relative z-10 w-full max-w-4xl mx-auto text-center flex flex-col items-center">
            
            <div class="inline-flex items-center gap-2 px-3 py-1 mb-4 sm:mb-6 md:mb-8 rounded-full border border-base-300 bg-base-200/50 text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-base-content/80 max-w-full">
                <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse shrink-0"></span>
                <span class="truncate">{{ __('DokuFlow Workflow Engine') }}</span>
            </div>
            
            <h1 class="text-[23px] min-[390px]:text-[26px] min-[520px]:text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight sm:tracking-tighter leading-[1.2] sm:leading-[1.1] mb-4 sm:mb-6 w-full text-center">
                <span class="block text-center">{{ __('Control your documents.') }}</span>
                <span class="block text-base-content/60 transition-all duration-300 hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-secondary cursor-default mt-1 sm:mt-2 text-center">
                    <span class="typing-text inline text-center" id="typewriter-text"></span>
                </span>
            </h1>
            
            <p class="max-w-xl text-sm sm:text-base md:text-lg text-base-content/70 font-normal sm:font-medium mb-6 sm:mb-10 px-2 sm:px-0 text-center leading-relaxed">
                {{ __('A unified platform to create, review, approve, and distribute your corporate documents with complete audit trails and automated workflows.') }}
            </p>
            
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 w-full sm:w-auto justify-center items-stretch sm:items-center max-w-xs sm:max-w-none mx-auto">
                @auth
                    <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 sm:px-8 py-3 sm:py-3.5 text-sm font-bold bg-primary text-primary-content rounded-full hover:bg-primary/90 transition-transform hover:scale-105 active:scale-95 shadow-xl shadow-primary/20 whitespace-nowrap">
                        {{ __('Go to Workspace') }}
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                    </a>
                @else
                    <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 sm:px-8 py-3 sm:py-3.5 text-sm font-bold bg-primary text-primary-content rounded-full hover:bg-primary/90 transition-transform hover:scale-105 active:scale-95 shadow-xl shadow-primary/20 whitespace-nowrap">
                        {{ __('Sign up') }}
                    </a>
                    <a href="{{ route('login') }}" class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-6 sm:px-8 py-3 sm:py-3.5 text-sm font-bold bg-base-200 text-base-content rounded-full border border-base-300 hover:bg-base-300 transition-colors whitespace-nowrap">
                        {{ __('Sign in') }}
                    </a>
                @endauth
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-6 sm:py-8 text-center text-xs sm:text-sm font-medium text-base-content/50 border-t border-base-200 bg-base-100 z-10 relative px-4">
        &copy; {{ date('Y') }} {{ config('app.name', 'DokuFlow') }}. {{ __('All rights reserved.') }}
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const text = @json(__('From draft to distribution.'));
            const el = document.getElementById("typewriter-text");
            if (!el) return;
            let i = 0;
            const speed = 75; // milliseconds per character
            
            function type() {
                if (i < text.length) {
                    el.textContent += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            
            // Start the typing effect with a slight delay
            setTimeout(type, 500);
        });
    </script>
</body>
</html>