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
    ChevronsUpDown,
    CircleHelp,
    Clock3,
    ContactRound,
    createIcons,
    FileChartColumn,
    LayoutDashboard,
    Lightbulb,
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
    ChevronsUpDown,
    CircleHelp,
    Clock3,
    ContactRound,
    FileChartColumn,
    LayoutDashboard,
    Lightbulb,
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

const renderSalesChart = (canvas, period) => {
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
};

const initializeSalesChart = () => {
    const chart = document.querySelector('[data-sales-chart]');

    if (! chart) {
        return;
    }

    const periods = JSON.parse(chart.dataset.periods);
    const canvas = chart.querySelector('[data-sales-canvas]');
    let currentPeriod = chart.dataset.defaultPeriod;

    const render = () => renderSalesChart(canvas, periods[currentPeriod]);
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
        render();
    };

    chart.querySelectorAll('[data-chart-period]').forEach((button) => {
        button.addEventListener('click', () => selectPeriod(button.dataset.chartPeriod));
    });

    render();
    new ResizeObserver(render).observe(canvas.parentElement);
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

document.addEventListener('DOMContentLoaded', () => {
    initializeIcons();
    initializeSidebar();
    initializeSearchShortcut();
    initializeBusinessMenu();
    initializeSparklines();
    initializeSalesChart();
    initializeAiSuggestions();
});
