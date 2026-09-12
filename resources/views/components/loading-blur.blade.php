<style>
    /* Instant zero-flash visibility during page navigation transitions */
    html.is-page-loading #global-loading-blur {
        opacity: 1 !important;
        pointer-events: auto !important;
        visibility: visible !important;
    }
    html.is-page-loading #loading-blur-content {
        opacity: 1 !important;
        transform: scale(1) !important;
        visibility: visible !important;
    }
</style>

{{-- Immediate pre-paint script to activate loading blur on next page if transitioning --}}
<script>
    (function() {
        try {
            var t = sessionStorage.getItem('dokuflow:page-loading');
            if (t && (Date.now() - parseInt(t, 10)) < 15000) {
                document.documentElement.classList.add('is-page-loading');
            }
        } catch (e) {}
    })();
</script>

<div id="global-loading-blur"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950/40 dark:bg-slate-950/70 backdrop-blur-md opacity-0 pointer-events-none transition-all duration-300 ease-out select-none cursor-wait"
     aria-hidden="true"
     role="status"
     aria-live="polite">

    {{-- Center Animated Logo & Pulsing Ambient Aura --}}
    <div id="loading-blur-content"
         class="relative flex items-center justify-center transform scale-95 opacity-0 transition-all duration-300 ease-out pointer-events-none">
        
        {{-- Ambient Glow Aura --}}
        <div class="absolute -inset-4 bg-gradient-to-tr from-primary/40 via-accent/30 to-primary/40 rounded-full blur-2xl animate-pulse"></div>

        {{-- Conic Rotating Spinner Ring --}}
        <div class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-full p-[3px] bg-gradient-to-tr from-primary via-accent to-primary/20 animate-spin flex items-center justify-center shadow-2xl shadow-primary/20" style="animation-duration: 2s;">
            <div class="w-full h-full bg-base-100/90 dark:bg-base-100/80 backdrop-blur-sm rounded-full"></div>
        </div>

        {{-- Floating Brand Logo --}}
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
            <img src="{{ asset('logo.png') }}"
                 alt="{{ config('app.name', 'DokuFlow') }}"
                 class="w-10 h-10 sm:w-12 sm:h-12 object-contain animate-loading-float drop-shadow-md" />
        </div>
    </div>
</div>

<script>
(function() {
    const overlay = document.getElementById('global-loading-blur');
    const content = document.getElementById('loading-blur-content');

    let safetyTimer = null;
    let isShowing = false;

    window.showLoadingBlur = function(title, subtitle) {
        if (!overlay || !content) return;

        // Persist loading state across page navigations
        try {
            sessionStorage.setItem('dokuflow:page-loading', Date.now().toString());
            document.documentElement.classList.add('is-page-loading');
        } catch(e) {}

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

        overlay.classList.remove('opacity-0', 'pointer-events-none');
        overlay.classList.add('opacity-100', 'pointer-events-auto');
        overlay.setAttribute('aria-hidden', 'false');

        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');

        isShowing = true;

        // Silent background failsafe timeout to prevent permanent lock if network drops
        clearTimeout(safetyTimer);
        safetyTimer = setTimeout(function() {
            window.hideLoadingBlur();
        }, 15000);
    };

    window.hideLoadingBlur = function() {
        try {
            sessionStorage.removeItem('dokuflow:page-loading');
            document.documentElement.classList.remove('is-page-loading');
        } catch(e) {}

        if (!overlay || !content) return;
        clearTimeout(safetyTimer);

        overlay.classList.remove('opacity-100', 'pointer-events-auto');
        overlay.classList.add('opacity-0', 'pointer-events-none');
        overlay.setAttribute('aria-hidden', 'true');

        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');

        isShowing = false;
    };

    // ─── Dismiss ONLY after the page has completely finished loading ───
    function onPageFullyLoaded() {
        setTimeout(function() {
            window.hideLoadingBlur();
        }, 120);
    }

    if (document.readyState === 'complete') {
        onPageFullyLoaded();
    } else {
        window.addEventListener('load', onPageFullyLoaded);
    }

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
        window.showLoadingBlur();
    });

    // Handle bfcache (Back/Forward navigation restores state from memory)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            window.hideLoadingBlur();
        }
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
            window.showLoadingBlur();
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

        window.showLoadingBlur();
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

            window.showLoadingBlur();
        }
        return originalFormSubmit.apply(this, arguments);
    };

    // ─── Legacy #loading-modal Compatibility Shim ───────────────────
    function initLegacyShim() {
        document.querySelectorAll('#loading-modal').forEach(function(legacyModal) {
            legacyModal.style.display = 'none';
            legacyModal.setAttribute('aria-hidden', 'true');
            legacyModal.showModal = function() {
                window.showLoadingBlur();
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
                window.showLoadingBlur();
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
