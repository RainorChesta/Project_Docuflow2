<div class="dropdown dropdown-end" aria-label="{{ __('Pilih Bahasa') }}">
    <div tabindex="0" role="button" aria-haspopup="true" class="btn btn-ghost btn-sm px-2 gap-1.5 flex items-center" title="{{ __('Pilih Bahasa') }}" aria-label="{{ __('Pilih Bahasa') }}: {{ strtoupper(app()->getLocale()) }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-base-content/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        <span class="text-xs font-semibold uppercase tracking-wider text-base-content">{{ app()->getLocale() }}</span>
        <svg class="w-3 h-3 text-base-content/40" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
    </div>
    <ul tabindex="0" role="menu" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-300 w-36 mt-2 p-1.5 z-50">
        <li role="none">
            <a role="menuitem" href="{{ route('language.switch', 'id') }}" class="flex items-center justify-between py-2 text-xs {{ app()->getLocale() === 'id' ? 'active font-semibold' : '' }}" aria-label="Bahasa Indonesia">
                <span>🇮🇩 Indonesia</span>
                @if(app()->getLocale() === 'id')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                @endif
            </a>
        </li>
        <li role="none">
            <a role="menuitem" href="{{ route('language.switch', 'en') }}" class="flex items-center justify-between py-2 text-xs {{ app()->getLocale() === 'en' ? 'active font-semibold' : '' }}" aria-label="English">
                <span>🇬🇧 English</span>
                @if(app()->getLocale() === 'en')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                @endif
            </a>
        </li>
    </ul>
</div>
