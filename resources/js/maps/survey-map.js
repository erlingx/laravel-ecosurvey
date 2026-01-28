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

        // Clean up existing map instance if it exists
        if (window.surveyMap) {
            console.log('Removing existing map instance...');

            // Stop any ongoing animations
            window.surveyMap.stop();

            // Remove cluster group if it exists
            if (window.surveyClusterGroup && window.surveyMap.hasLayer(window.surveyClusterGroup)) {
                window.surveyMap.removeLayer(window.surveyClusterGroup);
            }

            // Remove the map instance
            window.surveyMap.remove();
            window.surveyMap = null;
            window.surveyClusterGroup = null;
        }

        console.log('Creating Leaflet map...');

        // Check for saved map state in sessionStorage
        let savedState = null;
        try {
            const savedStateJson = sessionStorage.getItem('surveyMapState');
            if (savedStateJson) {
                savedState = JSON.parse(savedStateJson);
                console.log('Restored map state:', savedState);
            }
        } catch (e) {
            console.warn('Could not restore map state:', e);
        }

        // Initialize map with saved state or default
        const defaultCenter = [55.6761, 12.5683];
        const defaultZoom = 10;
        const map = L.map('survey-map').setView(
            savedState ? [savedState.lat, savedState.lng] : defaultCenter,
            savedState ? savedState.zoom : defaultZoom
        );

        console.log('Map created:', map);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        console.log('Tiles added');

        // Initialize marker cluster group with custom icon based on quality
        const clusterGroup = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: false, // Disable default behavior, we'll handle it manually
            maxClusterRadius: 80,
            spiderfyDistanceMultiplier: 1.5,
            iconCreateFunction: function(cluster) {
                const markers = cluster.getAllChildMarkers();
                const total = markers.length;

                // Count marker types by quality
                let flaggedCount = 0;
                let rejectedCount = 0;
                let lowAccuracyCount = 0;
                let approvedCount = 0;
                let normalCount = 0;

                markers.forEach(marker => {
                    const props = marker.feature?.properties || marker.options.properties;
                    if (!props) return;

                    const hasQAFlags = props.qa_flags && props.qa_flags.length > 0;
                    const isRejected = props.status === 'rejected';
                    const lowAccuracy = props.accuracy && props.accuracy > 50;
                    const isApproved = props.status === 'approved' && (!props.accuracy || props.accuracy <= 50);

                    if (hasQAFlags) {
                        flaggedCount++;
                    } else if (isRejected) {
                        rejectedCount++;
                    } else if (lowAccuracy) {
                        lowAccuracyCount++;
                    } else if (isApproved) {
                        approvedCount++;
                    } else {
                        normalCount++;
                    }
                });

                // Generate pie chart SVG
                const size = 40;
                const radius = size / 2;
                let cumulativePercent = 0;

                // Build segments in priority order
                const segments = [];

                if (flaggedCount > 0) {
                    segments.push({ count: flaggedCount, color: '#dc2626', label: 'Flagged' });
                }
                if (rejectedCount > 0) {
                    segments.push({ count: rejectedCount, color: '#374151', label: 'Rejected' });
                }
                if (lowAccuracyCount > 0) {
                    segments.push({ count: lowAccuracyCount, color: '#f59e0b', label: 'Low Accuracy' });
                }
                if (approvedCount > 0) {
                    segments.push({ count: approvedCount, color: '#059669', label: 'Approved' });
                }
                if (normalCount > 0) {
                    segments.push({ count: normalCount, color: '#1d4ed8', label: 'Pending' });
                }

                // Create SVG pie chart
                let pathsHTML = '';
                segments.forEach(segment => {
                    const percent = segment.count / total;
                    const startAngle = cumulativePercent * 360;
                    const endAngle = (cumulativePercent + percent) * 360;

                    // Convert angles to radians
                    const startRad = (startAngle - 90) * Math.PI / 180;
                    const endRad = (endAngle - 90) * Math.PI / 180;

                    // Calculate arc path
                    const x1 = radius + radius * Math.cos(startRad);
                    const y1 = radius + radius * Math.sin(startRad);
                    const x2 = radius + radius * Math.cos(endRad);
                    const y2 = radius + radius * Math.sin(endRad);

                    const largeArc = percent > 0.5 ? 1 : 0;

                    // Create path for this segment
                    pathsHTML += `<path d="M ${radius},${radius} L ${x1},${y1} A ${radius},${radius} 0 ${largeArc} 1 ${x2},${y2} Z" fill="${segment.color}" stroke="white" stroke-width="1"/>`;

                    cumulativePercent += percent;
                });

                // Single color fallback if only one type
                let iconHTML;
                if (segments.length === 1) {
                    iconHTML = `
                        <div style="background: ${segments[0].color}; border: 2px solid white; border-radius: 50%; width: ${size}px; height: ${size}px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                            <span style="color: white; font-weight: 600; font-size: 14px;">${total}</span>
                        </div>
                    `;
                } else {
                    // Pie chart with count in center
                    iconHTML = `
                        <div style="position: relative; width: ${size}px; height: ${size}px;">
                            <svg width="${size}" height="${size}" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                                ${pathsHTML}
                                <circle cx="${radius}" cy="${radius}" r="${radius * 0.5}" fill="white" stroke="white" stroke-width="1"/>
                            </svg>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #1f2937; font-weight: 600; font-size: 12px; pointer-events: none;">
                                ${total}
                            </div>
                        </div>
                    `;
                }

                return L.divIcon({
                    html: iconHTML,
                    className: 'marker-cluster-pie',
                    iconSize: L.point(size, size)
                });
            }
        });

        // Add custom click handler to zoom with proper padding
        clusterGroup.on('clusterclick', function(e) {
            const cluster = e.layer;
            const bounds = cluster.getBounds();
            const childMarkers = cluster.getAllChildMarkers();
            const currentZoom = map.getZoom();

            // If we're at max zoom or very close, spiderfy the cluster instead of zooming
            if (currentZoom >= 16 || currentZoom >= map.getMaxZoom() - 1) {
                cluster.spiderfy();
                return;
            }

            // If cluster has 10 or fewer markers, zoom to a reasonable level instead of max zoom
            if (childMarkers.length <= 10) {
                const center = cluster.getLatLng();
                const targetZoom = Math.min(currentZoom + 3, 16); // Limit to zoom level 16 max

                // If target zoom is at max, spiderfy instead
                if (targetZoom >= 16) {
                    cluster.spiderfy();
                } else {
                    map.setView(center, targetZoom);
                }
            } else {
                // For larger clusters, use fitBounds but limit max zoom
                map.fitBounds(bounds, {
                    padding: [80, 80],
                    maxZoom: 16 // Prevent excessive zoom
                });
            }
        });

        console.log('Cluster group created');

        // Add markers
        if (window.mapData && window.mapData.features) {
            console.log('Adding', window.mapData.features.length, 'markers');

            // Sort features by ID to ensure consistent order in clusters
            const sortedFeatures = [...window.mapData.features].sort((a, b) => {
                return a.properties.id - b.properties.id;
            });

            sortedFeatures.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = {
                    ...feature.properties,
                    longitude: coords[0],
                    latitude: coords[1]
                };

                const marker = L.marker([coords[1], coords[0]], {
                    icon: createMarkerIcon(props)
                })
                    .bindTooltip(`ID: #${props.id}`, {
                        permanent: false,
                        direction: 'top',
                        offset: [0, -15],
                        className: 'marker-tooltip'
                    })
                    .on('click', () => showCustomPopup(props));

                // Store properties on marker for cluster icon function
                marker.feature = feature;
                marker.options.properties = props;

                clusterGroup.addLayer(marker);
            });

            map.addLayer(clusterGroup);

            console.log('Markers added to map');
            console.log('🔍 DEBUG: Cluster group layer count:', clusterGroup.getLayers().length);
            console.log('🔍 DEBUG: Is cluster group on map?', map.hasLayer(clusterGroup));

            // ALWAYS fit bounds to show all markers (ignore saved state for now)
            // This ensures markers are visible on every page load
            if (window.mapBounds && window.mapBounds.southwest && window.mapBounds.northeast) {
                console.log('Fitting bounds to show all markers:', window.mapBounds);
                map.fitBounds([
                    window.mapBounds.southwest,
                    window.mapBounds.northeast
                ], { padding: [80, 80] });
                console.log('✅ Map bounds fitted - all markers should be visible now');
            } else {
                console.warn('⚠️ No bounding box available - using default view');
                // If no bounds, at least zoom out to see more area
                if (savedState) {
                    map.setView([savedState.lat, savedState.lng], Math.max(savedState.zoom, 10));
                } else {
                    map.setView([55.6761, 12.5683], 10);
                }
            }
        } else {
            console.log('No map data or features');
        }

        // Store map reference globally for controls
        window.surveyMap = map;
        window.surveyClusterGroup = clusterGroup;

        // Save map state whenever user zooms or pans
        const saveMapState = () => {
            const center = map.getCenter();
            const zoom = map.getZoom();
            const state = {
                lat: center.lat,
                lng: center.lng,
                zoom: zoom
            };
            sessionStorage.setItem('surveyMapState', JSON.stringify(state));
            console.log('Map state saved:', state);
        };

        // Listen for map movement and zoom
        map.on('moveend', saveMapState);
        map.on('zoomend', saveMapState);

        // Save initial state
        saveMapState();

        console.log('Map initialization complete!');

        // Force map to refresh its size (only if map still exists)
        setTimeout(() => {
            if (window.surveyMap && window.surveyMap._container) {
                window.surveyMap.invalidateSize();
                console.log('Map size invalidated (refreshed)');
            }
        }, 100);

    } catch (error) {
        console.error('Error in initSurveyMap:', error);
    }
}

