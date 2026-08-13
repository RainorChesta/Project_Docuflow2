<!-- Left Sidebar — SHARED untuk semua role (di-include dari layouts/app.blade.php).
     Compact mode (desktop, sidebar tertutup): lebar 72px, hanya icon + tooltip.
     Penting: fixed (off-canvas) HANYA di layar < lg. Di lg+ sidebar harus
     position:static (in-flow) supaya ikut "memakan" ruang flex — kalau tetap
     fixed, konten kanan TIDAK ikut melebar saat sidebar ditutup (fixed tidak
     pernah memakan space layout), cuma nav-nya hilang. -->
<aside class="bg-base-100 border-r border-base-300 flex flex-col shrink-0
              {{-- Mobile: off-canvas drawer --}}
              fixed inset-y-0 left-0 z-50 -translate-x-full lg:static lg:translate-x-0
              lg:sticky lg:top-0 lg:h-screen
              overflow-hidden transition-[width,transform] duration-300 ease-in-out"
       :class="open ? 'translate-x-0 w-60 max-w-[85vw]' : '-translate-x-full lg:translate-x-0 lg:w-[72px]'">
    <!-- Logo -->
    <div class="h-16 flex items-center border-b border-base-300 shrink-0 overflow-hidden"
         :class="open ? 'px-4' : 'justify-center px-0'">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5" :title="open ? '' : '{{ config('app.name', 'DokuFlow') }}'">
            <x-application-logo class="h-7 w-7 text-primary shrink-0" />
            <span class="font-bold text-base-content whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ config('app.name', 'DokuFlow') }}</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-3 space-y-1 overflow-y-auto overflow-x-hidden">
        <span class="px-3 py-1.5 text-xs font-semibold text-base-content/40 uppercase tracking-wider whitespace-nowrap"
              :class="open ? '' : 'lg:hidden'">Menu</span>

        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('dashboard') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : 'Dashboard'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">Dashboard</span>
        </a>

        @if(!auth()->user()->isAdmin())
        <a href="{{ route('documents.index', ['type' => 'general']) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('documents.*') && request('type', '') === 'general' ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Umum') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Umum') }}</span>
        </a>

        <a href="{{ route('documents.index', ['type' => 'mine']) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('documents.*') && request('type', '') === 'mine' ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Saya') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Saya') }}</span>
        </a>

        <a href="{{ route('documents.index', ['type' => 'division']) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('documents.*') && request('type', '') === 'division' ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Divisi') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Divisi') }}</span>
        </a>
        @endif

        @if(auth()->user()->isHead())
        <a href="{{ route('approvals.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('approvals.*') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Persetujuan') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Persetujuan') }}</span>
        </a>
        @endif

        @if(auth()->user()->isAdmin())
        <a href="{{ route('documents.index', ['type' => 'mine']) }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('documents.*') && request('type', '') === 'mine' ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Dokumen Saya') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Dokumen Saya') }}</span>
        </a>
        @endif

        @can('admin')
        <span class="px-3 pt-5 pb-1.5 text-xs font-semibold text-base-content/40 uppercase tracking-wider whitespace-nowrap"
              :class="open ? '' : 'lg:hidden'">{{ __('Administrasi') }}</span>

        <a href="{{ route('admin.divisions.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('admin.divisions.*') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Divisi') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Divisi') }}</span>
        </a>

        <a href="{{ route('admin.documents.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('admin.documents.*') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Semua Dokumen') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Semua Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.document-types.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('admin.document-types.*') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Jenis Dokumen') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Jenis Dokumen') }}</span>
        </a>

        <a href="{{ route('admin.users.index') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('admin.users.*') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Pengguna') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Pengguna') }}</span>
        </a>

        <a href="{{ route('admin.retention.edit') }}"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150
                  {{ request()->routeIs('admin.retention.*') ? 'bg-primary/10 text-primary' : 'text-base-content/60 hover:bg-base-200 hover:text-base-content' }}"
           :class="open ? '' : 'lg:justify-center lg:px-0'"
           :title="open ? '' : '{{ __('Retensi') }}'">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="whitespace-nowrap" :class="open ? '' : 'lg:hidden'">{{ __('Retensi') }}</span>
        </a>
        @endcan
    </nav>

    <!-- User footer -->
    <div class="border-t border-base-300 p-3">
        <div class="flex items-center gap-3 px-3 py-2 rounded-md text-sm text-base-content/60"
             :class="open ? '' : 'lg:justify-center lg:px-0'">
            <div class="flex-1 min-w-0" :class="open ? '' : 'lg:hidden'">
                <div class="font-medium text-base-content truncate">{{ Auth::user()->name }}</div>
                <div class="text-xs text-base-content/40 truncate">{{ Auth::user()->email }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="text-base-content/30 hover:text-error transition-colors" title="{{ __('Keluar') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
