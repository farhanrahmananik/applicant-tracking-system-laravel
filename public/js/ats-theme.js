(function () {
    const storageKey = 'ats-theme';
    const root = document.documentElement;

    function storedTheme() {
        try {
            const value = window.localStorage.getItem(storageKey);

            return value === 'dark' || value === 'light' ? value : null;
        } catch {
            return null;
        }
    }

    function preferredTheme() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function applyTheme(theme) {
        root.setAttribute('data-bs-theme', theme);
    }

    function syncToggle(toggle, theme) {
        const isDark = theme === 'dark';
        const icon = toggle.querySelector('[data-theme-icon]');
        const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';

        if (toggle instanceof HTMLInputElement) {
            toggle.checked = isDark;
        } else {
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            toggle.setAttribute('aria-label', label);
            toggle.setAttribute('title', label);
        }

        if (icon) {
            icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }

    applyTheme(storedTheme() || preferredTheme());

    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('[data-theme-toggle]');

        if (!toggle) {
            return;
        }

        syncToggle(toggle, root.getAttribute('data-bs-theme'));

        toggle.addEventListener(toggle instanceof HTMLInputElement ? 'change' : 'click', function () {
            const theme = toggle instanceof HTMLInputElement
                ? (toggle.checked ? 'dark' : 'light')
                : (root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark');

            applyTheme(theme);
            syncToggle(toggle, theme);

            try {
                window.localStorage.setItem(storageKey, theme);
            } catch {
                // The selected theme still applies for the current page.
            }
        });
    });
})();
