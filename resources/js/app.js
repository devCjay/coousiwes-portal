import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const applyTheme = (theme) => {
    const shouldUseDark =
        theme === 'dark' ||
        (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', shouldUseDark);
    document.documentElement.dataset.theme = theme;
};

const defaultTheme = 'light';
const storedTheme = localStorage.getItem('siwes-theme') || defaultTheme;

applyTheme(storedTheme);

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const showToast = ({ title = 'Notification', message = '', tone = 'info' } = {}) => {
    const toast = document.querySelector('[data-toast]');

    if (!toast) {
        return;
    }

    const toneClasses = {
        info: 'border-cyan-400/30',
        success: 'border-brand-400/30',
        warning: 'border-amber-400/35',
        danger: 'border-rose-400/30',
    };

    Object.values(toneClasses).forEach((className) => toast.classList.remove(className));
    toast.classList.add(toneClasses[tone] || toneClasses.info);

    toast.querySelector('[data-toast-title]').textContent = title;
    toast.querySelector('[data-toast-message]').textContent = message;
    toast.classList.remove('-translate-y-6', 'translate-y-6', 'opacity-0', 'pointer-events-none');

    window.clearTimeout(showToast.timeoutId);
    showToast.timeoutId = window.setTimeout(() => {
        toast.classList.add('-translate-y-6', 'opacity-0', 'pointer-events-none');
    }, 3600);
};

window.SiwesToast = showToast;

const flashToast = document.querySelector('[data-toast]');

if (flashToast?.dataset.flashMessage) {
    window.setTimeout(() => {
        showToast({
            title: flashToast.dataset.flashTitle || 'Notification',
            message: flashToast.dataset.flashMessage,
            tone: flashToast.dataset.flashTone || 'success',
        });
    }, 250);
}

const welcomeToast = document.querySelector('[data-welcome-toast]');
const hideWelcomeToast = () => {
    if (!welcomeToast) {
        return;
    }

    welcomeToast.classList.add('-translate-y-8', 'opacity-0', 'pointer-events-none');
};

if (welcomeToast) {
    const duration = Number(welcomeToast.dataset.welcomeDuration || 6000);
    const sessionKey = welcomeToast.dataset.welcomeSessionKey || 'siwes-welcome-seen';

    if (window.sessionStorage.getItem(sessionKey) === '1') {
        welcomeToast.remove();
    } else {
        window.sessionStorage.setItem(sessionKey, '1');

        window.setTimeout(() => {
            welcomeToast.classList.remove('-translate-y-8', 'opacity-0');
        }, 350);
        window.setTimeout(hideWelcomeToast, Number.isFinite(duration) ? duration : 12000);

        welcomeToast.querySelector('[data-welcome-close]')?.addEventListener('click', hideWelcomeToast);
    }
}

const updateNotificationCount = (count) => {
    const badge = document.querySelector('[data-notification-count]');

    if (!badge) {
        return;
    }

    const safeCount = Number(count || 0);
    badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
    badge.classList.toggle('hidden', safeCount === 0);
};

const pollNotificationSummary = async () => {
    const link = document.querySelector('[data-notification-link]');

    if (!link) {
        return;
    }

    try {
        const response = await fetch('/notifications/summary', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return;
        }

        const payload = await response.json();
        updateNotificationCount(payload.unread_count);
    } catch {
        // Polling is a progressive enhancement fallback for realtime notifications.
    }
};

if (import.meta.env.VITE_REVERB_APP_KEY) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
        wsPort: import.meta.env.VITE_REVERB_PORT || 80,
        wssPort: import.meta.env.VITE_REVERB_PORT || 443,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
        },
    });

    const userId = document.body.dataset.userId;

    if (userId) {
        window.Echo.private(`App.Models.User.${userId}`).notification((notification) => {
            updateNotificationCount(Number(document.querySelector('[data-notification-count]')?.textContent || 0) + 1);
            showToast({
                title: notification.title || 'Portal notification',
                message: notification.message || 'A new notification has arrived.',
                tone: notification.tone || 'info',
            });
        });
    }
}

