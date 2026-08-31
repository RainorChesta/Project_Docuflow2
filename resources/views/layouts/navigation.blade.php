<!-- New Floating Glass Sidebar (Scratch Build) -->
<style>
    /* Bulletproof Liquid Glass */
    .glass-panel {
        background-color: rgba(255, 255, 255, 0.5) !important;
        backdrop-filter: blur(28px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(28px) saturate(200%) !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        box-shadow: 0 24px 48px -12px rgba(0,0,0,0.1), inset 0 1px 1px rgba(255,255,255,0.5) !important;
    }
    
    [data-theme='dark'] .glass-panel {
        background-color: rgba(15, 15, 20, 0.55) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 24px 48px -12px rgba(0,0,0,0.5), inset 0 1px 1px rgba(255,255,255,0.1) !important;
    }
    
    .nav-item-new {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    
    .nav-item-new:hover {
        background-color: transparent !important;
        transform: translateY(-2px);
        filter: drop-shadow(0px 8px 16px rgba(0, 0, 0, 1)) drop-shadow(0px 4px 6px rgba(0, 0, 0, 0));
    }
    
    [data-theme='dark'] .nav-item-new:hover {
        filter: drop-shadow(0px 8px 16px rgba(255, 255, 255, 1)) drop-shadow(0px 4px 6px rgba(255, 255, 255, 1));
    }
    
    /* Tactile click effect */
    .nav-item-new:active {
        transform: scale(0.96) translateY(0) !important;
        transition-duration: 0.1s !important;
        background-color: oklch(var(--b2)) !important;
    }
    
    .nav-item-new .icon-wrapper {
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 12px;
        background-color: oklch(var(--b2));
        color: oklch(var(--bc) / 0.5);
    }
    
    .nav-item-new:hover .icon-wrapper {
        background-color: oklch(var(--p));
        color: oklch(var(--pc));
        transform: scale(1.1) rotate(-4deg);
        box-shadow: 0 6px 16px -4px oklch(var(--p) / 0.5);
    }

    /* Icon squish on click */
    .nav-item-new:active .icon-wrapper {
        transform: scale(0.92) !important;
        transition-duration: 0.1s !important;
    }
    
    .nav-item-new-active {
        background-color: oklch(var(--b2) / 0.8) !important;
        box-shadow: inset 4px 0 0 0 oklch(var(--p)), 0 4px 12px -4px rgba(0,0,0,0.05) !important;
        color: oklch(var(--bc)) !important;
    }
    
    .nav-item-new-active .icon-wrapper {
        background-color: oklch(var(--p));
        color: oklch(var(--pc));
        box-shadow: 0 6px 16px -4px oklch(var(--p) / 0.5);
        transform: scale(1.05);
    }

    .nav-item-new-active span.text-label {
        color: oklch(var(--bc)) !important;
        font-weight: 700;
    }
    
    .nav-item-new span.text-label {
        transition: transform 0.25s ease, color 0.2s ease;
    }
    .nav-item-new:hover span.text-label {
        transform: translateX(3px);
        color: oklch(var(--bc));
    }
    
    .nav-item-new:active span.text-label {
        transform: translateX(1px) !important;
        transition-duration: 0.1s !important;
    }
    
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<aside class="glass-panel flex flex-col shrink-0
              fixed inset-y-0 left-0 z-50 -translate-x-full 
              lg:static lg:translate-x-0 lg:my-4 lg:ml-4 lg:rounded-[28px] lg:h-[calc(100vh-2rem)]
              overflow-hidden transition-all duration-300 ease-out"
       :class="open ? 'translate-x-0 w-[280px] max-w-[85vw]' : '-translate-x-full lg:translate-x-0 lg:w-[92px]'">
    
    <!-- Logo Header -->
    <div class="h-16 flex items-center shrink-0 overflow-hidden mt-4" :class="open ? 'px-6' : 'justify-center px-0'">
        <div class="w-full flex items-center justify-between" x-show="open">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-4 group">
                <div class="w-8 h-8 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <x-application-logo class="h-6 w-6 text-primary shrink-0" />
                </div>
                <span class="font-bold text-lg tracking-tight">{{ config('app.name', 'DokuFlow') }}</span>
            </a>
            <button type="button" class="btn btn-ghost btn-circle btn-sm hover:bg-base-200" @click="toggle()" aria-label="Toggle sidebar">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 lg:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden lg:block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
            </button>
        </div>

        <button type="button" class="w-full h-full hidden lg:flex items-center justify-center group mt-4" x-show="!open" @click="toggle()" aria-label="Expand sidebar">
            <div class="w-11 h-11 flex items-center justify-center rounded-2xl transition-all duration-300 border border-transparent group-hover:border-primary/50 group-hover:scale-110 group-hover:shadow-lg group-hover:shadow-primary/20 group-hover:-rotate-3 mb-3">
                <x-application-logo class="h-7 w-7 text-primary transition-all duration-300 shrink-0 group-hover:drop-shadow-sm" />
            </div>
        </button>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 px-4 mt-2 space-y-1 overflow-y-auto overflow-x-hidden scrollbar-hide">
        
        <span class="px-2 text-[10px] font-extrabold text-base-content/40 uppercase tracking-[0.2em] whitespace-nowrap" :class="open ? 'block' : 'lg:hidden'">{{ __('Menu') }}</span>

        <a href="{{ route('dashboard') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('dashboard') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Dashboard') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Dashboard') }}</span>
        </a>

        @if(auth()->user()->isDirector())
        <a href="{{ route('director.documents.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('director.documents.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Semua Dokumen') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Semua Dokumen') }}</span>
        </a>
        @endif

        @if(!auth()->user()->isAdmin())
        @if(!auth()->user()->isDirector())
        <a href="{{ route('documents.index', ['type' => 'general']) }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('documents.*') && request('type', '') === 'general' ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Dokumen Umum') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Umum') }}</span>
        </a>
        @endif

        <a href="{{ route('documents.index', ['type' => 'mine']) }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('documents.*') && request('type', '') === 'mine' ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Dokumen Saya') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Saya') }}</span>
        </a>

        @if(!auth()->user()->isDirector())
        <a href="{{ route('documents.index', ['type' => 'shared']) }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('documents.*') && request('type', '') === 'shared' ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Dokumen Dibagikan') }}'">
            <div class="icon-wrapper shrink-0 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                @php($sharedDocsCount = auth()->user()->sharedDocumentsCount())
                @if($sharedDocsCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 border-2 border-base-100 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="text-label min-w-0 flex-1 flex items-center justify-between gap-2" :class="open ? '' : 'lg:hidden'">
                <span class="truncate">{{ __('Dokumen Dibagikan') }}</span>
                @if($sharedDocsCount > 0)
                    <span class="badge badge-error badge-sm font-bold text-white px-1.5 shrink-0 shadow-sm shadow-error/50">{{ $sharedDocsCount }}</span>
                @endif
            </span>
        </a>
        @endif

        @if(!auth()->user()->isDirector())
        <a href="{{ route('documents.index', ['type' => 'division']) }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('documents.*') && request('type', '') === 'division' ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Dokumen Divisi') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Divisi') }}</span>
        </a>
        @endif

        <a href="{{ route('signatures.requests.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('signatures.requests.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Persetujuan TTD') }}'">
            <div class="icon-wrapper shrink-0 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                @php($pendingTtdCount = auth()->user()->receivedSignatureRequests()->where('status', 'pending')->count())
                @if($pendingTtdCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 border-2 border-base-100 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="text-label min-w-0 flex-1 flex items-center justify-between gap-2" :class="open ? '' : 'lg:hidden'">
                <span class="truncate">{{ __('Persetujuan TTD') }}</span>
                @if($pendingTtdCount > 0)
                    <span class="badge badge-error badge-sm font-bold text-white px-1.5 shrink-0 shadow-sm shadow-error/50">{{ $pendingTtdCount }}</span>
                @endif
            </span>
        </a>
        @endif

        @if(auth()->user()->isHead() || auth()->user()->isDirector() || auth()->user()->isAdmin())
        <a href="{{ route('approvals.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('approvals.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Persetujuan') }}'">
            <div class="icon-wrapper shrink-0 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @php($pendingApprovalCount = auth()->user()->pendingApprovalsCount())
                @if($pendingApprovalCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 border-2 border-base-100 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="text-label min-w-0 flex-1 flex items-center justify-between gap-2" :class="open ? '' : 'lg:hidden'">
                <span class="truncate">{{ __('Persetujuan') }}</span>
                @if($pendingApprovalCount > 0)
                    <span class="badge badge-error badge-sm font-bold text-white px-1.5 shrink-0 shadow-sm shadow-error/50">{{ $pendingApprovalCount }}</span>
                @endif
            </span>
        </a>
        @endif

        @if(auth()->user()->isAdmin())
        <a href="{{ route('documents.index', ['type' => 'mine']) }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('documents.*') && request('type', '') === 'mine' ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Dokumen Saya') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Saya') }}</span>
        </a>

        @endif

        @can('admin')
        <div class="pt-6 pb-2">
            <span class="px-2 text-[10px] font-extrabold text-base-content/40 uppercase tracking-[0.2em] whitespace-nowrap" :class="open ? 'block' : 'lg:hidden'">{{ __('Administrasi') }}</span>
        </div>

        <a href="{{ route('admin.companies.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.companies.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Perusahaan') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Perusahaan') }}</span>
        </a>

        <a href="{{ route('admin.branches.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.branches.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Cabang') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Cabang') }}</span>
        </a>

        <a href="{{ route('signatures.requests.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('signatures.requests.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Tanda Tangan') }}'">
            <div class="icon-wrapper shrink-0 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                @php($pendingAdminTtdCount = auth()->user()->receivedSignatureRequests()->where('status', 'pending')->count())
                @if($pendingAdminTtdCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-3 w-3 border-2 border-base-100 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="text-label min-w-0 flex-1 flex items-center justify-between gap-2" :class="open ? '' : 'lg:hidden'">
                <span class="truncate">{{ __('Tanda Tangan') }}</span>
                @if($pendingAdminTtdCount > 0)
                    <span class="badge badge-error badge-sm font-bold text-white px-1.5 shrink-0 shadow-sm shadow-error/50">{{ $pendingAdminTtdCount }}</span>
                @endif
            </span>
        </a>

        <a href="{{ route('admin.divisions.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.divisions.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Divisi') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Divisi') }}</span>
        </a>

        <a href="{{ route('admin.documents.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.documents.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Semua Dokumen') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Semua Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.document-types.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.document-types.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Jenis Dokumen') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Jenis Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.templates.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.templates.*') || request()->routeIs('admin.template-categories.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Template Dokumen') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Template Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.users.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Pengguna') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Pengguna') }}</span>
        </a>

        <a href="{{ route('admin.retention.edit') }}"
           class="nav-item-new flex items-center gap-3.5 px-2 py-2 rounded-xl text-[14px] font-semibold text-base-content/60
                  {{ request()->routeIs('admin.retention.*') ? 'nav-item-new-active' : '' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0 lg:py-3'"
           :title="open ? '' : '{{ __('Retensi') }}'">
            <div class="icon-wrapper shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <span class="text-label min-w-0 flex-1 truncate" :class="open ? '' : 'lg:hidden'">{{ __('Retensi') }}</span>
        </a>
        @endcan
    </nav>

    <!-- User Footer (Redesigned) -->
    <div class="mt-auto px-4 pb-4 pt-2" x-data="{ profileOpen: false }">
        <button type="button"
                class="w-full flex items-center gap-3.5 p-2 rounded-2xl border border-base-300 bg-base-100/50 hover:bg-base-200 hover:shadow-lg transition-all duration-300 relative group"
                :class="open ? '' : 'lg:justify-center lg:px-0 lg:border-transparent lg:bg-transparent lg:hover:bg-transparent'"
                @click="profileOpen = !profileOpen"
                @click.outside="profileOpen = false">
                
            @if(Auth::user()->avatar_url)
                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="h-10 w-10 rounded-[10px] object-cover shrink-0 shadow-sm group-hover:scale-105 transition-transform">
            @else
                <div class="flex items-center justify-center h-10 w-10 rounded-[10px] bg-primary/20 text-primary text-sm font-bold shrink-0 group-hover:scale-105 transition-transform shadow-sm">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
            @endif
            
            <div class="flex-1 min-w-0 text-left" :class="open ? '' : 'lg:hidden'">
                <div class="font-bold text-sm text-base-content truncate leading-tight">{{ Auth::user()->name }}</div>
                <div class="text-[11px] font-medium text-base-content/50 truncate">{{ Auth::user()->email }}</div>
            </div>
            
            <svg class="h-4 w-4 text-base-content/40 shrink-0 mr-1 transition-transform duration-300 group-hover:text-base-content"
                 :class="[profileOpen ? 'rotate-180' : '', open ? '' : 'lg:hidden']"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>

        <!-- Dropdown popup -->
        <div x-show="profileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             class="absolute bottom-[72px] left-4 right-4 bg-base-100 border border-base-300 rounded-[16px] shadow-2xl overflow-hidden z-50 p-1"
             x-cloak>
            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-[12px] text-sm font-semibold text-base-content/70 hover:bg-base-200 hover:text-base-content transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <span :class="open ? '' : 'lg:hidden'">{{ __('Profil') }}</span>
            </a>
            <div class="h-px bg-base-300/50 my-1"></div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-[12px] text-sm font-semibold text-error hover:bg-error/10 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                    <span :class="open ? '' : 'lg:hidden'">{{ __('Keluar') }}</span>
                </button>
            </form>
        </div>
    </div>
</aside>
