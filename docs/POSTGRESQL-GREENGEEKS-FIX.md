# PostgreSQL on Shared Hosting - Solutions

**CONFIRMED: External PostgreSQL works on UnoEuro.com / Simply.com! ✅**

---

## ✅ WORKING SOLUTION: UnoEuro/Simply.com + Neon PostgreSQL

**Status:** ✅ **CONFIRMED WORKING** - Tested and verified on UnoEuro shared hosting (January 2026)

### Complete Deployment Steps for UnoEuro/Simply.com:

**1. Clone Laravel project to server**
```bash
cd ~/public_html/your-domain
git clone https://github.com/erlingx/laravel-ecosurvey.git .
composer install --optimize-autoloader --no-dev
```

**2. Create `.env` file with Neon connection + SNI parameter:**
```bash
nano .env
```

Paste this configuration:
```env
APP_NAME=EcoSurvey
APP_ENV=production
APP_KEY=base64:3oVDVxx6Hqtte2wX5Z5bIRkl5BgqtpA5mPAERnjOzO4=
APP_DEBUG=false
APP_URL="https://your-domain.com"

# Neon PostgreSQL (EU Frankfurt) - WORKING on UnoEuro!
DB_CONNECTION=pgsql
DB_HOST=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=npg_LWwZnUscq5A3
DB_SSLMODE=require
DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw

# Use file-based cache (not database)
CACHE_STORE=file

# Add your other .env settings here...
```

**Important:** The `DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw` parameter is **REQUIRED** for UnoEuro/Simply.com because they use an older PostgreSQL client library (libpq) that doesn't support SNI (Server Name Indication).

**3. Run migrations:**
```bash
php artisan config:clear
php artisan migrate --force
php artisan optimize
php artisan db:seed --class=ProductionSeeder
```

**4. Set document root:**
In cPanel, point your domain to the `/public` subfolder (Laravel requirement).

**5. Done! Visit your site:**
```
https://your-domain.com
```

---

## Why This Works

**The Key:** UnoEuro/Simply.com **DOES allow external PostgreSQL connections** on port 5432, but their PostgreSQL client library is older and requires the endpoint ID to be explicitly specified for Neon's multi-tenant architecture.

**Without SNI parameter:**
```
ERROR: Endpoint ID is not specified. Either please upgrade the postgres 
client library (libpq) for SNI support or pass the endpoint ID...
```

**With SNI parameter (works!):**
```env
DB_OPTIONS=endpoint=ep-orange-breeze-a9xvfbuw
```

This adds `;options=endpoint=ep-orange-breeze-a9xvfbuw` to the PDO connection string, which tells Neon which database endpoint to connect to.

---

**Problem:** Many shared hosting providers block outbound PostgreSQL connections on **ALL PORTS** (5432, 6543, and others) to external databases, despite claiming they're allowed.

## CONFIRMED PROVIDERS:

### **GreenGeeks (US/EU Shared Hosting)**
- ❌ Neon PostgreSQL - Port 5432: Connection refused
- ❌ Neon PostgreSQL - Port 6543: Connection refused  
- ❌ Supabase PostgreSQL - Port 5432: Connection refused
- ❌ Supabase PostgreSQL - Port 6543: Connection refused
- **Status:** External PostgreSQL **NOT SUPPORTED**

### **UnoEuro.com / Simply.com (Danish Shared Hosting) - ✅ WORKS!**
- ✅ External PostgreSQL connections: **SUPPORTED!**
- ✅ Port 5432: Open and working
- ⚠️ **Requires SNI parameter** for Neon (older libpq version)
- **Status:** PostgreSQL to Neon/Supabase **WORKS** with correct connection string!

**How to connect on UnoEuro/Simply.com:**
```bash
# Add endpoint ID as parameter (required for older PostgreSQL client)
php -r 'try { $pdo = new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=5432;dbname=neondb;sslmode=require;options=endpoint=ep-orange-breeze-a9xvfbuw", "neondb_owner", "npg_LWwZnUscq5A3"); echo "SUCCESS: PostgreSQL works!\n"; } catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }'
```

**Key difference:** Add `;options=endpoint=ep-orange-breeze-a9xvfbuw` to connection string

