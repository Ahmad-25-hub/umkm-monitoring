import { initializeAiInsight } from './ai-insight';
import { initializeOfflineSales } from './offline-sales';

import {
    Activity,
    ArrowRight,
    ArrowUp,
    ArrowUpRight,
    Award,
    Banknote,
    Bell,
    CalendarDays,
    ChartNoAxesColumnDecreasing,
    ChartNoAxesCombined,
    Check,
    ChevronDown,
    ChevronsLeft,
    ChevronsUpDown,
    Circle,
    CircleCheck,
    CircleHelp,
    Clock3,
    ContactRound,
    Copy,
    createIcons,
    DatabaseZap,
    FileChartColumn,
    Eye,
    EyeOff,
    KeyRound,
    LayoutDashboard,
    Lightbulb,
    ListFilter,
    Store,
    PackageSearch,
    LogOut,
    Mail,
    Menu,
    Monitor,
    MonitorSmartphone,
    Package,
    PackageX,
    Plus,
    RadioTower,
    ReceiptText,
    Search,
    Send,
    Save,
    Settings2,
    ShieldCheck,
    ShoppingBasket,
    Sparkles,
    TrendingUp,
    Trash2,
    Upload,
    UserRound,
    UserRoundCheck,
    UserRoundX,
    UsersRound,
    X,
} from 'lucide';

const icons = {
    Activity,
    ArrowRight,
    ArrowUp,
    ArrowUpRight,
    Award,
    Banknote,
    Bell,
    CalendarDays,
    ChartNoAxesColumnDecreasing,
    ChartNoAxesCombined,
    Check,
    ChevronDown,
    ChevronsLeft,
    ChevronsUpDown,
    Circle,
    CircleCheck,
    CircleHelp,
    Clock3,
    ContactRound,
    Copy,
    DatabaseZap,
    FileChartColumn,
    Eye,
    EyeOff,
    KeyRound,
    LayoutDashboard,
    Lightbulb,
    ListFilter,
    Store,
    PackageSearch,
    LogOut,
    Mail,
    Menu,
    Monitor,
    MonitorSmartphone,
    Package,
    PackageX,
    Plus,
    RadioTower,
    ReceiptText,
    Search,
    Send,
    Save,
    Settings2,
    ShieldCheck,
    ShoppingBasket,
    Sparkles,
    TrendingUp,
    Trash2,
    Upload,
    UserRound,
    UserRoundCheck,
    UserRoundX,
    UsersRound,
    X,
};

const initializeIcons = () => {
    createIcons({ icons, attrs: { 'stroke-width': 1.8 } });
};

const initializeSidebar = () => {
    const openButton = document.querySelector('[data-sidebar-open]');
    const closeButtons = document.querySelectorAll('[data-sidebar-close]');
    const collapseButton = document.querySelector('[data-sidebar-collapse]');
    const sidebar = document.querySelector('#dashboard-sidebar');

    if (! openButton || ! sidebar) {
        return;
    }

    const desktop = window.matchMedia('(min-width: 1024px)');
    const background = [document.querySelector('.dashboard-content'), document.querySelector('.employee-bottom-nav')].filter(Boolean);
    const setOpen = (isOpen, restoreFocus = true) => {
        document.body.classList.toggle('sidebar-open', isOpen);
        openButton.setAttribute('aria-expanded', String(isOpen));
        sidebar.inert = ! isOpen && ! desktop.matches;
        background.forEach((element) => { element.inert = isOpen && ! desktop.matches; });

        if (isOpen) {
            sidebar.querySelector('a')?.focus();
        } else if (restoreFocus) {
            openButton.focus();
        }
    };

    setOpen(false, false);
    openButton.addEventListener('click', () => setOpen(true));
    closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
    desktop.addEventListener('change', () => setOpen(false, false));
    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (document.body.classList.contains('sidebar-open')) {
                setOpen(false);
            }
        });
    });
    collapseButton?.addEventListener('click', () => {
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        collapseButton.setAttribute('aria-expanded', String(! collapsed));
        collapseButton.setAttribute('aria-label', collapsed ? 'Perluas navigasi' : 'Ciutkan navigasi');
    });

    document.addEventListener('keydown', (event) => {
        if (! document.body.classList.contains('sidebar-open')) {
            return;
        }

        if (event.key === 'Escape') {
            setOpen(false);
        } else if (event.key === 'Tab') {
            const items = [...sidebar.querySelectorAll('a[href], button:not(:disabled)')].filter((item) => item.getClientRects().length);
            const first = items[0];
            const last = items.at(-1);

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        }
    });
};

