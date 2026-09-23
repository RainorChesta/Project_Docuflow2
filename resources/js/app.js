import Alpine from 'alpinejs';
import { initPreviewPagination } from './paper-pagination';

// ─── Laravel Echo (Reverb WebSocket) ────────────────────────────
// Only initialise if Reverb env vars are set (makes Reverb optional).
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
window.Pusher = Pusher;

if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
        wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}

// ─── Global Double-Submit & Multi-Click Protection ───────────────
// Automatically intercepts submit events on approval, signature, and marked forms,
// disabling submit buttons and preventing concurrent double submissions.
if (typeof document !== 'undefined') {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form || !(form instanceof HTMLFormElement)) return;

        const method = (form.getAttribute('method') || 'GET').toUpperCase();
        if (method === 'GET') return;

        const action = form.getAttribute('action') || '';
        const isProtectedAction = action.includes('/approvals') || 
                                  action.includes('/signatures') || 
                                  action.includes('/shares') ||
                                  form.hasAttribute('data-prevent-double-submit');

        if (!isProtectedAction) return;

        // If form is already submitting, block duplicate submission completely
        if (form.dataset.isSubmitting === 'true') {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }

        form.dataset.isSubmitting = 'true';

        // Find submit button(s) and disable with visual loading indicator
        const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
        submitBtns.forEach((btn) => {
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');

            if (!btn.querySelector('.loading') && !btn.querySelector('.loading-spinner')) {
                const spinner = document.createElement('span');
                spinner.className = 'loading loading-spinner loading-xs mr-1.5';
                btn.prepend(spinner);
            }
        });
    }, true);
}

// ─── Modal & Action Scroll Protection ───────────────────────────
// Prevents browser native <dialog>.showModal(), button clicks, focus shifts,
// and embedded iframes (like ONLYOFFICE) from automatically scrolling the page down.
if (typeof window !== 'undefined') {
    let lastUserScrollTime = 0;
    let userScrollTimer = null;

    const markUserScroll = () => {
        lastUserScrollTime = Date.now();
        clearTimeout(userScrollTimer);
        userScrollTimer = setTimeout(() => {
            lastUserScrollTime = 0;
        }, 200);
    };

    window.addEventListener('wheel', markUserScroll, { passive: true, capture: true });
    window.addEventListener('touchmove', markUserScroll, { passive: true, capture: true });

    // 1. Global Button & Interactive Click Scroll Stabilizer
    window.addEventListener('pointerdown', (e) => {
        const target = e.target;
        if (!target) return;
        const isInteractive = target.closest('button, [role="button"], a, input[type="button"], input[type="submit"], select, .btn, [onclick], [x-on\\:click]');
        if (!isInteractive) return;

        // Don't interfere with standard in-page anchor navigation (e.g. href="#section")
        const href = isInteractive.getAttribute?.('href') || '';
        if (href.startsWith('#') && href.length > 1) return;

        const mainEl = document.querySelector('main') || document.documentElement;
        const beforeScrollTop = mainEl ? mainEl.scrollTop : 0;
        const beforeScrollY = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
        const beforeScrollX = window.scrollX || document.documentElement.scrollLeft || document.body.scrollLeft || 0;

        const restore = () => {
            if (Date.now() - lastUserScrollTime > 250) {
                if (mainEl && mainEl.scrollTop !== beforeScrollTop) {
                    mainEl.scrollTop = beforeScrollTop;
                }
                if ((window.scrollY || document.documentElement.scrollTop || document.body.scrollTop) !== beforeScrollY) {
                    window.scrollTo({ top: beforeScrollY, left: beforeScrollX, behavior: 'instant' });
                }
            }
        };

        requestAnimationFrame(restore);
        setTimeout(restore, 0);
        setTimeout(restore, 20);
        setTimeout(restore, 60);
        setTimeout(restore, 150);
        setTimeout(restore, 350);
    }, { capture: true, passive: true });

    // 2. HTMLDialogElement showModal & close Protection
    if (typeof HTMLDialogElement !== 'undefined') {
        const originalShowModal = HTMLDialogElement.prototype.showModal;
        const originalClose = HTMLDialogElement.prototype.close;

        HTMLDialogElement.prototype.showModal = function() {
            const mainEl = document.querySelector('main') || document.documentElement;
            const mainScroll = mainEl ? mainEl.scrollTop : 0;
            const winScrollY = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const winScrollX = window.scrollX || document.documentElement.scrollLeft || document.body.scrollLeft || 0;
            
            try {
                originalShowModal.call(this);
            } catch (e) {
                // Already open or unsupported state
            }

            const restoreScroll = () => {
                if (mainEl && mainEl.scrollTop !== mainScroll) {
                    mainEl.scrollTop = mainScroll;
                }
                if ((window.scrollY || document.documentElement.scrollTop || document.body.scrollTop) !== winScrollY) {
                    window.scrollTo({ top: winScrollY, left: winScrollX, behavior: 'instant' });
                }
            };

            restoreScroll();
            requestAnimationFrame(restoreScroll);
            setTimeout(restoreScroll, 0);
            setTimeout(restoreScroll, 20);
            setTimeout(restoreScroll, 60);
            setTimeout(restoreScroll, 150);
            setTimeout(restoreScroll, 300);
        };

        HTMLDialogElement.prototype.close = function(returnValue) {
            const mainEl = document.querySelector('main') || document.documentElement;
            const mainScroll = mainEl ? mainEl.scrollTop : 0;
            const winScrollY = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
            const winScrollX = window.scrollX || document.documentElement.scrollLeft || document.body.scrollLeft || 0;

            try {
                originalClose.call(this, returnValue);
            } catch (e) {
                // Already closed
            }

            const restoreScroll = () => {
                if (mainEl && mainEl.scrollTop !== mainScroll) {
                    mainEl.scrollTop = mainScroll;
                }
                if ((window.scrollY || document.documentElement.scrollTop || document.body.scrollTop) !== winScrollY) {
                    window.scrollTo({ top: winScrollY, left: winScrollX, behavior: 'instant' });
                }
            };

            restoreScroll();
            requestAnimationFrame(restoreScroll);
            setTimeout(restoreScroll, 0);
            setTimeout(restoreScroll, 20);
            setTimeout(restoreScroll, 60);
            setTimeout(restoreScroll, 150);
            setTimeout(restoreScroll, 300);
        };
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Halaman preview (show / preview-version / preview): sisipkan batas
    // antar halaman ke .doku-paper, baca ukuran kertas dari localStorage/dataset.
    initPreviewPagination('.doku-paper-scope');
});

