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
    CircleHelp,
    Clock3,
    ContactRound,
    Copy,
    createIcons,
    DatabaseZap,
    FileChartColumn,
    LayoutDashboard,
    Lightbulb,
    LogOut,
    Menu,
    Package,
    PackageX,
    Plus,
    RadioTower,
    ReceiptText,
    Search,
    Settings2,
    ShieldCheck,
    ShoppingBasket,
    Sparkles,
    TrendingUp,
    UserRoundCheck,
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
    CircleHelp,
    Clock3,
    ContactRound,
    Copy,
    DatabaseZap,
    FileChartColumn,
    LayoutDashboard,
    Lightbulb,
    LogOut,
    Menu,
    Package,
    PackageX,
    Plus,
    RadioTower,
    ReceiptText,
    Search,
    Settings2,
    ShieldCheck,
    ShoppingBasket,
    Sparkles,
    TrendingUp,
    UserRoundCheck,
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

    const open = () => {
        document.body.classList.add('sidebar-open');
        openButton.setAttribute('aria-expanded', 'true');
        sidebar.querySelector('a')?.focus();
    };

    const close = () => {
        document.body.classList.remove('sidebar-open');
        openButton.setAttribute('aria-expanded', 'false');
        openButton.focus();
    };

    openButton.setAttribute('aria-expanded', 'false');
    openButton.addEventListener('click', open);
    closeButtons.forEach((button) => button.addEventListener('click', close));
    collapseButton?.addEventListener('click', () => {
        const collapsed = document.body.classList.toggle('sidebar-collapsed');
        collapseButton.setAttribute('aria-expanded', String(! collapsed));
        collapseButton.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            close();
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

const prepareCanvas = (canvas) => {
    const bounds = canvas.getBoundingClientRect();
    const width = Math.max(bounds.width, Number(canvas.getAttribute('width')) || 1);
    const height = Math.max(bounds.height, Number(canvas.getAttribute('height')) || 1);
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
            padding + (index / (values.length - 1)) * (width - padding * 2),
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

const renderSalesChart = (canvas, period, hoverIndex = null) => {
    const { context, width, height } = prepareCanvas(canvas);
    const bounds = { left: 48, right: width - 46, top: 16, bottom: height - 34 };
    const revenueMax = Math.ceil(Math.max(...period.revenue) * 1.22);
    const transactionMax = Math.ceil(Math.max(...period.transactions) * 1.22 / 50) * 50;

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
        context.fillText(index === 0 ? 'Rp0' : `Rp${Math.round(revenueMax * ratio)}jt`, bounds.left - 9, y);
        context.textAlign = 'left';
        context.fillText(Math.round(transactionMax * ratio).toLocaleString('id-ID'), bounds.right + 9, y);
    }

    const revenuePoints = chartPoints(period.revenue, revenueMax, bounds);
    const transactionPoints = chartPoints(period.transactions, transactionMax, bounds);
    const area = context.createLinearGradient(0, bounds.top, 0, bounds.bottom);
    area.addColorStop(0, 'rgba(47,139,104,.22)');
    area.addColorStop(1, 'rgba(47,139,104,0)');

    context.beginPath();
    context.moveTo(revenuePoints[0][0], bounds.bottom);
    revenuePoints.forEach(([x, y]) => context.lineTo(x, y));
    context.lineTo(revenuePoints.at(-1)[0], bounds.bottom);
    context.closePath();
    context.fillStyle = area;
    context.fill();

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

    if (hoverIndex !== null) {
        const [hoverX, revenueY] = revenuePoints[hoverIndex];
        const [, transactionY] = transactionPoints[hoverIndex];

        context.beginPath();
        context.moveTo(hoverX, bounds.top);
        context.lineTo(hoverX, bounds.bottom);
        context.strokeStyle = 'rgba(105,115,111,.24)';
        context.lineWidth = 1;
        context.stroke();

        [[revenueY, '#2f8b68'], [transactionY, '#69736f']].forEach(([y, color]) => {
            context.beginPath();
            context.arc(hoverX, y, 4, 0, Math.PI * 2);
            context.fillStyle = '#ffffff';
            context.fill();
            context.strokeStyle = color;
            context.lineWidth = 2.2;
            context.stroke();
        });
    }

    return { bounds, revenuePoints, transactionPoints };
};

const initializeSalesChart = () => {
    const chart = document.querySelector('[data-sales-chart]');

    if (! chart) {
        return;
    }

    const periods = JSON.parse(chart.dataset.periods);
    const canvas = chart.querySelector('[data-sales-canvas]');
    const tooltip = chart.querySelector('[data-chart-tooltip]');
    const chartToggle = chart.querySelector('[data-chart-toggle]');
    let currentPeriod = chart.dataset.defaultPeriod;

    const render = (hoverIndex = null) => renderSalesChart(canvas, periods[currentPeriod], hoverIndex);
    const selectPeriod = (key) => {
        currentPeriod = key;
        const period = periods[key];

        chart.querySelectorAll('[data-chart-period]').forEach((button) => {
            button.classList.toggle('is-active', button.dataset.chartPeriod === key);
        });
        chart.querySelector('[data-revenue-total]').textContent = period.revenueTotal;
        chart.querySelector('[data-revenue-change]').textContent = period.revenueChange;
        chart.querySelector('[data-transaction-total]').textContent = period.transactionTotal;
        chart.querySelector('[data-transaction-change]').textContent = period.transactionChange;
        tooltip.hidden = true;
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

    canvas.addEventListener('pointermove', (event) => {
        const period = periods[currentPeriod];
        const bounds = canvas.getBoundingClientRect();
        const pointerX = event.clientX - bounds.left;
        const plotLeft = 48;
        const plotRight = bounds.width - 46;

        if (pointerX < plotLeft || pointerX > plotRight) {
            tooltip.hidden = true;
            render();
            return;
        }

        const ratio = (pointerX - plotLeft) / Math.max(plotRight - plotLeft, 1);
        const index = Math.max(0, Math.min(period.labels.length - 1, Math.round(ratio * (period.labels.length - 1))));
        const state = render(index);
        const x = state.revenuePoints[index][0];
        const y = Math.min(state.revenuePoints[index][1], state.transactionPoints[index][1]);

        tooltip.querySelector('[data-tooltip-label]').textContent = period.labels[index];
        tooltip.querySelector('[data-tooltip-revenue]').textContent = `Rp${period.revenue[index].toLocaleString('id-ID')} jt`;
        tooltip.querySelector('[data-tooltip-transactions]').textContent = `${period.transactions[index].toLocaleString('id-ID')} transaksi`;
        tooltip.style.left = `${x}px`;
        tooltip.style.top = `${Math.max(y, 72)}px`;
        tooltip.hidden = false;
    });

    canvas.addEventListener('pointerleave', () => {
        tooltip.hidden = true;
        render();
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
        } catch {
            code.focus?.();
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

document.addEventListener('DOMContentLoaded', () => {
    initializeIcons();
    initializeSidebar();
    initializeSearchShortcut();
    initializeBusinessMenu();
    initializeNotificationCenter();
    initializeSparklines();
    initializeSalesChart();
    initializeAiSuggestions();
    initializeInvitationCodeCopy();
    initializeTaskForm();
});
