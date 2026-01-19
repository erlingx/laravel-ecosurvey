# Environment: DDEV

**Type:** Docker-based local development (DDEV)  
**OS:** Windows 11  
**Recommended Shell:** Git Bash (preferred over PowerShell for better DDEV compatibility)

---

## Shell Configuration

### PhpStorm Terminal Setup (IMPORTANT)
For best DDEV experience, configure PhpStorm to use Git Bash:

1. Open PhpStorm Settings: `File → Settings → Tools → Terminal`
2. Set Shell path to: `C:\Program Files\Git\bin\bash.exe`
3. Restart PhpStorm

**Why Git Bash is preferred:**
- ✅ No output buffering issues (see test results immediately)
- ✅ Native Unix command support (`tail`, `grep`, `sed`, etc.)
- ✅ Command chaining works (`&&`, `||`, `;`)
- ✅ Better DDEV compatibility (DDEV expects Unix-like environment)
- ✅ Clean terminal output without DDEV startup messages

**PowerShell Issues:**
- ❌ Output buffering causes delayed/missing test results
- ❌ Unix commands don't exist (`tail`, `head`, `grep`)
- ❌ Command chaining (`&&`, `||`) fails or behaves unexpectedly
- ❌ DDEV output gets mixed with container logs

---

## Command Execution Rules

### All Commands MUST Use DDEV Prefix

```bash
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

## PowerShell Limitations (Use Git Bash Instead)

**⚠️ Recommendation: Use Git Bash terminal instead of PowerShell (see Shell Configuration above)**

If you must use PowerShell, be aware of these limitations:

### Cannot Chain Commands with && or ||
PowerShell doesn't support these operators properly:

```powershell
# ❌ Wrong - fails in PowerShell
ddev artisan migrate && ddev artisan db:seed

# ✅ In Git Bash - works perfectly
ddev artisan migrate && ddev artisan db:seed

# ✅ In PowerShell - run separately
ddev artisan migrate
ddev artisan db:seed

# ✅ In PowerShell - chain inside bash
ddev exec bash -lc "php artisan migrate; php artisan db:seed"
```

### Unix Commands Need Bash Wrapper

```powershell
# ❌ Wrong - commands don't exist in PowerShell
ddev exec tail -n 50 storage/logs/laravel.log
ddev exec cat file.txt | grep "search"

# ✅ In Git Bash - native support
ddev exec tail -n 50 storage/logs/laravel.log
ddev exec cat file.txt | grep "search"

# ✅ In PowerShell - wrap in bash -c
ddev exec bash -c "tail -n 50 storage/logs/laravel.log"
ddev exec bash -c "grep 'search' file.txt"

# ✅ PowerShell alternatives (less convenient)
Get-Content storage/logs/laravel.log -Tail 50
Select-String -Pattern "search" -Path file.txt
```

### Exiting Pagers (less, more)

When commands open a pager (shows `(END)` at bottom), you cannot see output in AI tools:

**How to Exit Pager:**
- Press **`q`** (quit) to exit and return to prompt

**Avoid Pagers in Commands:**
```bash
# ❌ May open pager
ddev exec psql -c '\d table_name'
git log
git diff

# ✅ Prevent pager - use | cat
ddev exec bash -c "psql -c '\d table_name' | cat"
git --no-pager log
git --no-pager diff

# ✅ Limit output - use | tail or | head
ddev exec bash -c "psql -c '\d table_name' | tail -50"
git log --oneline | head -20
```

**For AI Assistants:**
- Always use `| cat`, `| tail`, or `| head` to avoid pagers
- Use `--no-pager` flag for git commands
- If user reports `(END)` in terminal, instruct them to press `q`

### PowerShell Output Buffering Issue (CRITICAL)

**Problem:** PowerShell buffers output from DDEV commands, causing delayed or missing output in PhpStorm terminal and GitHub Copilot tool responses.

**Symptoms:**
- Test output not visible until command fully completes
- `get_terminal_output` returns empty/truncated results
- Long-running commands appear frozen

**✅ BEST SOLUTION: Use Git Bash (see Shell Configuration above)**

**PowerShell Workarounds (not recommended):**

#### 1. Redirect to File (PowerShell only)
For Copilot/automation, redirect output to file then read:
```powershell
ddev artisan test --filter=MyTest > test-output.txt 2>&1
Start-Sleep -Seconds 5
Get-Content test-output.txt
```

#### 2. Use stdbuf (PowerShell only)
Force line-buffered output:
```powershell
ddev exec bash -c "stdbuf -oL -eL php artisan test --filter=MyTest"
```

#### 3. PowerShell Helper Functions (PowerShell only)
Load helper functions (see `.ddev/powershell-fix.ps1`):
```powershell
. .\.ddev\powershell-fix.ps1
ddev-test --filter=MyTest
```

**For AI Assistants:** 
- **Prefer Git Bash** - Recommend users switch to Git Bash if using PowerShell
- When running DDEV commands via `run_in_terminal` in PowerShell, be aware that buffering may cause empty `Output` fields
- Solutions: Use file redirection method for reliable output capture, or flag to user when terminal output appears empty
- **Always check if output is empty and flag it to the user**

See `.ddev/POWERSHELL-OUTPUT-FIX.txt` for detailed troubleshooting (PowerShell legacy support).

---

## Development Workflow

### Quick Start
```bash
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
```bash
ddev start
```

