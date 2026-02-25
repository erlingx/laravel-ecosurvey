# Neon Unarchive Instructions - Visual Guide

## ❌ What You're Seeing (Not Working)

When you click "Connect" button, you see:
```
Connection details for production
This branch is archived. Connecting to the branch will unarchive it.
[Connection details popup - no confirm button]
```

**Problem:** This is just showing connection info, not actually unarchiving.

---

## ✅ How to Actually Unarchive

The popup message says: **"Connecting to the branch will unarchive it."**

This means: **Making an actual database connection** will trigger the unarchive, not just clicking "Connect" in the UI.

### Method 1: Use the `psql` Command from Popup

1. **In the popup**, look for the **`psql`** connection string
2. **Copy the full command** (it will look like):
   ```bash
   psql postgresql://neondb_owner:YOUR_PASSWORD@ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432/neondb?sslmode=require
   ```

3. **Close the popup**

4. **SSH to your production server:**
   ```bash
   ssh overstimulated.dk@linux216.unoeuro.com
   cd laravel-ecosurvey
   ```

5. **Test the connection** (this will unarchive):
   ```bash
   # Using Laravel Tinker (easier)
   php artisan tinker --execute="DB::connection()->getPdo(); echo 'Unarchived!';"
   ```

   **What happens:**
   - First connection attempt will fail (branch is archived)
   - Neon detects the connection attempt
   - Neon automatically unarchives the branch
   - Takes 30-60 seconds to activate
   - Retry connection - should work!

---

### Method 2: Look for "Resume" or "Activate" Button

In the Neon dashboard, there might be a different button:

1. **Go to Branches page**
2. **Find your `production` branch**
3. **Look for buttons:**
   - "Resume"
   - "Activate"
   - "Wake up"
   - Three-dot menu (⋮) with "Unarchive" option

4. **Click that button** (not "Connect")

---

### Method 3: Check Compute Settings

The popup shows: **Compute: Primary - Idle**

This suggests the compute endpoint exists but is idle/suspended.

1. **In Neon dashboard**, go to: **Settings → Compute**
2. **Find your compute endpoint** (should show "Primary")
3. **Look for:**
   - "Start" button
   - "Resume" button
   - "Status" toggle

4. **Click to activate** the compute endpoint

---

## 🚀 Quick Test Script (Run on Production)

This will attempt connection and show you exactly what's happening:

```bash
#!/bin/bash
# Run on production: overstimulated.dk@linux216.unoeuro.com

echo "Attempting to unarchive Neon database by connecting..."
echo ""

# Attempt 1
echo "🔄 Attempt 1: Connecting to database..."
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo '✅ Connected! Branch is unarchived.\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Failed: ' . \$e->getMessage() . '\n';
    echo '\nThis is expected if branch is archived.\n';
    echo 'Neon is now starting the compute endpoint...\n';
    exit(1);
}
" 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Success! Database is online."
    exit 0
fi

echo ""
echo "⏳ Waiting 30 seconds for compute endpoint to activate..."
sleep 30

# Attempt 2
echo ""
echo "🔄 Attempt 2: Connecting to database..."
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo '✅ Connected! Branch is now unarchived and active.\n';
    exit(0);
} catch (Exception \$e) {
    echo '❌ Still failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
" 2>&1

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Success! Database is online."
    echo ""
    echo "Next: Run recovery commands..."
    exit 0
else
    echo ""
    echo "❌ Still not connecting. Wait 60 more seconds and try again."
    echo ""
    echo "Or check Neon dashboard for manual activation options."
    exit 1
fi
```

Save this as `test-unarchive.sh` and run:
```bash
bash test-unarchive.sh
```

---

## 🎯 Recommended: Just Try Connecting from Production

The simplest approach - let the connection attempt trigger the unarchive:

```bash
# SSH to production
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey

# Try connecting (this triggers unarchive)
php artisan tinker

# In tinker, run:
DB::connection()->getPdo();

# You'll likely see an error about archived branch
# Wait 30-60 seconds, then try again:
DB::connection()->getPdo();

# Should work now! Exit tinker:
exit
```

Then continue with cache clearing:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
rm -f public/debug-db.php
```

---

## 📞 Alternative: Neon UI Navigation

If you're still stuck in the UI:

1. **Close the connection details popup**
2. **Look at the branch list** - there should be a status indicator
3. **Check for these UI elements:**
   - Status badge showing "Archived" with an action icon
   - Three-dot menu (⋮) next to the branch name
   - "Actions" dropdown menu
   - Compute endpoint status with "Resume" option

4. **Screenshot the branch list page** if you're unsure - the unarchive action is there somewhere

---

## 🔍 What to Look For in Neon Dashboard

**Branches Page:**
```
Branch Name | Status    | Compute  | Actions
------------|-----------|----------|----------
production  | Archived  | Idle     | [⋮] Menu
  ↑                                   ↑
  Click here                    Or click here
```

**Compute Section (Settings → Compute):**
```
Compute Endpoint: Primary
Status: [Suspended] [Resume Button] ← Click this
Auto-suspend delay: [___] minutes
```

---

## ⚡ TL;DR - Do This Now:

1. **SSH to production server**
2. **Run:** `php artisan tinker`
3. **Run:** `DB::connection()->getPdo();`
4. **Expected:** Error about archived/suspended
5. **Wait:** 30-60 seconds
6. **Run again:** `DB::connection()->getPdo();`
7. **Expected:** Success! ✅

The first connection attempt triggers Neon to unarchive the branch automatically.

Then configure auto-suspend in Neon dashboard: Settings → Compute → 5 minutes

---

**Next Action:** SSH to production and attempt connection - it will trigger the unarchive!