const initializeSearchShortcut = () => {
    const search = document.querySelector('[data-global-search]');

    if (! search) {
        return;
    }

    document.addEventListener('keydown', (event) => {
        if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            search.focus();
        }
    });
};

const initializeBusinessMenu = () => {
    const selector = document.querySelector('.business-selector');

    if (! selector) {
        return;
    }

    document.addEventListener('click', (event) => {
        if (! selector.contains(event.target)) {
            selector.removeAttribute('open');
        }
    });
};

const initializeNotificationCenter = () => {
    const center = document.querySelector('.notification-center');
    const toggle = center?.querySelector('[data-notification-toggle]');
    const menu = center?.querySelector('[data-notification-menu]');
    const markRead = center?.querySelector('[data-mark-notifications-read]');

    if (! center || ! toggle || ! menu) {
        return;
    }

    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        const willOpen = menu.hidden;
        menu.hidden = ! willOpen;
        toggle.setAttribute('aria-expanded', String(willOpen));
        document.querySelector('.business-selector')?.removeAttribute('open');
    });

    markRead?.addEventListener('click', () => {
        center.querySelectorAll('.notification-item').forEach((item) => item.classList.remove('is-unread'));
        center.querySelector('[data-notification-dot]')?.remove();
        center.querySelector('[data-notification-count]').textContent = 'Semua sudah dibaca';
        toggle.setAttribute('aria-label', 'Buka notifikasi');
    });

    document.addEventListener('click', (event) => {
        if (! center.contains(event.target)) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && ! menu.hidden) {
            close();
            toggle.focus();
        }
    });
};

const initializeProfileMenus = () => {
    document.querySelectorAll('[data-profile-menu]').forEach((menu) => {
        const toggle = menu.querySelector('[data-profile-menu-toggle]');
        const panel = menu.querySelector('[data-profile-menu-panel]');

        if (! toggle || ! panel) {
            return;
        }

        const items = () => [...panel.querySelectorAll('[data-profile-menu-item]')];
        const close = (restoreFocus = false) => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');

            if (restoreFocus) {
                toggle.focus();
            }
        };
        const open = (focusFirst = false) => {
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            document.querySelector('.business-selector')?.removeAttribute('open');

            if (focusFirst) {
                items()[0]?.focus();
            }
        };

        toggle.addEventListener('click', () => {
            panel.hidden ? open() : close();
        });

        toggle.addEventListener('keydown', (event) => {
            if (['ArrowDown', 'Enter', ' '].includes(event.key)) {
                event.preventDefault();
                open(true);
            }
        });

        panel.addEventListener('keydown', (event) => {
            const menuItems = items();
            const currentIndex = menuItems.indexOf(document.activeElement);
            let nextIndex = currentIndex;

            if (event.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % menuItems.length;
            } else if (event.key === 'ArrowUp') {
                nextIndex = (currentIndex - 1 + menuItems.length) % menuItems.length;
            } else if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = menuItems.length - 1;
            } else if (event.key === 'Escape') {
                event.preventDefault();
                close(true);
                return;
            } else {
                return;
            }

            event.preventDefault();
            menuItems[nextIndex]?.focus();
        });

        document.addEventListener('click', (event) => {
            if (! menu.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && ! panel.hidden) {
                close(true);
            }
        });
    });
};

