# GIS & PostGIS Crash Course

A practical guide for developers working with geospatial data in Laravel applications.

---

## 📍 What is GIS?

**Geographic Information System (GIS)** = Technology for storing, analyzing, and visualizing spatial data.

**Key Concepts:**
- **Geometry**: Shape of geographic feature (point, line, polygon)
- **Geography**: Geometry + Earth's curvature (more accurate over distance)
- **Coordinates**: Position on Earth (latitude/longitude or projected)
- **Spatial Reference System (SRS)**: Coordinate system definition

---

## 🌍 Coordinate Systems 101

### **WGS 84 (SRID 4326)** - Most Common
- **What**: Latitude/Longitude coordinate system
- **Units**: Degrees (-180 to 180, -90 to 90)
- **Use**: GPS, web maps, mobile apps
- **Example**: `POINT(-122.4194 37.7749)` = San Francisco

```sql
-- Store GPS coordinates
INSERT INTO locations (name, location) 
VALUES ('Golden Gate Bridge', ST_SetSRID(ST_MakePoint(-122.4783, 37.8199), 4326));
```

### **Web Mercator (SRID 3857)**
- **What**: Projected coordinate system (flat map)
- **Units**: Meters from origin
- **Use**: Google Maps, OpenStreetMap, Leaflet
- **Limitation**: Distorts areas near poles

### **UTM Zones (SRID 326XX)**
- **What**: Local projected systems (60 zones worldwide)
- **Units**: Meters
- **Use**: Accurate measurements in specific regions
- **Example**: SRID 32610 = UTM Zone 10N (California)

**Pro Tip**: Use 4326 for storage, transform to 3857 or UTM for calculations.

---

## 🗺️ PostGIS Geometry Types

### **Point** - Single Location
```sql
-- Restaurant location
ST_MakePoint(-122.4194, 37.7749)

-- With SRID
ST_SetSRID(ST_MakePoint(-122.4194, 37.7749), 4326)
```

### **LineString** - Path/Route
```sql
-- Hiking trail
ST_MakeLine(ARRAY[
    ST_MakePoint(-122.4, 37.8),
    ST_MakePoint(-122.5, 37.9),
    ST_MakePoint(-122.6, 38.0)
])
```

### **Polygon** - Area/Zone
```sql
-- Survey area boundary
ST_MakePolygon(ST_MakeLine(ARRAY[
    ST_MakePoint(-122.4, 37.8),
    ST_MakePoint(-122.5, 37.8),
    ST_MakePoint(-122.5, 37.9),
    ST_MakePoint(-122.4, 37.9),
    ST_MakePoint(-122.4, 37.8)  -- Must close the ring
]))
```

### **MultiPoint, MultiLineString, MultiPolygon**
```sql
-- Multiple locations
ST_Collect(ARRAY[
    ST_MakePoint(-122.4, 37.8),
    ST_MakePoint(-122.5, 37.9)
])
```

---

## 🔧 Essential PostGIS Functions

### **1. Distance Calculations**

```sql
-- Distance in degrees (not useful!)
SELECT ST_Distance(
    ST_MakePoint(-122.4194, 37.7749),
    ST_MakePoint(-122.4083, 37.7849)
);

-- Distance in meters (accurate!)
SELECT ST_Distance(
    ST_MakePoint(-122.4194, 37.7749)::geography,
    ST_MakePoint(-122.4083, 37.7849)::geography
);
-- Returns: 1165.76 meters

-- Find locations within 1km
SELECT * FROM locations
WHERE ST_DWithin(
    location::geography,
    ST_MakePoint(-122.4194, 37.7749)::geography,
    1000  -- 1000 meters
);
```

**Key**: Cast to `::geography` for meter-based calculations!

### **2. Containment Queries**

```sql
-- Is point inside polygon?
SELECT ST_Contains(
    survey_zone.geom,
    data_point.location
) FROM survey_zones, data_points;

-- Find all readings within survey zone
SELECT dp.* 
FROM data_points dp
JOIN survey_zones sz ON ST_Contains(sz.geom, dp.location)
WHERE sz.id = 123;

-- Reverse: which zone contains this point?
SELECT sz.* 
FROM survey_zones sz
WHERE ST_Contains(sz.geom, ST_MakePoint(-122.4194, 37.7749));
```

### **3. Buffer Zones**

```sql
-- Create 500m buffer around point (geography for meters)
SELECT ST_Buffer(
    ST_MakePoint(-122.4194, 37.7749)::geography,
    500
)::geometry;

-- Find readings within 500m of any contamination site
SELECT dr.* 
FROM data_readings dr
JOIN contamination_sites cs 
  ON ST_DWithin(
    dr.location::geography,
    cs.location::geography,
    500
  );
```