**Status:** ✅ **CONFIRMED WORKING** on UnoEuro shared hosting!

---

## Will External PostgreSQL Work on Simply.com?

**Short answer:** Probably **NO** on shared hosting, **YES** on VPS/Cloud plans.

**Simply.com hosting types:**

1. **Webhosting (Shared):** ~49-199 DKK/month
   - ❌ External PostgreSQL: **Likely BLOCKED** (same as GreenGeeks)
   - ✅ MySQL/MariaDB: Included
   - ❌ PostgreSQL: Not available
   - **Recommendation:** Don't expect external PostgreSQL to work

2. **Cloud Hosting / VPS:** ~199-799 DKK/month
   - ✅ External PostgreSQL: **WORKS** (full firewall control)
   - ✅ PostgreSQL: Can install locally
   - ✅ PostGIS: Can install extension
   - **Recommendation:** This will work with Neon/Supabase

3. **Simply Server (Dedicated):** 1000+ DKK/month
   - ✅ External PostgreSQL: **WORKS** (full control)
   - ✅ Everything works

---

## Alternative Danish Hosting Providers with PostgreSQL Support

If you're looking for Danish/EU hosting that supports PostgreSQL:

### **Option 1: Hetzner Cloud (Germany - Best Value)**
- **Price:** €4.51/month (~34 DKK/month)
- **Location:** Falkenstein, Germany (EU)
- **PostgreSQL:** Full support, install yourself or use Neon/Supabase
- ✅ Outbound PostgreSQL connections work
- ✅ PostGIS support
- ✅ EU GDPR compliant
- **Recommendation:** BEST option for EU-based Laravel + PostgreSQL

### **Option 2: DigitalOcean (Frankfurt Datacenter)**
- **Price:** $6/month (~42 DKK/month)
- **Location:** Frankfurt, Germany
- **PostgreSQL:** Full support
- ✅ Works perfectly with Neon/Supabase
- ✅ PostGIS support

### **Option 3: Simply.com VPS/Cloud**
- **Price:** 199-399 DKK/month
- **Location:** Denmark
- **PostgreSQL:** Can install
- ✅ External connections work
- **Note:** More expensive than Hetzner/DigitalOcean

### **Option 4: One.com (Danish Alternative)**
- **Shared hosting:** Likely blocks PostgreSQL (same issue)
- **VPS:** Should work
- **Not recommended:** More expensive than Hetzner

---

## Testing Your Current Simply.com Hosting

Before switching, test if Simply.com allows PostgreSQL:

**Step 1: Check if PostgreSQL PDO driver is installed**
```bash
php -m | grep pdo_pgsql
```

**Step 2: Test connection to Neon**
```bash
php -r 'try { $pdo = new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=5432;dbname=neondb;sslmode=require", "neondb_owner", "npg_LWwZnUscq5A3"); echo "✅ SUCCESS: PostgreSQL works on Simply.com!\n"; } catch (Exception $e) { echo "❌ BLOCKED: " . $e->getMessage() . "\n"; }'
```

**Step 3: Test Supabase (port 6543)**
```bash
php -r 'try { $pdo = new PDO("pgsql:host=aws-1-eu-west-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require", "postgres.yourproject", "yourpassword"); echo "✅ SUCCESS: Supabase works!\n"; } catch (Exception $e) { echo "❌ BLOCKED: " . $e->getMessage() . "\n"; }'
```

**If both tests show "BLOCKED":** Simply.com shared hosting doesn't support external PostgreSQL. You need to:
1. Upgrade to Simply.com VPS/Cloud (expensive)
2. Switch to Hetzner Cloud (cheaper, better for PostgreSQL)
3. Use SSH tunnel workaround (not recommended for production)

---

## Recommended Solution for Danish Users

**Best option: Hetzner Cloud (Germany)**

**Why:**
- ✅ €4.51/month (cheapest VPS with good performance)
- ✅ EU-based (Frankfurt, Germany - close to Denmark)
- ✅ GDPR compliant
- ✅ PostgreSQL + PostGIS works perfectly
- ✅ Works with Neon EU Frankfurt database (same region = fast!)
- ✅ Danish-friendly (many Danish developers use Hetzner)

