# Neon Database Recovery Guide

**Date:** 2026-02-24  
**Issue:** Branch auto-archived on 2026-02-18 due to inactivity  
**Project:** ep-orange-breeze-a9xvfbuw (Azure West Central)

---

## 📊 Current Situation Analysis

### What Happened:
- **Auto-archived:** February 18, 2026 (6 days ago)
- **Reason:** Inactivity detection
- **Root cause:** Auto-suspend NOT configured → database ran 24/7 even with no traffic
- **Compute usage:** ~432 hours (18 days × 24 hours) since start of February
- **Free tier limit:** 300 hours/month

### Why This is Actually Good News:
✅ You didn't hit quota from active usage  
✅ Just need to configure auto-suspend properly  
✅ May NOT need paid plan for low-traffic site  
✅ Simple configuration fix prevents future issues

---

## 🎯 Recovery Steps (Choose Your Path)

### Path A: Free Tier with Auto-Suspend (Recommended for Low Traffic) ⭐

**Best for:** Sites with <100 active hours/month traffic

1. **Unarchive the Branch**
   - Go to: https://console.neon.tech/app/projects
   - Select: `ep-orange-breeze-a9xvfbuw`
   - Go to: **Branches** section
   - Find your production branch (likely `main`)
   - Click: **"Connect"** or **"Unarchive"** button
   - Wait: 2-3 minutes for compute activation

2. **Configure Auto-Suspend (CRITICAL!)**
   - Go to: **Settings → Compute**
   - Set **Auto-suspend delay**: `5 minutes` (minimum)
   - Click: **Save**
   
   **What this does:**
   - Database suspends after 5 min of no connections
   - Wakes automatically on first request (adds ~1-2 sec latency)
   - Saves 95%+ of compute hours for low-traffic sites
   - **Example:** 10 active hours/day = ~300 hours/month (within free tier!)

3. **Set Up Billing Alerts**
   - Go to: **Settings → Billing**
   - Enable: **Usage alerts**
   - Set threshold: 80% of free tier (240 hours)
   - You'll get email warning before hitting limit

4. **Verify on Production Server**
   ```bash
   ssh overstimulated.dk@linux216.unoeuro.com
   cd laravel-ecosurvey
   
   # Run recovery script
   bash scripts/neon-recovery.sh
   ```

---

### Path B: Upgrade to Paid Plan (For Peace of Mind)

**Best for:** Production sites that need guaranteed uptime

1. **Upgrade First**
   - Go to: **Settings → Billing**
   - Click: **Upgrade plan**
   - Choose: **Launch** ($19/month)
     - 300 hours included
     - $0.102/hour for overages
     - No downtime risk

2. **Then Unarchive**
   - Go to: **Branches**
   - Click: **"Unarchive"** on production branch
   - Wait 2-3 minutes

3. **Still Configure Auto-Suspend!**
   - Settings → Compute → Set to 5 minutes
   - Reduces costs during idle periods
   - Even paid plans benefit from this

4. **Verify on Production**
   ```bash
   ssh overstimulated.dk@linux216.unoeuro.com
   cd laravel-ecosurvey
   bash scripts/neon-recovery.sh
   ```

---

## 🔧 Detailed Configuration: Auto-Suspend

### What is Auto-Suspend?

**Without auto-suspend (your current setup):**
```
Database runs 24/7 = 720 hours/month
Free tier limit = 300 hours/month
Result: Quota exceeded in ~12 days ❌
```

**With auto-suspend (5 minutes):**
```
Database runs only when active
Example traffic pattern:
- 100 requests/day × 2 sec each = 3.3 minutes/day
- With 5-min keep-alive = ~10 active hours/day
- 10 hours/day × 30 days = 300 hours/month ✅
```

### Configuration Steps:

1. **Neon Dashboard:** https://console.neon.tech/app/projects
2. **Select project:** ep-orange-breeze-a9xvfbuw
3. **Navigate:** Settings → Compute
4. **Find:** "Auto-suspend delay"
5. **Set:** 5 minutes (or lower if available)
6. **Save changes**

### Understanding the Delay:

- **5 minutes:** Database stays active for 5 min after last connection closes
- **Trade-off:** 
  - Shorter delay = Less compute usage = Lower costs
  - First request after suspend = +1-2 sec latency (cold start)
- **Recommendation:** Start with 5 min, monitor, adjust if needed

---

## 📊 Compute Usage Estimates

### Your Traffic Profile (Estimated):

Based on EcoSurvey (survey platform) typical usage:

| Scenario | Daily Active Time | Monthly Hours | Free Tier? |
|----------|-------------------|---------------|------------|
| Development/Testing | 1-2 hours | 30-60 | ✅ Yes |
| Low traffic (50 users/day) | 8-12 hours | 240-360 | ⚠️ Tight |
| Medium traffic (200 users/day) | 20-24 hours | 600-720 | ❌ Need paid |
| High traffic (1000+ users/day) | 24 hours | 720 | ❌ Need paid |