### **4. Area & Length**

```sql
-- Area in square meters
SELECT ST_Area(survey_zone.geom::geography) AS area_sqm
FROM survey_zones;

-- Convert to hectares
SELECT ST_Area(survey_zone.geom::geography) / 10000 AS area_hectares;

-- Length of path in meters
SELECT ST_Length(hiking_trail.path::geography) AS length_m;
```

### **5. Centroid & Bounding Box**

```sql
-- Center point of polygon
SELECT ST_Centroid(survey_zone.geom) AS center;

-- Bounding box (min/max coordinates)
SELECT ST_Envelope(survey_zone.geom) AS bbox;

-- Extract coordinates
SELECT 
    ST_XMin(ST_Envelope(geom)) AS min_lng,
    ST_YMin(ST_Envelope(geom)) AS min_lat,
    ST_XMax(ST_Envelope(geom)) AS max_lng,
    ST_YMax(ST_Envelope(geom)) AS max_lat
FROM survey_zones;
```

### **6. Intersection & Union**

```sql
-- Overlapping area between two polygons
SELECT ST_Intersection(zone1.geom, zone2.geom);

-- Combine multiple polygons into one
SELECT ST_Union(geom) FROM survey_zones WHERE campaign_id = 123;

-- Do two zones overlap?
SELECT ST_Intersects(zone1.geom, zone2.geom);
```

---

## 🎯 Real-World Query Cookbook

### **Find Nearest Locations**

```sql
-- 5 nearest stations to user location
SELECT 
    name,
    ST_Distance(
        location::geography,
        ST_MakePoint(-122.4194, 37.7749)::geography
    ) AS distance_m
FROM monitoring_stations
ORDER BY location <-> ST_MakePoint(-122.4194, 37.7749)::geometry
LIMIT 5;
```

**Note**: Use `<->` operator for fast index-based ordering!

### **Heatmap Data Generation**

```sql
-- Grid-based heatmap (average value per grid cell)
SELECT 
    ST_SnapToGrid(location, 0.01) AS grid_cell,  -- 0.01° ~1km
    AVG(metric_value) AS intensity,
    COUNT(*) AS sample_count
FROM data_points
WHERE campaign_id = 123
GROUP BY grid_cell;
```

### **Cluster Analysis**

```sql
-- Find dense clusters of readings (ST_ClusterDBSCAN)
SELECT 
    ST_ClusterDBSCAN(location::geometry, eps := 0.01, minpoints := 5) OVER () AS cluster_id,
    location,
    metric_value
FROM data_points;
```

### **Route Distance Calculation**

```sql
-- Total distance along a path
SELECT ST_Length(
    ST_MakeLine(ARRAY_AGG(location ORDER BY timestamp))::geography
) AS total_distance_m
FROM gps_tracks
WHERE trip_id = 456;
```

### **Point-in-Polygon with Multiple Polygons**

```sql
-- Which administrative region is this reading in?
SELECT 
    r.name,
    r.admin_level
FROM regions r
WHERE ST_Contains(r.geom, ST_MakePoint(-122.4194, 37.7749))
ORDER BY r.admin_level DESC
LIMIT 1;
```

---

## 🚀 Laravel Integration