export function createPopupContent(props) {
    // Format QA flags for display
    let qaFlagsHtml = '';
    if (props.qa_flags && props.qa_flags.length > 0) {
        const flagNames = props.qa_flags.map(flag => {
            // Convert flag names to readable format
            if (typeof flag === 'string') {
                return flag.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            return flag.type ? flag.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown';
        }).join(', ');

        qaFlagsHtml = `<p style="margin: 2px 0; color: #dc2626;"><strong>⚠️ QA Flags (${props.qa_flags.length}):</strong> ${flagNames}</p>`;
    }

    return `
        <div style="min-width: 250px; max-width: 350px; padding: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <h3 style="font-weight: bold; font-size: 1.125rem; margin: 0;">${props.metric}</h3>
                <a href="/data-points/${props.id}/edit"
                   style="color: #3b82f6; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;"
                   title="Edit this reading">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit
                </a>
            </div>
            <div style="font-size: 0.875rem;">
                ${qaFlagsHtml}
                <p style="margin: 2px 0;"><strong>Value:</strong> ${props.value} ${props.unit}</p>
                ${props.accuracy ? `<p style="margin: 2px 0;"><strong>Accuracy:</strong> ±${Math.round(props.accuracy)}m</p>` : ''}
                <p style="margin: 2px 0;"><strong>Location:</strong> ${props.latitude.toFixed(6)}°N, ${props.longitude.toFixed(6)}°E</p>
                <p style="margin: 2px 0;"><strong>Date:</strong> ${props.collected_at}</p>
                <p style="margin: 2px 0;"><strong>Campaign:</strong> ${props.campaign}</p>
                <p style="margin: 2px 0;"><strong>Submitted by:</strong> ${props.user}</p>
                ${props.status ? `<p style="margin: 2px 0;"><strong>Status:</strong> ${props.status}</p>` : ''}
                ${props.notes ? `<p style="margin: 2px 0;"><strong>Notes:</strong> ${props.notes}</p>` : ''}
            </div>
            ${props.photo_path ? `
                <div style="margin-top: 12px;">
                    <img src="${props.photo_path}"
                         alt="Data point photo"
                         style="width: 100%; max-height: 180px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d5db; display: block;"
                         onerror="this.style.display='none'">
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Get marker style based on data quality indicators
 * Returns a DivIcon instead of CircleMarker options for better visibility
 */
export function getMarkerStyle(props) {
    const hasQAFlags = props.qa_flags && props.qa_flags.length > 0;
    const lowAccuracy = props.accuracy && props.accuracy > 50;
    const isApproved = props.status === 'approved';
    const isRejected = props.status === 'rejected';

    let backgroundColor, borderColor, pattern;

    if (hasQAFlags) {
        backgroundColor = '#ef4444';
        borderColor = '#dc2626';
        pattern = 'dashed';
    } else if (isRejected) {
        backgroundColor = '#6b7280';
        borderColor = '#374151';
        pattern = 'dotted';
    } else if (lowAccuracy) {
        backgroundColor = '#fbbf24';
        borderColor = '#f59e0b';
        pattern = 'dashed';
    } else if (isApproved) {
        backgroundColor = '#10b981';
        borderColor = '#059669';
        pattern = 'solid';
    } else {
        backgroundColor = '#3b82f6';
        borderColor = '#1d4ed8';
        pattern = 'solid';
    }

    return {
        backgroundColor,
        borderColor,
        pattern
    };
}

/**
 * Create a visible DivIcon marker
 */
export function createMarkerIcon(props) {
    const style = getMarkerStyle(props);

    const html = `
        <div style="
            width: 24px;
            height: 24px;
            background-color: ${style.backgroundColor};
            border: 3px ${style.pattern} ${style.borderColor};
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.4);
            cursor: pointer;
            transform: translate(-50%, -50%);
        "></div>
    `;

    console.log('✅ Creating marker icon for ID:', props.id, 'Style:', style);

    return L.divIcon({
        html: html,
        className: 'custom-marker-icon',
        iconSize: [24, 24],
        iconAnchor: [12, 12]
    });
}

export function resetMapView() {
    // Clear saved state
    sessionStorage.removeItem('surveyMapState');
    console.log('Cleared saved map state');

    if (window.surveyMap && window.mapBounds && window.mapBounds.southwest && window.mapBounds.northeast) {
        window.surveyMap.fitBounds([
            window.mapBounds.southwest,
            window.mapBounds.northeast
        ], { padding: [80, 80] });
    } else if (window.surveyMap) {
        window.surveyMap.setView([55.6761, 12.5683], 10);
    }
}


/**
 * Update map markers when filters change
 */
export function updateMapMarkers() {
    console.log('Updating map markers', window.mapData);

    // Check if map still exists, if not reinitialize
    const mapElement = document.getElementById('survey-map');
    if (!window.surveyMap || !mapElement || !mapElement._leaflet_id) {
        console.log('Map lost after Livewire update, reinitializing...');
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

            // Sort features by ID to ensure consistent order in clusters
            const sortedFeatures = [...window.mapData.features].sort((a, b) => {
                return a.properties.id - b.properties.id;
            });

            sortedFeatures.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = {
                    ...feature.properties,
                    longitude: coords[0],
                    latitude: coords[1]
                };

                const marker = L.marker([coords[1], coords[0]], {
                    icon: createMarkerIcon(props)
                })
                    .bindTooltip(`ID: #${props.id}`, {
                        permanent: false,
                        direction: 'top',
                        offset: [0, -15],
                        className: 'marker-tooltip'
                    })
                    .on('click', () => showCustomPopup(props));

                // Store properties on marker for cluster icon function
                marker.feature = feature;
                marker.options.properties = props;

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
                ], { padding: [80, 80] });
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

/**
 * Show custom draggable popup modal
 */
export function showCustomPopup(props) {
    // Remove existing popup if any
    const existingPopup = document.getElementById('custom-map-popup');
    if (existingPopup) {
        existingPopup.remove();
    }

    // Create popup container
    const popup = document.createElement('div');
    popup.id = 'custom-map-popup';
    popup.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        z-index: 10000;
        max-width: 90vw;
        max-height: 90vh;
        overflow: auto;
        cursor: move;
    `;

    // Determine marker color and quality issues
    const hasQAFlags = props.qa_flags && props.qa_flags.length > 0;
    const isRejected = props.status === 'rejected';
    const lowAccuracy = props.accuracy && props.accuracy > 50;
    const isApproved = props.status === 'approved' && !hasQAFlags && !lowAccuracy;

    let markerColor = '';
    let markerIcon = '';
    let markerExplanation = '';

    if (hasQAFlags) {
        markerColor = '#dc2626'; // red
        markerIcon = '🔴';
        markerExplanation = 'Quality issues detected';
    } else if (isRejected) {
        markerColor = '#374151'; // gray
        markerIcon = '⚫';
        markerExplanation = 'Rejected';
    } else if (lowAccuracy) {
        markerColor = '#f59e0b'; // yellow
        markerIcon = '🟡';
        markerExplanation = 'Low GPS accuracy (>50m)';
    } else if (isApproved) {
        markerColor = '#059669'; // green
        markerIcon = '🟢';
        markerExplanation = 'Approved & high quality';
    } else {
        markerColor = '#1d4ed8'; // blue
        markerIcon = '🔵';
        markerExplanation = 'Pending review';
    }

    // Format QA flags
    let qaFlagsHtml = '';
    if (hasQAFlags) {
        const flagNames = props.qa_flags.map(flag => {
            if (typeof flag === 'string') {
                return flag.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            return flag.type ? flag.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown';
        }).join(', ');

        qaFlagsHtml = `
            <div style="margin: 8px 0; padding: 8px; background: #fee2e2; border-left: 3px solid #dc2626; border-radius: 4px;">
                <p style="margin: 0; color: #dc2626; font-weight: 600;">⚠️ QA Flags (${props.qa_flags.length})</p>
                <p style="margin: 4px 0 0 0; color: #991b1b; font-size: 0.8125rem;">${flagNames}</p>
            </div>`;
    }

    // Format low accuracy warning
    let accuracyWarningHtml = '';
    if (lowAccuracy && !hasQAFlags) {
        accuracyWarningHtml = `
            <div style="margin: 8px 0; padding: 8px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px;">
                <p style="margin: 0; color: #92400e; font-weight: 600;">⚠️ Low GPS Accuracy</p>
                <p style="margin: 4px 0 0 0; color: #78350f; font-size: 0.8125rem;">Location precision is ±${Math.round(props.accuracy)}m (threshold: 50m)</p>
            </div>`;
    }

    popup.innerHTML = `
        <div style="min-width: 300px; max-width: 400px;">
            <div id="popup-header" style="padding: 8px 12px; background: ${markerColor}; border-radius: 8px 8px 0 0; cursor: move; display: flex; justify-content: space-between; align-items: center; user-select: none; border-bottom: 1px solid rgba(0,0,0,0.1);">
                <div style="font-size: 1.25rem; color: white; line-height: 1;">☰</div>
                <h3 style="font-weight: bold; font-size: 1rem; margin: 0; flex: 1; text-align: center; color: white;">${markerIcon} ${markerExplanation}</h3>
                <button onclick="document.getElementById('custom-map-popup').remove()"
                        style="background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; color: white; line-height: 1; user-select: none; font-weight: bold;">
                    ×
                </button>
            </div>
            <div style="padding: 16px; cursor: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="font-weight: bold; font-size: 1.125rem; margin: 0; color: #1f2937; cursor: text;">${props.metric}</h4>
                    <button
                        onclick="window.dispatchEvent(new CustomEvent('open-edit-modal', { detail: { id: ${props.id} } })); document.getElementById('custom-map-popup').remove();"
                        style="color: #3b82f6; background: none; border: none; cursor: pointer; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 4px;"
                        title="Edit this reading">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit
                    </button>
                </div>
                <p style="margin: 0 0 12px 0; font-size: 0.75rem; color: #6b7280; cursor: text;"><strong>ID:</strong> #${props.id}</p>
                ${qaFlagsHtml}
                ${accuracyWarningHtml}
            <div style="font-size: 0.875rem; line-height: 1.6; cursor: text;">
                <p style="margin: 4px 0;"><strong>Value:</strong> ${props.value} ${props.unit}</p>
                ${props.accuracy ? `<p style="margin: 4px 0;"><strong>Accuracy:</strong> ±${Math.round(props.accuracy)}m</p>` : ''}
                <p style="margin: 4px 0;"><strong>Location:</strong> ${props.latitude.toFixed(6)}°N, ${props.longitude.toFixed(6)}°E</p>
                <p style="margin: 4px 0;"><strong>Date:</strong> ${props.collected_at}</p>
                <p style="margin: 4px 0;"><strong>Campaign:</strong> ${props.campaign}</p>
                <p style="margin: 4px 0;"><strong>Submitted by:</strong> ${props.user}</p>
                ${props.status ? `<p style="margin: 4px 0;"><strong>Status:</strong> ${props.status}</p>` : ''}
                ${props.notes ? `<p style="margin: 4px 0;"><strong>Notes:</strong> ${props.notes}</p>` : ''}
            </div>
            ${props.photo_path ? `
                <div style="margin-top: 12px; cursor: auto;">
                    <img src="${props.photo_path}"
                         alt="Data point photo"
                         style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 4px; border: 1px solid #d1d5db; display: block; cursor: auto;"
                         onerror="this.style.display='none'">
                </div>
            ` : ''}
            </div>
        </div>
    `;

    // Add to page
    document.body.appendChild(popup);

    // Make draggable from header only
    makeDraggable(popup, popup.querySelector('#popup-header'));

    // Close on escape key
    const escapeHandler = (e) => {
        if (e.key === 'Escape') {
            popup.remove();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);

    // Close when clicking outside
    const clickHandler = (e) => {
        if (!popup.contains(e.target)) {
            popup.remove();
            document.removeEventListener('click', clickHandler);
        }
    };
    // Add slight delay to prevent immediate closing from the marker click
    setTimeout(() => {
        document.addEventListener('click', clickHandler);
    }, 100);
}

/**
 * Make element draggable
 */
function makeDraggable(element, handle = null) {
    const dragHandle = handle || element;
    let isDragging = false;
    let offsetX = 0;
    let offsetY = 0;

    dragHandle.style.cursor = 'move';

    dragHandle.addEventListener('mousedown', dragStart);

    function dragStart(e) {
        // Prevent text selection while dragging
        e.preventDefault();

        isDragging = true;

        // Get current position
        const rect = element.getBoundingClientRect();

        // Calculate offset from mouse to element's current position
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;

        // Convert from centered transform to absolute positioning
        element.style.position = 'fixed';
        element.style.left = rect.left + 'px';
        element.style.top = rect.top + 'px';
        element.style.transform = 'none';
        element.style.margin = '0';

        document.addEventListener('mousemove', drag);
        document.addEventListener('mouseup', dragEnd);
    }

    function drag(e) {
        if (!isDragging) return;

        e.preventDefault();

        // Calculate new position
        const newLeft = e.clientX - offsetX;
        const newTop = e.clientY - offsetY;

        element.style.left = newLeft + 'px';
        element.style.top = newTop + 'px';
    }

    function dragEnd() {
        isDragging = false;
        document.removeEventListener('mousemove', drag);
        document.removeEventListener('mouseup', dragEnd);
    }
}