This auto-starts (via `web_extra_daemons`):
- PHP-FPM web server
- Queue worker (`php artisan queue:work`)
- Vite dev server (`npm run dev`)

### Stop DDEV
```bash
ddev stop
```

### After Code Changes
```bash
# Queue/Job changes - fast restart (1-3 seconds)
ddev artisan queue:restart

# Frontend changes (JS/CSS) - auto-reload via Vite HMR (already running)
# No action needed! Changes are hot-reloaded automatically.
# DO NOT run `ddev npm run build` - Vite handles it!

# ❌ NEVER restart entire DDEV for queue changes (too slow - 30+ seconds)
# ❌ NEVER run npm run build when Vite dev server is running
```

### Check Running Services
```bash
# Check queue worker and vite are running
ddev exec bash -c "ps aux | grep -E 'queue:work|vite' | grep -v grep"
```

---

## Queue Management

### Restart After Code Changes
```bash
# Fast restart (recommended):
ddev artisan queue:restart

# Worker restarts after finishing current job (1-3 seconds)
```

**DO NOT restart entire DDEV** - it's too slow (30+ seconds)!

### Check Queue Status
```bash
ddev artisan queue:monitor database
ddev artisan queue:failed
```

### Manual Queue Worker (for debugging)
```bash
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

```bash
# Git Bash - clean output, no buffering ✅
ddev exec bash -c "vendor/bin/pest tests/Feature/ListeningPartyTest.php 2>&1 | tail -50"

# Alternative: Use artisan test
ddev artisan test --filter=ListeningParty

# Full test suite
ddev artisan test

# ❌ Wrong - breaks database connection
vendor/bin/pest tests/Feature/ListeningPartyTest.php
```

### Git Bash vs PowerShell for Testing

**Git Bash (Recommended):**
```bash
# ✅ Clean output, immediate results
ddev artisan test --filter=MyTest

# ✅ Unix tools work natively
ddev exec bash -c "vendor/bin/pest tests/Feature/ArticleTest.php 2>&1 | tail -50"
```

**PowerShell (Legacy - Not Recommended):**
PowerShell shows DDEV startup messages that obscure test results. Workarounds:

**Workaround 1: Use tail**
```powershell
ddev exec bash -c "vendor/bin/pest tests/Feature/ArticleTest.php 2>&1 | tail -50"
```

**Workaround 2: Redirect to file**
```powershell
ddev exec bash -lc "vendor/bin/pest tests/Feature/ArticleTest.php" > test-results.txt
Get-Content test-results.txt
```

**Workaround 3: SSH into container**
```bash
ddev ssh
vendor/bin/pest tests/Feature/ArticleTest.php
exit
```

---

## Common DDEV Commands

### Artisan
```bash
ddev artisan migrate
ddev artisan migrate:fresh --seed
ddev artisan make:model Podcast -mfs
ddev artisan tinker
ddev artisan queue:restart
```

### Composer
```bash
ddev composer install
ddev composer require package/name
ddev composer update
```

### NPM
```bash
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
```bash
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
```bash
# Application logs
ddev exec bash -c "tail -n 50 storage/logs/laravel.log"

# DDEV logs
ddev logs

# Follow logs in real-time
ddev logs -f
```

### Access Database
```bash
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
```bash
ddev ssh

# Now you're inside the container, run commands without ddev prefix
php artisan tinker
vendor/bin/pest
tail -f storage/logs/laravel.log

# Exit
exit
```

### Cache Management
```bash
ddev artisan optimize:clear
ddev artisan config:clear
ddev artisan cache:clear
ddev artisan view:clear
```

---

## Vite with DDEV

### Development Mode (Hot Module Replacement)
Vite **auto-starts** with DDEV via `web_extra_daemons`:

```bash
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
```bash
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
| Command chaining fails | Use Git Bash instead of PowerShell |
| `tail` command fails | Use Git Bash instead of PowerShell, or wrap: `ddev exec bash -c "tail ..."` |
| Queue not processing | `ddev artisan queue:restart` |
| Vite not connecting | Check `ddev npm run dev -- --host` is running |
| Port already in use | `ddev stop` then `ddev start` |
| Terminal buffering issues | Switch to Git Bash (see Shell Configuration) |

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

- ✅ **Use Git Bash terminal** for best DDEV experience (see Shell Configuration above)
- ⚠️ **ALL commands must use `ddev` prefix** when working with PHP/Composer/NPM
- ⚠️ **Avoid PowerShell** - it has output buffering and command compatibility issues
- ⚠️ **Tests must run through DDEV** - database connection requires it
- ⚠️ **Use `ddev artisan queue:restart`** not `ddev restart` for queue changes
- ✅ **Queue worker and Vite auto-start** when you run `ddev start`
- ✅ **PostGIS is enabled** - use spatial queries and geometry types
- ✅ **Database is PostgreSQL 16** - use `ddev psql` (not `ddev mysql`)

