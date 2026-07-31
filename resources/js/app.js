

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// ─── Theme Toggle ───────────────────────────────────────────────
(function() {
    const KEY = 'theme';

    // effective theme: explicit user choice, or fallback to OS (Windows) preference
    function eff() {
        var t = localStorage.getItem(KEY);
        if (t === 'light' || t === 'dark') return t;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function apply(t) {
        document.documentElement.setAttribute('data-theme', t);
    }

    function syncIcon() {
        var e = eff();
        document.getElementById('themeIconSun')?.classList.toggle('hidden', e !== 'light');
        document.getElementById('themeIconMoon')?.classList.toggle('hidden', e !== 'dark');
    }

    function markActive() {
        var p = localStorage.getItem(KEY);
        document.querySelectorAll('.theme-option').forEach(function(el) {
            var isActive = el.dataset.themeValue === p;
            el.classList.toggle('bg-primary/10', isActive);
            el.classList.toggle('text-primary', isActive);
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        apply(eff());
        syncIcon();
        markActive();

        // OS change → re-evaluate (no-op when user has explicit choice)
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
            apply(eff());
        });

        document.querySelectorAll('.theme-option').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var v = this.dataset.themeValue;
                // click the active option again → reset to auto-follow OS
                if (localStorage.getItem(KEY) === v) {
                    localStorage.removeItem(KEY);
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
