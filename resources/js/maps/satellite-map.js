import L from 'leaflet';

/**
 * Satellite Map Functions
 * Handles the satellite imagery viewer with Copernicus Data Space / Sentinel-2 overlays
 */

/**
 * Calculate temporal proximity color based on days difference between field data collection and satellite observation
 *
 * This function provides visual feedback about data quality correlation by color-coding markers.
 * The closer the field measurement date is to the satellite observation date, the higher the
 * confidence in correlating the two datasets (green = best, red = worst).
 *
 * **Color Scale:**
 * - Green (Excellent): 0-3 days difference
 * - Yellow (Good): 4-7 days difference
 * - Orange (Acceptable): 8-14 days difference
 * - Red (Poor): 15+ days difference
 *
 * **Scientific Rationale:**
 * Environmental conditions (vegetation health, soil moisture, temperature) change over time.
 * A field measurement taken on Day X is most reliable for validating satellite data from Day X±3.
 * Beyond 14 days, seasonal changes or weather events may reduce correlation validity.
 *
 * **Technical Notes:**
 * - Date calculation uses JavaScript Date objects (assumes UTC)
 * - Days difference is calculated as absolute value (direction doesn't matter)
 * - Thresholds (3, 7, 14 days) are based on Sentinel-2 revisit interval (5 days) and
 *   environmental change rates in temperate climates
 *
 * @param {string} dataPointDate - ISO 8601 date when field data was collected (YYYY-MM-DD)
 * @param {string} satelliteDate - ISO 8601 date of satellite imagery (YYYY-MM-DD)
 * @returns {object} Color configuration object with properties:
 *   - {string} fill - Hex color for marker fill (#10b981 for green, etc.)
 *   - {string} border - Hex color for marker border (darker shade of fill)
 *   - {string} label - Human-readable quality label ('Excellent', 'Good', 'Acceptable', 'Poor')
 *   - {number} days - Absolute days difference between dates
 *
 * @example
 * // Field data from Aug 12, satellite from Aug 15 (3 days difference)
 * const color = getTemporalProximityColor('2025-08-12', '2025-08-15');
 * // Returns: { fill: '#10b981', border: '#059669', label: 'Excellent', days: 3 }
 *
 * @example
 * // Field data from Aug 1, satellite from Aug 15 (14 days difference)
 * const color = getTemporalProximityColor('2025-08-01', '2025-08-15');
 * // Returns: { fill: '#fb923c', border: '#f97316', label: 'Acceptable', days: 14 }
 */