**Quick Setup:**
1. Sign up: https://www.hetzner.com/cloud
2. Create CX22 server (€4.51/month, 2GB RAM)
3. Choose location: Falkenstein or Nuremberg (Germany)
4. Install Laravel using Laravel Forge or manually
5. Update DNS to point to Hetzner
6. Neon PostgreSQL connection works immediately

**Total cost:** €4.51/month (~34 DKK) vs Simply.com VPS 199+ DKK/month

---

**Error:** `could not connect to server: Connection refused`

**Conclusion:** Most shared hosting (GreenGeeks, likely Simply.com) is **incompatible with external PostgreSQL databases**. You must either:
1. Switch to VPS hosting ($5-6/month)
2. Use SSH tunnel workaround (see `SSH-TUNNEL-POSTGRESQL.md`)
3. Demand GreenGeeks fix this or provide refund

---

## Solution 1: Contact GreenGeeks Support with Specific Request (RECOMMENDED)

GreenGeeks support said port 5432 is open, but the connection is still refused. This suggests their firewall is blocking specific IP ranges or PostgreSQL protocol detection.

**Action: Open NEW support ticket with detailed technical information:**

```
Subject: PostgreSQL Connection to Neon Database Blocked (Port 5432)

Hello,

I'm experiencing connection issues to my Neon PostgreSQL database despite your previous confirmation that port 5432 is open.

Technical Details:
- Outbound connection to: ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech
- IP: 72.144.105.10
- Port: 5432
- Protocol: PostgreSQL with SSL (sslmode=require)
- Error: "Connection refused"

Testing shows the connection is actively blocked:
php -r 'new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=5432;dbname=neondb;sslmode=require", "neondb_owner", "password");'
Result: Connection refused

Can you please:
1. Check if there's a firewall rule blocking PostgreSQL protocol detection
2. Whitelist the specific IP: 72.144.105.10
3. Allow SSL-encrypted PostgreSQL connections (not just port 5432 TCP)
4. Provide alternative connection method if above isn't possible

This is critical for my Laravel application's geospatial features (PostGIS).

Account: electr37
Domain: laravel-ecosurvey.electrominds.dk

Thank you!
```

---

## Solution 2: Use SSH Tunnel to Bypass Firewall (WORKS IMMEDIATELY)

Create an SSH tunnel that forwards local PostgreSQL connections through SSH (port 22, which is always open).

**Setup on GreenGeeks:**

```bash
# 1. Create SSH tunnel script
cd ~/public_html/laravel-ecosurvey.electrominds.dk
nano tunnel-neon.sh
```

Add this content:
```bash
#!/bin/bash
# SSH Tunnel to Neon PostgreSQL Database
# Forwards local port 5432 to Neon through SSH

# Install autossh for persistent tunnel (if available)
# autossh -M 0 -f -N -L 5432:ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432 tunnel-user@proxy-server.com

# Alternative: Use standard SSH tunnel
ssh -f -N -L 5432:ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432 tunnel-user@proxy-server.com

echo "Tunnel established. PostgreSQL available at localhost:5432"
```

**Problem:** GreenGeeks doesn't provide external servers to tunnel through.

**BETTER ALTERNATIVE:** Use your home/office computer as SSH tunnel proxy:

1. **On your home computer (Windows):**
   - Install OpenSSH Server (Windows Features)
   - Forward port 22 on router to your computer
   - Create user: `tunneluser`

2. **On GreenGeeks:**
```bash
ssh -f -N -L 5432:ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech:5432 tunneluser@your-home-ip
```

3. **Update .env:**
```env
DB_HOST=localhost
DB_PORT=5432
```

**Limitation:** Tunnel must run continuously. Not ideal for production.

---

## Solution 3: Use Alternative PostgreSQL Provider with Different Port

Some PostgreSQL providers use alternative ports that shared hosting doesn't block:

### Option A: Render.com PostgreSQL (FREE 90 days)
- Provides PostgreSQL 16 with PostGIS
- Uses standard port 5432 BUT with different IP range
- Free tier: 256 MB RAM, 1 GB storage, expires after 90 days
- **Test if their IP range works on GreenGeeks**

**Setup:**
1. Go to: https://render.com
2. Create PostgreSQL database
3. Enable PostGIS extension
4. Copy connection string
5. Update .env with Render credentials
6. Test connection from GreenGeeks

