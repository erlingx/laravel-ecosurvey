# PostgreSQL vs MySQL: Crash Course

**Context:** Laravel 12 Application with PostGIS  
**Last updated:** 2025-12-19

---

## Pronunciation & Etymology

### PostgreSQL
- **Pronunciation:** "Post-gres-cue-ell" or "Post-gres"
- **NOT an acronym** - It's a proper name derived from:
  - **"Postgres"** - The original database project (1986) at UC Berkeley
  - **"SQL"** - Added when SQL support was introduced (1996)
- **Origin:** Named after "Post Ingres" (successor to the INGRES database)
- **Community nickname:** "Postgres" (commonly used and officially accepted)

### MySQL
- **Pronunciation:** "My-ess-cue-ell" or "My-sequel"
- **NOT an acronym** - Named after:
  - **"My"** - Co-founder Michael Widenius's daughter's name
  - **"SQL"** - Structured Query Language
- **Origin:** Created in 1995 by MySQL AB (Swedish company)

---

## Quick Comparison

| Feature | PostgreSQL | MySQL |
|---------|-----------|-------|
| **License** | Open Source (PostgreSQL) | Open Source (GPL) / Commercial |
| **ACID Compliance** | Fully compliant | Mostly compliant (InnoDB) |
| **Data Types** | Rich (JSON, Arrays, Geographic) | Basic (limited JSON) |
| **Performance** | Complex queries, analytics | Simple reads, high concurrency |
| **Extensions** | Highly extensible (PostGIS) | Limited extensions |
| **Standards** | SQL standard compliant | Some proprietary syntax |
| **Full Text Search** | Built-in, powerful | Basic |
| **Window Functions** | Comprehensive | Limited (added in 8.0) |
| **CTEs** | Recursive & non-recursive | Basic (added in 8.0) |
| **Replication** | Streaming, logical | Binary log, GTID |

---

## When to Choose PostgreSQL

✅ **Use PostgreSQL when you need:**

1. **Advanced Data Types**
   - JSON/JSONB with indexing
   - Arrays, ranges, hstore
   - Geographic data (PostGIS)
   - Full-text search vectors

2. **Complex Queries**
   - Window functions
   - CTEs (Common Table Expressions)
   - Lateral joins
   - Advanced aggregations

3. **Data Integrity**
   - Strict ACID compliance
   - Foreign key constraints
   - Custom constraints
   - Transactional DDL

4. **Extensibility**
   - Custom data types
   - Extensions (PostGIS, pg_trgm, etc.)
   - Custom functions in multiple languages
   - Custom operators

5. **Geospatial Applications**
   - PostGIS extension (industry standard)
   - Geometry/Geography types
   - Spatial indexing (GiST, SP-GiST)
   - Spatial queries (intersects, contains, distance)

---

## When to Choose MySQL

✅ **Use MySQL when you need:**

1. **Simple Web Applications**
   - CRUD operations
   - Basic joins
   - Read-heavy workloads
   - WordPress, Drupal, Joomla

2. **High Read Performance**
   - Query cache (older versions)
   - Simple SELECT queries
   - Minimal joins
   - Web-scale reads

3. **Replication Simplicity**
   - Master-slave replication
   - Easy setup
   - Wide hosting support

4. **Ecosystem**
   - phpMyAdmin
   - Shared hosting support
   - Legacy application compatibility

---

## Key Differences

### 1. Data Types

#### PostgreSQL
```sql
-- Rich data types
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    metadata JSONB,                    -- Indexable JSON
    tags TEXT[],                       -- Arrays
    price_range NUMRANGE,              -- Ranges
    location GEOGRAPHY(POINT, 4326),   -- PostGIS
    search_vector TSVECTOR             -- Full-text search
);

-- Index JSONB
CREATE INDEX idx_metadata ON products USING GIN (metadata);

-- Query JSONB
SELECT * FROM products WHERE metadata->>'category' = 'electronics';
SELECT * FROM products WHERE metadata @> '{"featured": true}';
```

