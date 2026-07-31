<div class="dropdown dropdown-end">
    <button tabindex="0" role="button" class="btn btn-ghost btn-sm btn-square {{ $attributes->get('class') }}" id="themeToggleBtn" title="Switch theme" aria-label="Theme">
        <svg id="themeIconSun" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <svg id="themeIconMoon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
        <svg id="themeIconAuto" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
    </button>
    <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-300 w-40 mt-2 p-2 z-50">
        <li><button data-theme-value="light" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm rounded-md hover:bg-base-200 transition-colors"><span>☀️</span> Light</button></li>
        <li><button data-theme-value="dark" class="theme-option flex items-center gap-2 w-full px-3 py-2 text-sm rounded-md hover:bg-base-200 transition-colors"><span>🌙</span> Dark</button></li>
    </ul>
</div>