const initializePasswordControls = () => {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.getAttribute('aria-controls'));

        if (! input) {
            return;
        }

        button.addEventListener('click', () => {
            const willShow = input.type === 'password';
            input.type = willShow ? 'text' : 'password';
            button.setAttribute('aria-label', willShow ? 'Sembunyikan password' : 'Tampilkan password');
            const replacementIcon = document.createElement('i');
            replacementIcon.setAttribute('data-lucide', willShow ? 'eye-off' : 'eye');
            replacementIcon.setAttribute('aria-hidden', 'true');
            replacementIcon.className = 'h-4 w-4';
            button.querySelector('svg')?.replaceWith(replacementIcon);
            initializeIcons();
            input.focus();
        });
    });

    const password = document.querySelector('[data-new-password]');

    if (! password) {
        return;
    }

    const updateRequirements = () => {
        const checks = {
            length: password.value.length >= 8,
            case: /[a-z]/.test(password.value) && /[A-Z]/.test(password.value),
            number: /\d/.test(password.value),
        };

        Object.entries(checks).forEach(([key, passes]) => {
            const requirement = document.querySelector(`[data-password-requirement="${key}"]`);
            requirement?.classList.toggle('text-brand-700', passes);
            requirement?.classList.toggle('font-semibold', passes);
        });
    };

    password.addEventListener('input', updateRequirements);
    updateRequirements();
};

const initializeSensitiveForms = () => {
    document.querySelectorAll('form[data-confirm], form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.getAttribute('aria-busy') === 'true') {
                event.preventDefault();
                return;
            }

            if (form.dataset.confirm && ! window.confirm(form.dataset.confirm)) {
                event.preventDefault();
                return;
            }

            if (form.hasAttribute('data-submit-once')) {
                const submitButton = form.querySelector('[type="submit"]');
                submitButton?.setAttribute('disabled', '');
                form.setAttribute('aria-busy', 'true');

                if (submitButton) {
                    const progress = document.createElement('span');
                    progress.textContent = 'Memproses…';
                    progress.className = 'text-xs';
                    progress.setAttribute('data-submit-progress', '');
                    progress.setAttribute('role', 'status');
                    submitButton.after(progress);
                }
            }
        });
    });
};

const prepareCanvas = (canvas) => {
    const bounds = canvas.getBoundingClientRect();
    const width = Math.max(bounds.width, 1);
    const height = Math.max(bounds.height, 1);
    const ratio = Math.min(window.devicePixelRatio || 1, 2);

    canvas.width = Math.round(width * ratio);
    canvas.height = Math.round(height * ratio);

    const context = canvas.getContext('2d');
    context.setTransform(ratio, 0, 0, ratio, 0, 0);

    return { context, width, height };
};

const drawLine = (context, points, color, width = 2, dashed = false) => {
    context.beginPath();
    points.forEach(([x, y], index) => {
        if (index === 0) {
            context.moveTo(x, y);
        } else {
            context.lineTo(x, y);
        }
    });
    context.setLineDash(dashed ? [5, 5] : []);
    context.strokeStyle = color;
    context.lineWidth = width;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.stroke();
    context.setLineDash([]);
};

const initializeSparklines = () => {
    document.querySelectorAll('[data-sparkline]').forEach((canvas) => {
        const values = canvas.dataset.sparkline.split(',').map(Number);
        const { context, width, height } = prepareCanvas(canvas);
        const min = Math.min(...values);
        const max = Math.max(...values);
        const range = Math.max(max - min, 1);
        const padding = 3;
        const points = values.map((value, index) => [
            padding + (index / Math.max(values.length - 1, 1)) * (width - padding * 2),
            padding + (1 - (value - min) / range) * (height - padding * 2),
        ]);
        const color = canvas.dataset.tone === 'neutral' ? '#7a8580' : '#2f8b68';

        const gradient = context.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, canvas.dataset.tone === 'neutral' ? 'rgba(122,133,128,.16)' : 'rgba(47,139,104,.2)');
        gradient.addColorStop(1, 'rgba(255,255,255,0)');

        context.beginPath();
        context.moveTo(points[0][0], height);
        points.forEach(([x, y]) => context.lineTo(x, y));
        context.lineTo(points.at(-1)[0], height);
        context.closePath();
        context.fillStyle = gradient;
        context.fill();
        drawLine(context, points, color, 1.8);
    });
};

