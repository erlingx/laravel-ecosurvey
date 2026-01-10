# Environment: DDEV

**Type:** Docker-based local development (DDEV)  
**OS:** Windows 11 + PowerShell

---

## Command Execution Rules

### All Commands MUST Use DDEV Prefix

```powershell
# ✅ Correct
ddev artisan migrate
ddev composer install
ddev npm run dev -- --host
ddev artisan test

# ❌ Wrong
php artisan migrate
composer install
npm run dev
vendor/bin/pest
```

---

## PowerShell Limitations with DDEV

### Cannot Chain Commands with && or ||
PowerShell doesn't support these operators properly:

```powershell
# ❌ Wrong - fails in PowerShell
ddev artisan migrate && ddev artisan db:seed

# ✅ Correct - run separately
ddev artisan migrate
ddev artisan db:seed

# ✅ Correct - chain inside bash
ddev exec bash -lc "php artisan migrate; php artisan db:seed"
```

### Unix Commands Need Bash Wrapper

```powershell
# ❌ Wrong - commands don't exist in PowerShell
ddev exec tail -n 50 storage/logs/laravel.log
ddev exec cat file.txt | grep "search"

# ✅ Correct - wrap in bash -c
ddev exec bash -c "tail -n 50 storage/logs/laravel.log"
ddev exec bash -c "grep 'search' file.txt"

# ✅ Alternative - use PowerShell commands
Get-Content storage/logs/laravel.log -Tail 50
Select-String -Pattern "search" -Path file.txt
```

---

## Development Workflow

### Quick Start
```powershell
# Start DDEV (auto-starts queue worker + Vite dev server)
ddev start

# Install dependencies (first time setup)
ddev composer install
ddev npm install

# Run migrations
ddev artisan migrate

# Run tests
ddev artisan test

# Build frontend assets
ddev npm run build
```

### Start DDEV
```powershell
ddev start
```

This auto-starts (via `web_extra_daemons`):
- PHP-FPM web server
- Queue worker (`php artisan queue:work`)
- Vite dev server (`npm run dev`)

### Stop DDEV
```powershell
ddev stop
```

### After Code Changes
```powershell
# Queue/Job changes - fast restart (1-3 seconds)
ddev artisan queue:restart

# Frontend changes (JS/CSS) - auto-reload via Vite HMR (already running)
# No action needed! Changes are hot-reloaded automatically.
# DO NOT run `ddev npm run build` - Vite handles it!

# ❌ NEVER restart entire DDEV for queue changes (too slow - 30+ seconds)
# ❌ NEVER run npm run build when Vite dev server is running
```

### Check Running Services
```powershell
# Check queue worker and vite are running
ddev exec bash -c "ps aux | grep -E 'queue:work|vite' | grep -v grep"
```

---

## Queue Management

### Restart After Code Changes
```powershell
# Fast restart (recommended):
ddev artisan queue:restart

# Worker restarts after finishing current job (1-3 seconds)
```

**DO NOT restart entire DDEV** - it's too slow (30+ seconds)!

### Check Queue Status
```powershell
ddev artisan queue:monitor database
ddev artisan queue:failed
```

### Manual Queue Worker (for debugging)
```powershell
# Stop daemon
ddev exec pkill -f "queue:work"

# Run manually with verbose output
ddev exec php artisan queue:work --verbose

# Exit and daemon will auto-restart
```

---

## Testing

### Run Tests (Important!)
Tests **MUST** run through DDEV for proper database/environment:

```powershell
# Recommended: Use bash -c with tail for clean output
ddev exec bash -c "vendor/bin/pest tests/Feature/ListeningPartyTest.php 2>&1 | tail -50"

# Alternative: Use artisan test
ddev artisan test --filter=ListeningParty

# Full test suite
ddev artisan test

# ❌ Wrong - breaks database connection
vendor/bin/pest tests/Feature/ListeningPartyTest.php
```

### PowerShell Output Issues
PowerShell shows DDEV startup messages that obscure test results:

**Solution 1: Use tail** (recommended)
```powershell
ddev exec bash -c "vendor/bin/pest tests/Feature/ArticleTest.php 2>&1 | tail -50"
```

**Solution 2: Redirect to file**
```powershell
ddev exec bash -lc "vendor/bin/pest tests/Feature/ArticleTest.php" > test-results.txt
Get-Content test-results.txt
```

**Solution 3: SSH into container**
```powershell
ddev ssh
vendor/bin/pest tests/Feature/ArticleTest.php
exit
```

---

## Common DDEV Commands

### Artisan
```powershell
ddev artisan migrate
ddev artisan migrate:fresh --seed
ddev artisan make:model Podcast -mfs
ddev artisan tinker
ddev artisan queue:restart
```

### Composer
```powershell
ddev composer install
ddev composer require package/name
ddev composer update
```

### NPM
```powershell
ddev npm install
ddev npm run dev -- --host
ddev npm run build  # Only when hot reloading is not available
```

**Important:** `ddev npm run build` is **only needed** when:
- Vite dev server is NOT running (production builds)
- User explicitly requests a production build
- **DO NOT run** if Vite hot reloading is active (default with `ddev start`)
- Vite HMR automatically picks up JS/CSS changes when running

### Database
```powershell
# PostgreSQL CLI
ddev psql

# Or use pgAdmin/other GUI tools with:
# Host: 127.0.0.1
# Port: (run `ddev describe` to get port)
# User: db
# Password: db
# Database: db

# Export database
ddev export-db --file=backup.sql.gz

# Import database
ddev import-db --file=backup.sql.gz

# Check PostGIS extensions
ddev exec psql -c '\dx'

# Enable PostGIS (if not already enabled)
ddev exec psql -c "CREATE EXTENSION IF NOT EXISTS postgis;"
ddev exec psql -c "CREATE EXTENSION IF NOT EXISTS postgis_topology;"
```