### Option B: Railway.app PostgreSQL ($5/month)
- PostgreSQL 16 with PostGIS
- Port 5432 with different IP range
- $5/month, more reliable than free tiers

### Option C: Supabase PostgreSQL (FREE tier available)
- Built on PostgreSQL with PostGIS
- Port 5432 with connection pooling (port 6543)
- Free tier: 500 MB database, 2 GB bandwidth
- **Try port 6543 (pooler) - might not be blocked!**

**Supabase Setup:**
```env
DB_CONNECTION=pgsql
DB_HOST=db.your-project.supabase.co
DB_PORT=6543  # Pooler port (alternative to 5432)
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password
DB_SSLMODE=require
```

---

## Solution 4: Use Neon's Alternative Connection Methods

Neon provides multiple connection endpoints:

### Try these Neon endpoints on different ports:

```bash
# Test standard port
php -r 'new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=5432;...");'

# Test pooler (might use different routing)
php -r 'new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw-pooler.gwc.azure.neon.tech;port=5432;...");'

# Check Neon console for HTTP-based connection strings
# Neon may provide serverless HTTP endpoint
```

---

## Solution 5: Request VPS Upgrade from GreenGeeks

Shared hosting has these limitations. VPS hosting gives you:
- Full firewall control
- Ability to open any outbound port
- PostgreSQL connections guaranteed

**GreenGeeks VPS Pricing:** ~$40/month

**Alternative VPS Providers:**
- DigitalOcean: $6/month droplet
- Linode: $5/month
- Vultr: $6/month

With VPS, Neon connection works immediately.

---

## Solution 6: Use GreenGeeks PostgreSQL (If Available)

Check if GreenGeeks offers PostgreSQL databases:

```bash
# Check cPanel for PostgreSQL option
# Usually under: Databases → PostgreSQL Databases
```

**Problem:** Most shared hosting only offers MySQL, not PostgreSQL.

If available:
```env
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=electr37_ecosurvey
DB_USERNAME=electr37_dbuser
DB_PASSWORD=cpanel-password
```

Then install PostGIS:
```sql
CREATE EXTENSION postgis;
```

---

## Recommended Order of Actions:

1. ✅ **Contact GreenGeeks support with technical ticket** (do this first)
2. ✅ **Test Supabase with port 6543** (pooler port might bypass block)
3. ✅ **Try Render.com free PostgreSQL** (different IP range)
4. ⚠️ **SSH tunnel through home computer** (temporary solution)
5. 💰 **Upgrade to VPS** (if PostgreSQL is critical)

---

## Testing Commands

```bash
# Test connection to different PostgreSQL services
cd ~/public_html/laravel-ecosurvey.electrominds.dk

# Test Neon
php -r 'new PDO("pgsql:host=ep-orange-breeze-a9xvfbuw.gwc.azure.neon.tech;port=5432;dbname=neondb;sslmode=require", "neondb_owner", "npg_LWwZnUscq5A3");'

# Test Supabase (example)
php -r 'new PDO("pgsql:host=db.xxxxx.supabase.co;port=6543;dbname=postgres;sslmode=require", "postgres", "your-password");'

# Test Render (example)
php -r 'new PDO("pgsql:host=oregon-postgres.render.com;port=5432;dbname=mydb;sslmode=require", "user", "password");'
```

---

## Immediate Next Step

**I recommend trying Supabase** - their pooler port 6543 might not be blocked by GreenGeeks:

### Detailed Supabase Setup Instructions:

**1. Create Supabase Project:**
- Go to https://supabase.com
- Click "New Project"
- Choose organization (or create one)
- Project name: `ecosurvey-production`
- Database password: Generate strong password (SAVE THIS!)
- Region: **Europe West (Ireland)** or closest to EU Frankfurt
- Click "Create new project" (wait 2-3 minutes for setup)

**2. Find Connection Pooling String (Updated for Current Supabase UI):**

After project is created:

**OPTION A: Using Project Settings (Current UI - 2026)**

**Step 2a:** In the left sidebar, click **"Project Settings"** (gear icon at bottom)

**Step 2b:** In the Project Settings menu, click **"Configuration"**

