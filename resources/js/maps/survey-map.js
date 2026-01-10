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
            window.surveyMap.remove();
            window.surveyMap = null;
            window.surveyClusterGroup = null;
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

        // Initialize marker cluster group with custom icon based on quality
        const clusterGroup = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            iconCreateFunction: function(cluster) {
                const markers = cluster.getAllChildMarkers();

                // Count marker types by quality
                let flaggedCount = 0;
                let lowAccuracyCount = 0;
                let approvedCount = 0;
                let normalCount = 0;

                markers.forEach(marker => {
                    const props = marker.feature?.properties || marker.options.properties;
                    if (!props) return;

                    const hasQAFlags = props.qa_flags && props.qa_flags.length > 0;
                    const lowAccuracy = props.accuracy && props.accuracy > 50;
                    const isApproved = props.status === 'approved' && (!props.accuracy || props.accuracy <= 50);

                    if (hasQAFlags) {
                        flaggedCount++;
                    } else if (lowAccuracy) {
                        lowAccuracyCount++;
                    } else if (isApproved) {
                        approvedCount++;
                    } else {
                        normalCount++;
                    }
                });

                // Determine cluster color based on priority: flagged > low accuracy > approved > normal
                let colorClass = 'marker-cluster-blue';
                if (flaggedCount > 0) {
                    colorClass = 'marker-cluster-red';
                } else if (lowAccuracyCount > 0) {
                    colorClass = 'marker-cluster-yellow';
                } else if (approvedCount > 0) {
                    colorClass = 'marker-cluster-green';
                }

                return L.divIcon({
                    html: '<div><span>' + markers.length + '</span></div>',
                    className: 'marker-cluster ' + colorClass,
                    iconSize: L.point(40, 40)
                });
            }
        });

        console.log('Cluster group created');

        // Add markers
        if (window.mapData && window.mapData.features) {
            console.log('Adding', window.mapData.features.length, 'markers');

            window.mapData.features.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = {
                    ...feature.properties,
                    longitude: coords[0],
                    latitude: coords[1]
                };

                const marker = L.circleMarker([coords[1], coords[0]], getMarkerStyle(props))
                    .on('click', () => showCustomPopup(props));

                // Store properties on marker for cluster icon function
                marker.feature = feature;
                marker.options.properties = props;

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
 * - Yellow dashed outline for accuracy > 50m (low confidence)
 * - Red outline for points with QA flags (flagged issues)
 * - Normal black outline for approved/high quality data
 */
export function getMarkerStyle(props) {
    const hasQAFlags = props.qa_flags && props.qa_flags.length > 0;
    const lowAccuracy = props.accuracy && props.accuracy > 50;
    const isApproved = props.status === 'approved';

    if (hasQAFlags) {
        // Red outline for flagged data
        return {
            radius: 8,
            fillColor: '#ef4444',
            color: '#dc2626',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.6,
            dashArray: '5, 5'
        };
    } else if (lowAccuracy) {
        // Yellow dashed outline for low confidence
        return {
            radius: 8,
            fillColor: '#fbbf24',
            color: '#f59e0b',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.5,
            dashArray: '5, 5'
        };
    } else if (isApproved) {
        // Green for approved high quality data
        return {
            radius: 8,
            fillColor: '#10b981',
            color: '#059669',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.7
        };
    } else {
        // Default blue for pending/normal data
        return {
            radius: 8,
            fillColor: '#3b82f6',
            color: '#1d4ed8',
            weight: 2,
            opacity: 1,
            fillOpacity: 0.6
        };
    }
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

            window.mapData.features.forEach(feature => {
                const coords = feature.geometry.coordinates;
                const props = {
                    ...feature.properties,
                    longitude: coords[0],
                    latitude: coords[1]
                };

                const marker = L.circleMarker([coords[1], coords[0]], getMarkerStyle(props))
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

    // Format QA flags
    let qaFlagsHtml = '';
    if (props.qa_flags && props.qa_flags.length > 0) {
        const flagNames = props.qa_flags.map(flag => {
            if (typeof flag === 'string') {
                return flag.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            }
            return flag.type ? flag.type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) : 'Unknown';
        }).join(', ');

        qaFlagsHtml = `<p style="margin: 4px 0; color: #dc2626;"><strong>⚠️ QA Flags (${props.qa_flags.length}):</strong> ${flagNames}</p>`;
    }

    popup.innerHTML = `
        <div style="min-width: 300px; max-width: 400px;">
            <div id="popup-header" style="padding: 8px 12px; background: #f3f4f6; border-radius: 8px 8px 0 0; cursor: move; display: flex; justify-content: space-between; align-items: center; user-select: none; border-bottom: 1px solid #e5e7eb;">
                <div style="font-size: 1.25rem; color: #9ca3af; line-height: 1;">☰</div>
                <h3 style="font-weight: bold; font-size: 1rem; margin: 0; flex: 1; text-align: center; color: #6b7280;">Data Point Details</h3>
                <button onclick="document.getElementById('custom-map-popup').remove()"
                        style="background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; color: #9ca3af; line-height: 1; user-select: none; font-weight: bold;">
                    ×
                </button>
            </div>
            <div style="padding: 16px; cursor: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="font-weight: bold; font-size: 1.125rem; margin: 0; color: #1f2937; cursor: text;">${props.metric}</h4>
                    <a href="/data-points/${props.id}/edit"
                       style="color: #3b82f6; text-decoration: none; font-size: 0.875rem; display: flex; align-items: center; gap: 4px; cursor: pointer;"
                       title="Edit this reading">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Edit
                    </a>
                </div>
                <p style="margin: 0 0 12px 0; font-size: 0.75rem; color: #6b7280; cursor: text;"><strong>ID:</strong> #${props.id}</p>
            <div style="font-size: 0.875rem; line-height: 1.6; cursor: text;">
                ${qaFlagsHtml}
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