#### MySQL
```sql
-- Limited data types
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    metadata JSON,                     -- JSON (no GIN index)
    tags TEXT,                         -- Comma-separated string
    min_price DECIMAL(10,2),
    max_price DECIMAL(10,2),
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8)
);

-- Query JSON (slower without index)
SELECT * FROM products WHERE JSON_EXTRACT(metadata, '$.category') = 'electronics';
```

---

### 2. Geographic Data

#### PostgreSQL with PostGIS
```sql
-- Enable PostGIS
CREATE EXTENSION postgis;

-- Create table with geometry
CREATE TABLE survey_zones (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255),
    boundary GEOGRAPHY(POLYGON, 4326)
);

-- Spatial index
CREATE INDEX idx_boundary ON survey_zones USING GIST (boundary);

-- Spatial queries
-- Find points within polygon
SELECT * FROM data_points 
WHERE ST_Within(location, (SELECT boundary FROM survey_zones WHERE id = 1));

-- Find points within 5km radius
SELECT * FROM data_points 
WHERE ST_DWithin(location, ST_MakePoint(-122.4194, 37.7749)::geography, 5000);

-- Calculate distance in meters
SELECT ST_Distance(
    ST_MakePoint(-122.4194, 37.7749)::geography,
    location
) as distance_meters
FROM data_points;
```

#### MySQL (Basic Spatial)
```sql
-- Create table with geometry
CREATE TABLE survey_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    boundary POLYGON NOT NULL,
    SPATIAL INDEX (boundary)
);

-- Spatial queries (limited)
-- Find points within polygon
SELECT * FROM data_points 
WHERE ST_Contains(
    (SELECT boundary FROM survey_zones WHERE id = 1),
    POINT(longitude, latitude)
);

-- Distance calculation (requires manual Haversine formula)
SELECT *, (
    6371 * acos(
        cos(radians(37.7749)) * 
        cos(radians(latitude)) * 
        cos(radians(longitude) - radians(-122.4194)) + 
        sin(radians(37.7749)) * 
        sin(radians(latitude))
    )
) AS distance_km
FROM data_points
HAVING distance_km < 5;
```

---

### 3. Advanced Queries

#### PostgreSQL
```sql
-- Window functions
SELECT 
    campaign_id,
    created_at,
    value,
    AVG(value) OVER (PARTITION BY campaign_id ORDER BY created_at 
                     ROWS BETWEEN 2 PRECEDING AND CURRENT ROW) as moving_avg
FROM data_points;

-- Recursive CTE
WITH RECURSIVE campaign_hierarchy AS (
    SELECT id, name, parent_id, 1 as level
    FROM campaigns
    WHERE parent_id IS NULL
    
    UNION ALL
    
    SELECT c.id, c.name, c.parent_id, ch.level + 1
    FROM campaigns c
    INNER JOIN campaign_hierarchy ch ON c.parent_id = ch.id
)
SELECT * FROM campaign_hierarchy;

-- Array operations
SELECT * FROM products WHERE 'organic' = ANY(tags);
SELECT array_agg(name) FROM products WHERE price > 100;
```

#### MySQL
```sql
-- Window functions (MySQL 8.0+)
SELECT 
    campaign_id,
    created_at,
    value,
    AVG(value) OVER (PARTITION BY campaign_id ORDER BY created_at 
                     ROWS BETWEEN 2 PRECEDING AND CURRENT ROW) as moving_avg
FROM data_points;

-- Recursive CTE (MySQL 8.0+)
WITH RECURSIVE campaign_hierarchy AS (
    SELECT id, name, parent_id, 1 as level
    FROM campaigns
    WHERE parent_id IS NULL
    
    UNION ALL
    
    SELECT c.id, c.name, c.parent_id, ch.level + 1
    FROM campaigns c
    INNER JOIN campaign_hierarchy ch ON c.parent_id = ch.id
)
SELECT * FROM campaign_hierarchy;

-- No array support - use JSON or separate table
SELECT * FROM products WHERE JSON_CONTAINS(tags, '"organic"');
```

