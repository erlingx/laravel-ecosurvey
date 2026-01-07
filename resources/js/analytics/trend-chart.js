let trendChart = null;
let distributionChart = null;
let lastRevision = null;
let initialized = false;

function normalizeTrendData(trendData) {
    if (!Array.isArray(trendData)) {
        return [];
    }

    return trendData
        .map((d) => ({
            period: d.period,
            average: Number(d.average),
            minimum: Number(d.minimum),
            maximum: Number(d.maximum),
        }))
        .filter((d) => Number.isFinite(d.average) && Number.isFinite(d.minimum) && Number.isFinite(d.maximum));
}

export function initCharts() {
    const dataContainer = document.getElementById('trend-data-container');
    if (!dataContainer) {
        return;
    }

    const trendElement = document.getElementById('trend-chart');
    if (!trendElement) {
        return;
    }

    const revision = dataContainer.getAttribute('data-revision');
    if (revision === lastRevision && initialized) {
        return;
    }

    const trendDataAttr = dataContainer.getAttribute('data-trend-data');
    const distributionDataAttr = dataContainer.getAttribute('data-distribution-data');

    const trendDataRaw = trendDataAttr ? JSON.parse(trendDataAttr) : [];
    const distData = distributionDataAttr ? JSON.parse(distributionDataAttr) : [];

    const trendData = normalizeTrendData(trendDataRaw);

    // If there's no usable data, mark as done and return
    if (trendData.length === 0 && (!Array.isArray(distData) || distData.length === 0)) {
        lastRevision = revision;
        initialized = true;
        return;
    }

    const labels = trendData.map((d) => new Date(d.period).toLocaleDateString());
    const avg = trendData.map((d) => d.average);
    const max = trendData.map((d) => d.maximum);
    const min = trendData.map((d) => d.minimum);


    // Trend chart
    if (trendElement && trendData.length > 0) {
        const ctx = trendElement.getContext('2d');

        // Destroy any existing chart on this canvas first
        const existingChart = Chart.getChart(trendElement);
        if (existingChart) {
            existingChart.destroy();
        }
        if (trendChart) {
            trendChart.destroy();
            trendChart = null;
        }

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Average',
                        data: avg,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0,
                        spanGaps: false,
                    },
                    {
                        label: 'Maximum',
                        data: max,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0,
                        spanGaps: false,
                    },
                    {
                        label: 'Minimum',
                        data: min,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        tension: 0,
                        spanGaps: false,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { mode: 'index', intersect: false },
                },
                scales: {
                    y: {
                        beginAtZero: false,
                    },
                },
            },
        });
    }

    // Distribution chart
    const distElement = document.getElementById('distribution-chart');
    if (distElement && Array.isArray(distData) && distData.length > 0) {
        const ctx = distElement.getContext('2d');
        const distLabels = distData.map((d) => d.range);
        const distCounts = distData.map((d) => Number(d.count));

        // Destroy any existing chart on this canvas first
        const existingDistChart = Chart.getChart(distElement);
        if (existingDistChart) {
            existingDistChart.destroy();
        }
        if (distributionChart) {
            distributionChart.destroy();
            distributionChart = null;
        }

        distributionChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: distLabels,
                datasets: [
                    {
                        label: 'Count',
                        data: distCounts,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    }

    lastRevision = revision;
    initialized = true;
}

export function setupTrendChartListeners() {
    // Register once globally.
    if (window.__trendChartListenersRegistered) {
        return;
    }
    window.__trendChartListenersRegistered = true;

    const tryInit = () => {
        const hasContainer = !!document.getElementById('trend-data-container');
        const hasCanvas = !!document.getElementById('trend-chart');

        if (hasContainer && hasCanvas) {
            initCharts();
        }
    };

    // First load - wait for DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(tryInit, 100);
        }, { once: true });
    } else {
        setTimeout(tryInit, 100);
    }

    // SPA navigation
    document.addEventListener('livewire:navigated', () => {
        // Reset state for new page
        lastRevision = null;
        initialized = false;
        trendChart = null;
        distributionChart = null;

        setTimeout(tryInit, 100);
    });

    // Listen for Livewire component updates (filter changes)
    document.addEventListener('livewire:morph', () => {
        // Don't reset initialized - just allow revision check
        setTimeout(tryInit, 50);
    });
}
