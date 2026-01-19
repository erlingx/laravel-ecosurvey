# Fast Testing Cheat Sheet - Laravel EcoSurvey

**Quick Reference for Incremental Testing**

---

## 🚀 Fastest Commands (Use These During Development)

### Single Test (2-5 seconds)
```bash
ddev artisan test --filter="detects high GPS error" --compact
```

### Single File (1-2 minutes)
```bash
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact
```

### Last Failed Tests Only
```bash
ddev artisan test --failed --compact
```

---

## 📋 Common Test Patterns

### Phase 9 Quality Check Tests
```bash
# All Quality Check tests
ddev artisan test --filter=QualityCheck --compact

# Specific test
ddev artisan test --filter="detects high GPS error" --compact
ddev artisan test --filter="auto-approves qualified" --compact
ddev artisan test --filter="flags suspicious" --compact
```

### By Feature Area
```bash
# Data collection tests
ddev artisan test tests/Feature/DataCollection --compact

# Map tests
ddev artisan test tests/Feature/Maps --compact

# Satellite tests
ddev artisan test tests/Feature/Satellite --compact

# Service tests
ddev artisan test tests/Feature/Services --compact
```

### By Test Type
```bash
# Feature tests only
ddev artisan test tests/Feature --compact

# Unit tests only
ddev artisan test tests/Unit --compact

# Specific feature
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact
```

---

## ⚡ Direct Pest Runner (Even Faster)

Skip Laravel artisan overhead:

```bash
# Run single file
ddev exec vendor/bin/pest tests/Feature/Services/QualityCheckServiceTest.php

# Filter test
ddev exec vendor/bin/pest --filter="detects high GPS error"

# With compact output
ddev exec bash -c "vendor/bin/pest tests/Feature/Services/QualityCheckServiceTest.php 2>&1 | tail -30"
```

---

## 🔍 Debugging Failed Tests

### Get More Details
```bash
# Verbose output
ddev artisan test --filter="test name" -vvv

# Stop on first failure
ddev artisan test --stop-on-failure

# Show test execution times
ddev artisan test --profile
```

### Run with DD/Dump
```php
// In your test file
test('something', function () {
    $result = someFunction();
    dd($result); // Dies and dumps
    // or
    dump($result); // Dumps but continues
    
    expect($result)->toBe('expected');
});
```

---

## 🎯 Workflow Examples

### Developing a New Feature
```bash
# 1. Write test, run it (fails)
ddev artisan test --filter="my new test" --compact

# 2. Write code, run test (passes)
ddev artisan test --filter="my new test" --compact

# 3. Run all related tests
ddev artisan test tests/Feature/Services/MyServiceTest.php --compact

# 4. Before commit - run all affected tests
ddev artisan test --filter=MyFeature --compact
```

### Fixing a Bug
```bash
# 1. Run test that exposes bug (fails)
ddev artisan test --filter="bug test" --compact

# 2. Fix code, run test (passes)
ddev artisan test --filter="bug test" --compact

# 3. Run related tests to ensure no regression
ddev artisan test tests/Feature/BugArea --compact

# 4. Run full suite before push
ddev artisan test --compact
```

### Refactoring Code
```bash
# 1. Run all tests for area being refactored
ddev artisan test tests/Feature/Services --compact

# 2. Make refactoring changes

# 3. Re-run same tests
ddev artisan test tests/Feature/Services --compact

# 4. If all pass, run full suite
ddev artisan test --compact
```

---

## 📊 Test Execution Times

**Typical Times (on DDEV):**

| Command | Time | When to Use |
|---------|------|-------------|
| `--filter="single test"` | 2-5s | Developing specific feature |
| Single file | 1-2min | Testing one service/feature |
| `--filter=Feature` | 30-60s | Testing related functionality |
| `tests/Feature` | 2-3min | Before commit |
| Full suite | 5-10min | Before push / CI |

---

## 🛠️ Optimization Tips

### 1. Use --compact Flag
Always use `--compact` for cleaner output:
```bash
ddev artisan test --compact
```

