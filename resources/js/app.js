import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

import L from 'leaflet';
import 'leaflet.markercluster';

// Fix Leaflet's default icon path issue with Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
    iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
    shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
});

// Make Leaflet globally available
window.L = L;

// Survey Map Functions
window.initSurveyMap = function() {
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
                    .bindPopup(window.createPopupContent(props));

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
};

// Make createPopupContent globally available
window.createPopupContent = function(props) {
    let content = `
        <div class="p-2 min-w-[200px]">
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
    return content;
};

window.resetMapView = function() {
    if (window.surveyMap && window.mapBounds && window.mapBounds.southwest && window.mapBounds.northeast) {
        window.surveyMap.fitBounds([
            window.mapBounds.southwest,
            window.mapBounds.northeast
        ], { padding: [50, 50] });
    } else if (window.surveyMap) {
        window.surveyMap.setView([55.6761, 12.5683], 10);
    }
};

window.toggleClustering = function() {
    alert('Clustering toggle coming soon!');
};

// Function to update map markers when filters change
window.updateMapMarkers = function() {
    console.log('Updating map markers', window.mapData);

    // Check if map still exists, if not reinitialize
    if (!window.surveyMap || !document.getElementById('survey-map')._leaflet_id) {
        console.log('Map lost after Livewire update, reinitializing...');
        window.mapInitialized = false;
        window.initSurveyMap();
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
                    .bindPopup(window.createPopupContent(props));

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
};

// Set up Livewire event listener for filter changes (register early, before DOMContentLoaded)
document.addEventListener('livewire:initialized', () => {
    console.log('🎉 Livewire initialized!');

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
        window.updateMapMarkers();
    });

    console.log('✅ Livewire event listener registered for map-filter-changed');
});

// Satellite Map Functions
window.initSatelliteMap = function() {
    console.log('Starting initSatelliteMap()...');

    try {
        if (typeof L === 'undefined') {
            console.error('Leaflet (L) is not defined!');
            return;
        }

        const mapElement = document.getElementById('satellite-map');
        if (!mapElement) {
            console.error('Map element #satellite-map not found!');
            return;
        }

        console.log('Creating satellite map...');

        // Initialize map
        const map = L.map('satellite-map').setView([55.6761, 12.5683], 13);

        // Add satellite tile layer (ESRI World Imagery)
        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
            maxZoom: 19,
        }).addTo(map);

        console.log('Satellite tiles added');

        // Store map reference globally
        window.satelliteMap = map;
        window.satelliteImageLayer = null;
        window.satelliteMarker = null;

        // Load initial data (marker) from DOM
        const dataContainer = document.getElementById('satellite-data-container');
        if (dataContainer) {
            const latAttr = dataContainer.getAttribute('data-lat');
            const lonAttr = dataContainer.getAttribute('data-lon');
            const lat = latAttr !== null ? parseFloat(latAttr) : NaN;
            const lon = lonAttr !== null ? parseFloat(lonAttr) : NaN;

            if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
                map.setView([lat, lon], 13);

                window.satelliteMarker = L.marker([lat, lon])
                    .bindPopup(`<strong>Selected Location</strong><br>${lat.toFixed(6)}°N, ${lon.toFixed(6)}°E`)
                    .addTo(map);
            }
        }

        console.log('Satellite map initialization complete!');

        // IMPORTANT: Always do a full sync after init (marker + overlay) once Livewire has hydrated.
        setTimeout(() => {
            if (window.updateSatelliteImagery) {
                console.log('📡 Loading initial satellite data...');
                window.updateSatelliteImagery();
            }
        }, 600);

        setTimeout(() => {
            map.invalidateSize();
            console.log('Satellite map size invalidated');
        }, 150);

    } catch (error) {
        console.error('Error in initSatelliteMap:', error);
    }
};

window.updateSatelliteImagery = function() {
    console.log('🛰️ Updating satellite imagery...');

    // Defensive check: if map was destroyed, reinitialize it
    if (!window.satelliteMap || !window.satelliteMap.getContainer()) {
        console.warn('⚠️ Satellite map not found, reinitializing...');
        if (window.initSatelliteMap) {
            window.initSatelliteMap();
        } else {
            console.error('Cannot reinitialize - initSatelliteMap function not found');
            return;
        }
    }

    if (!window.satelliteMap) {
        console.error('Satellite map still not initialized');
        return;
    }

    const dataContainer = document.getElementById('satellite-data-container');
    if (!dataContainer) {
        console.error('Satellite data container not found');
        return;
    }

    const latAttr = dataContainer.getAttribute('data-lat');
    const lonAttr = dataContainer.getAttribute('data-lon');
    const lat = latAttr !== null ? parseFloat(latAttr) : NaN;
    const lon = lonAttr !== null ? parseFloat(lonAttr) : NaN;
    const overlayType = dataContainer.getAttribute('data-overlay-type');
    const imageryData = dataContainer.getAttribute('data-imagery');
    const analysisData = dataContainer.getAttribute('data-analysis');
    const revision = dataContainer.getAttribute('data-revision');

    console.log('📊 DOM Attributes:', {
        'revision': revision,
        'data-lat': lat,
        'data-lon': lon,
        'data-overlay-type': overlayType,
        'has-imagery': !!imageryData,
        'has-analysis': !!analysisData,
        'wire-key': dataContainer.getAttribute('wire:key')
    });

    // Update map center and marker
    if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
        window.satelliteMap.setView([lat, lon], 13);

        // Update or create marker
        if (window.satelliteMarker) {
            window.satelliteMarker.setLatLng([lat, lon]);
            window.satelliteMarker.setPopupContent(`<strong>Selected Location</strong><br>${lat.toFixed(6)}°N, ${lon.toFixed(6)}°E`);
        } else {
            window.satelliteMarker = L.marker([lat, lon])
                .bindPopup(`<strong>Selected Location</strong><br>${lat.toFixed(6)}°N, ${lon.toFixed(6)}°E`)
                .addTo(window.satelliteMap);
        }
    }

    // ALWAYS remove existing overlay first
    if (window.satelliteImageLayer) {
        window.satelliteMap.removeLayer(window.satelliteImageLayer);
        window.satelliteImageLayer = null;
        console.log('♻️ Removed old overlay layer');
    }

    // Handle imagery overlay
    if (imageryData && imageryData !== 'null' && imageryData !== 'false') {
        try {
            const imagery = JSON.parse(imageryData);

            // CRITICAL: Validate that imagery coordinates match DOM coordinates
            const imageryLat = imagery?.latitude;
            const imageryLon = imagery?.longitude;
            const coordsMatch = (
                imageryLat && imageryLon &&
                Math.abs(imageryLat - lat) < 0.0001 &&
                Math.abs(imageryLon - lon) < 0.0001
            );

            console.log('🖼️ Parsed imagery:', {
                'DOM coords': `${lat}, ${lon}`,
                'Imagery coords': `${imageryLat}, ${imageryLon}`,
                'Match': coordsMatch ? '✓' : '✗ MISMATCH!',
                overlayType: imagery?.overlay_type,
                source: imagery?.source,
                provider: imagery?.provider,
            });

            if (!coordsMatch) {
                console.warn('⚠️ COORDINATE MISMATCH - Imagery is for different location!');
                console.warn('  Expected:', lat, lon);
                console.warn('  Got:', imageryLat, imageryLon);
            }

            if (imagery && imagery.url) {
                // Use DOM coordinates for bounds (they are the current state)
                const dim = 0.025;
                const bounds = [
                    [lat - dim, lon - dim],
                    [lat + dim, lon + dim]
                ];

                // Add imagery as overlay with appropriate opacity
                window.satelliteImageLayer = L.imageOverlay(imagery.url, bounds, {
                    opacity: 0.7,
                    interactive: true
                }).addTo(window.satelliteMap);

                console.log('✅ Overlay added:', {
                    overlayType: overlayType,
                    bounds: bounds,
                    source: imagery.source,
                    opacity: 0.7
                });
            } else {
                console.log('⚠️ Imagery data exists but no URL found');
            }
        } catch (e) {
            console.error('❌ Error parsing imagery data:', e);
        }
    } else {
        console.log('ℹ️ No imagery data - overlay removed');
    }

    // Force map to recalculate its size
    setTimeout(() => {
        if (window.satelliteMap) {
            window.satelliteMap.invalidateSize();
            console.log('✨ Satellite map size refreshed!');
        }
    }, 100);
};

// Set up Livewire event listener for satellite data changes
document.addEventListener('livewire:initialized', () => {
    console.log('✅ Livewire initialized for satellite viewer');

    let updateTimeout = null;

    // Livewire v3: use commit + respond() to run AFTER the DOM has been morphed.
    Livewire.hook('commit', ({ component, respond }) => {
        const expectedNames = ['maps.satellite-viewer', 'livewire.maps.satellite-viewer'];
        if (!expectedNames.includes(component.name)) {
            return;
        }

        respond(() => {
            // Debounce post-morph updates
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }

            updateTimeout = setTimeout(() => {
                if (!window.updateSatelliteImagery) {
                    return;
                }

                // If map isn't initialized yet (navigated to page), initialize now.
                const satelliteElement = document.getElementById('satellite-map');
                if (satelliteElement && (!window.satelliteMap || !window.satelliteMap.getContainer())) {
                    console.log('🗺️ Map not ready after commit, initializing...');
                    window.initSatelliteMap();
                }

                if (window.satelliteMap && window.satelliteMap.getContainer()) {
                    console.log('🔄 Livewire commit->respond: syncing satellite map...');
                    window.updateSatelliteImagery();
                }
            }, 250);
        });
    });
});

// Initialize map when DOM is ready
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
    } else {
        // Keep console noise low on non-survey pages
    }

    // Satellite Map
    const satelliteElement = document.getElementById('satellite-map');
    if (satelliteElement) {
        console.log('Initializing satellite map...');
        initSatelliteMap();
        // NOTE: no satelliteMapInitialized flag needed
    } else {
        // Keep console noise low on non-satellite pages
    }
});

// Re-initialize satellite map after Livewire navigation
document.addEventListener('livewire:navigated', function() {
    console.log('Livewire navigated - checking for satellite map...');

    const satelliteElement = document.getElementById('satellite-map');
    if (satelliteElement && (!window.satelliteMap || !window.satelliteMap.getContainer())) {
        console.log('Re-initializing satellite map after navigation...');
        initSatelliteMap();
    }
});
