# Map Visualization

## Leaflet Core

### Initialization
```javascript
const map = L.map('map-element').setView([lat, lon], zoom);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap',
    maxZoom: 19
}).addTo(map);
```

### Tile Providers
```javascript
// OpenStreetMap (default)
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png'

// Satellite
'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'

// Terrain
'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png'
```

---

## GeoJSON Integration

### Data Structure
```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "geometry": {
        "type": "Point",
        "coordinates": [lon, lat]
      },
      "properties": {
        "id": 1,
        "value": 42.5,
        "metric": "Temperature",
        "status": "approved"
      }
    }
  ]
}
```

**Note:** GeoJSON uses `[lon, lat]` NOT `[lat, lon]`

### Rendering
```javascript
L.geoJSON(geoJsonData, {
    pointToLayer: function(feature, latlng) {
        return L.marker(latlng, {
            icon: getCustomIcon(feature.properties)
        });
    },
    onEachFeature: function(feature, layer) {
        layer.bindPopup(createPopupContent(feature.properties));
    }
}).addTo(map);
```

---

## Marker Clustering

### Setup
```javascript
import 'leaflet.markercluster';

const clusterGroup = L.markerClusterGroup({
    chunkedLoading: true,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    maxClusterRadius: 80,
    spiderfyDistanceMultiplier: 1.5,
    iconCreateFunction: function(cluster) {
        const count = cluster.getChildCount();
        return L.divIcon({
            html: `<div>${count}</div>`,
            className: 'marker-cluster',
            iconSize: L.point(40, 40)
        });
    }
});
```

### Custom Cluster Icons (Quality-Based)
```javascript
iconCreateFunction: function(cluster) {
    const markers = cluster.getAllChildMarkers();
    
    let flaggedCount = 0;
    let rejectedCount = 0;
    let approvedCount = 0;
    
    markers.forEach(marker => {
        const props = marker.feature.properties;
        if (props.status === 'rejected') rejectedCount++;
        else if (props.qa_flags?.length > 0) flaggedCount++;
        else if (props.status === 'approved') approvedCount++;
    });
    
    let className = 'cluster-normal';
    if (rejectedCount > 0) className = 'cluster-rejected';
    else if (flaggedCount > 0) className = 'cluster-flagged';
    else if (approvedCount === markers.length) className = 'cluster-approved';
    
    return L.divIcon({
        html: `<div><span>${markers.length}</span></div>`,
        className: `marker-cluster ${className}`,
        iconSize: L.point(40, 40)
    });
}
```

---

## Custom Markers

### Icon Creation
```javascript
const customIcon = L.icon({
    iconUrl: '/images/marker-icon.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34]
});

L.marker([lat, lon], { icon: customIcon }).addTo(map);
```

### DivIcon (HTML/CSS)
```javascript
const divIcon = L.divIcon({
    html: '<div class="custom-marker"></div>',
    className: 'marker-wrapper',
    iconSize: [30, 30],
    iconAnchor: [15, 15]
});
```

### Quality-Based Styling
```javascript
function getMarkerIcon(properties) {
    const hasFlags = properties.qa_flags?.length > 0;
    const isRejected = properties.status === 'rejected';
    const isLowAccuracy = properties.accuracy > 50;
    
    let className = 'marker-normal';
    if (isRejected) className = 'marker-rejected';
    else if (hasFlags) className = 'marker-flagged';
    else if (isLowAccuracy) className = 'marker-low-accuracy';
    
    return L.divIcon({
        html: `<div class="${className}"></div>`,
        iconSize: [12, 12]
    });
}
```

---

## Popups

### Basic Popup
```javascript
marker.bindPopup(`
    <h3>${feature.properties.metric}</h3>
    <p>Value: ${feature.properties.value}</p>
`);
```

### Interactive Popup
```javascript
function createPopupContent(props) {
    return `
        <div class="popup-content">
            <h3>${props.metric}</h3>
            <p><strong>Value:</strong> ${props.value} ${props.unit}</p>
            <p><strong>Collected:</strong> ${props.collected_at}</p>
            <p><strong>Accuracy:</strong> ±${props.accuracy}m</p>
            <button onclick="editDataPoint(${props.id})">Edit</button>
        </div>
    `;
}
```

### Alpine.js Integration
```javascript
// Popup triggers Livewire event
marker.bindPopup(content).on('click', function() {
    window.Livewire.dispatch('edit-data-point', { id: props.id });
});
```

