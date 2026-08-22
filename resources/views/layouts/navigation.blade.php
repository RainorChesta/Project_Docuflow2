<!-- Left Sidebar — SHARED untuk semua role (di-include dari layouts/app.blade.php).
     Compact mode (desktop, sidebar tertutup): lebar 72px, hanya icon + tooltip.
     Penting: fixed (off-canvas) HANYA di layar < lg. Di lg+ sidebar harus
     position:static (in-flow) supaya ikut "memakan" ruang flex — kalau tetap
     fixed, konten kanan TIDAK ikut melebar saat sidebar ditutup (fixed tidak
     pernah memakan space layout), cuma nav-nya hilang. -->
<aside class="bg-base-100 border-r border-base-300 flex flex-col shrink-0
              {{-- Mobile: off-canvas drawer --}}
              fixed inset-y-0 left-0 z-50 -translate-x-full lg:static lg:translate-x-0
              h-full
              overflow-hidden transition-[width,transform] duration-300 ease-in-out"
       :class="open ? 'translate-x-0 w-60 max-w-[85vw]' : '-translate-x-full lg:translate-x-0 lg:w-[72px]'">
    <!-- Logo -->
    <div class="h-16 flex items-center border-b border-base-300 shrink-0 overflow-hidden"
         :class="open ? 'px-4' : 'justify-center px-0'">
        
        <!-- OPEN STATE -->
        <div class="w-full flex items-center justify-between" x-show="open">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" :title="open ? '' : '{{ config('app.name', 'DokuFlow') }}'">
                <x-application-logo class="h-7 w-7 text-primary shrink-0" />
                <span class="font-bold text-base-content whitespace-nowrap">{{ config('app.name', 'DokuFlow') }}</span>
            </a>
            <button type="button"
                    class="btn btn-ghost btn-sm px-2 shrink-0 hidden lg:flex"
                    aria-label="Toggle sidebar"
                    x-on:click="toggle()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
        </div>

        <!-- CLOSED STATE -->
        <button type="button"
                class="group w-full h-full hidden lg:flex items-center justify-center"
                x-show="!open"
                aria-label="Toggle sidebar"
                x-on:click="toggle()">
            <div class="group-hover:hidden flex items-center justify-center">
                <x-application-logo class="h-7 w-7 text-primary shrink-0" />
            </div>
            <div class="hidden group-hover:flex items-center justify-center text-base-content">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </div>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto overflow-x-hidden">
        <span class="nav-section-label px-3 py-1.5 text-xs font-semibold text-base-content/40 uppercase tracking-wider whitespace-nowrap"
              :class="open ? '' : 'lg:hidden'">Menu</span>

        <a href="{{ route('dashboard') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('dashboard') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : 'Dashboard'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">Dashboard</span>
        </a>

        @if(!auth()->user()->isAdmin())
        <a href="{{ route('documents.index', ['type' => 'general']) }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('documents.*') && request('type', '') === 'general' ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Umum') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Umum') }}</span>
        </a>

        <a href="{{ route('documents.index', ['type' => 'mine']) }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('documents.*') && request('type', '') === 'mine' ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Saya') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Saya') }}</span>
        </a>

        <a href="{{ route('documents.index', ['type' => 'shared']) }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('documents.*') && request('type', '') === 'shared' ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : 'Shared Documents'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">Shared Documents</span>
        </a>

        <a href="{{ route('documents.index', ['type' => 'division']) }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('documents.*') && request('type', '') === 'division' ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Divisi') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Divisi') }}</span>
        </a>

        <a href="{{ route('signatures.requests.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('signatures.requests.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Signature Approvals') }}'">
            <div class="relative shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                @php($pendingTtdCount = auth()->user()->receivedSignatureRequests()->where('status', 'pending')->count())
                @if($pendingTtdCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="whitespace-nowrap flex-1 flex items-center justify-between" :class="open ? '' : 'lg:hidden'">
                <span>{{ __('Signature Approvals') }}</span>
                @if($pendingTtdCount > 0)
                    <span class="badge badge-error badge-xs font-bold text-white px-1.5">{{ $pendingTtdCount }}</span>
                @endif
            </span>
        </a>
        @endif

        @if(auth()->user()->isHead())
        <a href="{{ route('approvals.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('approvals.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Persetujuan') }}'">
            <div class="relative shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                @php($pendingApprovalCount = auth()->user()->pendingApprovalsCount())
                @if($pendingApprovalCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="whitespace-nowrap flex-1 flex items-center justify-between" :class="open ? '' : 'lg:hidden'">
                <span>{{ __('Persetujuan') }}</span>
                @if($pendingApprovalCount > 0)
                    <span class="badge badge-error badge-xs font-bold text-white px-1.5">{{ $pendingApprovalCount }}</span>
                @endif
            </span>
        </a>
        @endif

        @if(auth()->user()->isDirector())
        <a href="{{ route('director.documents.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('director.documents.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Semua Dokumen PT & Cabang') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Semua Dokumen (Direktur)') }}</span>
        </a>
        @endif

        @if(auth()->user()->isAdmin())
        <a href="{{ route('documents.index', ['type' => 'mine']) }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('documents.*') && request('type', '') === 'mine' ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Saya') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Saya') }}</span>
        </a>
        @endif

        @can('admin')
        <span class="nav-section-label px-3 pt-5 pb-1.5 text-xs font-semibold text-base-content/40 uppercase tracking-wider whitespace-nowrap"
              :class="open ? '' : 'lg:hidden'">{{ __('Administrasi') }}</span>

        <a href="{{ route('admin.companies.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.companies.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Perusahaan') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Perusahaan') }}</span>
        </a>

        <a href="{{ route('admin.branches.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.branches.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Cabang') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Cabang') }}</span>
        </a>

        <a href="{{ route('signatures.requests.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('signatures.requests.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Tanda Tangan') }}'">
            <div class="relative shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                @php($pendingAdminTtdCount = auth()->user()->receivedSignatureRequests()->where('status', 'pending')->count())
                @if($pendingAdminTtdCount > 0)
                    <span class="absolute -top-1 -right-1 flex h-2.5 w-2.5">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-error opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-error"></span>
                    </span>
                @endif
            </div>
            <span class="whitespace-nowrap flex-1 flex items-center justify-between" :class="open ? '' : 'lg:hidden'">
                <span>{{ __('Tanda Tangan') }}</span>
                @if($pendingAdminTtdCount > 0)
                    <span class="badge badge-error badge-xs font-bold text-white px-1.5">{{ $pendingAdminTtdCount }}</span>
                @endif
            </span>
        </a>

        <a href="{{ route('admin.divisions.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.divisions.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Divisi') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Divisi') }}</span>
        </a>

        <a href="{{ route('admin.documents.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.documents.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Semua Dokumen') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Semua Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.document-types.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.document-types.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Jenis Dokumen') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Jenis Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.users.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Pengguna') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Pengguna') }}</span>
        </a>

        <a href="{{ route('admin.retention.edit') }}"
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                  {{ request()->routeIs('admin.retention.*') ? 'nav-item-active bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Retensi') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="nav-item-icon h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Retensi') }}</span>
        </a>
        @endcan
    </nav>

    <!-- User footer — Profile + Logout dropdown (bottom-left) -->
    <div class="border-t border-base-300 p-3" x-data="{ profileOpen: false }">
        <button type="button"
                class="nav-user-footer w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-base-content/60 cursor-pointer"
                :class="open ? '' : 'lg:justify-center lg:px-0'"
                @click="profileOpen = !profileOpen"
                @click.outside="profileOpen = false">
            {{-- Avatar circle or photo --}}
            @if(Auth::user()->avatar_url)
                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="h-8 w-8 rounded-full object-cover border border-base-300 shrink-0 select-none">
            @else
                <div class="flex items-center justify-center h-8 w-8 rounded-full bg-primary/15 text-primary text-xs font-bold shrink-0 select-none">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1 min-w-0 text-left" :class="open ? '' : 'lg:hidden'">
                <div class="font-medium text-base-content truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-base-content/40 truncate">{{ Auth::user()->email }}</div>
            </div>
            {{-- Chevron --}}
            <svg class="h-4 w-4 text-base-content/30 shrink-0 transition-transform duration-200"
                 :class="[profileOpen ? 'rotate-180' : '', open ? '' : 'lg:hidden']"
                 xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
            </svg>
        </button>

        {{-- Dropdown popup --}}
        <div x-show="profileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="mt-1 py-1 bg-base-100 border border-base-300 rounded-lg shadow-lg overflow-hidden"
             x-cloak>
            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-2 px-3 py-2 text-sm text-base-content/70 hover:bg-base-200 hover:text-base-content transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span :class="open ? '' : 'lg:hidden'">{{ __('Profil') }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-error/70 hover:bg-error/10 hover:text-error transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span :class="open ? '' : 'lg:hidden'">{{ __('Keluar') }}</span>
                </button>
            </form>
        </div>
    </div>
</aside>