**With auto-suspend @ 5 min:**
- Idle periods consume 0 hours
- Only active connection time counts
- Low-traffic sites easily stay within 300 hours

---

## 🚀 Production Recovery Checklist

### In Neon Dashboard:

- [ ] Unarchive production branch (Branches → Connect)
- [ ] Configure auto-suspend to 5 minutes (Settings → Compute)
- [ ] Enable usage alerts at 80% threshold (Settings → Billing)
- [ ] Verify compute endpoint is "Active" (not "Idle" or "Suspended")

### On Production Server:

```bash
# 1. SSH to server
ssh overstimulated.dk@linux216.unoeuro.com
cd laravel-ecosurvey

# 2. Test database connection
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Connected!';"

# 3. Clear all caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Remove debug file
rm -f public/debug-db.php

# 5. Test homepage
curl -I https://laravel-ecosurvey.overstimulated.dk/

# 6. Check Laravel logs
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Verification:

- [ ] Homepage loads (HTTP 200)
- [ ] Login functionality works
- [ ] Survey pages load
- [ ] No 500 errors
- [ ] Database queries execute successfully

---

## 📈 Monitoring Setup

### Check Usage Weekly:

1. **Neon Dashboard:** Settings → Usage
2. **Monitor:** Compute hours consumed
3. **Target:** Stay under 240 hours/month (80% of free tier)

### Set Calendar Reminders:

- **Weekly:** Check Neon usage dashboard
- **Monthly:** Review total compute hours
- **Before month-end:** Verify auto-suspend is working

### Signs Auto-Suspend is Working:

- Compute endpoint shows "Suspended" in dashboard when idle
- Usage graph shows gaps during idle periods
- First page load after idle is slightly slower (~1-2 sec)

---

## 🔄 If You Still Hit Quota Limits

### Troubleshooting:

1. **Check Connection Pooling:**
   - Ensure Laravel closes idle connections
   - Check `config/database.php` settings

2. **Verify Auto-Suspend:**
   - Settings → Compute → Confirm 5 min delay
   - Check usage graph for suspended periods

3. **Monitor Active Connections:**
   ```sql
   -- In Neon SQL editor or psql:
   SELECT count(*) FROM pg_stat_activity WHERE datname = 'neondb';
   ```

4. **Consider Connection Optimization:**
   ```php
   // config/database.php
   'pgsql' => [
       'driver' => 'pgsql',
       // ... other settings
       'pool' => [
           'max_connections' => 10,
           'min_connections' => 2,
       ],
   ],
   ```

### When to Upgrade:

If you consistently exceed 300 hours/month even with auto-suspend:

- **Launch Plan ($19/month):** For 300-750 hours
- **Scale Plan ($69/month):** For unlimited compute

---

## 🎯 Recommended Action Plan

### Right Now (Next 10 Minutes):

1. ✅ **Unarchive branch** in Neon dashboard
2. ✅ **Configure auto-suspend** to 5 minutes
3. ✅ **Enable billing alerts** at 80%
4. ✅ **Wait 3 minutes** for compute activation
5. ✅ **Test database** via SSH (tinker)
6. ✅ **Clear caches** on production
7. ✅ **Test site** in browser

### This Week:

- Monitor usage daily for first 3 days
- Verify auto-suspend is triggering
- Confirm no 500 errors
- Test all key functionality

### This Month:

- Check usage weekly
- Should stay well under 300 hours with auto-suspend
- Upgrade to paid plan if needed

---

## 📞 Support Resources

- **Neon Dashboard:** https://console.neon.tech/app/projects
- **Neon Docs - Auto-Suspend:** https://neon.tech/docs/guides/auto-suspend-guide
- **Neon Docs - Compute:** https://neon.tech/docs/introduction/compute
- **Neon Pricing:** https://neon.tech/pricing
- **Support:** support@neon.tech

---

## 🎉 Expected Outcome

After completing these steps:

✅ Site back online within 5-10 minutes  
✅ Database auto-suspends during idle periods  
✅ Compute usage drops to ~50-150 hours/month  
✅ Stays within free tier limits  
✅ No more unexpected downtime  
✅ First-load latency: +1-2 seconds (acceptable for low-traffic)

---

## 💡 Pro Tips

1. **Monitor first month closely** - Adjust auto-suspend if needed
2. **Set up uptime monitoring** - Use UptimeRobot (free) to alert on downtime
3. **Cache aggressively** - Reduce database queries where possible
4. **Consider read replicas** (paid plans) for scaling if traffic grows
5. **Document your setup** - Keep notes on configuration for future reference

---

**Next Action:** Unarchive branch + Configure auto-suspend → Site online in 5 minutes!