const chartPoints = (values, max, bounds) => values.map((value, index) => [
    bounds.left + (index / Math.max(values.length - 1, 1)) * (bounds.right - bounds.left),
    bounds.bottom - (value / max) * (bounds.bottom - bounds.top),
]);

const renderSalesChart = (canvas, period) => {
    const { context, width, height } = prepareCanvas(canvas);
    const bounds = { left: 48, right: width - 46, top: 16, bottom: height - 34 };
    const revenueMax = Math.max(1, Math.ceil(Math.max(...period.revenue) * 1.22));
    const transactionMax = Math.max(50, Math.ceil(Math.max(...period.transactions) * 1.22 / 50) * 50);

    context.clearRect(0, 0, width, height);
    context.font = '10px "Instrument Sans", sans-serif';
    context.textBaseline = 'middle';

    for (let index = 0; index <= 4; index += 1) {
        const ratio = index / 4;
        const y = bounds.bottom - ratio * (bounds.bottom - bounds.top);

        context.beginPath();
        context.moveTo(bounds.left, y);
        context.lineTo(bounds.right, y);
        context.strokeStyle = '#e8ede9';
        context.lineWidth = 1;
        context.stroke();

        context.fillStyle = '#929c98';
        context.textAlign = 'right';
        context.fillText(index === 0 ? 'Rp0' : `Rp${(revenueMax * ratio).toLocaleString('id-ID', { maximumFractionDigits: 2 })}jt`, bounds.left - 9, y);
        context.textAlign = 'left';
        context.fillText(Math.round(transactionMax * ratio).toLocaleString('id-ID'), bounds.right + 9, y);
    }

    const revenuePoints = chartPoints(period.revenue, revenueMax, bounds);
    const transactionPoints = chartPoints(period.transactions, transactionMax, bounds);

    drawLine(context, revenuePoints, '#2f8b68', 2.5);
    drawLine(context, transactionPoints, '#69736f', 1.7, true);

    revenuePoints.forEach(([x, y]) => {
        context.beginPath();
        context.arc(x, y, 3, 0, Math.PI * 2);
        context.fillStyle = '#ffffff';
        context.fill();
        context.strokeStyle = '#2f8b68';
        context.lineWidth = 1.8;
        context.stroke();
    });

    context.fillStyle = '#929c98';
    context.textAlign = 'center';
    context.textBaseline = 'bottom';
    period.labels.forEach((label, index) => {
        const x = bounds.left + (index / Math.max(period.labels.length - 1, 1)) * (bounds.right - bounds.left);
        context.fillText(label, x, height - 5);
    });

};