if (document.querySelector('[data-notification-link]')) {
    pollNotificationSummary();
    window.setInterval(pollNotificationSummary, 45000);
}

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if ((localStorage.getItem('siwes-theme') || defaultTheme) === 'system') {
        applyTheme('system');
    }
});

const publicMenu = document.querySelector('[data-public-menu]');
const publicMenuPanel = document.querySelector('[data-public-menu-panel]');
const publicMenuBackdrop = document.querySelector('[data-public-menu-close]');
const publicMenuOpenButton = document.querySelector('[data-public-menu-open]');
const sidebar = document.querySelector('[data-sidebar]');
const sidebarBackdrop = document.querySelector('[data-sidebar-close]');
const sidebarToggleButton = document.querySelector('[data-sidebar-toggle]');

const openSidebar = () => {
    if (!sidebar) {
        return;
    }

    sidebar.classList.remove('-translate-x-full');
    sidebarBackdrop?.classList.remove('pointer-events-none', 'opacity-0');
    sidebarToggleButton?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
};

const closeSidebar = () => {
    if (!sidebar) {
        return;
    }

    sidebar.classList.add('-translate-x-full');
    sidebarBackdrop?.classList.add('pointer-events-none', 'opacity-0');
    sidebarToggleButton?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('overflow-hidden');
};

const toggleSidebar = () => {
    if (!sidebar) {
        return;
    }

    if (sidebar.classList.contains('-translate-x-full')) {
        openSidebar();
    } else {
        closeSidebar();
    }
};

const openPublicMenu = () => {
    if (!publicMenu || !publicMenuPanel) {
        return;
    }

    publicMenu.classList.remove('hidden');
    publicMenu.setAttribute('aria-hidden', 'false');
    publicMenuOpenButton?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');

    window.requestAnimationFrame(() => {
        publicMenuPanel.classList.remove('-translate-x-full');
        publicMenuBackdrop?.classList.remove('opacity-0');
    });
};

const closePublicMenu = () => {
    if (!publicMenu || !publicMenuPanel) {
        return;
    }

    publicMenuPanel.classList.add('-translate-x-full');
    publicMenuBackdrop?.classList.add('opacity-0');
    publicMenu.setAttribute('aria-hidden', 'true');
    publicMenuOpenButton?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('overflow-hidden');

    window.setTimeout(() => {
        if (publicMenuPanel.classList.contains('-translate-x-full')) {
            publicMenu.classList.add('hidden');
        }
    }, 300);
};

