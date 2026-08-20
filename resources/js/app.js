import Alpine from 'alpinejs';
import './jodit';
import { initJoditEditor, initPreviewPagination } from './jodit';

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

document.addEventListener('DOMContentLoaded', () => {
    initJoditEditor('#jodit-editor');
    initJoditEditor('#editor-shared');
    // Halaman preview (show / preview-version / preview): sisipkan batas
    // antar halaman ke .doku-paper, baca ukuran kertas dari localStorage.
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