---

### 4. Full-Text Search

#### PostgreSQL
```sql
-- Built-in full-text search
CREATE TABLE articles (
    id SERIAL PRIMARY KEY,
    title TEXT,
    content TEXT,
    search_vector TSVECTOR
);

-- Create index
CREATE INDEX idx_search ON articles USING GIN (search_vector);

-- Update search vector automatically
CREATE TRIGGER articles_search_update
BEFORE INSERT OR UPDATE ON articles
FOR EACH ROW EXECUTE FUNCTION
tsvector_update_trigger(search_vector, 'pg_catalog.english', title, content);

-- Search with ranking
SELECT *, ts_rank(search_vector, query) as rank
FROM articles, plainto_tsquery('environmental pollution') as query
WHERE search_vector @@ query
ORDER BY rank DESC;
```

#### MySQL
```sql
-- Full-text search (InnoDB)
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title TEXT,
    content TEXT,
    FULLTEXT (title, content)
);

-- Search (boolean mode)
SELECT *, MATCH(title, content) AGAINST('environmental pollution' IN BOOLEAN MODE) as score
FROM articles
WHERE MATCH(title, content) AGAINST('environmental pollution' IN BOOLEAN MODE)
ORDER BY score DESC;
```

---

## Laravel Integration

### PostgreSQL Configuration

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecosurvey
DB_USERNAME=postgres
DB_PASSWORD=secret
```

```php
// config/database.php
'pgsql' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'prefer',
];
```

### MySQL Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecosurvey
DB_USERNAME=root
DB_PASSWORD=secret
```

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
];
```

---

## Migrations: PostgreSQL-Specific Features

### Using PostGIS

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Enable PostGIS extension
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        
        Schema::create('data_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('metric_type');
            $table->decimal('value', 10, 2);
            
            // PostGIS geography column
            $table->geography('location', 'point', 4326);
            
            $table->timestamps();
        });
        
        // Create spatial index
        DB::statement('CREATE INDEX data_points_location_idx ON data_points USING GIST (location)');
    }
    
    public function down(): void
    {
        Schema::dropIfExists('data_points');
    }
};
```

### Using JSONB

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->jsonb('metadata'); // JSONB for PostgreSQL
    $table->timestamps();
});

// Create GIN index on JSONB
DB::statement('CREATE INDEX products_metadata_idx ON products USING GIN (metadata)');
```

---

## Eloquent Queries

### PostgreSQL JSONB Queries

```php
// Query JSONB columns
Product::where('metadata->category', 'electronics')->get();
Product::where('metadata->featured', true)->get();

// Containment operator
Product::whereRaw("metadata @> ?", ['{"featured": true}'])->get();

// Array contains
Product::whereRaw("tags @> ARRAY[?]", ['organic'])->get();
```

### PostGIS Queries

```php
use Illuminate\Support\Facades\DB;

// Find points within 5km
$points = DataPoint::whereRaw(
    'ST_DWithin(location, ST_MakePoint(?, ?)::geography, ?)',
    [$longitude, $latitude, 5000]
)->get();

// Find points within polygon
$zone = SurveyZone::find(1);
$points = DataPoint::whereRaw(
    'ST_Within(location, ?::geography)',
    [$zone->boundary]
)->get();

// Calculate distance
$points = DataPoint::select('*')
    ->selectRaw(
        'ST_Distance(location, ST_MakePoint(?, ?)::geography) as distance_meters',
        [$longitude, $latitude]
    )
    ->orderBy('distance_meters')
    ->get();
