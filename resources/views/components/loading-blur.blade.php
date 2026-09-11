<div id="global-loading-blur"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/40 dark:bg-slate-950/70 backdrop-blur-md opacity-0 pointer-events-none transition-all duration-300 ease-out cursor-pointer"
     aria-hidden="true"
     role="status"
     aria-live="polite">

    {{-- Center Frosted Glass Card --}}
    <div id="loading-blur-card"
         class="glass-panel relative flex flex-col items-center justify-center p-6 sm:p-8 rounded-3xl border border-base-300/70 dark:border-white/10 bg-base-100/90 dark:bg-base-100/80 shadow-[0_20px_50px_rgba(0,0,0,0.25)] dark:shadow-[0_20px_50px_rgba(0,0,0,0.5)] max-w-[320px] w-full mx-4 text-center transform scale-95 opacity-0 transition-all duration-300 ease-out cursor-default"
         onclick="event.stopPropagation()">
        
        {{-- Close Button for immediate manual dismissal --}}
        <button type="button"
                onclick="window.hideLoadingBlur()"
                class="absolute top-3 right-3 w-7 h-7 rounded-full flex items-center justify-center text-base-content/40 hover:text-base-content hover:bg-base-200/80 transition-all cursor-pointer"
                title="{{ __('Tutup') }}"
                aria-label="{{ __('Tutup') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Animated Rotating Ring & Pulsing Logo Aura --}}
        <div class="relative flex items-center justify-center mb-4">
            {{-- Ambient Glow Aura --}}
            <div class="absolute -inset-3 bg-gradient-to-tr from-primary/30 via-accent/20 to-primary/30 rounded-full blur-xl animate-pulse"></div>

            {{-- Conic Rotating Spinner Ring --}}
            <div class="relative w-16 h-16 rounded-full p-[2.5px] bg-gradient-to-tr from-primary via-accent to-primary/20 animate-spin flex items-center justify-center" style="animation-duration: 2s;">
                <div class="w-full h-full bg-base-100 rounded-full"></div>
            </div>

            {{-- Floating Brand Logo --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <img src="{{ asset('logo.png') }}"
                     alt="{{ config('app.name', 'DokuFlow') }}"
                     class="w-8 h-8 object-contain animate-loading-float drop-shadow-sm" />
            </div>
        </div>

        {{-- Dynamic Text --}}
        <h3 id="loading-blur-title"
            class="text-base sm:text-lg font-bold text-base-content tracking-tight transition-all duration-200">
            {{ __('Memuat...') }}
        </h3>
        
        <p id="loading-blur-subtitle"
           class="text-xs sm:text-sm text-base-content/60 max-w-[240px] mt-1 leading-relaxed transition-all duration-200">
            {{ __('Mohon tunggu sebentar...') }}
        </p>

        {{-- Animated Gradient Shimmer Progress Indicator --}}
        <div class="w-36 h-1 bg-base-300/60 dark:bg-base-300/30 rounded-full overflow-hidden relative mt-4">
            <div class="loading-shimmer-bar absolute inset-y-0 w-1/2 bg-gradient-to-r from-transparent via-primary to-transparent rounded-full"></div>
        </div>

        {{-- Subtle dismiss hint --}}
        <button type="button"
                onclick="window.hideLoadingBlur()"
                class="text-[11px] text-base-content/40 hover:text-base-content/70 mt-3.5 transition-colors cursor-pointer focus:outline-none">
            {{ __('Klik di luar area untuk menutup') }}
        </button>
    </div>
</div>

<script>
(function() {
    const overlay = document.getElementById('global-loading-blur');
    const card = document.getElementById('loading-blur-card');
    const titleEl = document.getElementById('loading-blur-title');
    const subtitleEl = document.getElementById('loading-blur-subtitle');

    const defaultTitle = @json(__('Memuat...'));
    const defaultSubtitle = @json(__('Mohon tunggu sebentar...'));

    let safetyTimer = null;
    let isShowing = false;

    window.showLoadingBlur = function(title, subtitle) {
        if (!overlay || !card) return;

        // Auto-close all open native <dialog> elements so they never conflict or hang behind
        try {
            document.querySelectorAll('dialog[open]').forEach(function(dlg) {
                try { dlg.close(); } catch (err) {}
            });
        } catch (e) {}

        // Also dispatch Alpine close-modal event
        try {
            window.dispatchEvent(new CustomEvent('close-modal'));
        } catch (e) {}
        
        if (titleEl) titleEl.textContent = title || defaultTitle;
        if (subtitleEl) subtitleEl.textContent = subtitle || defaultSubtitle;

        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100', 'pointer-events-auto');
        overlay.setAttribute('aria-hidden', 'false');

        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');

        isShowing = true;

        // Failsafe auto-dismiss after 8s so user is never trapped if request lags or fails
        clearTimeout(safetyTimer);
        safetyTimer = setTimeout(function() {
            window.hideLoadingBlur();
        }, 8000);
    };

    window.hideLoadingBlur = function() {
        if (!overlay || !card) return;
        clearTimeout(safetyTimer);

        overlay.classList.remove('opacity-100', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.setAttribute('aria-hidden', 'true');

        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');

        isShowing = false;
    };

    // ─── Backdrop click to dismiss ("click out of area the modal") ───
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            window.hideLoadingBlur();
        }
    });

    // ─── Escape key to dismiss ──────────────────────────────────────
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && isShowing) {
            window.hideLoadingBlur();
        }
    });

    // ─── Event-based API for Alpine / Livewire / Vanilla JS ─────────
    window.addEventListener('loading:show', function(e) {
        const detail = e.detail || {};
        window.showLoadingBlur(detail.title, detail.subtitle);
    });

    window.addEventListener('loading:hide', function() {
        window.hideLoadingBlur();
    });

    // ─── Page Lifecycle & Browser Refresh / bfcache ─────────────────
    // Fired on browser refresh, tab close, or navigating away via address bar / reload button
    window.addEventListener('beforeunload', function() {
        window.showLoadingBlur(defaultTitle, defaultSubtitle);
    });

    // Handle bfcache (Back/Forward navigation restores state from memory)
    window.addEventListener('pageshow', function(e) {
        window.hideLoadingBlur();
    });
    window.addEventListener('popstate', function() {
        window.hideLoadingBlur();
    });

    // ─── Automatic Navigation Links Trigger (Sidebar, menus, etc.) ──
    document.addEventListener('click', function(e) {
        if (e.defaultPrevented) return;

        // Find closest <a> anchor tag
        const link = e.target.closest('a');
        if (!link) return;

        // Don't intercept if modified keys pressed (open in new tab/window)
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) {
            return;
        }

        // Skip non-navigational links opted out, target blank, or downloads
        if (link.hasAttribute('data-no-loading') || link.getAttribute('target') === '_blank' || link.hasAttribute('download')) {
            return;
        }

        const href = link.href || link.getAttribute('href');
        if (!href) return;

        // Skip non-http navigation (anchors, javascript, mailto, tel)
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
            return;
        }

        try {
            const url = new URL(href, window.location.origin);
            // Must be same origin
            if (url.origin !== window.location.origin) return;

            // Skip same page anchor jumps
            if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash !== '') {
                return;
            }

            // Skip file download routes
            if (url.pathname.includes('/download') || url.pathname.includes('/export')) {
                return;
            }

            // Instantly show loading blur
            window.showLoadingBlur(
                link.getAttribute('data-loading-title') || @json(__('Memuat Halaman...')),
                link.getAttribute('data-loading-subtitle') || @json(__('Menyiapkan konten dokumen...'))
            );
        } catch (err) {
            // Let standard navigation proceed
        }
    });

    // ─── Automatic Form Submission Trigger ──────────────────────────
    document.addEventListener('submit', function(e) {
        if (e.defaultPrevented) return;
        const form = e.target;
        if (!form || form.tagName !== 'FORM') return;

        // Skip dialog closing forms (DaisyUI/HTML5 dialog backdrop)
        if (form.getAttribute('method')?.toLowerCase() === 'dialog') {
            return;
        }

        // Skip forms opted-out or targeting new tab/window
        if (form.hasAttribute('data-no-loading') || form.getAttribute('target') === '_blank') {
            return;
        }

        // HTML5 validation check
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }

        // If inside a dialog, close the dialog immediately
        const parentDlg = form.closest('dialog');
        if (parentDlg && typeof parentDlg.close === 'function') {
            try { parentDlg.close(); } catch(err) {}
        }

        const customTitle = form.getAttribute('data-loading-title') || @json(__('Memproses Permintaan...'));
        const customSubtitle = form.getAttribute('data-loading-subtitle') || @json(__('Sedang memproses data, mohon tunggu...'));

        window.showLoadingBlur(customTitle, customSubtitle);
    });

    // ─── Programmatic form.submit() Trigger (for dropdown filters, switchers, etc.) ──
    const originalFormSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function() {
        if (!this.hasAttribute('data-no-loading') && 
            this.getAttribute('target') !== '_blank' && 
            this.getAttribute('method')?.toLowerCase() !== 'dialog') {
            
            const parentDlg = this.closest('dialog');
            if (parentDlg && typeof parentDlg.close === 'function') {
                try { parentDlg.close(); } catch(err) {}
            }

            const customTitle = this.getAttribute('data-loading-title') || @json(__('Memuat Data...'));
            const customSubtitle = this.getAttribute('data-loading-subtitle') || @json(__('Sedang memproses data, mohon tunggu...'));
            window.showLoadingBlur(customTitle, customSubtitle);
        }
        return originalFormSubmit.apply(this, arguments);
    };

    // ─── Legacy #loading-modal Compatibility Shim ───────────────────
    function initLegacyShim() {
        document.querySelectorAll('#loading-modal').forEach(function(legacyModal) {
            legacyModal.style.display = 'none';
            legacyModal.setAttribute('aria-hidden', 'true');
            legacyModal.showModal = function() {
                const title = legacyModal.querySelector('h3')?.textContent || @json(__('Memproses Dokumen...'));
                const sub = legacyModal.querySelector('p')?.textContent || @json(__('Harap tunggu sebentar, sistem sedang memproses...'));
                window.showLoadingBlur(title, sub);
            };
            legacyModal.close = function() {
                window.hideLoadingBlur();
            };
        });

        if (!document.getElementById('loading-modal')) {
            const dummy = document.createElement('div');
            dummy.id = 'loading-modal';
            dummy.style.display = 'none';
            dummy.showModal = function() {
                window.showLoadingBlur(@json(__('Memproses Dokumen...')), @json(__('Harap tunggu sebentar, sistem sedang memproses...')));
            };
            dummy.close = function() {
                window.hideLoadingBlur();
            };
            document.body.appendChild(dummy);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLegacyShim);
    } else {
        initLegacyShim();
    }
})();
</script>
