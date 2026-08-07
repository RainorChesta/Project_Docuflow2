import Alpine from 'alpinejs';
import './jodit';
import { initJoditEditor, initPreviewPagination } from './jodit';

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

    function markActive() {
        var o = override();
        document.querySelectorAll('.theme-option').forEach(function(el) {
            var isActive = el.dataset.themeValue === o;
            el.classList.toggle('bg-primary/10', isActive);
            el.classList.toggle('text-primary', isActive);
            el.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        apply(eff());
        syncIcon();
        markActive();

        // Windows berubah tema → selalu menang, hapus override sesi
        mq.addEventListener('change', function() {
            sessionStorage.removeItem(KEY);
            apply(eff());
            syncIcon();
            markActive();
        });

        document.querySelectorAll('.theme-option').forEach(function(btn) {
            btn.addEventListener('click', function() {
                sessionStorage.setItem(KEY, this.dataset.themeValue);
                apply(eff());
                syncIcon();
                markActive();
            });
        });
    });
})();