---

## Dynamic Updates

### Livewire Integration
```javascript
// Listen for Livewire events
window.addEventListener('map-filter-changed', event => {
    const { dataPoints, boundingBox } = event.detail;
    
    // Clear existing markers
    clusterGroup.clearLayers();
    
    // Add new markers
    L.geoJSON(dataPoints, {
        pointToLayer: createMarker,
        onEachFeature: bindPopup
    }).addTo(clusterGroup);
    
    // Fit bounds
    if (boundingBox) {
        map.fitBounds([
            [boundingBox.southwest[0], boundingBox.southwest[1]],
            [boundingBox.northeast[0], boundingBox.northeast[1]]
        ]);
    }
});
```

### State Persistence
```javascript
// Save map state on move/zoom
map.on('moveend', function() {
    const center = map.getCenter();
    const zoom = map.getZoom();
    
    sessionStorage.setItem('surveyMapState', JSON.stringify({
        lat: center.lat,
        lng: center.lng,
        zoom: zoom
    }));
});

// Restore on load
const savedState = JSON.parse(sessionStorage.getItem('surveyMapState'));
if (savedState) {
    map.setView([savedState.lat, savedState.lng], savedState.zoom);
}
```

---

## Performance Optimization

### Chunked Loading
```javascript
L.markerClusterGroup({
    chunkedLoading: true,
    chunkInterval: 200,  // Process 200ms chunks
    chunkDelay: 50       // 50ms delay between chunks
});
```

### Viewport Filtering
```javascript
// Only render markers in viewport
function updateVisibleMarkers() {
    const bounds = map.getBounds();
    
    const visibleFeatures = allFeatures.filter(feature => {
        const [lon, lat] = feature.geometry.coordinates;
        return bounds.contains([lat, lon]);
    });
    
    renderMarkers(visibleFeatures);
}

map.on('moveend', updateVisibleMarkers);
```

### Debounced Updates
```javascript
let updateTimeout;
function scheduleMapUpdate(data) {
    clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
        updateMap(data);
    }, 300);
}
```

---

## Layer Control

### Adding Overlays
```javascript
const baseMaps = {
    "OpenStreetMap": osmLayer,
    "Satellite": satelliteLayer
};

const overlays = {
    "Data Points": clusterGroup,
    "Survey Zones": zoneLayer
};

L.control.layers(baseMaps, overlays).addTo(map);
```

---

## Pitfalls

### Coordinate Order
```javascript
// Leaflet: [lat, lon]
L.marker([55.676, 12.568])

// GeoJSON: [lon, lat]
{ "coordinates": [12.568, 55.676] }

// PostGIS extraction: lon = X, lat = Y
ST_X(location) as lon, ST_Y(location) as lat
```

### Memory Leaks
```javascript
// Remove existing map before re-creating
if (window.surveyMap) {
    window.surveyMap.remove();
    window.surveyMap = null;
}
```

### Cluster Click Events
```javascript
// Default behavior opens cluster
// Override for custom behavior
clusterGroup.on('clusterclick', function(event) {
    event.originalEvent.stopPropagation();
    // Custom logic
});
```

### Large Datasets
```javascript
// Don't render 10,000+ markers at once
// Use server-side filtering or viewport bounds

// Backend: only return points in bbox
$service->getDataPointsInBounds($north, $south, $east, $west);
```

### Map Not Displaying
```javascript
// Common issue: container has height: 0
#map-element {
    height: 600px; // Must have explicit height
    width: 100%;
}

// After dynamic resize
map.invalidateSize();
```

### Z-Index Issues
```css
/* Ensure map controls appear above markers */
.leaflet-control {
    z-index: 1000;
}

/* Popups above everything */
.leaflet-popup {
    z-index: 1100;
}
```

---

## Best Practices

1. **Explicit heights** - Map container needs height in px or vh
2. **Invalidate size** - After container resize
3. **Cleanup instances** - Remove old maps before creating new
4. **Debounce updates** - Don't update on every keystroke
5. **Chunk large datasets** - Use MarkerCluster chunking
6. **Viewport filtering** - Only render visible markers
7. **State persistence** - Save zoom/center to sessionStorage
8. **Coordinate consistency** - [lat, lon] for Leaflet, [lon, lat] for GeoJSON
9. **Event delegation** - Use cluster events, not individual markers
10. **Cache tiles** - Let browser handle tile caching