---

## Debugging

### View Logs
```powershell
# Application logs
ddev exec bash -c "tail -n 50 storage/logs/laravel.log"

# DDEV logs
ddev logs

# Follow logs in real-time
ddev logs -f
```

### Access Database
```powershell
# PostgreSQL CLI
ddev psql

# List databases
ddev exec psql -c '\l'

# List tables
ddev exec psql -c '\dt'

# Check PostGIS extensions
ddev exec psql -c '\dx'

# Or use GUI tools (pgAdmin, DBeaver, etc.) with:
# Host: 127.0.0.1
# Port: (run `ddev describe` to get port)
# User: db
# Password: db
# Database: db
```

### SSH into Container
```powershell
ddev ssh

# Now you're inside the container, run commands without ddev prefix
php artisan tinker
vendor/bin/pest
tail -f storage/logs/laravel.log

# Exit
exit
```

### Cache Management
```powershell
ddev artisan optimize:clear
ddev artisan config:clear
ddev artisan cache:clear
ddev artisan view:clear
```

---

## Vite with DDEV

### Development Mode (Hot Module Replacement)
Vite **auto-starts** with DDEV via `web_extra_daemons`:

```powershell
# Check if running
ddev exec bash -c "ps aux | grep vite | grep -v grep"

# Manually start if needed (rarely required)
ddev npm run dev -- --host
```

**Important:**
- ✅ Vite HMR is **always active** after `ddev start`
- ✅ JS/CSS changes are **auto-reloaded** in browser
- ❌ **DO NOT run `ddev npm run build`** during development
- ⚠️ Only build for production or when Vite is not running

### Production Build
```powershell
# Only when deploying or Vite dev server is stopped
ddev npm run build
```

### Vite Configuration
Ensure `vite.config.js` has:
```js
export default defineConfig({
    server: {
        host: '0.0.0.0', // Allow external connections
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost', // Or your DDEV hostname
        },
    },
});
```

---

## Common Issues

| Issue | Solution |
|-------|----------|
| Tests fail with DB error | Run through DDEV: `ddev artisan test` |
| Command chaining fails | Don't use `&&` in PowerShell, run separately |
| `tail` command fails | Use `ddev exec bash -c "tail ..."` |
| Queue not processing | `ddev artisan queue:restart` |
| Vite not connecting | Check `ddev npm run dev -- --host` is running |
| Port already in use | `ddev stop` then `ddev start` |

---

## PostgreSQL with PostGIS

This project uses PostgreSQL 16 with PostGIS extension for spatial/GIS data.

### PostGIS Features Available
- **Geometry types:** POINT, LINESTRING, POLYGON, MULTIPOINT, MULTILINESTRING, MULTIPOLYGON
- **Geography types:** For earth-surface calculations
- **Spatial functions:** ST_Contains, ST_Within, ST_Distance, ST_Buffer, etc.
- **Spatial indexing:** GIST indexes for performance

### Common PostGIS Queries
```sql
-- Check PostGIS version
SELECT PostGIS_version();

-- Create a point (latitude, longitude)
INSERT INTO locations (name, coordinates) 
VALUES ('Sample Location', ST_SetSRID(ST_MakePoint(-122.4194, 37.7749), 4326));

-- Find locations within 1000 meters
SELECT * FROM locations 
WHERE ST_DWithin(
    coordinates::geography,
    ST_SetSRID(ST_MakePoint(-122.4194, 37.7749), 4326)::geography,
    1000
);

-- Calculate distance between two points (in meters)
SELECT ST_Distance(
    ST_SetSRID(ST_MakePoint(-122.4194, 37.7749), 4326)::geography,
    ST_SetSRID(ST_MakePoint(-122.4189, 37.7750), 4326)::geography
);
```

### Migration Examples with PostGIS
```php
// In migration file
Schema::create('locations', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->geography('coordinates', 'point', 4326); // POINT with SRID 4326 (WGS84)
    $table->timestamps();
    
    // Spatial index for performance
    $table->spatialIndex('coordinates');
});

// For polygons (areas)
Schema::create('survey_areas', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->geography('area', 'polygon', 4326);
    $table->timestamps();
    
    $table->spatialIndex('area');
});
```

### Working with PostGIS in Laravel
```php
// Using raw queries
$locations = DB::select("
    SELECT *, ST_AsText(coordinates) as coords_text
    FROM locations 
    WHERE ST_DWithin(
        coordinates::geography,
        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
        ?
    )
", [$longitude, $latitude, $radiusInMeters]);

// Or use Laravel-PostGIS package (if installed)
$location = Location::whereWithin('coordinates', $polygon)->get();
```

---

## DDEV Configuration Files

- `.ddev/config.yaml` - Main DDEV configuration (database: postgres:16)
- `.ddev/postgres/Dockerfile.postgres` - Custom PostgreSQL build (PostGIS installation)
- `.ddev/postgres/enable-postgis.sql` - PostGIS extension initialization
- `.ddev/web_extra_daemons/` - Auto-start services (queue, vite)
- `.ddev/php/` - PHP configuration overrides

---

## Important Notes

- ⚠️ **ALL commands must use `ddev` prefix** when working with PHP/Composer/NPM
- ⚠️ **PowerShell cannot chain with `&&`** - run commands separately or use bash
- ⚠️ **Tests must run through DDEV** - database connection requires it
- ⚠️ **Use `ddev artisan queue:restart`** not `ddev restart` for queue changes
- ✅ **Queue worker and Vite auto-start** when you run `ddev start`
- ✅ **PostGIS is enabled** - use spatial queries and geometry types
- ✅ **Database is PostgreSQL 16** - use `ddev psql` (not `ddev mysql`)