document.addEventListener('click', (event) => {
    const themeButton = event.target.closest('[data-theme-toggle]');
    const modalButton = event.target.closest('[data-modal-target]');
    const modalClose = event.target.closest('[data-modal-close]');
    const notificationButton = event.target.closest('[data-notification-demo]');
    const sidebarButton = event.target.closest('[data-sidebar-toggle]');
    const sidebarClose = event.target.closest('[data-sidebar-close]');
    const sidebarLink = event.target.closest('[data-sidebar] a');
    const publicMenuOpen = event.target.closest('[data-public-menu-open]');
    const publicMenuClose = event.target.closest('[data-public-menu-close]');
    const publicMenuLink = event.target.closest('[data-public-menu] a');
    const ticketSelectAll = event.target.closest('[data-ticket-select-all]');
    const ticketPrint = event.target.closest('[data-ticket-print], [data-ticket-export-pdf]');
    const academicTab = event.target.closest('[data-academic-tab-target]');
    const settingsTab = event.target.closest('[data-settings-tab-target]');
    const profilePageTab = event.target.closest('[data-profile-page-tab-target]');
    const profileEditToggle = event.target.closest('[data-profile-edit-toggle]');

    if (themeButton) {
        const currentTheme = localStorage.getItem('siwes-theme') || defaultTheme;
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        localStorage.setItem('siwes-theme', nextTheme);
        applyTheme(nextTheme);

        if (themeButton.closest('[data-public-menu]')) {
            closePublicMenu();
        }
    }

    if (modalButton) {
        document.querySelector(modalButton.dataset.modalTarget)?.showModal();
    }

    if (modalClose) {
        modalClose.closest('dialog')?.close();
    }

    if (notificationButton) {
        showToast({
            title: 'Notification delivered',
            message: 'This preview uses the shared toast pattern for alerts and push events.',
            tone: 'info',
        });
    }

    if (sidebarButton) {
        toggleSidebar();
    }

    if (sidebarClose || sidebarLink) {
        closeSidebar();
    }

    if (publicMenuOpen) {
        openPublicMenu();
    }

    if (publicMenuClose || publicMenuLink) {
        closePublicMenu();
    }

    if (ticketSelectAll) {
        document.querySelectorAll('[data-ticket-checkbox]').forEach((checkbox) => {
            checkbox.checked = ticketSelectAll.checked;
        });
    }

    if (ticketPrint) {
        const selected = [...document.querySelectorAll('[data-ticket-checkbox]:checked')];

        if (selected.length === 0) {
            showToast({
                title: 'No tickets selected',
                message: 'Select at least one ticket before printing or exporting.',
                tone: 'warning',
            });
            return;
        }

        window.print();
    }

    if (academicTab) {
        const target = document.querySelector(academicTab.dataset.academicTabTarget);

        if (!target) {
            return;
        }

        document.querySelectorAll('[data-academic-tab-target]').forEach((tab) => {
            const isActive = tab === academicTab;

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        document.querySelectorAll('[data-academic-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel !== target);
        });
    }

    if (settingsTab) {
        const target = document.querySelector(settingsTab.dataset.settingsTabTarget);

        if (!target) {
            return;
        }

        document.querySelectorAll('[data-settings-tab-target]').forEach((tab) => {
            const isActive = tab === settingsTab;

            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        document.querySelectorAll('[data-settings-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel !== target);
        });
    }

    if (profilePageTab) {
        const target = document.querySelector(profilePageTab.dataset.profilePageTabTarget);

        if (!target) {
            return;
        }

        document.querySelectorAll('[data-profile-page-tab-target]').forEach((tab) => {
            const isActive = tab === profilePageTab;

            tab.classList.toggle('is-active', isActive);
            tab.classList.toggle('border-brand-600', isActive);
            tab.classList.toggle('bg-[var(--surface-raised)]', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        document.querySelectorAll('[data-profile-page-panel]').forEach((panel) => {
            panel.classList.toggle('hidden', panel !== target);
        });
    }

    if (profileEditToggle) {
        const target = document.querySelector(profileEditToggle.dataset.profileEditToggle);

        if (target) {
            target.classList.toggle('hidden');
        }
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closePublicMenu();
        closeSidebar();
    }
});

const progressBar = document.createElement('div');
progressBar.className = 'fixed left-0 top-0 z-[60] h-0.5 w-0 bg-cyan-400 shadow-glow transition-all duration-300';
progressBar.dataset.requestProgress = '';
document.body.append(progressBar);

const setProgress = (active) => {
    progressBar.style.width = active ? '72%' : '100%';
    progressBar.style.opacity = active ? '1' : '0';

    if (!active) {
        window.setTimeout(() => {
            progressBar.style.width = '0';
        }, 320);
    }
};

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form');

    if (!form || form.dataset.ajax === 'false') {
        return;
    }

    event.preventDefault();

    const submitter = event.submitter;
    const originalText = submitter?.textContent;

    if (submitter) {
        submitter.disabled = true;
        submitter.textContent = submitter.dataset.loadingText || 'Processing...';
    }

    try {
        setProgress(true);
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        const contentType = response.headers.get('content-type') || '';
        const payload = contentType.includes('application/json') ? await response.json() : {};

        if (response.redirected && !contentType.includes('application/json')) {
            window.location.assign(response.url);
            return;
        }

        if (!response.ok) {
            const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;

            throw new Error(firstError || payload.message || 'The request could not be completed.');
        }

        showToast({
            title: 'Success',
            message: payload.message || 'Action completed successfully.',
            tone: 'success',
        });

        form.dispatchEvent(new CustomEvent('ajax:success', { bubbles: true, detail: payload }));
        renderPreview(form, payload);

        if (payload.redirect) {
            window.setTimeout(() => window.location.assign(payload.redirect), 450);
            return;
        }

        if (payload.reload) {
            window.setTimeout(() => window.location.reload(), 450);
            return;
        }

        if (form.dataset.ajaxReset !== 'false') {
            form.reset();
        }
    } catch (error) {
        showToast({
            title: 'Action failed',
            message: error.message,
            tone: 'danger',
        });
    } finally {
        setProgress(false);
        if (submitter) {
            submitter.disabled = false;
            submitter.textContent = originalText;
        }
    }
});

document.addEventListener('change', (event) => {
    const toggle = event.target.closest('[data-check-all]');

    if (!toggle) {
        return;
    }

    document.querySelectorAll(toggle.dataset.checkAll).forEach((checkbox) => {
        checkbox.checked = toggle.checked;
    });
});

document.querySelectorAll('input[type="file"]').forEach((input) => {
    const field = input.closest('label, form');

    ['dragenter', 'dragover'].forEach((eventName) => {
        field?.addEventListener(eventName, () => field.classList.add('upload-active'));
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        field?.addEventListener(eventName, () => field.classList.remove('upload-active'));
    });
});

const renderPreview = (form, payload) => {
    const targetSelector = form.dataset.previewTarget;

    if (!targetSelector) {
        return;
    }

    const target = document.querySelector(targetSelector);

    if (!target) {
        return;
    }

    const rows = payload.preview || [];
    const errors = payload.errors ? Object.values(payload.errors) : [];
    const rowMarkup = rows
        .map((row) => `<tr>${['name', 'email', 'matric_no', 'faculty_code', 'department_code', 'level', 'academic_session']
            .map((key) => `<td class="whitespace-nowrap px-3 py-2">${row[key] || ''}</td>`)
            .join('')}</tr>`)
        .join('');
    const errorMarkup = errors
        .map((error) => `<li>Row ${error.row}: ${(error.messages || []).join(' ')}</li>`)
        .join('');

    target.innerHTML = `
        <div class="rounded-lg border border-[var(--line)] bg-[var(--surface-raised)] p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold">${payload.total || rows.length} rows scanned</p>
                <p class="text-xs text-[var(--text-soft)]">${errors.length} row issues found</p>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-left text-xs">
                    <thead class="text-[var(--text-soft)]"><tr><th class="px-3 py-2">Name</th><th class="px-3 py-2">Email</th><th class="px-3 py-2">Matric</th><th class="px-3 py-2">Faculty</th><th class="px-3 py-2">Department</th><th class="px-3 py-2">Level</th><th class="px-3 py-2">Session</th></tr></thead>
                    <tbody>${rowMarkup}</tbody>
                </table>
            </div>
            ${errorMarkup ? `<ul class="mt-4 list-disc space-y-1 pl-5 text-xs text-rose-600 dark:text-rose-200">${errorMarkup}</ul>` : ''}
            ${payload.process_url ? `<form method="POST" action="${payload.process_url}" class="mt-4"><input type="hidden" name="_token" value="${document.querySelector('input[name=_token]')?.value || ''}"><button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white shadow-glow">Queue Import</button></form>` : ''}
        </div>
    `;
};

document.addEventListener('input', (event) => {
    const input = event.target.closest('[data-live-search]');

    if (!input) {
        return;
    }

    const rows = document.querySelectorAll(input.dataset.liveSearch);
    const query = input.value.trim().toLowerCase();

    rows.forEach((row) => {
        row.hidden = query.length > 0 && !row.textContent.toLowerCase().includes(query);
    });
});

const filterDependentSelect = (parent) => {
    const target = document.querySelector(parent.dataset.filterParent || '');

    if (!target) {
        return;
    }

    const parentValue = parent.value;
    let firstVisibleValue = '';

    target.querySelectorAll('option').forEach((option) => {
        const optionParent = option.dataset.parentValue || '';
        const isPlaceholder = option.value === '';
        const isVisible = isPlaceholder || parentValue === '' || optionParent === parentValue;

        option.hidden = !isVisible;
        option.disabled = !isVisible;

        if (isVisible && !isPlaceholder && firstVisibleValue === '') {
            firstVisibleValue = option.value;
        }
    });

    if (target.value && target.selectedOptions[0]?.disabled) {
        target.value = '';
    }

    if (!target.value && parentValue !== '' && firstVisibleValue !== '') {
        target.value = firstVisibleValue;
    }
};

document.querySelectorAll('[data-filter-parent]').forEach(filterDependentSelect);

document.addEventListener('change', (event) => {
    const parent = event.target.closest('[data-filter-parent]');

    if (parent) {
        filterDependentSelect(parent);
    }
});

document.querySelectorAll('[data-countup]').forEach((element) => {
    const rawValue = element.textContent.trim();
    const target = Number(rawValue.replace(/[^0-9.]/g, ''));

    if (prefersReducedMotion || !Number.isFinite(target) || target <= 0 || rawValue.includes('/')) {
        return;
    }

    const suffix = rawValue.endsWith('%') ? '%' : '';
    const start = performance.now();
    const duration = 650;

    const tick = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const value = Math.round(target * progress);
        element.textContent = `${value}${suffix}`;

        if (progress < 1) {
            requestAnimationFrame(tick);
        }
    };

    requestAnimationFrame(tick);
});

const revealElements = document.querySelectorAll('[data-reveal]');

if (revealElements.length > 0) {
    revealElements.forEach((element, index) => {
        if (!element.style.getPropertyValue('--reveal-delay')) {
            element.style.setProperty('--reveal-delay', `${Math.min(index * 140, 700)}ms`);
        }
    });

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    } else {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.12 });

        revealElements.forEach((element) => revealObserver.observe(element));
    }
}

const profileWizard = document.querySelector('[data-profile-wizard], [data-student-profile]');

if (profileWizard) {
    const stepButtons = [...profileWizard.querySelectorAll('[data-profile-step-button]')];
    const stepPanels = [...profileWizard.querySelectorAll('[data-profile-step-panel]')];
    const progressFill = profileWizard.querySelector('[data-profile-progress]');
    const progressPercent = profileWizard.querySelector('[data-profile-percent]');
    const hasStepWizard = stepPanels.length > 0;
    let activeStep = 0;
    let highestUnlockedStep = 0;

    const updateProfileProgress = (completion) => {
        const safeCompletion = Math.max(0, Math.min(100, Number(completion || 0)));

        if (progressFill) {
            progressFill.style.width = `${safeCompletion}%`;
        }

        if (progressPercent) {
            progressPercent.textContent = String(Math.round(safeCompletion));
        }
    };

    const setWizardStep = (index) => {
        if (!hasStepWizard) {
            return;
        }

        const targetIndex = Math.max(0, Math.min(index, stepPanels.length - 1));

        activeStep = targetIndex;
        stepPanels.forEach((panel) => {
            panel.classList.toggle('hidden', Number(panel.dataset.profileStepPanel) !== targetIndex);
        });
        stepButtons.forEach((button) => {
            const buttonIndex = Number(button.dataset.profileStepButton);
            const isActive = buttonIndex === targetIndex;
            const isUnlocked = buttonIndex <= highestUnlockedStep;

            button.classList.toggle('border-brand-600', isActive);
            button.classList.toggle('bg-[var(--surface-raised)]', isActive);
            button.classList.toggle('shadow-[0_14px_34px_rgb(0_81_54_/_0.10)]', isActive);
            button.classList.toggle('opacity-55', !isUnlocked);
            button.disabled = !isUnlocked;
        });
    };

    const closeComboboxes = (except = null) => {
        document.querySelectorAll('[data-profile-combobox]').forEach((combobox) => {
            if (combobox !== except) {
                combobox.querySelector('[data-profile-combobox-list]')?.classList.add('hidden');
            }
        });
    };

    const escapeHtml = (value = '') => String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const renderComboboxOptions = (combobox, options) => {
        const list = combobox.querySelector('[data-profile-combobox-list]');
        const hidden = combobox.querySelector('[data-profile-combobox-value]');
        const input = combobox.querySelector('[data-profile-combobox-input]');

        if (!list) {
            return;
        }

        hidden.value = '';
        input.value = '';
        combobox.dataset.selectedLabel = '';
        list.innerHTML = options.length
            ? options.map((option) => `
                <button type="button" data-profile-combobox-option data-value="${escapeHtml(option.value)}" data-label="${escapeHtml(option.label)}" ${option.sort_code ? `data-sort-code="${escapeHtml(option.sort_code)}"` : ''} class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm text-[var(--text-strong)] theme-transition hover:bg-[var(--surface-muted)]">
                    <span class="min-w-0">
                        <span class="block truncate font-medium">${escapeHtml(option.label)}</span>
                        ${option.meta ? `<span class="mt-0.5 block truncate text-xs text-[var(--text-soft)]">${escapeHtml(option.meta)}</span>` : ''}
                    </span>
                    <svg data-profile-combobox-check class="hidden size-4 shrink-0 text-brand-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                </button>
            `).join('')
            : '<p class="px-3 py-2 text-sm text-[var(--text-soft)]">No records available.</p>';
    };

    const fetchProfileData = async (url) => {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error('Profile data could not be loaded.');
        }

        return response.json();
    };

    stepButtons.forEach((button) => {
        button.addEventListener('click', () => setWizardStep(Number(button.dataset.profileStepButton)));
    });

    profileWizard.querySelectorAll('[data-profile-combobox]').forEach((combobox) => {
        combobox.dataset.selectedLabel = combobox.querySelector('[data-profile-combobox-input]')?.value || '';
    });

    profileWizard.addEventListener('click', async (event) => {
        const prevButton = event.target.closest('[data-profile-prev]');
        const combobox = event.target.closest('[data-profile-combobox]');
        const option = event.target.closest('[data-profile-combobox-option]');

        if (prevButton) {
            setWizardStep(activeStep - 1);
        }

        if (!combobox && !option) {
            closeComboboxes();
        }

        if (combobox && !option) {
            combobox.querySelector('[data-profile-combobox-list]')?.classList.remove('hidden');
            closeComboboxes(combobox);
        }

        if (option) {
            const owningCombobox = option.closest('[data-profile-combobox]');
            const hidden = owningCombobox.querySelector('[data-profile-combobox-value]');
            const input = owningCombobox.querySelector('[data-profile-combobox-input]');

            hidden.value = option.dataset.value || '';
            input.value = option.dataset.label || '';
            owningCombobox.dataset.selectedLabel = input.value;
            owningCombobox.querySelectorAll('[data-profile-combobox-check]').forEach((icon) => icon.classList.add('hidden'));
            option.querySelector('[data-profile-combobox-check]')?.classList.remove('hidden');
            owningCombobox.querySelector('[data-profile-combobox-list]')?.classList.add('hidden');

            if (owningCombobox.hasAttribute('data-profile-bank')) {
                const sortCode = owningCombobox.closest('form')?.querySelector('[data-profile-sort-code]')
                    || profileWizard.querySelector('[data-profile-sort-code]');

                if (sortCode) {
                    sortCode.value = option.dataset.sortCode || '';
                }
            }

            if (owningCombobox.hasAttribute('data-profile-state')) {
                const lgaCombobox = owningCombobox.closest('form')?.querySelector('[data-profile-lga]')
                    || profileWizard.querySelector('[data-profile-lga]');
                const payload = await fetchProfileData(`/student/profile-data/lgas?state=${encodeURIComponent(hidden.value)}`);
                renderComboboxOptions(lgaCombobox, (payload.lgas || []).map((lga) => ({ value: lga, label: lga })));
            }

            if (owningCombobox.hasAttribute('data-profile-faculty')) {
                const departmentCombobox = owningCombobox.closest('form')?.querySelector('[data-profile-department]')
                    || profileWizard.querySelector('[data-profile-department]');
                const payload = await fetchProfileData(`/student/profile-data/departments?faculty_id=${encodeURIComponent(hidden.value)}`);
                renderComboboxOptions(departmentCombobox, (payload.departments || []).map((department) => ({
                    value: String(department.id),
                    label: department.name,
                    meta: department.code,
                })));
            }
        }
    });

    profileWizard.addEventListener('input', (event) => {
        const input = event.target.closest('[data-profile-combobox-input]');

        if (!input) {
            return;
        }

        const combobox = input.closest('[data-profile-combobox]');
        const hidden = combobox.querySelector('[data-profile-combobox-value]');
        const query = input.value.trim().toLowerCase();

        if (hidden && input.value !== (combobox.dataset.selectedLabel || '')) {
            hidden.value = '';
            combobox.querySelectorAll('[data-profile-combobox-check]').forEach((icon) => icon.classList.add('hidden'));
        }

        combobox.querySelector('[data-profile-combobox-list]')?.classList.remove('hidden');
        closeComboboxes(combobox);

        combobox.querySelectorAll('[data-profile-combobox-option]').forEach((option) => {
            option.hidden = query.length > 0 && !(option.textContent || '').toLowerCase().includes(query);
        });
    });

    profileWizard.addEventListener('ajax:success', (event) => {
        const form = event.target.closest('[data-profile-step-form]');

        if (!form) {
            return;
        }

        updateProfileProgress(event.detail.completion);
        if (form.dataset.stepIndex !== undefined) {
            highestUnlockedStep = Math.max(highestUnlockedStep, Number(form.dataset.stepIndex) + 1);
        }

        if (form.dataset.stepIndex !== undefined && !event.detail.redirect) {
            setWizardStep(Number(form.dataset.stepIndex) + 1);
        }
    });

    if (hasStepWizard) {
        setWizardStep(0);
    }
}

document.querySelectorAll('[data-pin-reveal]').forEach((button) => {
    const value = button.querySelector('[data-pin-value]');
    const pin = button.dataset.pin || '';
    const mask = '******';

    const showPin = () => {
        if (value && pin) {
            value.textContent = pin;
        }
    };

    const hidePin = () => {
        if (button.dataset.pinPinned === 'true') {
            return;
        }

        if (value) {
            value.textContent = mask;
        }
    };

    button.addEventListener('mouseenter', showPin);
    button.addEventListener('mouseleave', hidePin);
    button.addEventListener('focus', showPin);
    button.addEventListener('blur', hidePin);
    button.addEventListener('click', () => {
        if (!value || !pin) {
            return;
        }

        const shouldPin = button.dataset.pinPinned !== 'true';
        button.dataset.pinPinned = shouldPin ? 'true' : 'false';
        value.textContent = shouldPin ? pin : mask;
    });
});
