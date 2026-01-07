let heatmapLayer = null;
let baseLayer = null;
let lastRevision = null;

export function initHeatmap() {
    const element = document.getElementById('heatmap');
    if (!element || window.heatmapMap) return;

    const map = L.map('heatmap').setView([55.6761, 12.5683], 11);
    window.heatmapMap = map;

    // Initialize with street view
    baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    updateHeatmap();
}

export function updateHeatmap() {
    if (!window.heatmapMap) return;

    const dataContainer = document.getElementById('heatmap-data-container');
    if (!dataContainer) {
        console.error('Heatmap data container not found');
        return;
    }

    const revision = dataContainer.getAttribute('data-revision');

    // Only update if revision has changed
    if (revision === lastRevision) {
        console.log('Heatmap already up to date - revision:', revision);
        return;
    }

    lastRevision = revision;

    const heatmapDataAttr = dataContainer.getAttribute('data-heatmap-data');
    const mapType = dataContainer.getAttribute('data-map-type') || 'street';

    const data = heatmapDataAttr ? JSON.parse(heatmapDataAttr) : [];

    console.log('Updating heatmap - revision:', revision, 'points:', data.length, 'mapType:', mapType);

    // Update base layer
    if (baseLayer) {
        window.heatmapMap.removeLayer(baseLayer);
    }

    if (mapType === 'satellite') {
        baseLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: '© Esri'
        }).addTo(window.heatmapMap);
    } else {
        baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(window.heatmapMap);
    }

    // Remove existing heatmap layer
    if (heatmapLayer) {
        window.heatmapMap.removeLayer(heatmapLayer);
    }

    // Add new heatmap layer
    if (data.length > 0) {
        heatmapLayer = L.heatLayer(data, {
            radius: 25,
            blur: 15,
            maxZoom: 17,
            max: 1.0,
            gradient: {
                0.0: 'blue',
                0.5: 'lime',
                1.0: 'red'
            }
        }).addTo(window.heatmapMap);

        // Fit bounds to data
        const bounds = L.latLngBounds(data.map(point => [point[0], point[1]]));
        window.heatmapMap.fitBounds(bounds, { padding: [50, 50] });
    }
}

export function setupHeatmapListeners() {
    // Initialize on DOMContentLoaded (first page load)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initHeatmap, 600);
        });
    } else {
        // DOM already loaded
        setTimeout(initHeatmap, 600);
    }

    // Re-initialize on Livewire navigation (SPA navigation)
    document.addEventListener('livewire:navigated', () => {
        lastRevision = null; // Reset revision tracking
        setTimeout(initHeatmap, 600);
    });

    // Update when Livewire updates (filter changes)
    Livewire.hook('morph.updated', () => {
        setTimeout(updateHeatmap, 100);
    });
}

