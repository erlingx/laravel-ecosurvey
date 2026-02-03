# PostGIS Quick Reference

## Data Types
```sql
geometry(POINT, 4326)  -- Cartesian (degrees)
geography              -- Spherical (meters)
```

## Point Operations

### Create
```sql
ST_SetSRID(ST_MakePoint(lon, lat), 4326)
ST_GeomFromText('POINT(lon lat)', 4326)
```

### Extract
```sql
ST_X(location::geometry) AS longitude
ST_Y(location::geometry) AS latitude
```

## Distance

### Meters (accurate)
```sql
ST_Distance(
  point1::geography,
  point2::geography
) -- returns meters
```

### Within radius
```sql
ST_DWithin(
  location::geography,
  ST_SetSRID(ST_MakePoint(lon, lat), 4326)::geography,
  radius_in_meters
)
```

## Spatial Queries

### Within polygon
```sql
ST_Within(
  point,
  ST_GeomFromText('POLYGON((...))', 4326)
)
```

### Buffer zone
```sql
ST_Buffer(
  point::geography,
  radius_meters
)::geometry
```

### Bounding box
```sql
ST_Extent(location::geometry)
ST_XMin(extent) AS min_lon
ST_YMin(extent) AS min_lat
ST_XMax(extent) AS max_lon
ST_YMax(extent) AS max_lat
```

## Indexing
```sql
CREATE INDEX idx_name 
ON table_name 
USING GIST (location);
```

## Cast Rules
- **Distance in meters** → `::geography`
- **Bounding box** → `::geometry`
- **Fast queries** → `::geometry` + `::geography` for precision

## Common Patterns

### Find nearby points
```php
DataPoint::whereRaw(
  'ST_DWithin(location::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
  [$lon, $lat, $radiusMeters]
)->get()
```

### GeoJSON export
```php
DB::select('
  SELECT 
    ST_X(location::geometry) as lon,
    ST_Y(location::geometry) as lat
  FROM data_points
')
```

### Zone-based aggregation
```php
DB::select('
  SELECT 
    sz.id,
    COUNT(*) as point_count,
    AVG(dp.value) as avg_value
  FROM survey_zones sz
  JOIN data_points dp ON ST_Within(dp.location, sz.area)
  GROUP BY sz.id
')
```
