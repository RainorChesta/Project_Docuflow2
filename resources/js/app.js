

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ─── Theme Toggle ───────────────────────────────────────────────
// No stored key = follow OS live. Manual light/dark persists and overrides OS.
(function() {
    const KEY = 'theme:v2';
    const mq = window.matchMedia('(prefers-color-scheme: dark)');

    // stored choice, or 'auto' when nothing stored
    function pref() {
        return localStorage.getItem(KEY) || 'auto';
    }

    // effective theme for rendering: explicit choice, else OS preference
    function eff() {
        var p = pref();
        if (p !== 'auto') return p;
        return mq.matches ? 'dark' : 'light';
    }

    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
    }

    function syncIcon() {
        var e = eff();
        document.getElementById('themeIconSun')?.classList.toggle('hidden', e !== 'light');
        document.getElementById('themeIconMoon')?.classList.toggle('hidden', e !== 'dark');
        document.getElementById('themeIconAuto')?.classList.toggle('hidden', pref() !== 'auto');
        document.getElementById('themeToggleBtn')?.setAttribute('aria-label', 'Theme: ' + e + (pref() === 'auto' ? ' (auto)' : ''));
    }

    function markActive() {
        var p = pref();
        document.querySelectorAll('.theme-option').forEach(function(el) {
            var isActive = el.dataset.themeValue === p;
            el.classList.toggle('bg-primary/10', isActive);
            el.classList.toggle('text-primary', isActive);
            el.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        apply(eff());
        syncIcon();
        markActive();

        // OS theme change → follow live unless user picked a manual theme
        mq.addEventListener('change', function() {
            if (pref() === 'auto') {
                apply(eff());
                syncIcon();
            }
        });

        document.querySelectorAll('.theme-option').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var v = this.dataset.themeValue;
                if (localStorage.getItem(KEY) === v) {
                    localStorage.removeItem(KEY); // re-click active → back to following OS
                } else {
                    localStorage.setItem(KEY, v);
                }
                apply(eff());
                syncIcon();
                markActive();
            });
        });
    });
})();
