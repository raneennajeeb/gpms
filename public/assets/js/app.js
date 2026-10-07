// GPMS front-end helpers (plain JavaScript, no libraries)

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
    initSidebar();
    initDropdowns();
    initModals();
    initPasswordToggles();
    initFlashToasts();
    // Order matters: confirm forms must register before the double-submit guard
    initConfirmForms();
    initPreventDoubleSubmit();
});

/* ---------- Theme ---------- */

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

/* ---------- Mobile navigation ---------- */

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

/* ---------- Modals ---------- */

function initModals() {
    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            document.getElementById(button.dataset.modalOpen)?.showModal();
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    // Clicking the dark backdrop (outside the box) closes the modal
    document.querySelectorAll('dialog.modal').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });
}

/* ---------- Toasts ---------- */

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');

    if (!container) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.setAttribute('role', type === 'danger' ? 'alert' : 'status');
    // textContent (not innerHTML) so a message can never inject HTML
    toast.textContent = message;

    container.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

function initFlashToasts() {
    const container = document.getElementById('toast-container');

    if (!container) {
        return;
    }

    if (container.dataset.flashSuccess) {
        showToast(container.dataset.flashSuccess, 'success');
    }

    if (container.dataset.flashError) {
        showToast(container.dataset.flashError, 'danger');
    }
}

/* ---------- Confirm ---------- */

function confirmAction(message) {
    const dialog = document.getElementById('confirm-dialog');

    if (!dialog) {
        return Promise.resolve(window.confirm(message));
    }

    dialog.querySelector('#confirm-dialog-message').textContent = message;
    dialog.showModal();

    return new Promise((resolve) => {
        const finish = (result) => {
            dialog.close();
            resolve(result);
        };

        dialog.querySelector('[data-confirm-ok]').onclick = () => finish(true);
        dialog.querySelector('[data-confirm-cancel]').onclick = () => finish(false);
        // Pressing Esc closes the dialog: treat it as "cancel"
        dialog.oncancel = () => resolve(false);
    });
}

function initConfirmForms() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.confirmed === 'yes') {
                return;
            }

            event.preventDefault();

            if (await confirmAction(form.dataset.confirm)) {
                form.dataset.confirmed = 'yes';
                form.requestSubmit();
            }
        });
    });
}

/* ---------- Double-submit guard ---------- */

function initPreventDoubleSubmit() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            // A confirm form that is still waiting for the user has stopped the submit
            if (event.defaultPrevented) {
                return;
            }

            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
                button.classList.add('is-loading');
            });
        });
    });
}

// When the user comes back with the browser Back button, re-enable the buttons
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        document.querySelectorAll('button.is-loading').forEach((button) => {
            button.disabled = false;
            button.classList.remove('is-loading');
        });
    }
});

/* ---------- Password show/hide ---------- */

function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.classList.toggle('is-shown', show);
        });
    });
}

/* ---------- Sidebar drawer (small screens) ---------- */

function initSidebar() {
    if (!document.getElementById('sidebar')) {
        return;
    }

    const open = () => document.body.classList.add('sidebar-open');
    const close = () => document.body.classList.remove('sidebar-open');

    document.querySelectorAll('[data-sidebar-open]').forEach((button) => button.addEventListener('click', open));
    document.querySelectorAll('[data-sidebar-close]').forEach((element) => element.addEventListener('click', close));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });
}

/* ---------- Dropdowns (user menu) ---------- */

function initDropdowns() {
    document.querySelectorAll('[data-dropdown-toggle]').forEach((button) => {
        const menu = button.nextElementSibling;

        button.addEventListener('click', (event) => {
            // Stop the click from reaching the document listener below, which would close it again
            event.stopPropagation();
            const isOpen = menu.classList.toggle('is-open');
            button.setAttribute('aria-expanded', isOpen);
        });
    });

    // A click anywhere else closes any open dropdown
    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown].is-open').forEach((menu) => {
            menu.classList.remove('is-open');
            menu.previousElementSibling?.setAttribute('aria-expanded', 'false');
        });
    });
}
