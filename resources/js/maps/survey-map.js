import L from 'leaflet';
import 'leaflet.markercluster';

/**
 * Survey Map Functions
 * Handles the interactive survey map with clustered data points
 */

export function initSurveyMap() {
    console.log('Starting initSurveyMap()...');

    try {
        // Check if Leaflet is available
        if (typeof L === 'undefined') {
            console.error('Leaflet (L) is not defined!');
            return;
        }

        // Check if map element exists
        const mapElement = document.getElementById('survey-map');
        if (!mapElement) {
            console.error('Map element #survey-map not found!');
            return;
        }

        console.log('Creating Leaflet map...');

        // Initialize map
        const map = L.map('survey-map').setView([55.6761, 12.5683], 10);

        console.log('Map created:', map);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        console.log('Tiles added');

        // Initialize marker cluster group
        const clusterGroup = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
        });

        console.log('Cluster group created');

        // Add markers
        if (window.mapData && window.mapData.features) {
            console.log('Adding', window.mapData.features.length, 'markers');

            window.mapData.features.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = feature.properties;

                const marker = L.marker([coords[1], coords[0]])
                    .bindPopup(createPopupContent(props));

                clusterGroup.addLayer(marker);
            });

            map.addLayer(clusterGroup);

            console.log('Markers added to map');

            // Fit bounds if we have data
            if (window.mapBounds && window.mapBounds.southwest && window.mapBounds.northeast) {
                map.fitBounds([
                    window.mapBounds.southwest,
                    window.mapBounds.northeast
                ], { padding: [50, 50] });
                console.log('Map bounds fitted');
            }
        } else {
            console.log('No map data or features');
        }

        // Store map reference globally for controls
        window.surveyMap = map;
        window.surveyClusterGroup = clusterGroup;

        console.log('Map initialization complete!');

        // Force map to refresh its size
        setTimeout(() => {
            map.invalidateSize();
            console.log('Map size invalidated (refreshed)');
        }, 100);

    } catch (error) {
        console.error('Error in initSurveyMap:', error);
    }
}

export function createPopupContent(props) {
    return `
        <div class="p-2 min-w-50">
            <h3 class="font-bold text-lg mb-2">${props.metric}</h3>
            <div class="space-y-1 text-sm">
                <p><strong>Value:</strong> ${props.value} ${props.unit}</p>
                <p><strong>Campaign:</strong> ${props.campaign}</p>
                <p><strong>Submitted by:</strong> ${props.user}</p>
                <p><strong>Date:</strong> ${props.collected_at}</p>
                ${props.accuracy ? `<p><strong>Accuracy:</strong> ±${Math.round(props.accuracy)}m</p>` : ''}
                ${props.notes ? `<p><strong>Notes:</strong> ${props.notes}</p>` : ''}
            </div>
        </div>
    `;
}

export function resetMapView() {
    if (window.surveyMap && window.mapBounds && window.mapBounds.southwest && window.mapBounds.northeast) {
        window.surveyMap.fitBounds([
            window.mapBounds.southwest,
            window.mapBounds.northeast
        ], { padding: [50, 50] });
    } else if (window.surveyMap) {
        window.surveyMap.setView([55.6761, 12.5683], 10);
    }
}

export function toggleClustering() {
    alert('Clustering toggle coming soon!');
}

/**
 * Update map markers when filters change
 */
export function updateMapMarkers() {
    console.log('Updating map markers', window.mapData);

    // Check if map still exists, if not reinitialize
    if (!window.surveyMap || !document.getElementById('survey-map')._leaflet_id) {
        console.log('Map lost after Livewire update, reinitializing...');
        window.mapInitialized = false;
        initSurveyMap();
        return;
    }

    if (window.surveyMap && window.surveyClusterGroup) {
        // ALWAYS remove cluster group first to force visual update
        console.log('Checking if cluster group is on map:', window.surveyMap.hasLayer(window.surveyClusterGroup));
        if (window.surveyMap.hasLayer(window.surveyClusterGroup)) {
            window.surveyMap.removeLayer(window.surveyClusterGroup);
            console.log('Removed cluster group from map');
        }

        // Clear all layers from the cluster group
        console.log('Clearing cluster group...');
        window.surveyClusterGroup.clearLayers();
        console.log('Cluster group cleared');

        // Add new markers to the cluster group
        if (window.mapData && window.mapData.features && window.mapData.features.length > 0) {
            console.log('Adding', window.mapData.features.length, 'markers after filter');

            window.mapData.features.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = feature.properties;

                const marker = L.marker([coords[1], coords[0]])
                    .bindPopup(createPopupContent(props));

                window.surveyClusterGroup.addLayer(marker);
            });

            console.log('Added', window.mapData.features.length, 'markers to cluster group');

            // Re-add cluster group to the map (it now has new markers)
            window.surveyMap.addLayer(window.surveyClusterGroup);
            console.log('Re-added cluster group to map with', window.mapData.features.length, 'markers');

            // Fit bounds if we have data
            if (window.mapBounds && window.mapBounds.southwest && window.mapBounds.northeast) {
                window.surveyMap.fitBounds([
                    window.mapBounds.southwest,
                    window.mapBounds.northeast
                ], { padding: [50, 50] });
                console.log('Map bounds refitted after filter');
            }
        } else {
            console.log('No filtered data - cluster group will NOT be re-added to map');
            // No data - cluster group was removed above and won't be re-added
            // Reset view
            window.surveyMap.setView([55.6761, 12.5683], 10);
        }

        // Update badge count
        const badge = document.getElementById('map-point-count');
        if (badge) {
            badge.textContent = (window.mapData.features?.length || 0) + ' points';
        }

        // Force map refresh
        window.surveyMap.invalidateSize();
    } else {
        console.error('Map or cluster group not available for update');
    }
}

/**
 * Set up Livewire event listener for filter changes
 */
export function setupSurveyMapListeners() {
    document.addEventListener('livewire:initialized', () => {
        console.log('🎉 Livewire initialized for survey map!');

        Livewire.on('map-filter-changed', (event) => {
            console.log('🎯 Livewire event received: map-filter-changed', event);

            // Extract data (Livewire v3 wraps in array)
            const data = Array.isArray(event) ? event[0] : event;

            console.log('Extracted data:', data);
            console.log('Features count:', data.dataPoints?.features?.length);

            // Update global data
            window.mapData = data.dataPoints;
            window.mapBounds = data.boundingBox;

            console.log('🗺️ Calling updateMapMarkers() from Livewire event...');
            updateMapMarkers();
        });

        console.log('✅ Livewire event listener registered for map-filter-changed');
    });
}

