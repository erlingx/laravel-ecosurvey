let heatmapLayer = null;
let baseLayer = null;
let lastRevision = null;

export function initHeatmap() {
    const element = document.getElementById('heatmap');
    if (!element) return;

    // Check if element is hidden - if so, don't initialize yet
    if (element.classList.contains('hidden')) {
        console.log('[Heatmap] Element hidden, skipping initialization');
        return;
    }

    // If map already exists, don't recreate it
    if (window.heatmapMap) {
        // Force map to recalculate size in case container changed
        setTimeout(() => {
            window.heatmapMap.invalidateSize();
            updateHeatmap();
        }, 100);
        return;
    }

    const map = L.map('heatmap').setView([55.6761, 12.5683], 11);
    window.heatmapMap = map;

    // Initialize with street view
    baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Force size recalculation after a short delay
    setTimeout(() => {
        map.invalidateSize();
        updateHeatmap();
    }, 100);
}

export function updateHeatmap() {
    if (!window.heatmapMap) return;

    const dataContainer = document.getElementById('heatmap-data-container');
    if (!dataContainer) {
        console.error('[Heatmap] Data container not found');
        return;
    }

    const revision = dataContainer.getAttribute('data-revision');

    // Only update if revision has changed
    if (revision === lastRevision) {
        return;
    }

    lastRevision = revision;

    const heatmapDataAttr = dataContainer.getAttribute('data-heatmap-data');
    const mapType = dataContainer.getAttribute('data-map-type') || 'street';

    const data = heatmapDataAttr ? JSON.parse(heatmapDataAttr) : [];

    console.log('[Heatmap] Update: revision=' + revision + ', points=' + data.length + ', type=' + mapType);

    // Debug: Log first few data points to inspect structure
    if (data.length > 0) {
        console.log('[Heatmap] Sample data point:', data[0]);
        console.log('[Heatmap] Data point structure check:', {
            isArray: Array.isArray(data[0]),
            length: data[0]?.length,
            lat: data[0]?.[0],
            lng: data[0]?.[1],
            value: data[0]?.[2]
        });
    }

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
        heatmapLayer = null;
    }

    // Add new heatmap layer
    if (data.length > 0) {
        // Filter out invalid data points (null coords, NaN values)
        const validData = data.filter(point => {
            return Array.isArray(point) &&
                   point.length === 3 &&
                   typeof point[0] === 'number' && Number.isFinite(point[0]) &&
                   typeof point[1] === 'number' && Number.isFinite(point[1]) &&
                   typeof point[2] === 'number' && Number.isFinite(point[2]) &&
                   point[0] >= -90 && point[0] <= 90 && // Valid latitude
                   point[1] >= -180 && point[1] <= 180; // Valid longitude
        });

        if (validData.length === 0) {
            console.log('[Heatmap] No valid data points after filtering');
            window.heatmapMap.setView([55.6761, 12.5683], 11);
            return;
        }

        console.log('[Heatmap] Valid points: ' + validData.length + '/' + data.length);

        // Force map size recalculation to ensure canvas has proper dimensions
        window.heatmapMap.invalidateSize();

        // Check if map container has valid size
        const mapContainer = window.heatmapMap.getContainer();
        if (mapContainer.offsetWidth === 0 || mapContainer.offsetHeight === 0) {
            console.error('[Heatmap] Map container has invalid size:', {
                width: mapContainer.offsetWidth,
                height: mapContainer.offsetHeight
            });
            return;
        }

        // Calculate max value for proper intensity normalization
        const maxValue = Math.max(...validData.map(point => point[2]));

        console.log('[Heatmap] Creating layer: min=' + Math.min(...validData.map(point => point[2])) + ', max=' + maxValue);

        heatmapLayer = L.heatLayer(validData, {
            radius: 30,      // Increased from 25 for better visibility
            blur: 20,        // Increased from 15 for smoother gradient
            maxZoom: 17,
            minOpacity: 0.3, // Added minimum opacity
            max: maxValue,   // Use actual data max for normalization
            gradient: {
                0.0: 'blue',    // Low values
                0.5: 'lime',    // Medium values
                1.0: 'red'      // High values
            }
        }).addTo(window.heatmapMap);

        // Fit bounds to data
        const bounds = L.latLngBounds(validData.map(point => [point[0], point[1]]));
        window.heatmapMap.fitBounds(bounds, { padding: [50, 50] });

        console.log('[Heatmap] Layer created and map fitted to bounds');
    } else {
        console.log('[Heatmap] No data - resetting to default view');
        // Reset map view to default Copenhagen center when no data
        window.heatmapMap.setView([55.6761, 12.5683], 11);
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

        // Reset map state if navigating away from heatmap page
        if (!document.getElementById('heatmap')) {
            if (window.heatmapMap) {
                window.heatmapMap.remove();
                window.heatmapMap = null;
            }
            heatmapLayer = null;
            baseLayer = null;
        }

        setTimeout(initHeatmap, 600);
    });

    // Update when Livewire updates (filter changes)
    Livewire.hook('morph.updated', () => {
        setTimeout(updateHeatmap, 100);
    });
}

