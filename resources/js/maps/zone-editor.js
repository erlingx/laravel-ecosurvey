import L from 'leaflet';
import 'leaflet-draw';
import 'leaflet-draw/dist/leaflet.draw.css';

/**
 * Initialize the zone editor map with drawing tools
 */
export function initZoneEditorMap() {
    const mapElement = document.getElementById('zone-editor-map');

    if (!mapElement) {
        console.log('Zone editor map element not found');
        return;
    }

    // Prevent duplicate initialization
    if (window.zoneEditorMap) {
        console.log('Zone editor map already initialized');
        updateZoneEditorMap();
        return;
    }

    // Find the Livewire component instance for this page
    const componentRoot = mapElement.closest('[wire\\:id]');
    const componentId = componentRoot?.getAttribute('wire:id');

    const getComponent = () => {
        if (!componentId || !window.Livewire) {
            return null;
        }

        return window.Livewire.find(componentId);
    };

    // Initialize map centered on Copenhagen
    const map = L.map('zone-editor-map').setView([55.6761, 12.5683], 13);

    // Add OpenStreetMap base layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);

    // Create feature groups for zones and data points
    const zoneLayerGroup = L.featureGroup().addTo(map);
    const dataPointsLayerGroup = L.featureGroup().addTo(map);

    // Store globally for updates
    window.zoneEditorMap = map;
    window.zoneLayerGroup = zoneLayerGroup;
    window.dataPointsLayerGroup = dataPointsLayerGroup;

    // Initialize drawing controls
    const drawControl = new L.Control.Draw({
        position: 'topright',
        draw: {
            polygon: {
                allowIntersection: true, // Allow self-intersecting polygons to prevent auto-complete
                showArea: false, // DISABLED: Causes strict mode bug "assignment to undeclared variable type"
                drawError: {
                    color: '#e74c3c',
                    message: '<strong>Error:</strong> Shape edges cannot cross!'
                },
                shapeOptions: {
                    color: '#3b82f6',
                    fillOpacity: 0.2
                },
                icon: new L.DivIcon({
                    iconSize: new L.Point(8, 8),
                    className: 'leaflet-div-icon leaflet-editing-icon'
                }),
                touchIcon: new L.DivIcon({
                    iconSize: new L.Point(20, 20),
                    className: 'leaflet-div-icon leaflet-editing-icon leaflet-touch-icon'
                }),
                guidelineDistance: 20,
                maxGuideLineLength: 4000,
                showLength: true,
                metric: true,
                feet: false,
                nautic: false,
                repeatMode: false // Prevent repeat mode
            },
            polyline: false,
            rectangle: false,
            circle: false,
            marker: false,
            circlemarker: false
        },
        edit: {
            featureGroup: zoneLayerGroup,
            remove: false // We handle deletion via UI
        }
    });
    map.addControl(drawControl);


    // Handle polygon creation
    map.on(L.Draw.Event.CREATED, function (event) {
        const layer = event.layer;
        const geoJSON = layer.toGeoJSON();

        // Show the zone creation modal
        const modal = document.getElementById('zone-creation-modal');
        const nameInput = document.getElementById('zone-name-input');
        const descriptionInput = document.getElementById('zone-description-input');
        const saveBtn = document.getElementById('save-zone-btn');
        const cancelBtn = document.getElementById('cancel-zone-btn');

        if (!modal) {
            console.error('Zone creation modal not found');
            return;
        }

        // Reset and show modal
        nameInput.value = '';
        descriptionInput.value = '';
        modal.classList.remove('hidden');
        nameInput.focus();

        // Handle save
        const handleSave = () => {
            const name = nameInput.value.trim();

            if (!name) {
                nameInput.classList.add('border-red-500');
                return;
            }

            const description = descriptionInput.value.trim();

            const component = getComponent();
            if (!component) {
                console.error('Zone manager Livewire component not found');
                modal.classList.add('hidden');
                return;
            }

            // Hide modal immediately
            modal.classList.add('hidden');

            // Call Livewire method
            component.call('saveZone', geoJSON, name, description || null)
                .then(() => {
                    console.log('Zone saved successfully');
                    updateZoneEditorMap();
                })
                .catch((error) => {
                    console.error('Error saving zone:', error);
                    alert('Error saving zone. Please try again.');
                });

            // Cleanup
            saveBtn.removeEventListener('click', handleSave);
            cancelBtn.removeEventListener('click', handleCancel);
        };

        // Handle cancel
        const handleCancel = () => {
            modal.classList.add('hidden');
            saveBtn.removeEventListener('click', handleSave);
            cancelBtn.removeEventListener('click', handleCancel);
            nameInput.removeEventListener('keydown', handleEnter);
            document.removeEventListener('keydown', handleEscape);
        };

        // Handle Enter key in name input
        const handleEnter = (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                handleSave();
            } else {
                nameInput.classList.remove('border-red-500');
            }
        };

        // Handle ESC key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                handleCancel();
            }
        };

        saveBtn.addEventListener('click', handleSave);
        cancelBtn.addEventListener('click', handleCancel);
        nameInput.addEventListener('keydown', handleEnter);
        document.addEventListener('keydown', handleEscape);

        // Close modal when clicking outside
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                handleCancel();
            }
        });
    });

    // Load initial zones and data points
    updateZoneEditorMap();

    console.log('Zone editor map initialized');
}

