// GPMS front-end helpers (plain JavaScript, no libraries)

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initMobileNav();
    initSidebar();
    initDropdowns();
    initModals();
    initPasswordToggles();
    initPasswordStrength();
    initCountUp();
    initQrCode();
    initShareButton();
    initPrintButton();
    initTagInputs();
    initDashboardRefresh();
    initSimilarityCheck();
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

/* ---------- Password strength (helper only; the server does the real check) ---------- */

function initPasswordStrength() {
    const input = document.querySelector('[data-password-strength-input]');
    const box = document.querySelector('[data-password-strength]');

    if (!input || !box) {
        return;
    }

    const bar = box.querySelector('[data-strength-bar]');
    const colors = ['fill-danger', 'fill-danger', 'fill-warning', 'fill-info', 'fill-success'];

    input.addEventListener('input', () => {
        const value = input.value;
        const rules = {
            length: value.length >= 8,
            letters: /\p{L}/u.test(value),
            numbers: /\d/.test(value),
        };

        let score = Object.values(rules).filter(Boolean).length;

        // Bonus point for a long password or one with symbols
        if (score === 3 && (value.length >= 12 || /[^\p{L}\d]/u.test(value))) {
            score = 4;
        }

        box.querySelectorAll('[data-rule]').forEach((item) => {
            item.classList.toggle('is-met', rules[item.dataset.rule]);
        });

        bar.className = 'progress-bar__fill ' + colors[score];
        bar.style.width = (score * 25) + '%';
    });
}

/* ---------- Count-up numbers (home statistics) ---------- */

function initCountUp() {
    const items = document.querySelectorAll('[data-count-up]');

    // Users who asked for less motion just see the final numbers
    if (!items.length || !('IntersectionObserver' in window)
        || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                animateCount(entry.target);
                observer.unobserve(entry.target); // count only once
            }
        });
    }, { threshold: 0.5 });

    items.forEach((item) => observer.observe(item));
}

function animateCount(element) {
    const target = Number(element.dataset.countUp);
    const suffix = element.dataset.suffix || '';
    const duration = 1200;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        element.textContent = Math.round(target * progress) + suffix;

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };

    requestAnimationFrame(step);
}

/* ---------- Archive entry: QR code, share, print ---------- */

function initQrCode() {
    const holder = document.getElementById('qr');

    // qrcode.min.js (qrcode-generator, MIT) is only loaded on the entry page
    if (!holder || typeof qrcode === 'undefined') {
        return;
    }

    // Type 0 lets the library pick the smallest size that fits the URL
    const qr = qrcode(0, 'M');
    qr.addData(holder.dataset.qr);
    qr.make();

    holder.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
}

function initShareButton() {
    document.querySelectorAll('[data-share]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(window.location.href);
                showToast(button.dataset.copied, 'success');
            } catch (error) {
                // Blocked clipboard, or an insecure origin
                showToast(button.dataset.failed, 'error');
            }
        });
    });
}

function initPrintButton() {
    document.querySelectorAll('[data-print]').forEach((button) => {
        button.addEventListener('click', () => window.print());
    });
}

/* ---------- Tag inputs (proposal keywords and technologies) ---------- */

function initTagInputs() {
    document.querySelectorAll('[data-tag-input]').forEach((box) => {
        if (box.hasAttribute('data-disabled')) {
            return;
        }

        const field = box.querySelector('[data-tag-field]');
        const tags = box.querySelector('[data-tags]');
        const name = box.dataset.name;

        const values = () => [...tags.querySelectorAll('input')].map((input) => input.value);

        const addTag = (raw) => {
            const value = raw.trim();

            // No empties, no duplicates
            if (!value || values().includes(value)) {
                return;
            }

            const tag = document.createElement('span');
            tag.className = 'tag-input__tag';

            const label = document.createElement('span');
            label.textContent = value; // textContent, never innerHTML
            tag.appendChild(label);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name + '[]';
            hidden.value = value;
            tag.appendChild(hidden);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('data-remove-tag', '');
            remove.textContent = '×';
            tag.appendChild(remove);

            tags.appendChild(tag);
        };

        field.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault(); // Enter must not submit the form
                addTag(field.value);
                field.value = '';
            } else if (event.key === 'Backspace' && field.value === '') {
                tags.lastElementChild?.remove();
            }
        });

        // Picking from the datalist fires change, not keydown
        field.addEventListener('change', () => {
            addTag(field.value);
            field.value = '';
        });

        box.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-tag]');

            if (button) {
                button.closest('.tag-input__tag').remove();
            }
        });
    });
}