const initializeSalesChart = () => {
    const chart = document.querySelector('[data-sales-chart]');

    if (! chart) {
        return;
    }

    const periods = JSON.parse(chart.dataset.periods);
    const canvas = chart.querySelector('[data-sales-canvas]');
    const chartToggle = chart.querySelector('[data-chart-toggle]');
    let currentPeriod = chart.dataset.defaultPeriod;

    const render = () => renderSalesChart(canvas, periods[currentPeriod]);
    const selectPeriod = (key) => {
        currentPeriod = key;
        const period = periods[key];

        chart.querySelectorAll('[data-chart-period]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.chartPeriod === key);
            button.setAttribute('aria-pressed', String(button.dataset.chartPeriod === key));
        });
        chart.querySelector('[data-revenue-total]').textContent = period.revenueTotal;
        chart.querySelector('[data-revenue-change]').textContent = period.revenueChange;
        chart.querySelector('[data-transaction-total]').textContent = period.transactionTotal;
        chart.querySelector('[data-transaction-change]').textContent = period.transactionChange;
        render();
    };

    chart.querySelectorAll('[data-chart-period]').forEach((button) => {
        button.addEventListener('click', () => selectPeriod(button.dataset.chartPeriod));
    });

    chartToggle?.addEventListener('click', () => {
        const expanded = chart.classList.toggle('chart-expanded');
        chartToggle.setAttribute('aria-expanded', String(expanded));
        chartToggle.querySelector('span').textContent = expanded ? 'Sembunyikan grafik' : 'Lihat grafik lengkap';
        window.requestAnimationFrame(() => render());
    });

    render();
    new ResizeObserver(() => render()).observe(canvas.parentElement);
};

const initializeAiSuggestions = () => {
    const input = document.querySelector('[data-ai-input]');

    if (! input) {
        return;
    }

    document.querySelectorAll('[data-ai-suggestion]').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = button.dataset.aiSuggestion;
            input.dispatchEvent(new Event('input'));
            input.focus();
        });
    });
};

const initializeInvitationCodeCopy = () => {
    const button = document.querySelector('[data-copy-invitation-code]');
    const code = document.querySelector('[data-invitation-code]');

    if (! button || ! code) {
        return;
    }

    button.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(code.textContent.trim());
            button.querySelector('span').textContent = 'Tersalin';
            document.querySelector('[data-copy-feedback]').textContent = 'Kode berhasil disalin. Bagikan kepada karyawan Anda.';
        } catch {
            document.querySelector('[data-copy-feedback]').textContent = 'Kode belum bisa disalin otomatis. Pilih teks kode, lalu salin secara manual.';
        }
    });
};

const initializeTaskForm = () => {
    const form = document.querySelector('[data-task-form]');
    const typeSelect = form?.querySelector('[data-task-type]');

    if (! form || ! typeSelect) {
        return;
    }

    const updateScheduleFields = () => {
        const isDaily = typeSelect.value === 'daily';

        form.querySelectorAll('[data-daily-field]').forEach((field) => {
            field.hidden = ! isDaily;
            field.querySelector('input').disabled = ! isDaily;
        });
        form.querySelectorAll('[data-one-time-field]').forEach((field) => {
            field.hidden = isDaily;
            field.querySelector('input').disabled = isDaily;
        });
    };

    typeSelect.addEventListener('change', updateScheduleFields);
    updateScheduleFields();
};


const initializeDailyTaskReset = () => {
    const workspace = document.querySelector('[data-daily-reset-at]');
    const resetAt = Date.parse(workspace?.dataset.dailyResetAt);

    if (! Number.isFinite(resetAt)) {
        return;
    }

    const hasUnsavedWork = () => [...document.querySelectorAll('form')].some((form) =>
        form.getAttribute('aria-busy') === 'true'
        || [...form.querySelectorAll('textarea, input, select')].some((field) => {
            if (field.type === 'file') {
                return field.files.length > 0;
            }
            if (field.type === 'checkbox' || field.type === 'radio') {
                return field.checked !== field.defaultChecked;
            }
            if (field.tagName === 'SELECT') {
                const defaultOption = [...field.options].find((option) => option.defaultSelected) ?? field.options[0];
                return field.value !== defaultOption?.value;
            }

            return field.value !== field.defaultValue || (field.tagName === 'TEXTAREA' && field.value.trim() !== '');
        })
    );
    const refresh = () => {
        if (Date.now() < resetAt || document.visibilityState === 'hidden') {
            return;
        }

        if (hasUnsavedWork()) {
            workspace.querySelector('[data-daily-reset-notice]').hidden = false;
            return;
        }

        window.location.reload();
    };

    window.setTimeout(refresh, Math.max(0, resetAt - Date.now()) + 1000);
    document.addEventListener('visibilitychange', refresh);
    window.addEventListener('pageshow', refresh);
};

