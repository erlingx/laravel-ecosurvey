import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.heat';
import Chart from 'chart.js/auto';
import {BarWithErrorBarsController, PointWithErrorBar} from 'chartjs-chart-error-bars';
import annotationPlugin from 'chartjs-plugin-annotation';
import zoomPlugin from 'chartjs-plugin-zoom';

// Register Chart.js plugins
Chart.register(BarWithErrorBarsController, PointWithErrorBar);
Chart.register(annotationPlugin);
Chart.register(zoomPlugin);

// Fix Leaflet's default icon path issue with Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
});

// Make Leaflet and Chart globally available
window.L = L;
window.Chart = Chart;

// Import modular map functions
import {
    initSurveyMap,
    createPopupContent,
    resetMapView,
    toggleClustering,
    updateMapMarkers,
    setupSurveyMapListeners
} from './maps/survey-map.js';

import {
    initSatelliteMap,
    updateSatelliteImagery,
    setupSatelliteMapListeners,
    setupSatelliteNavigation
} from './maps/satellite-map.js';

import {
    initCharts,
    setupTrendChartListeners
} from './analytics/trend-chart.js';

import {
    initHeatmap,
    updateHeatmap,
    setupHeatmapListeners
} from './analytics/heatmap.js';

// Expose functions globally for backward compatibility
window.initSurveyMap = initSurveyMap;
window.createPopupContent = createPopupContent;
window.resetMapView = resetMapView;
window.toggleClustering = toggleClustering;
window.updateMapMarkers = updateMapMarkers;

window.initSatelliteMap = initSatelliteMap;
window.updateSatelliteImagery = updateSatelliteImagery;

window.initCharts = initCharts;
window.initHeatmap = initHeatmap;
window.updateHeatmap = updateHeatmap;


// Set up event listeners
setupSurveyMapListeners();
setupSatelliteMapListeners();
setupSatelliteNavigation();
setupTrendChartListeners();
setupHeatmapListeners();

// Initialize maps when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Survey Map
    const mapElement = document.getElementById('survey-map');
    const dataContainer = document.getElementById('map-data-container');

    console.log('DOM loaded - Map element:', mapElement, 'Data container:', dataContainer);

    if (mapElement) {
        // Initialize map data from data container if it exists
        if (dataContainer) {
            try {
                const pointsData = dataContainer.getAttribute('data-points');
                const boundsData = dataContainer.getAttribute('data-bounds');

                window.mapData = pointsData ? JSON.parse(pointsData) : {"type":"FeatureCollection","features":[]};
                window.mapBounds = boundsData ? JSON.parse(boundsData) : null;

                console.log('Loaded map data:', window.mapData);
            } catch (e) {
                console.error('Error parsing initial map data:', e);
                window.mapData = {"type":"FeatureCollection","features":[]};
                window.mapBounds = null;
            }
        } else {
            // Fallback if data container doesn't exist yet
            window.mapData = {"type":"FeatureCollection","features":[]};
            window.mapBounds = null;
        }

        // Initialize the map
        if (!window.mapInitialized) {
            console.log('Initializing survey map...');
            initSurveyMap();
            window.mapInitialized = true;
        }
    }

    // Satellite Map
    const satelliteElement = document.getElementById('satellite-map');
    if (satelliteElement) {
        console.log('Initializing satellite map...');
        initSatelliteMap();
    }
});