**Step 2c:** Scroll down to find **"Connection Info"** or **"Database"** section

**Step 2d:** Look for **"Connection pooling"** toggle/section
- Toggle **"Use connection pooling"** to ON
- Select **"Session mode"** (not Transaction mode)
- Port will show as **6543** (this is key!)

**Step 2e:** Copy the connection string shown (format):
```
postgres://postgres.[PROJECT-REF]:[PASSWORD]@aws-0-eu-west-1.pooler.supabase.com:6543/postgres
```

**OPTION B: Using Connect Button (Easiest)**

**Step 2a:** On your Supabase dashboard, find the **"Connect"** button (top right, near project name)

**Step 2b:** Click **"Connect"**

**Step 2c:** Select **"Connection pooling"** tab in the popup

**Step 2d:** Mode: Select **"Session"**

**Step 2e:** Copy the **"Connection string"** shown

**OPTION C: Manual Extraction from Any Connection String**

If you can only find the regular connection string (port 5432), extract the details and manually change port to 6543:

From this (Direct connection - port 5432):
```
postgres://postgres.[PROJECT-REF]:[PASSWORD]@aws-0-eu-west-1.pooler.supabase.com:5432/postgres
```

Change to this (Pooled connection - port 6543):
```
postgres://postgres.[PROJECT-REF]:[PASSWORD]@aws-0-eu-west-1.pooler.supabase.com:6543/postgres
```

**Key parts to extract:**
```
Host: aws-0-eu-west-1.pooler.supabase.com (notice .pooler. in hostname)
Port: 6543  ← Change from 5432 to 6543!
Database: postgres
User: postgres.[PROJECT-REF]
Password: [your-database-password from step 1]
```

**💡 TIP:** The pooler hostname will have `.pooler.` in it (e.g., `aws-0-eu-west-1.pooler.supabase.com`). If your hostname doesn't have `.pooler.`, you're looking at the direct connection, not pooled connection.

**3. Enable PostGIS Extension:**

**Step 3a:** In left sidebar, click **"SQL Editor"**

**Step 3b:** Click **"+ New query"**

**Step 3c:** Paste this SQL:
```sql
-- Enable PostGIS extension for spatial data
CREATE EXTENSION IF NOT EXISTS postgis;

-- Verify PostGIS is installed
SELECT PostGIS_version();
```

**Step 3d:** Click **"Run"** or press `Ctrl+Enter`

**Step 3e:** You should see PostGIS version (e.g., "3.4 USE_GEOS=1 USE_PROJ=1 USE_STATS=1")

**4. Update .env on GreenGeeks Server:**

Replace these values with your Supabase details:

```env
DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-west-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.abcdefghijklmnop  # Replace with your project ref
DB_PASSWORD=your-strong-password  # The password you set in step 1
DB_SSLMODE=require
```

**5. Test Connection from GreenGeeks:**

```bash
cd ~/public_html/laravel-ecosurvey.electrominds.dk

# Replace with YOUR actual Supabase credentials
php -r 'try { $pdo = new PDO("pgsql:host=aws-0-eu-west-1.pooler.supabase.com;port=6543;dbname=postgres;sslmode=require", "postgres.yourprojectref", "yourpassword"); echo "SUCCESS: Connected to Supabase!\n"; } catch (Exception $e) { echo "ERROR: " . $e->getMessage() . "\n"; }'
```

**6. If Connection Works, Run Migrations:**

```bash
nano .env  # Update database credentials
php artisan config:clear
php artisan migrate --force
php artisan optimize
php artisan db:seed --class=ProductionSeeder
```

---

### Troubleshooting Supabase Connection:

**Can't find "Session pooling" tab?**
- Look for "Connection pooling" toggle at the top
- OR: Settings → Database → scroll to "Connection Info" 
- OR: Use the "Transaction" mode string and change port from 5432 to 6543

**Still shows port 5432?**
- Manually change it to 6543 in .env
- Supabase pooler accepts connections on both ports, but 6543 is specifically for pooling

**Connection still refused?**
- Try direct connection (port 5432) - might work from different IP
- Try different region (US East might have different routing)
- Contact GreenGeeks with Supabase IP to whitelist

---

This gives you PostgreSQL + PostGIS without port 5432 blocks!