const initializeTaskWorkspace = () => {
    const workspace = document.querySelector('[data-task-workspace]');

    if (! workspace) {
        return;
    }

    const search = workspace.querySelector('[data-task-search]');
    const filter = workspace.querySelector('[data-task-filter]');
    const groups = [...workspace.querySelectorAll('[data-task-group]')];
    const resultCount = workspace.querySelector('[data-task-result-count]');
    const noResults = workspace.querySelector('[data-task-no-results]');
    const initialOpenStates = new Map(groups.map((group) => [group, group.open]));
    const update = () => {
        const query = search.value.trim().toLocaleLowerCase('id-ID');
        const filtering = query !== '' || filter.value !== 'all';
        let visibleCount = 0;

        groups.forEach((group) => {
            const matchesGroup = filter.value === 'all' || filter.value === group.dataset.taskGroup;
            let groupCount = 0;

            group.querySelectorAll('[data-task-entry]').forEach((entry) => {
                const matches = matchesGroup && entry.dataset.taskTitle.toLocaleLowerCase('id-ID').includes(query);
                entry.hidden = ! matches;

                if (matches) {
                    groupCount += 1;
                }
            });

            group.hidden = groupCount === 0;
            group.open = filtering ? groupCount > 0 : initialOpenStates.get(group);
            visibleCount += groupCount;
        });

        resultCount.textContent = `${visibleCount} tugas ditampilkan.`;
        noResults.hidden = ! filtering || visibleCount > 0;
    };

    workspace.querySelector('[data-task-filters]').hidden = false;
    search.addEventListener('input', update);
    filter.addEventListener('change', update);
    workspace.querySelector('[data-task-reset]').addEventListener('click', () => {
        search.value = '';
        filter.value = 'all';
        update();
        search.focus();
    });
};

const initializeSectionNavigation = () => {
    const links = [...document.querySelectorAll('[data-section-link]')];
    const update = () => {
        const section = window.location.hash.slice(1) || 'employee-tasks';

        links.forEach((link) => {
            const active = link.dataset.sectionLink === section;
            link.classList.toggle('is-active', active);

            if (active) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        if (window.location.hash === '#team-access') {
            const invitation = document.querySelector('#team-access details');

            if (invitation) {
                invitation.open = true;
            }
        }
    };

    window.addEventListener('hashchange', update);
    update();
};

const initializeSalesUpload = () => {
    const input = document.querySelector('[data-sales-file]');
    const selection = document.querySelector('[data-file-selection]');

    if (! input || ! selection) {
        return;
    }

    input.addEventListener('change', () => {
        const file = input.files[0];
        selection.textContent = file
            ? `${file.name} · ${Math.max(1, Math.round(file.size / 1024)).toLocaleString('id-ID')} KB · siap diunggah`
            : 'Belum ada file dipilih.';
    });
};

window.addEventListener('pageshow', () => {
    document.querySelectorAll('form[aria-busy="true"]').forEach((form) => {
        form.removeAttribute('aria-busy');
        form.querySelector('[type="submit"]')?.removeAttribute('disabled');
        form.querySelector('[data-submit-progress]')?.remove();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    initializeIcons();
    initializeSidebar();
    initializeSearchShortcut();
    initializeBusinessMenu();
    initializeNotificationCenter();
    initializeProfileMenus();
    initializePasswordControls();
    initializeSensitiveForms();
    initializeSparklines();
    initializeSalesChart();
    initializeOfflineSales();
    initializeAiSuggestions();
    initializeAiInsight();
    initializeInvitationCodeCopy();
    initializeTaskForm();
    initializeTaskWorkspace();
    initializeDailyTaskReset();
    initializeSectionNavigation();
    initializeSalesUpload();
});
