import L from 'leaflet';

/**
 * Satellite Map Functions
 * Handles the satellite imagery viewer with Copernicus Data Space / Sentinel-2 overlays
 */

export function initSatelliteMap() {
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
}

export function updateSatelliteImagery() {
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
}

/**
 * Set up Livewire event listener for satellite data changes
 */
export function setupSatelliteMapListeners() {
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
}

/**
 * Re-initialize satellite map after Livewire navigation
 */
export function setupSatelliteNavigation() {
    document.addEventListener('livewire:navigated', function() {
        console.log('Livewire navigated - checking for satellite map...');

        const satelliteElement = document.getElementById('satellite-map');
        if (satelliteElement && (!window.satelliteMap || !window.satelliteMap.getContainer())) {
            console.log('Re-initializing satellite map after navigation...');
            initSatelliteMap();
        }
    });
}

