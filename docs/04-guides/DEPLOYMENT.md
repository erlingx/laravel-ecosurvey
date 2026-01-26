# Deployment Guide

**Production deployment instructions for EcoSurvey**

---

## 🚀 Deployment Options

### Recommended Platforms

1. **Railway** (Recommended) ⭐
   - PostgreSQL + PostGIS built-in
   - Automatic HTTPS/SSL
   - GitHub integration
   - Easy webhook setup
   - $5-20/month

2. **Render**
   - Similar to Railway
   - Free tier available
   - Good PostgreSQL support
   - $7-25/month

3. **DigitalOcean App Platform**
   - More control
   - PostgreSQL managed database
   - $12-50/month

4. **AWS/Heroku**
   - Enterprise-grade
   - PostGIS add-on required
   - $25-100+/month

---

## 📋 Pre-Deployment Checklist

### 1. Environment Configuration

```env
# Application
APP_NAME=EcoSurvey
APP_ENV=production
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database (provided by platform)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=redis

# Queue
QUEUE_CONNECTION=database

# External APIs
COPERNICUS_USERNAME=your@email.com
COPERNICUS_PASSWORD=your-password
COPERNICUS_CLIENT_ID=your-client-id
COPERNICUS_CLIENT_SECRET=your-secret

NASA_EONET_API_KEY=your-api-key
OPENWEATHER_API_KEY=your-api-key
WAQI_API_KEY=your-api-key

# Stripe
STRIPE_PUBLIC_KEY=pk_live_XXXXXXXXXXX
STRIPE_SECRET_KEY=sk_live_XXXXXXXXXXX
STRIPE_WEBHOOK_SECRET=whsec_XXXXXXXXXXX

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ecosurvey.app
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong `APP_KEY` generated
- [ ] All API keys rotated to production values
- [ ] HTTPS/SSL certificate active
- [ ] Stripe webhook signature verification enabled
- [ ] CORS configured properly
- [ ] Rate limiting active
- [ ] Database backups scheduled

### 3. Performance Checklist

- [ ] Redis cache configured
- [ ] Asset compilation complete (`npm run build`)
- [ ] Database indexes created
- [ ] Queue worker running
- [ ] CDN configured (optional)
- [ ] Image optimization enabled

---

## 🛠️ Railway Deployment (Step-by-Step)

### Step 1: Prepare Repository

```bash
# Ensure .gitignore excludes:
/vendor/
/node_modules/
.env
.env.backup
storage/*.key
```

### Step 2: Create Railway Project

1. Go to [railway.app](https://railway.app)
2. Sign in with GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Choose `laravel-ecosurvey`

### Step 3: Add PostgreSQL

1. In Railway dashboard, click "+ New"
2. Select "Database" → "PostgreSQL"
3. Wait for provisioning (~2 minutes)
4. Railway auto-configures `DATABASE_URL`

### Step 4: Enable PostGIS Extension

```bash
# Connect to Railway PostgreSQL
railway connect postgres

# Run in psql:
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS postgis_topology;
\dx  # Verify PostGIS installed
```

### Step 5: Configure Environment Variables

In Railway dashboard → Variables tab:

```
APP_NAME=EcoSurvey
APP_ENV=production
APP_KEY=base64:... (generate with: php artisan key:generate --show)
APP_DEBUG=false
APP_URL=${{ RAILWAY_PUBLIC_DOMAIN }}

# Database auto-configured by Railway
# Add your API keys:
COPERNICUS_USERNAME=...
STRIPE_SECRET_KEY=...
(etc.)
```

### Step 6: Add Build & Start Commands

Railway Settings → Deploy:

**Build Command:**
```bash
composer install --no-dev --optimize-autoloader && npm install && npm run build
```

**Start Command:**
```bash
php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan queue:work --daemon & php artisan serve --host=0.0.0.0 --port=$PORT
```

### Step 7: Deploy

```bash
git push origin main
```

Railway auto-deploys on push.

### Step 8: Run Migrations & Seed

```bash
railway run php artisan migrate:fresh --seed --force
```

### Step 9: Configure Stripe Webhooks

1. In Stripe Dashboard → Developers → Webhooks
2. Add endpoint: `https://your-railway-app.railway.app/stripe/webhook`
3. Select events:
   - `customer.subscription.*`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. Copy signing secret to `STRIPE_WEBHOOK_SECRET`

### Step 10: Test Production

```bash
# Check health
curl https://your-railway-app.railway.app/health

# Check database
railway run php artisan tinker
>>> User::count()
```

---

## 🔧 Render Deployment

### Step 1: Create Web Service

1. Go to [render.com](https://render.com)
2. New → Web Service
3. Connect GitHub repo
4. Configure:
   - **Name**: ecosurvey
   - **Environment**: Docker
   - **Region**: Choose nearest
   - **Instance**: Starter ($7/mo)

### Step 2: Create PostgreSQL

1. New → PostgreSQL
2. **Name**: ecosurvey-db
3. **Plan**: Starter ($7/mo)
4. **Version**: 16
5. After creation, add PostGIS:

```bash
# Connect to database
psql -h <host> -U <user> -d <database>

CREATE EXTENSION postgis;
```

### Step 3: Link Database

In Web Service → Environment:

```
DATABASE_URL=${{postgres:ecosurvey-db.DATABASE_URL}}
```

Render auto-syncs this value.

### Step 4: Add Environment Variables

Same as Railway (see above).

### Step 5: Create `render.yaml`

```yaml
services:
  - type: web
    name: ecosurvey
    env: docker
    plan: starter
    buildCommand: composer install --no-dev && npm install && npm run build
    startCommand: php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
    envVars:
      - key: APP_KEY
        generateValue: true
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: DATABASE_URL
        fromDatabase:
          name: ecosurvey-db
          property: connectionString

databases:
  - name: ecosurvey-db
    plan: starter
    databaseName: ecosurvey
    user: ecosurvey
```

### Step 6: Deploy

Push to GitHub, Render auto-deploys.

---

## 🐳 Docker Production Setup

### Dockerfile

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    nodejs \
    npm

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Install PostGIS support
RUN apt-get install -y libgeos-dev
RUN docker-php-ext-install pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 8000

CMD php artisan migrate --force && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan queue:work --daemon & \
    php artisan serve --host=0.0.0.0 --port=8000
```

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - DB_HOST=db
      - DB_DATABASE=ecosurvey
      - DB_USERNAME=ecosurvey
      - DB_PASSWORD=secret
    depends_on:
      - db
      - redis
    volumes:
      - ./storage:/var/www/storage

  db:
    image: postgis/postgis:16-3.4
    environment:
      - POSTGRES_DB=ecosurvey
      - POSTGRES_USER=ecosurvey
      - POSTGRES_PASSWORD=secret
    volumes:
      - pgdata:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"

volumes:
  pgdata:
```

---

## 📊 Post-Deployment Tasks

### 1. Verify Deployment

```bash
# Check application
curl https://your-domain.com

# Check database
railway run php artisan tinker
>>> DB::select('SELECT version()');
>>> DB::select('SELECT PostGIS_Version()');

# Check queue
railway run php artisan queue:monitor database
```

### 2. Seed Demo Data (Optional)

```bash
railway run php artisan db:seed --class=DemoSeeder
```

### 3. Set Up Monitoring

**Laravel Telescope** (Development only):
```bash
php artisan telescope:install
php artisan migrate
```

**Error Tracking (Sentry)**:
```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=YOUR_DSN
```

### 4. Configure Scheduled Tasks

In Railway/Render:
- Add cron job or use Laravel Scheduler
- Run: `php artisan schedule:work` (or use cron)

**Cron Jobs Needed:**
```
# Daily satellite sync
0 2 * * * php artisan satellite:sync

# Hourly subscription check
0 * * * * php artisan subscriptions:check

# Daily cleanup
0 3 * * * php artisan cache:clear
```

### 5. Performance Optimization

```bash
# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize Composer
composer install --optimize-autoloader --no-dev
```

### 6. Database Backups

**Railway**: Auto-backups included

**Render**: Configure in dashboard

**Manual Backup**:
```bash
railway run pg_dump > backup-$(date +%Y%m%d).sql
```

---

## 🔐 SSL/HTTPS Configuration

Both Railway and Render provide automatic HTTPS.

**Custom Domain:**

1. Add domain in platform dashboard
2. Update DNS records:
   ```
   Type: CNAME
   Name: www (or @)
   Value: your-app.railway.app
   ```
3. Wait for SSL provisioning (~5 minutes)
4. Update `APP_URL` in environment variables

---

## 🚨 Troubleshooting

### Database Connection Failed

```bash
# Check PostGIS extension
railway run psql -c "SELECT PostGIS_Version();"

# Verify credentials
railway run php artisan tinker
>>> config('database.connections.pgsql')
```

### Queue Not Processing

```bash
# Check worker status
railway run php artisan queue:monitor database

# Restart worker
railway run php artisan queue:restart
```

### Assets Not Loading

```bash
# Rebuild assets
npm run build

# Clear cache
php artisan cache:clear
php artisan view:clear
```

### Stripe Webhooks Failing

1. Check webhook secret matches `.env`
2. Verify endpoint URL is correct
3. Check signature verification in logs
4. Test with Stripe CLI:
   ```bash
   stripe listen --forward-to https://your-domain.com/stripe/webhook
   ```

---

## 📈 Scaling Strategy

### Vertical Scaling (Easier)

**Railway/Render:**
- Upgrade instance size in dashboard
- No code changes needed

### Horizontal Scaling (Advanced)

1. **Load Balancer**: Distribute traffic across multiple app instances
2. **Read Replicas**: PostgreSQL read replicas for queries
3. **Queue Workers**: Multiple queue worker instances
4. **CDN**: Cloudflare/CloudFront for static assets
5. **Redis Cluster**: Distributed cache

---

## 💰 Cost Estimates

### Railway (Recommended for MVP)

```
Web Service (Starter):    $5/month
PostgreSQL (Starter):     $5/month
Redis (if needed):        $5/month
──────────────────────────────────
TOTAL:                    $15/month
```

### Render

```
Web Service (Starter):    $7/month
PostgreSQL (Starter):     $7/month
──────────────────────────────────
TOTAL:                    $14/month
```

### Production (1000+ users)

```
Web Service (Pro):        $25/month
PostgreSQL (Standard):    $25/month
Redis:                    $10/month
CDN:                      $5/month
Monitoring (Sentry):      $26/month
──────────────────────────────────
TOTAL:                    $91/month
```

---

## ✅ Launch Checklist

- [ ] All environment variables set
- [ ] Database migrated and seeded
- [ ] PostGIS extension enabled
- [ ] Queue worker running
- [ ] Stripe webhooks configured and tested
- [ ] SSL certificate active
- [ ] Custom domain configured (if applicable)
- [ ] Error monitoring enabled
- [ ] Database backups scheduled
- [ ] API rate limits tested
- [ ] Performance optimizations applied
- [ ] Security headers configured
- [ ] CORS configured
- [ ] Email service tested
- [ ] Demo account created
- [ ] Documentation updated with live URL

---

## 🆘 Support

**Deployment Issues:**
- Railway Discord: https://discord.gg/railway
- Render Support: https://render.com/docs

**Application Issues:**
- Check logs: `railway run php artisan pail`
- GitHub Issues: https://github.com/yourusername/laravel-ecosurvey/issues

---

**Last Updated**: January 26, 2026