### **Migration with Geometry Column**

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained();
            $table->string('metric_type');
            $table->decimal('metric_value', 10, 4);
            $table->timestamps();
        });

        // Add geometry column with PostGIS
        DB::statement('ALTER TABLE data_points ADD COLUMN location geometry(Point, 4326)');
        
        // Create spatial index for performance
        DB::statement('CREATE INDEX data_points_location_idx ON data_points USING GIST (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('data_points');
    }
};
```

### **Eloquent Model with Spatial Attributes**

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DataPoint extends Model
{
    protected $fillable = ['campaign_id', 'metric_type', 'metric_value', 'latitude', 'longitude'];
    
    protected $appends = ['latitude', 'longitude'];

    // Automatically set location from lat/lng
    public function setLatitudeAttribute($value): void
    {
        $this->attributes['latitude'] = $value;
        $this->updateLocation();
    }

    public function setLongitudeAttribute($value): void
    {
        $this->attributes['longitude'] = $value;
        $this->updateLocation();
    }

    protected function updateLocation(): void
    {
        if (isset($this->attributes['latitude']) && isset($this->attributes['longitude'])) {
            $this->attributes['location'] = DB::raw(sprintf(
                "ST_SetSRID(ST_MakePoint(%F, %F), 4326)",
                $this->attributes['longitude'],
                $this->attributes['latitude']
            ));
        }
    }

    // Extract lat/lng from geometry
    public function getLatitudeAttribute(): ?float
    {
        if (!$this->location) return null;
        
        return DB::selectOne(
            'SELECT ST_Y(location) as lat FROM data_points WHERE id = ?',
            [$this->id]
        )?->lat;
    }

    public function getLongitudeAttribute(): ?float
    {
        if (!$this->location) return null;
        
        return DB::selectOne(
            'SELECT ST_X(location) as lng FROM data_points WHERE id = ?',
            [$this->id]
        )?->lng;
    }

    // Query scope: within distance
    public function scopeWithinDistance($query, float $lat, float $lng, int $meters)
    {
        return $query->whereRaw(
            'ST_DWithin(location::geography, ST_MakePoint(?, ?)::geography, ?)',
            [$lng, $lat, $meters]
        );
    }

    // Query scope: inside polygon
    public function scopeInsideZone($query, int $zoneId)
    {
        return $query->whereRaw(
            'ST_Contains((SELECT geom FROM survey_zones WHERE id = ?), location)',
            [$zoneId]
        );
    }

    // Get distance to another point
    public function distanceTo(float $lat, float $lng): float
    {
        $result = DB::selectOne(
            'SELECT ST_Distance(location::geography, ST_MakePoint(?, ?)::geography) as distance
             FROM data_points WHERE id = ?',
            [$lng, $lat, $this->id]
        );

        return $result?->distance ?? 0;
    }
}
```

### **Service Class for Spatial Queries**

```php
namespace App\Services;

use App\Models\DataPoint;
use Illuminate\Support\Facades\DB;

class GeospatialService
{
    /**
     * Generate heatmap data for map visualization
     */
    public function getHeatmapData(int $campaignId, float $gridSize = 0.01): array
    {
        $results = DB::select("
            SELECT 
                ST_X(grid_cell) as lng,
                ST_Y(grid_cell) as lat,
                AVG(metric_value) as intensity,
                COUNT(*) as count
            FROM (
                SELECT 
                    ST_SnapToGrid(location, ?) as grid_cell,
                    metric_value
                FROM data_points
                WHERE campaign_id = ?
            ) AS gridded
            GROUP BY grid_cell
        ", [$gridSize, $campaignId]);

        return array_map(fn($row) => [
            'lat' => $row->lat,
            'lng' => $row->lng,
            'intensity' => $row->intensity,
            'count' => $row->count,
        ], $results);
    }

    /**
     * Find data points within polygon
     */
    public function getPointsInZone(int $zoneId): array
    {
        return DataPoint::whereRaw(
            'ST_Contains((SELECT geom FROM survey_zones WHERE id = ?), location)',
            [$zoneId]
        )->get();
    }

    /**
     * Calculate statistics for area
     */
    public function getZoneStatistics(int $zoneId): array
    {
        $stats = DB::selectOne("
            SELECT 
                COUNT(*) as total_readings,
                AVG(metric_value) as avg_value,
                MIN(metric_value) as min_value,
                MAX(metric_value) as max_value,
                ST_Area((SELECT geom FROM survey_zones WHERE id = ?)::geography) as area_sqm
            FROM data_points
            WHERE ST_Contains((SELECT geom FROM survey_zones WHERE id = ?), location)
        ", [$zoneId, $zoneId]);

        return [
            'total_readings' => $stats->total_readings,
            'average' => round($stats->avg_value, 2),
            'min' => $stats->min_value,
            'max' => $stats->max_value,
            'area_hectares' => round($stats->area_sqm / 10000, 2),
        ];
    }

    /**
     * Find nearest monitoring station
     */
    public function findNearestStation(float $lat, float $lng): ?object
    {
        return DB::selectOne("
            SELECT 
                id,
                name,
                ST_Distance(
                    location::geography,
                    ST_MakePoint(?, ?)::geography
                ) as distance_m
            FROM monitoring_stations
            ORDER BY location <-> ST_MakePoint(?, ?)::geometry
            LIMIT 1
        ", [$lng, $lat, $lng, $lat]);
    }
}
```

---

## 🎨 Frontend Integration (Leaflet.js)

### **Display Points on Map**

