import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import 'leaflet-draw/dist/leaflet.draw.css';

import L from 'leaflet';
import 'leaflet.markercluster';
import 'leaflet.heat';
import 'leaflet-draw';
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
    initZoneEditorMap,
    updateZoneEditorMap
} from './maps/zone-editor.js';

import {
    initHeatmap,
    updateHeatmap,
    setupHeatmapListeners
} from './analytics/heatmap.js';

// Expose functions globally for backward compatibility
window.initSurveyMap = initSurveyMap;
window.createPopupContent = createPopupContent;
window.resetMapView = resetMapView;
window.updateMapMarkers = updateMapMarkers;

window.initSatelliteMap = initSatelliteMap;
window.updateSatelliteImagery = updateSatelliteImagery;

window.initZoneEditorMap = initZoneEditorMap;
window.updateZoneEditorMap = updateZoneEditorMap;

window.initCharts = initCharts;
window.initHeatmap = initHeatmap;
window.updateHeatmap = updateHeatmap;


// Set up event listeners
setupSurveyMapListeners();
setupSatelliteMapListeners();
setupSatelliteNavigation();
setupTrendChartListeners();
setupHeatmapListeners();

// Initialize maps - called on both DOMContentLoaded and Livewire navigations
let initTimeout = null;

function initializeMaps() {
    // Clear any pending initialization to prevent double init
    if (initTimeout) {
        clearTimeout(initTimeout);
    }

    // Debounce to prevent rapid re-initialization
    initTimeout = setTimeout(() => {
        console.log('initializeMaps() called');

        // Survey Map
        const mapElement = document.getElementById('survey-map');
        const dataContainer = document.getElementById('map-data-container');

        console.log('Map element:', mapElement, 'Data container:', dataContainer);

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
            console.log('Initializing survey map...');
            initSurveyMap();
        }

        // Satellite Map
        const satelliteElement = document.getElementById('satellite-map');
        if (satelliteElement) {
            console.log('Initializing satellite map...');
            initSatelliteMap();
        }
    }, 100); // 100ms debounce
}

// Dark Mode Toggle System
function initDarkMode() {
    // Check for saved user preference, otherwise default to light mode
    const savedTheme = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

    // Apply the theme
    if (isDark) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }

    updateDarkModeButton(isDark);
}

function updateDarkModeButton(isDark) {
    const toggle = document.getElementById('dark-mode-toggle');
    const label = document.getElementById('dark-mode-label');

    if (toggle && label) {
        // Update icon
        const icon = toggle.querySelector('[data-flux-icon]');
        if (icon) {
            icon.setAttribute('data-flux-icon', isDark ? 'moon' : 'sun');
        }

        // Update label
        label.textContent = isDark ? 'Dark Mode' : 'Light Mode';
    }
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    updateDarkModeButton(isDark);
}

// Setup dark mode toggle listener
function setupDarkModeToggle() {
    const toggle = document.getElementById('dark-mode-toggle');
    if (toggle) {
        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            toggleDarkMode();
        });
    }
}

// Initialize dark mode on page load
initDarkMode();

// Initialize maps when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeMaps();
    setupDarkModeToggle();
});

// Initialize maps after Livewire navigation
document.addEventListener('livewire:navigated', () => {
    console.log('Livewire navigated - reinitializing maps...');
    // Reset flag to allow reinitialization
    window.mapInitialized = false;
    initializeMaps();
    setupDarkModeToggle();
});
