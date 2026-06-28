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

    applyTheme(storedTheme() || preferredTheme());

    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.querySelector('[data-theme-toggle]');

        if (!toggle) {
            return;
        }

        toggle.checked = root.getAttribute('data-bs-theme') === 'dark';

        toggle.addEventListener('change', function () {
            const theme = toggle.checked ? 'dark' : 'light';

            applyTheme(theme);

            try {
                window.localStorage.setItem(storageKey, theme);
            } catch {
                // The selected theme still applies for the current page.
            }
        });
    });
})();