```

---

## Performance Considerations

### PostgreSQL Advantages
- **Complex queries:** Better query planner for joins, CTEs
- **Write-heavy workloads:** MVCC (Multi-Version Concurrency Control)
- **Analytics:** Window functions, CTEs, aggregations
- **Data integrity:** Strict constraints, transactional DDL
- **Extensibility:** PostGIS, full-text search, custom types

### MySQL Advantages
- **Simple reads:** Fast for basic SELECT queries
- **Replication:** Easier master-slave setup
- **Memory usage:** Generally lower for simple queries
- **Shared hosting:** More widely available

---

## Migration Strategy (MySQL → PostgreSQL)

### 1. Schema Conversion

```bash
# Export MySQL schema
mysqldump -u root -p --no-data ecosurvey > schema.sql

# Convert to PostgreSQL (manual adjustments needed)
# - AUTO_INCREMENT → SERIAL
# - TINYINT → SMALLINT or BOOLEAN
# - DATETIME → TIMESTAMP
# - TEXT → TEXT (same)
# - ENUM → VARCHAR + CHECK constraint
```

### 2. Data Migration

```bash
# Option 1: Laravel database seeder
php artisan db:seed

# Option 2: pgloader (automated tool)
pgloader mysql://root@localhost/ecosurvey postgresql://postgres@localhost/ecosurvey

# Option 3: CSV export/import
mysqldump -u root -p --tab=/tmp ecosurvey
psql -d ecosurvey -c "\COPY table FROM '/tmp/table.txt'"
```

### 3. Query Updates

```php
// MySQL specific (avoid)
DB::select("SELECT CONCAT(first_name, ' ', last_name) FROM users");

// Cross-compatible
DB::select("SELECT first_name || ' ' || last_name FROM users");

// Or use Eloquent (best)
User::selectRaw("first_name || ' ' || last_name as full_name")->get();
```

---

## Common Pitfalls

### PostgreSQL
❌ Case-sensitive by default (use `ILIKE` instead of `LIKE`)  
❌ No `LIMIT 10, 20` syntax (use `LIMIT 20 OFFSET 10`)  
❌ Strings must use single quotes `'text'` not `"text"`  
❌ Boolean is actual BOOLEAN type, not 0/1  

### MySQL
❌ Silent data truncation by default  
❌ Case-insensitive collation by default  
❌ `GROUP BY` requires all non-aggregated columns (strict mode)  
❌ Limited JSON indexing  

---

## Testing Considerations

### PostgreSQL Tests

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class DataPointTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_query_points_within_radius(): void
    {
        // Enable PostGIS in test database
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        
        $point = DataPoint::factory()->create([
            'location' => DB::raw("ST_MakePoint(-122.4194, 37.7749)::geography")
        ]);
        
        $results = DataPoint::whereRaw(
            'ST_DWithin(location, ST_MakePoint(?, ?)::geography, ?)',
            [-122.4194, 37.7749, 1000]
        )->get();
        
        $this->assertCount(1, $results);
    }
}
```

---

## Verdict for EcoSurvey Project

✅ **PostgreSQL is the clear winner because:**

1. **PostGIS** - Industry-standard geospatial extension
2. **Advanced spatial queries** - Critical for survey zones
3. **JSONB indexing** - Store metadata efficiently
4. **Data integrity** - Environmental data requires accuracy
5. **Analytics capabilities** - Complex aggregations and heatmaps
6. **Array support** - Tag systems, metric types
7. **Full-text search** - Search campaign descriptions
8. **Future-proof** - Scales with complexity

---

## Resources

- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [PostGIS Documentation](https://postgis.net/documentation/)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Laravel Database Documentation](https://laravel.com/docs/12.x/database)
- [DB Fiddle](https://www.db-fiddle.com/) - Test SQL online

---

**Summary:** Use PostgreSQL for data-rich applications with complex queries, geospatial features, and advanced analytics. Use MySQL for simple web applications with basic CRUD operations and read-heavy workloads.

