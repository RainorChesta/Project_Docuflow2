<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'DokuFlow') }} — Document Management System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    {{-- Flash-prevention: set data-theme before CSS renders. No stored choice = follow OS live --}}
    <script>(function(){var t=sessionStorage.getItem('theme:v2'),m=window.matchMedia('(prefers-color-scheme: dark)'),d=(t==='dark')||(t!=='light'&&m.matches);document.documentElement.setAttribute('data-theme',d?'dark':'light')})()</script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 font-sans antialiased">
    <div class="flex flex-col min-h-screen">
        <!-- Nav -->
        @if (Route::has('login'))
        <div class="navbar bg-base-100/80 backdrop-blur-sm border-b border-base-300 sticky top-0 z-50">
            <div class="flex-1">
                <a href="/" class="flex items-center gap-2 px-2">
                    <x-application-logo class="h-8 w-8 text-primary" />
                    <span class="text-lg font-bold text-base-content">{{ config('app.name', 'DokuFlow') }}</span>
                </a>
            </div>
            <div class="flex-none gap-2">
                {{-- Theme Toggle: follows OS until user picks Light/Dark --}}
                <x-theme-toggle />
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
                    @endif
                @endauth
            </div>
        </div>
        @endif

        <!-- Hero -->
        <section class="flex-1 bg-gradient-to-br from-primary/[0.08] via-base-200 to-secondary/[0.08]">
            <div class="max-w-6xl mx-auto px-6 py-20 lg:py-28 flex flex-col lg:flex-row items-center gap-16">
                <!-- Left -->
                <div class="flex-1 text-center lg:text-left">
                    <div class="badge badge-primary badge-outline mb-5">Document Workflow Platform</div>
                    <h1 class="text-4xl lg:text-5xl xl:text-6xl font-extrabold text-base-content leading-[1.1] tracking-tight">
                        Kelola dokumen<br />
                        <span class="text-primary">dalam satu alur</span>
                    </h1>
                    <p class="text-base-content/60 mt-5 max-w-lg mx-auto lg:mx-0 leading-relaxed text-base lg:text-lg">
                        DokuFlow mengelola siklus hidup dokumen — dari pembuatan, revisi, 
                        persetujuan, hingga distribusi — dengan pelacakan <span class="text-base-content font-semibold">real-time</span> 
                        di setiap divisi.
                    </p>
                    <div class="flex flex-wrap gap-4 mt-10 justify-center lg:justify-start">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-primary btn-lg px-8">Buka Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-primary btn-lg px-8">Mulai Sekarang</a>
                        @endauth
                    </div>
                </div>

                <!-- Right: Workflow visual -->
                <div class="flex-1 w-full max-w-md">
                    <div class="card bg-base-100 border border-base-300 shadow-sm">
                        <div class="card-body p-6">
                            <div class="flex items-center gap-2 mb-7">
                                <div class="flex gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-error"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-warning"></span>
                                    <span class="w-2.5 h-2.5 rounded-full bg-success"></span>
                                </div>
                                <span class="text-xs font-medium text-base-content/40 ml-2">Workflow</span>
                            </div>

                            <!-- Step 1 -->
                            <div class="flex items-start gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-sm font-semibold">1</div>
                                    <div class="w-px h-9 bg-base-300"></div>
                                </div>
                                <div class="pb-5">
                                    <p class="font-semibold text-sm text-base-content">Dibuat</p>
                                    <p class="text-xs text-base-content/50 mt-0.5">User membuat draft dokumen baru</p>
                                </div>
                            </div>
                            <!-- Step 2 -->
                            <div class="flex items-start gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full bg-warning/10 text-warning flex items-center justify-center text-sm font-semibold">2</div>
                                    <div class="w-px h-9 bg-base-300"></div>
                                </div>
                                <div class="pb-5">
                                    <p class="font-semibold text-sm text-base-content">Menunggu</p>
                                    <p class="text-xs text-base-content/50 mt-0.5">Division Head meninjau & menyetujui</p>
                                </div>
                            </div>
                            <!-- Step 3 -->
                            <div class="flex items-start gap-4">
                                <div class="flex flex-col items-center">
                                    <div class="w-8 h-8 rounded-full bg-success/10 text-success flex items-center justify-center text-sm font-semibold">3</div>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-base-content">Terbit</p>
                                    <p class="text-xs text-base-content/50 mt-0.5">Dokumen aktif & bisa dibagikan</p>
                                </div>
                            </div>

                            <div class="divider my-4"></div>

                            <div class="space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-base-content/50">Revisi v2.3</span>
                                    <span class="badge badge-success badge-sm gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                        Active
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-base-content/50">Approval cycle</span>
                                    <span class="badge badge-warning badge-sm gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        Pending
                                    </span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-xs text-base-content/50">Shared links</span>
                                    <span class="badge badge-ghost badge-sm">3 active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer footer-center bg-base-100 border-t border-base-300 p-5 text-xs text-base-content/30">
            <span>&copy; {{ date('Y') }} {{ config('app.name', 'DokuFlow') }}. All rights reserved.</span>
        </footer>
    </div>
</body>
</html>
 