// GPMS front-end helpers (plain JavaScript, no libraries)

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
});

function initThemeToggle() {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const html = document.documentElement;
            const newTheme = html.dataset.theme === 'dark' ? 'light' : 'dark';

            html.dataset.theme = newTheme;
            saveTheme(newTheme, button.dataset.saveUrl);
        });
    });
}

function saveTheme(theme, saveUrl) {
    // Guests keep the choice in the browser, logged-in users on the server
    if (!saveUrl) {
        try {
            localStorage.setItem('theme', theme);
        } catch (e) {
            // Private browsing can block localStorage; the theme still changes for this page
        }
        return;
    }

    fetch(saveUrl, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ theme }),
    });
}

function initMobileNav() {
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.getElementById('site-nav');

    if (!toggle || !nav) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen);
    });
}