window.Alpine = Alpine;

Alpine.start();

// ─── Theme Toggle ───────────────────────────────────────────────
// Ikut Windows secara default. Klik Light/Dark = override untuk SESI
// browser ini saja (sessionStorage) — nempel walau reload/pindah
// halaman, tapi hilang kalau tab/browser ditutup. OS berubah tema
// selalu langsung menang & menghapus override sesi yang aktif.
(function() {
    const KEY = 'theme:v2';
    const mq = window.matchMedia('(prefers-color-scheme: dark)');

    function override() {
        return sessionStorage.getItem(KEY); // 'light' | 'dark' | null
    }

    function osTheme() {
        return mq.matches ? 'dark' : 'light';
    }

    function eff() {
        return override() || osTheme();
    }

    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
        document.documentElement.classList.toggle('dark', t === 'dark');
    }

    function syncIcon() {
        var e = eff();
        document.getElementById('themeIconSun')?.classList.toggle('hidden', e !== 'light');
        document.getElementById('themeIconMoon')?.classList.toggle('hidden', e !== 'dark');
        document.getElementById('themeToggleBtn')?.setAttribute('aria-label', 'Theme: ' + e);
    }

    document.addEventListener('DOMContentLoaded', function() {
        apply(eff());
        syncIcon();

        // Windows berubah tema → selalu menang, hapus override sesi
        mq.addEventListener('change', function() {
            sessionStorage.removeItem(KEY);
            apply(eff());
            syncIcon();
        });

        // Klik toggle = langsung balik tema (light ↔ dark)
        document.getElementById('themeToggleBtn')?.addEventListener('click', function() {
            var next = eff() === 'dark' ? 'light' : 'dark';
            sessionStorage.setItem(KEY, next);
            apply(next);
            syncIcon();
        });
    });
})();