```javascript
// In Livewire component or Blade
const map = L.map('map').setView([37.7749, -122.4194], 10);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Add markers from Laravel
@foreach($dataPoints as $point)
    L.marker([{{ $point->latitude }}, {{ $point->longitude }}])
        .bindPopup(`
            <strong>{{ $point->metric_type }}</strong><br>
            Value: {{ $point->metric_value }}<br>
            Date: {{ $point->created_at->format('Y-m-d') }}
        `)
        .addTo(map);
@endforeach

// Heatmap layer
const heatmapData = @json($heatmapData);
L.heatLayer(
    heatmapData.map(d => [d.lat, d.lng, d.intensity])
).addTo(map);
```

---

## ⚡ Performance Tips

### **1. Always Use Spatial Indexes**
```sql
CREATE INDEX idx_location ON data_points USING GIST (location);
CREATE INDEX idx_geom ON survey_zones USING GIST (geom);
```

### **2. Use Appropriate Operators**
- `ST_DWithin()` instead of `ST_Distance() < X` (index-optimized)
- `<->` for nearest neighbor searches
- `&&` for bounding box overlap (fast preliminary check)

### **3. Cast to Geography for Accuracy**
```sql
-- Accurate but slower
location::geography

-- Fast but approximate
location::geometry
```

### **4. Simplify Complex Geometries**
```sql
-- Reduce polygon complexity
SELECT ST_Simplify(geom, 0.001) FROM complex_zones;
```

### **5. Cache Expensive Calculations**
```php
// In Laravel
Cache::remember("zone-{$zoneId}-stats", 3600, function() use ($zoneId) {
    return $this->geospatialService->getZoneStatistics($zoneId);
});
```

---

## 🐛 Common Pitfalls

### ❌ **Wrong: Mixing Geometry/Geography**
```sql
-- Error: can't mix types
ST_Distance(location::geometry, point::geography)
```
✅ **Correct: Use consistent types**
```sql
ST_Distance(location::geography, point::geography)
```

### ❌ **Wrong: Forgetting SRID**
```sql
ST_MakePoint(-122.4, 37.7)  -- SRID 0 (unknown)
```
✅ **Correct: Always set SRID**
```sql
ST_SetSRID(ST_MakePoint(-122.4, 37.7), 4326)
```

### ❌ **Wrong: Distance in degrees**
```sql
-- Returns ~0.01 (useless!)
ST_Distance(geom1, geom2)
```
✅ **Correct: Cast to geography**
```sql
-- Returns meters
ST_Distance(geom1::geography, geom2::geography)
```

### ❌ **Wrong: Unclosed polygon**
```sql
-- Missing closing point
ST_MakeLine(ARRAY[point1, point2, point3])
```
✅ **Correct: Close the ring**
```sql
ST_MakeLine(ARRAY[point1, point2, point3, point1])
```

---

## 📚 Quick Reference Card

| Task | Function | Example |
|------|----------|---------|
| Create point | `ST_MakePoint(lng, lat)` | `ST_SetSRID(ST_MakePoint(-122.4, 37.7), 4326)` |
| Distance (meters) | `ST_Distance(::geography)` | `ST_Distance(a::geography, b::geography)` |
| Within radius | `ST_DWithin(::geography, m)` | `ST_DWithin(loc::geography, pt::geography, 1000)` |
| Inside polygon | `ST_Contains(poly, pt)` | `ST_Contains(zone.geom, point.location)` |
| Buffer zone | `ST_Buffer(::geography, m)` | `ST_Buffer(pt::geography, 500)::geometry` |
| Area (sqm) | `ST_Area(::geography)` | `ST_Area(polygon::geography)` |
| Length (m) | `ST_Length(::geography)` | `ST_Length(path::geography)` |
| Nearest | `ORDER BY a <-> b` | `ORDER BY location <-> ST_MakePoint(-122.4, 37.7)` |
| Centroid | `ST_Centroid(geom)` | `ST_Centroid(survey_zone.geom)` |
| Bounding box | `ST_Envelope(geom)` | `ST_Envelope(area.geom)` |

---

## 🎓 Learning Resources

- **PostGIS Documentation**: https://postgis.net/documentation/
- **PostGIS in Action (Book)**: Comprehensive reference
- **Coordinate System Reference**: https://epsg.io/
- **GeoJSON Validator**: https://geojson.io/
- **Leaflet.js Docs**: https://leafletjs.com/
- **Laravel Spatial Package**: https://github.com/MatanYadaev/laravel-eloquent-spatial

---

## 🚀 Next Steps

1. **Install PostGIS** in your database
2. **Create your first spatial table** with geometry column
3. **Add spatial index** for performance
4. **Test basic queries** (distance, containment)
5. **Integrate with Laravel** using custom model methods
6. **Build interactive map** with Leaflet.js
7. **Optimize with caching** for expensive calculations

---

**Happy mapping! 🗺️**

