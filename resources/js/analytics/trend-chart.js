let trendChart = null;
let distributionChart = null;
let lastRevision = null;
let updateTimeout = null;

export function initCharts() {
    const dataContainer = document.getElementById('trend-data-container');
    if (!dataContainer) {
        console.error('Trend data container not found');
        return;
    }

    const revision = dataContainer.getAttribute('data-revision');

    // Only update if revision has changed
    if (revision === lastRevision) {
        console.log('Charts already up to date - revision:', revision);
        return;
    }

    lastRevision = revision;

    const trendDataAttr = dataContainer.getAttribute('data-trend-data');
    const distributionDataAttr = dataContainer.getAttribute('data-distribution-data');

    const trendData = trendDataAttr ? JSON.parse(trendDataAttr) : [];
    const distData = distributionDataAttr ? JSON.parse(distributionDataAttr) : [];

    console.log('Updating charts - revision:', revision, 'trend points:', trendData.length, 'dist bins:', distData.length);

    // Trend Chart
    const trendElement = document.getElementById('trend-chart');
    if (trendElement && trendData.length > 0) {
        if (trendChart) {
            trendChart.destroy();
            trendChart = null;
        }

        const ctx = trendElement.getContext('2d');

        try {
            trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => new Date(d.period).toLocaleDateString()),
                    datasets: [
                        {
                            label: 'Average',
                            data: trendData.map(d => d.average),
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Maximum',
                            data: trendData.map(d => d.maximum),
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5]
                        },
                        {
                            label: 'Minimum',
                            data: trendData.map(d => d.minimum),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'transparent',
                            borderDash: [5, 5]
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating trend chart:', error);
        }
    }

    // Distribution Chart
    const distElement = document.getElementById('distribution-chart');
    if (distElement && distData.length > 0) {
        if (distributionChart) {
            distributionChart.destroy();
            distributionChart = null;
        }

        const ctx = distElement.getContext('2d');

        try {
            distributionChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: distData.map(d => d.range),
                    datasets: [{
                        label: 'Count',
                        data: distData.map(d => d.count),
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating distribution chart:', error);
        }
    }
}

export function setupTrendChartListeners() {
    // Initialize on DOMContentLoaded (first page load)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                initCharts();
                setupMutationObserver();
            }, 600);
        });
    } else {
        // DOM already loaded
        setTimeout(() => {
            initCharts();
            setupMutationObserver();
        }, 600);
    }

    // Re-initialize on Livewire navigation (SPA navigation)
    document.addEventListener('livewire:navigated', () => {
        lastRevision = null; // Reset revision tracking
        setTimeout(() => {
            initCharts();
            setupMutationObserver();
        }, 600);
    });
}

function setupMutationObserver() {
    // Watch for when the data container gets replaced by Livewire (due to wire:key change)
    const targetNode = document.body;
    const config = { childList: true, subtree: true };

    const callback = function(mutationsList, observer) {
        for (const mutation of mutationsList) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(node => {
                    if (node.id === 'trend-data-container') {
                        console.log('Data container replaced, updating charts');
                        setTimeout(initCharts, 50);
                    }
                });
            }
        }
    };

    const observer = new MutationObserver(callback);
    observer.observe(targetNode, config);
}