function getTemporalProximityColor(dataPointDate, satelliteDate) {
    // Calculate difference in days
    const dpDate = new Date(dataPointDate);
    const satDate = new Date(satelliteDate);
    const diffMs = Math.abs(satDate - dpDate);
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    // Color-code based on temporal proximity
    if (diffDays <= 3) {
        // Excellent alignment (0-3 days)
        return {
            fill: '#10b981', // Green
            border: '#059669',
            label: 'Excellent',
            days: diffDays
        };
    } else if (diffDays <= 7) {
        // Good alignment (4-7 days)
        return {
            fill: '#fbbf24', // Yellow
            border: '#f59e0b',
            label: 'Good',
            days: diffDays
        };
    } else if (diffDays <= 14) {
        // Acceptable (8-14 days)
        return {
            fill: '#fb923c', // Orange
            border: '#f97316',
            label: 'Acceptable',
            days: diffDays
        };
    } else {
        // Poor alignment (15+ days)
        return {
            fill: '#ef4444', // Red
            border: '#dc2626',
            label: 'Poor',
            days: diffDays
        };
    }
}

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
        window.dataPointsLayer = null;
        window.dataPointsClusterGroup = null;

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
    const dataPointsData = dataContainer.getAttribute('data-datapoints');
    const revision = dataContainer.getAttribute('data-revision');

    console.log('📊 DOM Attributes:', {
        'revision': revision,
        'data-lat': lat,
        'data-lon': lon,
        'data-overlay-type': overlayType,
        'has-imagery': !!imageryData,
        'has-analysis': !!analysisData,
        'has-datapoints': !!dataPointsData,
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

    // Handle data points overlay
    // Remove existing data points layer/cluster
    if (window.dataPointsClusterGroup) {
        window.satelliteMap.removeLayer(window.dataPointsClusterGroup);
        window.dataPointsClusterGroup = null;
        console.log('♻️ Removed old data points cluster group');
    }
    if (window.dataPointsLayer) {
        window.satelliteMap.removeLayer(window.dataPointsLayer);
        window.dataPointsLayer = null;
        console.log('♻️ Removed old data points layer');
    }

    // Add data points with clustering if available
    if (dataPointsData && dataPointsData !== 'null' && dataPointsData !== 'false') {
        try {
            const dataPoints = JSON.parse(dataPointsData);

            if (dataPoints && dataPoints.features && dataPoints.features.length > 0) {
                console.log(`📍 Adding ${dataPoints.features.length} data points to map with clustering`);

                // Get current satellite date for temporal proximity calculation
                const satelliteDate = dataContainer.getAttribute('data-date') || new Date().toISOString().split('T')[0];

                // Create marker cluster group
                window.dataPointsClusterGroup = L.markerClusterGroup({
                    chunkedLoading: true,
                    maxClusterRadius: 50,
                    spiderfyOnMaxZoom: true,
                    showCoverageOnHover: false,
                    zoomToBoundsOnClick: false, // Disable auto-zoom on cluster click
                    animate: true,
                    animateAddingMarkers: false, // Disable animation when adding markers
                    disableClusteringAtZoom: 16, // Show individual markers at zoom 16+
                    iconCreateFunction: function(cluster) {
                        const count = cluster.getChildCount();
                        return L.divIcon({
                            html: `<div style="background: rgba(59, 130, 246, 0.8); border: 2px solid white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                                <span style="color: white; font-weight: 600; font-size: 14px;">${count}</span>
                            </div>`,
                            className: 'satellite-marker-cluster',
                            iconSize: L.point(40, 40)
                        });
                    }
                });

                // Add custom cluster click handler for controlled zooming
                window.dataPointsClusterGroup.on('clusterclick', function(e) {
                    const cluster = e.layer;
                    const childCount = cluster.getChildCount();

                    // If small cluster (≤5 markers), spiderfy instead of zoom
                    if (childCount <= 5) {
                        cluster.spiderfy();
                    } else {
                        // For larger clusters, zoom in moderately (not to max zoom)
                        const bounds = cluster.getBounds();
                        window.satelliteMap.fitBounds(bounds, {
                            padding: [50, 50],
                            maxZoom: 15 // Limit zoom to level 15
                        });
                    }
                });

                // Add markers to cluster group
                dataPoints.features.forEach(function(feature) {
                    const props = feature.properties;
                    const latlng = L.latLng(feature.geometry.coordinates[1], feature.geometry.coordinates[0]);
                    const collectionDate = props.collected_at.split(' ')[0]; // Extract date part

                    // Calculate temporal proximity color
                    const proximity = getTemporalProximityColor(collectionDate, satelliteDate);

                    const marker = L.circleMarker(latlng, {
                        radius: 6,
                        fillColor: proximity.fill,
                        color: proximity.border,
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    });

                    // Format date for display
                    const dateObj = new Date(collectionDate);
                    const dateStr = dateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                    const popupContent = `
                        <div class="p-2">
                            <strong class="text-sm font-semibold">${props.metric}</strong>
                            <div class="text-sm mt-1">
                                <strong>Value:</strong> ${props.value} ${props.unit}
                            </div>
                            <div class="text-xs text-gray-600 mt-1">
                                ${props.collected_at}<br>
                                Accuracy: ±${props.accuracy}m
                            </div>
                            <div class="text-xs mt-2 p-1.5 rounded" style="background-color: ${proximity.fill}20; border-left: 3px solid ${proximity.fill};">
                                <strong>Temporal Alignment:</strong> ${proximity.label}<br>
                                <span class="text-gray-600">${proximity.days} day(s) from satellite image</span>
                            </div>
                            <button
                                onclick="event.stopPropagation(); window.jumpToDataPoint(${props.latitude}, ${props.longitude}, '${collectionDate}'); return false;"
                                class="mt-2 w-full px-2 py-1.5 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded font-medium transition-colors"
                                title="Jump to satellite imagery from ${dateStr} (when this measurement was taken)"
                            >
                                📅 View satellite on ${dateStr}
                            </button>
                            <div class="text-xs text-gray-500 mt-1 text-center">
                                Compare field data with satellite conditions
                            </div>
                        </div>
                    `;
                    marker.bindPopup(popupContent);

                    // Add click event for jump-to-analyze - prevent cluster interference
                    marker.on('click', function(e) {
                        // Stop event from bubbling to cluster
                        L.DomEvent.stopPropagation(e);

                        console.log('📍 Data point clicked:', {
                            lat: props.latitude,
                            lon: props.longitude,
                            date: collectionDate,
                            proximity: proximity
                        });
                    });

                    window.dataPointsClusterGroup.addLayer(marker);
                });

                window.satelliteMap.addLayer(window.dataPointsClusterGroup);
                console.log('✅ Data points cluster group added with temporal proximity colors');
            } else {
                console.log('ℹ️ No data points features to display');
            }
        } catch (e) {
            console.error('❌ Error parsing data points:', e);
        }
    } else {
        console.log('ℹ️ No data points data - overlay removed');
    }

    // Handle survey zones overlay
    // Remove existing survey zones layer
    if (window.surveyZonesLayer) {
        window.satelliteMap.removeLayer(window.surveyZonesLayer);
        window.surveyZonesLayer = null;
        console.log('♻️ Removed old survey zones layer');
    }

    // Add survey zones if available
    const surveyZonesData = dataContainer.getAttribute('data-surveyzones');
    if (surveyZonesData && surveyZonesData !== 'null' && surveyZonesData !== 'false') {
        try {
            const surveyZones = JSON.parse(surveyZonesData);

            if (surveyZones && surveyZones.features && surveyZones.features.length > 0) {
                console.log(`🏞️ Adding ${surveyZones.features.length} survey zone(s) to map`);

                window.surveyZonesLayer = L.geoJSON(surveyZones, {
                    style: function(feature) {
                        return {
                            color: '#3b82f6',        // Blue border
                            weight: 3,
                            opacity: 0.8,
                            fillColor: '#3b82f6',    // Blue fill
                            fillOpacity: 0.1,
                            dashArray: '5, 5'        // Dashed line
                        };
                    },
                    onEachFeature: function(feature, layer) {
                        if (feature.properties) {
                            const props = feature.properties;
                            const popupContent = `
                                <div class="p-2">
                                    <strong class="text-sm font-semibold">📍 ${props.name}</strong>
                                    ${props.description ? `<div class="text-xs text-gray-600 mt-1">${props.description}</div>` : ''}
                                    ${props.area_km2 ? `<div class="text-xs mt-1"><strong>Area:</strong> ${props.area_km2} km²</div>` : ''}
                                </div>
                            `;
                            layer.bindPopup(popupContent);
                        }
                    }
                }).addTo(window.satelliteMap);

                console.log('✅ Survey zones added to map');
            } else {
                console.log('ℹ️ No survey zones features to display');
            }
        } catch (e) {
            console.error('❌ Error parsing survey zones:', e);
        }
    } else {
        console.log('ℹ️ No survey zones data');
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
 * Jump to a specific data point location and date
 * Called when user clicks "View satellite on [DATE]" in data point popup
 * Always syncs date for temporal correlation analysis (scientific best practice)
 */
window.jumpToDataPoint = function(latitude, longitude, date) {
    console.log('🎯 Jumping to data point for temporal correlation:', { latitude, longitude, date });

    if (!window.satelliteMap) {
        console.error('Satellite map not initialized');
        return;
    }

    // Close any open popups to prevent interference
    window.satelliteMap.closePopup();

    // Disable cluster animations temporarily
    if (window.dataPointsClusterGroup) {
        window.dataPointsClusterGroup.options.animate = false;
    }

    // Center map on the clicked location with smooth animation
    window.satelliteMap.flyTo([latitude, longitude], 15, {
        animate: true,
        duration: 0.8,
        easeLinearity: 0.25
    });

    // Re-enable cluster animations after jump completes
    setTimeout(() => {
        if (window.dataPointsClusterGroup) {
            window.dataPointsClusterGroup.options.animate = true;
        }
    }, 1000);

    // Dispatch event to update satellite viewer (always syncs date)
    window.dispatchEvent(new CustomEvent('jump-to-datapoint', {
        detail: {
            latitude: latitude,
            longitude: longitude,
            date: date
        }
    }));

    console.log('✅ Jump event dispatched for temporal correlation analysis');
};

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

