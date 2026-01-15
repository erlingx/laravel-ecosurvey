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
                allowIntersection: false,
                showArea: true,
                shapeOptions: {
                    color: '#3b82f6',
                    fillOpacity: 0.2
                }
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

        const name = prompt('Enter a name for this survey zone:');

        if (!name) {
            alert('Zone name is required. Zone not saved.');
            return;
        }

        const description = prompt('Enter an optional description (or leave blank):');

        const component = getComponent();
        if (!component) {
            console.error('Zone manager Livewire component not found');
            return;
        }

        component.call('saveZoneData', JSON.stringify(geoJSON), name, description || '');

        console.log('Zone creation call sent:', { name });
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
    if (zonesData && zonesData !== 'null' && zonesData !== '[]') {
        try {
            const zones = JSON.parse(zonesData);

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
        } catch (e) {
            console.error('Error parsing zones:', e);
        }
    }

    // Load data points
    const dataPointsData = dataContainer.getAttribute('data-datapoints');
    if (dataPointsData && dataPointsData !== 'null' && dataPointsData !== '[]') {
        try {
            const dataPoints = JSON.parse(dataPointsData);

            if (dataPoints && dataPoints.features && dataPoints.features.length > 0) {
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

