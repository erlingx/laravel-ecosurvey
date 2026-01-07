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
            std_dev: Number(d.std_dev) || 0,
            count: Number(d.count) || 0,
            ci_lower: Number(d.ci_lower) || Number(d.average),
            ci_upper: Number(d.ci_upper) || Number(d.average),
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
    const ciUpper = trendData.map((d) => d.ci_upper);
    const ciLower = trendData.map((d) => d.ci_lower);

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
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.2,
                        order: 1,
                    },
                    {
                        label: '95% CI Upper',
                        data: ciUpper,
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 1,
                        borderDash: [2, 2],
                        pointRadius: 0,
                        fill: '+1',
                        tension: 0.2,
                        order: 2,
                    },
                    {
                        label: '95% CI Lower',
                        data: ciLower,
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        backgroundColor: 'transparent',
                        borderWidth: 1,
                        borderDash: [2, 2],
                        pointRadius: 0,
                        tension: 0.2,
                        order: 2,
                    },
                    {
                        label: 'Maximum',
                        data: max,
                        borderColor: 'rgb(239, 68, 68)',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        pointRadius: 2,
                        tension: 0.2,
                        hidden: true,
                    },
                    {
                        label: 'Minimum',
                        data: min,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        pointRadius: 2,
                        tension: 0.2,
                        hidden: true,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            filter: function(item) {
                                // Hide CI lines from legend
                                return !item.text.includes('95% CI');
                            }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            footer: function(tooltipItems) {
                                if (tooltipItems.length > 0) {
                                    const index = tooltipItems[0].dataIndex;
                                    const n = trendData[index]?.count || 0;
                                    const stdDev = trendData[index]?.std_dev || 0;
                                    const ciLower = trendData[index]?.ci_lower || 0;
                                    const ciUpper = trendData[index]?.ci_upper || 0;
                                    return `n = ${n} | σ = ${stdDev.toFixed(2)}\n95% CI: [${ciLower.toFixed(2)}, ${ciUpper.toFixed(2)}]`;
                                }
                                return '';
                            }
                        }
                    },
                    zoom: {
                        pan: {
                            enabled: true,
                            mode: 'x',
                            modifierKey: 'ctrl',
                        },
                        zoom: {
                            wheel: {
                                enabled: true,
                                speed: 0.1,
                            },
                            pinch: {
                                enabled: true
                            },
                            mode: 'x',
                            onZoomComplete({chart}) {
                                // Optional callback for zoom complete
                            }
                        },
                        limits: {
                            x: {min: 'original', max: 'original'},
                        }
                    },
                    annotation: {
                        annotations: {
                            // Average line across all data
                            averageLine: {
                                type: 'line',
                                yMin: trendData.reduce((sum, d) => sum + d.average, 0) / trendData.length,
                                yMax: trendData.reduce((sum, d) => sum + d.average, 0) / trendData.length,
                                borderColor: 'rgba(59, 130, 246, 0.3)',
                                borderWidth: 2,
                                borderDash: [10, 5],
                                label: {
                                    content: 'Overall Average',
                                    enabled: true,
                                    position: 'end',
                                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                                    color: 'white',
                                    font: {
                                        size: 10
                                    }
                                }
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Value'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Time Period (Double-click chart to reset zoom)'
                        }
                    }
                },
            },
        });

        // Add double-click to reset zoom
        trendElement.addEventListener('dblclick', () => {
            if (trendChart) {
                trendChart.resetZoom();
            }
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
                        title: {
                            display: true,
                            text: 'Frequency (n)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Value Range'
                        }
                    }
                },
            },
        });
    }

    lastRevision = revision;
    initialized = true;

    // Expose chart globally for toggle buttons
    window.trendChartInstance = trendChart;

    // Sync button text with actual dataset state
    syncToggleButtons();
}