/* ---------- Student dashboard: refresh without a page load ---------- */

function initDashboardRefresh() {
    document.querySelectorAll('[data-refresh]').forEach((button) => {
        button.addEventListener('click', async () => {
            button.disabled = true;

            try {
                const response = await fetch(button.dataset.url, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(response.status);
                }

                const data = await response.json();

                setStat('progress', data.progress + '%');
                setStat('status', data.status);
                setStat('evaluation', data.evaluation);

                showToast(button.dataset.done, 'success');
            } catch (error) {
                showToast(button.dataset.done, 'error');
            } finally {
                button.disabled = false;
            }
        });
    });
}

function setStat(key, value) {
    const card = document.querySelector('[data-stat="' + key + '"] .stat-card__value');

    if (card && value) {
        card.textContent = value;
    }
}

/* ---------- Proposal similarity check ---------- */

function initSimilarityCheck() {
    const form = document.getElementById('proposal-form');
    const button = document.getElementById('check-similarity');

    if (!form || !button) {
        return;
    }

    const card = document.getElementById('similarity-card');
    const threshold = Number(form.dataset.threshold);
    let lastChecked = '';

    const payload = () => ({
        title: form.querySelector('[name="title"]').value,
        description: form.querySelector('[name="description"]').value,
        keywords: [...form.querySelectorAll('[name="keywords[]"]')].map((input) => input.value),
    });

    const runCheck = async () => {
        const body = payload();

        const response = await fetch(form.dataset.checkUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
            },
            body: JSON.stringify(body),
        });

        if (!response.ok) {
            throw new Error(response.status);
        }

        lastChecked = body.title + body.description;

        return response.json();
    };

    const render = (result) => {
        card.hidden = false;

        const over = result.score >= threshold;

        const ring = document.getElementById('similarity-ring');
        ring.textContent = '';
        const figure = document.createElement('div');
        figure.className = 'similarity__score ' + (over ? 'tone-danger' : 'tone-success');
        figure.textContent = result.score + '%';
        ring.appendChild(figure);

        const message = document.getElementById('similarity-message');
        message.textContent = over ? form.dataset.warning : form.dataset.safe;

        const list = document.getElementById('similarity-matches');
        list.textContent = '';

        result.matches.forEach((match) => {
            const item = document.createElement('li');

            const score = document.createElement('strong');
            score.textContent = match.score + '%';
            item.appendChild(score);

            const title = document.createElement('span');
            title.textContent = ' ' + match.title + (match.year ? ' (' + match.year + ')' : '');
            item.appendChild(title);

            if (match.shared.length) {
                const shared = document.createElement('small');
                shared.textContent = match.shared.join(' ، ');
                item.appendChild(shared);
            }

            list.appendChild(item);
        });

        card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    };

    button.addEventListener('click', async () => {
        button.disabled = true;

        try {
            render(await runCheck());
        } catch (error) {
            showToast(form.dataset.failed, 'error');
        } finally {
            button.disabled = false;
        }
    });

    // The proposal is always checked before it is finally sent
    form.addEventListener('submit', (event) => {
        const body = payload();

        if (lastChecked === body.title + body.description) {
            return; // already checked for this exact text
        }

        event.preventDefault();

        runCheck()
            .then(render)
            .catch(() => {
                lastChecked = body.title + body.description; // never block on a failed check
            })
            .finally(() => form.submit());
    });
}
