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
ddev npm run build
```

### Database
```powershell
ddev mysql
ddev export-db --file=backup.sql.gz
ddev import-db --file=backup.sql.gz
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
# MySQL CLI
ddev mysql

# Or use GUI tools with:
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

### Development Mode
Vite auto-starts with DDEV via `web_extra_daemons`:

```powershell
# Check if running
ddev exec bash -c "ps aux | grep vite | grep -v grep"

# Manually start if needed
ddev npm run dev -- --host
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

## DDEV Configuration Files

- `.ddev/config.yaml` - Main DDEV configuration
- `.ddev/web_extra_daemons/` - Auto-start services (queue, vite)
- `.ddev/php/` - PHP configuration overrides

---

## Important Notes

- ⚠️ **ALL commands must use `ddev` prefix** when working with PHP/Composer/NPM
- ⚠️ **PowerShell cannot chain with `&&`** - run commands separately or use bash
- ⚠️ **Tests must run through DDEV** - database connection requires it
- ⚠️ **Use `ddev artisan queue:restart`** not `ddev restart` for queue changes
- ✅ **Queue worker and Vite auto-start** when you run `ddev start`