// Sync toggle button text with actual chart state
function syncToggleButtons() {
    if (!window.trendChartInstance) return;

    const chart = window.trendChartInstance;

    // Sync Maximum button
    const maxIndex = chart.data.datasets.findIndex(ds => ds.label === 'Maximum');
    if (maxIndex !== -1) {
        const maxBtn = document.getElementById('toggle-max-btn');
        if (maxBtn) {
            const isVisible = chart.isDatasetVisible(maxIndex);
            const span = maxBtn.querySelector('span');
            if (span) {
                span.innerHTML = isVisible
                    ? '<span class="h-2 w-2 rounded-full bg-red-500"></span> Hide Maximum'
                    : '<span class="h-2 w-2 rounded-full bg-red-500"></span> Show Maximum';
            }
        }
    }

    // Sync Minimum button
    const minIndex = chart.data.datasets.findIndex(ds => ds.label === 'Minimum');
    if (minIndex !== -1) {
        const minBtn = document.getElementById('toggle-min-btn');
        if (minBtn) {
            const isVisible = chart.isDatasetVisible(minIndex);
            const span = minBtn.querySelector('span');
            if (span) {
                span.innerHTML = isVisible
                    ? '<span class="h-2 w-2 rounded-full bg-green-500"></span> Hide Minimum'
                    : '<span class="h-2 w-2 rounded-full bg-green-500"></span> Show Minimum';
            }
        }
    }
}

// Toggle function for Max/Min lines
window.toggleTrendLine = function(label) {
    if (!window.trendChartInstance) {
        console.error('[Toggle] No chart instance available');
        return;
    }

    const chart = window.trendChartInstance;
    const datasetIndex = chart.data.datasets.findIndex(ds => ds.label === label);

    if (datasetIndex === -1) {
        console.error('[Toggle] Dataset not found:', label);
        return;
    }

    // Show loading overlay on chart
    const chartContainer = document.getElementById('trend-chart')?.parentElement;
    if (chartContainer) {
        // Create overlay
        const overlay = document.createElement('div');
        overlay.id = 'chart-loading-overlay';
        overlay.className = 'absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center z-50 rounded-lg';
        overlay.innerHTML = `
            <div class="flex flex-col items-center gap-2">
                <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 dark:text-gray-400">Updating chart...</span>
            </div>
        `;
        chartContainer.style.position = 'relative';
        chartContainer.appendChild(overlay);
    }

    // Use setTimeout to allow overlay to render
    setTimeout(() => {
        // Chart.js: isDatasetVisible returns true if visible, false if hidden
        const isCurrentlyVisible = chart.isDatasetVisible(datasetIndex);

        // Toggle: if visible now, hide it; if hidden now, show it
        chart.setDatasetVisibility(datasetIndex, !isCurrentlyVisible);
        chart.update();

        // Update button text
        const btnId = label === 'Maximum' ? 'toggle-max-btn' : 'toggle-min-btn';
        const btn = document.getElementById(btnId);
        const color = label === 'Maximum' ? 'red' : 'green';

        if (btn) {
            const innerSpan = btn.querySelector('span');
            if (innerSpan) {
                // After toggle: if it WAS visible, it's now hidden (show "Show")
                // if it WAS hidden, it's now visible (show "Hide")
                if (isCurrentlyVisible) {
                    // Just hid it, show "Show" button
                    innerSpan.innerHTML = `<span class="h-2 w-2 rounded-full bg-${color}-500"></span> Show ${label}`;
                } else {
                    // Just showed it, show "Hide" button
                    innerSpan.innerHTML = `<span class="h-2 w-2 rounded-full bg-${color}-500"></span> Hide ${label}`;
                }
            }
        }

        // Remove overlay after a short delay to show completion
        setTimeout(() => {
            const overlay = document.getElementById('chart-loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }, 200);
    }, 50);
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

    // Listen for Livewire component updates (filter changes) - same as heatmap
    Livewire.hook('morph.updated', () => {
        setTimeout(tryInit, 50);
    });
}
