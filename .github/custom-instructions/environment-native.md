# Environment: Native PHP (No DDEV/Sail)

**Type:** Native local development  
**OS:** Windows 11 + PowerShell

---

## Command Execution

### Standard Laravel Commands
```powershell
# Artisan
php artisan migrate
php artisan test
php artisan queue:restart

# Composer
composer install
composer require package/name
composer run dev

# NPM
npm install
npm run dev
npm run build

# Pest
vendor/bin/pest
vendor/bin/pest --filter=TestName
```

### PowerShell Command Chaining
```powershell
# ✅ Use semicolon to chain commands
php artisan migrate; php artisan db:seed

# ✅ PowerShell native commands work
Get-Content storage/logs/laravel.log -Tail 50
Select-String -Pattern "error" -Path storage/logs/laravel.log
```

---

## Development Workflow

### Quick Start All Services
```powershell
# Use the composer script (recommended):
composer run dev
```

This runs concurrently:
- `php artisan serve` - Laravel dev server
- `php artisan queue:listen --tries=1` - Queue worker
- `npm run dev` - Vite dev server with HMR

### Manual Start (for debugging)
```powershell
# Terminal 1
php artisan serve

# Terminal 2
php artisan queue:work --verbose

# Terminal 3
npm run dev
```

---

## Queue Management

### Restart After Code Changes
```powershell
# Fast restart (recommended):
php artisan queue:restart

# The queue worker will restart after finishing current job (1-3 seconds)
```

### Check Queue Status
```powershell
php artisan queue:monitor database
php artisan queue:failed
```

---

## Testing

### Run Tests
```powershell
# All tests
php artisan test

# Specific file
php artisan test tests/Feature/ListeningPartyTest.php

# Filtered
php artisan test --filter=ListeningParty

# Using Pest directly
vendor/bin/pest
vendor/bin/pest tests/Feature/ListeningPartyTest.php
```

### Test Writing Guidelines
- Write **Pest tests** (not PHPUnit)
- Prefer **feature tests** over unit tests
- Test every behavioral change
- Use **factories** for model creation
- Use **datasets** for validation rule testing
- Run focused tests after changes: `php artisan test --filter=RelatedTest`

### Test Structure Best Practices
```php
// Use factories for setup
$podcast = Podcast::factory()->create();

// Check for custom factory states before manual setup
$user = User::factory()->admin()->create();

// Use datasets for validation testing
test('validates required fields', function ($field) {
    // test code
})->with(['title', 'description', 'url']);
```

### After Making Code Changes
1. **Identify affected tests** based on changes
2. **Run minimum necessary tests** using `--filter`
3. **Fix any failures** immediately
4. **Ask about full suite** if needed for regression testing

---

## Debugging

### View Logs
```powershell
# Tail logs
Get-Content storage/logs/laravel.log -Tail 50 -Wait

# Search logs
Select-String -Pattern "error" -Path storage/logs/laravel.log

# Clear logs
Clear-Content storage/logs/laravel.log
```

### Cache Management
```powershell
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## Debugging

### View Logs
```powershell
# Tail logs
Get-Content storage/logs/laravel.log -Tail 50 -Wait

# Search logs
Select-String -Pattern "error" -Path storage/logs/laravel.log

# Clear logs
Clear-Content storage/logs/laravel.log
```

### Cache Management
```powershell
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Tinker
```powershell
php artisan tinker
# Interactive PHP console
# Query models, test code, debug issues
```

---

## Common Issues

| Issue | Solution |
|-------|----------|
| Port already in use | Change port: `php artisan serve --port=8001` |
| Queue not processing | Run `php artisan queue:restart` |
| Vite not connecting | Check `npm run dev` is running |
| Frontend changes not visible | Vite should auto-reload; check console |
| Class not found | Run `composer dump-autoload` |
| Route not found | Check `php artisan route:list` |
| Vite manifest error | Run `npm run build` or ensure `npm run dev` is running |

---

## File Generation

### Artisan Make Commands
```powershell
# Model with migration, factory, seeder
php artisan make:model Podcast -mfs

# Resource controller
php artisan make:controller PodcastController --resource

# Form request
php artisan make:request StorePodcastRequest

# Pest test
php artisan make:test PodcastTest --pest

# Generic PHP class
php artisan make:class Services/PodcastService

# Livewire component
php artisan make:livewire Podcasts\Create

# Volt component
php artisan make:volt podcasts/create --test
```

---

## Important Notes

- ✅ **No DDEV prefix needed** - commands run directly
- ✅ **PowerShell works normally** - can use native commands
- ✅ **Fast iteration** - no container overhead
- ⚠️ **Ensure correct PHP version** (8.3+) is in PATH
- ⚠️ **Database must be configured** in `.env`