### 2. Cache Configuration
```bash
# Clear all caches before testing
ddev artisan optimize:clear

# Or just config cache
ddev artisan config:clear
```

### 3. Fresh Test Database
```bash
# If tests are acting weird, refresh test DB
ddev artisan migrate:fresh --env=testing
```

### 4. Skip Slow Tests During Development
Tag slow tests:
```php
test('slow integration test')
    ->group('slow', 'integration')
    ->skip('Run manually before commit');
```

Then run fast tests only:
```bash
ddev artisan test --exclude-group=slow --compact
```

---

## 🏃 Watch Mode (Auto-Run Tests)

Install watch plugin:
```bash
ddev composer require pestphp/pest-plugin-watch --dev
```

Use watch mode:
```bash
# Auto-run tests when files change
ddev exec vendor/bin/pest --watch

# Watch specific directory
ddev exec vendor/bin/pest tests/Feature/Services --watch
```

**How it works:**
- Edit test file → Test runs automatically
- Edit source file → Related tests run automatically
- Instant feedback!

---

## 🎮 Keyboard Shortcuts (PhpStorm)

**Set up run configurations in PhpStorm:**

1. **Run → Edit Configurations**
2. **Add New → PHPUnit**
3. **Set:**
   - Test scope: File/Directory/Filter
   - Command: `ddev artisan test --compact`
   - Working directory: Project root

**Then:**
- `Shift + F10` - Run last test configuration
- `Ctrl + Shift + F10` - Run current test file
- `Ctrl + Shift + R` - Re-run last tests

---

## 📝 Example: Testing Quality Check Service

```bash
# Start with one test
ddev artisan test --filter="detects high GPS error" --compact
# ✓ passes in 3 seconds

# Run the whole file
ddev artisan test tests/Feature/Services/QualityCheckServiceTest.php --compact
# ✓ 8 tests pass in ~2 minutes

# Run all quality-related tests
ddev artisan test --filter=Quality --compact
# ✓ All quality tests pass in ~3 minutes

# Before commit - run feature tests
ddev artisan test tests/Feature --compact
# ✓ All features pass in ~5 minutes
```

---

## 🔧 Troubleshooting

### Tests are still slow?

**Check:**
1. Database seeding in tests (use minimal data)
2. Too many factory creations
3. Not using RefreshDatabase efficiently
4. External API calls (mock them)

**Profile tests:**
```bash
ddev artisan test --profile
```

### Tests hang/timeout?

**Check:**
1. Database connections
2. Queue workers interfering
3. beforeEach creating too much data

**Debug:**
```bash
# Run with verbose output
ddev artisan test --filter="hanging test" -vvv

# Check logs
tail -f storage/logs/laravel.log
```

### Database errors in tests?

**Fix:**
```bash
# Ensure test DB is fresh
ddev artisan migrate:fresh --env=testing --force

# Check test database exists
ddev exec psql -U db -d testing -c '\dt' | cat
```

---

## 💡 Pro Tips

1. **Use `--filter` liberally** - Test exactly what you're working on
2. **Run failed tests first** - `--failed` flag saves time
3. **Use `--compact`** - Cleaner output, easier to read
4. **Tag tests with groups** - Organize by speed/type/feature
5. **Set up PhpStorm shortcuts** - One-key test execution
6. **Use watch mode** - Automatic testing while coding
7. **Profile periodically** - Find and fix slow tests
8. **Mock external services** - Tests should be isolated
9. **Keep test data minimal** - Only create what you need
10. **Run full suite in CI** - Not on every local change

---

## 🎯 Quick Command Reference

```bash
# Fastest - single test
ddev artisan test --filter="test name" --compact

# Fast - single file
ddev artisan test path/to/TestFile.php --compact

# Medium - feature tests
ddev artisan test tests/Feature --compact

# Slow - full suite
ddev artisan test --compact

# Debug - verbose single test
ddev artisan test --filter="test name" -vvv

# Fix - run only failed
ddev artisan test --failed --compact

# Profile - find slow tests
ddev artisan test --profile
```

---

**Remember:** Fast feedback = faster development! 🚀

Test incrementally, commit with confidence, push when ready.