/**
 * Update zones and data points on the map
 */
export function updateZoneEditorMap() {
    if (!window.zoneEditorMap || !window.zoneLayerGroup || !window.dataPointsLayerGroup) {
        console.warn('Zone editor map not initialized yet');
        return;
    }

    const dataContainer = document.getElementById('zone-data-container');

    if (!dataContainer) {
        console.warn('Zone data container not found');
        return;
    }

    // Clear existing layers
    window.zoneLayerGroup.clearLayers();
    window.dataPointsLayerGroup.clearLayers();

    // Load zones
    const zonesData = dataContainer.getAttribute('data-zones');

    if (zonesData && zonesData.trim() !== '' && zonesData !== 'null' && zonesData !== '""') {
        try {
            const zones = JSON.parse(zonesData);

            if (Array.isArray(zones) && zones.length > 0) {
                zones.forEach(zone => {
                    if (zone.geojson && zone.geojson.geometry) {
                        const layer = L.geoJSON(zone.geojson, {
                            style: {
                                color: '#3b82f6',
                                weight: 3,
                                opacity: 0.8,
                                fillColor: '#3b82f6',
                                fillOpacity: 0.2,
                                dashArray: '5, 5'
                            }
                        });

                        layer.bindPopup(`
                            <div class="p-2">
                                <strong class="text-sm font-semibold">${zone.name}</strong>
                                ${zone.description ? `<div class="text-xs text-gray-600 mt-1">${zone.description}</div>` : ''}
                                <div class="text-xs mt-1"><strong>Area:</strong> ${zone.area_km2.toFixed(2)} km²</div>
                            </div>
                        `);

                        window.zoneLayerGroup.addLayer(layer);
                    }
                });

                console.log(`Loaded ${zones.length} survey zone(s)`);
            }
        } catch (e) {
            console.error('Error parsing zones:', e);
        }
    }

    // Load data points
    const dataPointsData = dataContainer.getAttribute('data-datapoints');

    if (dataPointsData && dataPointsData.trim() !== '' && dataPointsData !== 'null' && dataPointsData !== '""') {
        try {
            const dataPoints = JSON.parse(dataPointsData);

            if (dataPoints && dataPoints.features && Array.isArray(dataPoints.features) && dataPoints.features.length > 0) {
                dataPoints.features.forEach(feature => {
                    const props = feature.properties;
                    const latlng = L.latLng(feature.geometry.coordinates[1], feature.geometry.coordinates[0]);

                    const marker = L.circleMarker(latlng, {
                        radius: 5,
                        fillColor: '#10b981',
                        color: '#059669',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.6
                    });

                    marker.bindPopup(`
                        <div class="p-2">
                            <strong class="text-sm">${props.metric}</strong>
                            <div class="text-xs mt-1">Value: ${props.value} ${props.unit}</div>
                            <div class="text-xs text-gray-600">${props.collected_at}</div>
                        </div>
                    `);

                    window.dataPointsLayerGroup.addLayer(marker);
                });

                console.log(`Loaded ${dataPoints.features.length} data point(s)`);
            }
        } catch (e) {
            console.error('Error parsing data points:', e);
        }
    }

    // Fit map to show all zones and data points
    const allLayers = [];
    window.zoneLayerGroup.eachLayer(layer => allLayers.push(layer));
    window.dataPointsLayerGroup.eachLayer(layer => allLayers.push(layer));

    if (allLayers.length > 0) {
        const group = L.featureGroup(allLayers);
        window.zoneEditorMap.fitBounds(group.getBounds(), { padding: [50, 50] });
    }
}

// Make functions available globally
window.initZoneEditorMap = initZoneEditorMap;
window.updateZoneEditorMap = updateZoneEditorMap;

