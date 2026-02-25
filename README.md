# EcoSurvey - Environmental Data Platform

<div align="center">

[![Laravel 12](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL+PostGIS](https://img.shields.io/badge/PostgreSQL-PostGIS-336791?style=flat-square&logo=postgresql&logoColor=white)](https://postgis.net)
[![Livewire 3](https://img.shields.io/badge/Livewire-3-FB70A9?style=flat-square&logo=livewire&logoColor=white)](https://livewire.laravel.com)
[![Stripe](https://img.shields.io/badge/Stripe-Payment-008CDD?style=flat-square&logo=stripe&logoColor=white)](https://stripe.com)
[![Tests](https://img.shields.io/badge/Tests-370%2B%20Passing-brightgreen?style=flat-square)](https://github.com/yourusername/laravel-ecosurvey#testing)
[![License MIT](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

**Full-stack SaaS platform for environmental data collection with satellite integration and subscription billing.**

[🌐 Live Demo](#) | [📖 Architecture](docs/02-architecture/ARCHITECTURE.md) | [🚀 Quick Start](#quick-start) | [📊 Features](#key-features)

</div>

---

## 📋 Overview

EcoSurvey is a production-ready SaaS application designed for environmental scientists, researchers, and organizations to collect, analyze, and visualize environmental data with precision. The platform integrates satellite imagery from Copernicus Sentinel-2 to provide vegetation indices and environmental metrics alongside ground-truth data collection.

**Perfect for:**
- Environmental monitoring campaigns
- Conservation organizations
- Scientific research projects
- Climate impact assessment
- Land management agencies

### What Makes It Unique

- 🛰️ **Real-time satellite data integration** with 7 vegetation indices (NDVI, GNDVI, NDRE, EVI, SAVI, OSAVI, CVI)
- 📍 **Advanced geospatial features** with PostGIS for complex spatial queries and polygon operations
- 💳 **Subscription-based SaaS model** with usage metering and tier-based enforcement
- 📊 **Rich analytics engine** with heatmaps, time-series analysis, and statistical reporting
- 🌙 **Dark mode support** across the entire application
- ✅ **Comprehensive test coverage** (370+ tests, 100% passing) ensuring reliability

---

## 📸 Screenshots

### Dashboard Overview
![Dashboard](docs/screenshots/01-dashboard.png)
*Campaign overview with real-time metrics, usage tracking, and recent activity*

### Interactive Map with Survey Data
![Map View](docs/screenshots/02-map.png)
*Leaflet-based interactive map with GPS-tagged survey points, zone boundaries, and cluster visualization*

![Data Point Detail](docs/screenshots/02b-datapoint.png)
*Interactive data point popup showing survey details, photo, location, and quality status*

### Satellite Analysis & Vegetation Indices
![Satellite Analysis](docs/screenshots/03-satellite-2.png)
*Copernicus Sentinel-2 satellite viewer with 7 automated vegetation indices (NDVI, EVI, GNDVI, NDRE, SAVI, OSAVI, CVI)*

### Subscription Management & Billing
![Billing Dashboard](docs/screenshots/04-billing.png)
*Stripe-powered subscription management with usage metering, tier enforcement, and invoice history*

### Data Collection Form
![Create Survey](docs/screenshots/05-create.png)
*GPS-enabled field data entry with photo upload, environmental metrics, and real-time validation*

---

## ✨ Key Features

### Data Collection & Management
- **GPS-Tagged Readings** - Precise location capture with accuracy metrics
- **Photo Upload** - Document environmental conditions with timestamped photos
- **Field Validation** - Real-time validation of environmental measurements
- **Quality Assurance** - Flag system and approval workflow for data integrity
- **Batch Imports** - Upload multiple readings via CSV

### Geospatial Analysis
- **Interactive Maps** - Leaflet-based visualization with zoom, pan, clustering
- **Survey Zones** - Define geographic boundaries and analyze data within polygons
- **Spatial Queries** - PostGIS-powered queries (proximity, polygon intersection, distance)
- **Heatmap Visualization** - Density visualization of environmental metrics
- **Zone Statistics** - Automated aggregation within defined boundaries

### Satellite Integration
- **Copernicus Sentinel-2** - Daily imagery at 10m resolution
- **7 Vegetation Indices** - NDVI, GNDVI, NDRE, EVI, SAVI, OSAVI, CVI
- **Automated Sync** - Daily background processing of satellite data
- **Time-Series Analysis** - Track vegetation health over time
- **Image Overlay** - View satellite imagery directly on interactive maps

### Analytics & Reporting
- **Dashboard Analytics** - Real-time metrics and campaign overview
- **Chart Library** - Time-series, scatter, bar charts with zoom and annotations
- **Statistical Analysis** - Mean, median, std deviation, anomaly detection
- **Data Export** - CSV, PDF, JSON formats with satellite data enrichment
- **Custom Reports** - Generate professional reports with maps and charts

### Subscription & Monetization
- **3-Tier Plans** - Free, Pro, and Enterprise subscriptions
- **Usage Metering** - Track data points, satellite analyses, exports per billing cycle
- **Tier-Based Limits** - Enforce limits automatically per subscription level
- **Real-Time Dashboard** - Usage progress bars with upgrade prompts
- **Stripe Integration** - Secure payment processing with webhook support
- **Invoice Management** - View, download, and track billing history

### Admin Dashboard (Filament)
- **Campaign Management** - Create, configure, and monitor campaigns
- **User Management** - Role-based access control
- **Quality Assurance** - Review and approve flagged data
- **Subscription Analytics** - Track MRR, churn, user growth
- **API Monitoring** - Monitor external API integrations

---

## 🏗️ Architecture

### Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | Laravel 12, PHP 8.3 | Web framework with modern features |
| **Database** | PostgreSQL 16 + PostGIS | Relational data + spatial queries |
| **Database (Production)** | Supabase PostgreSQL (EU Frankfurt) | Serverless PostgreSQL with PostGIS on AWS |
| **Frontend** | Livewire 3 + Volt | Real-time reactive components |
| **Styling** | Tailwind CSS v4 | Utility-first CSS framework |
| **Maps** | Leaflet.js | Interactive geospatial visualization |
| **Payments** | Stripe + Cashier | Subscription billing and metering |
| **External APIs** | Copernicus, NASA, OpenWeatherMap, WAQI | Satellite, weather, air quality data |
| **Testing** | Pest v4 | Modern PHP testing framework |
| **Deployment** | Docker (DDEV) | Local development environment |

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Browser / Mobile Client                    │
└────────────────────────┬────────────────────────────────────┘
                         │
                    HTTP/WebSocket
                         │
┌────────────────────────▼────────────────────────────────────┐
│                   Laravel 12 Application                      │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Livewire 3 + Volt Components (Real-time UI)          │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Controllers & Actions (Business Logic)                │   │
│  │ ├─ Data Collection Handler                            │   │
│  │ ├─ Satellite Analysis Engine                          │   │
│  │ ├─ Subscription Manager (Stripe)                      │   │
│  │ ├─ Usage Meter & Enforcer                            │   │
│  │ └─ Rate Limiter (Tier-based)                         │   │
│  └──────────────────────────────────────────────────────┘   │
│  ┌──────────────────────────────────────────────────────┐   │
│  │ Eloquent Models & PostGIS Queries                     │   │
│  │ ├─ User, Campaign, Survey                            │   │
│  │ ├─ SatelliteImage, Subscription                      │   │
│  │ └─ Spatial Relationships (ST_Contains, ST_Distance)  │   │
│  └──────────────────────────────────────────────────────┘   │
└────────────────────────┬────────────────────────────────────┘
          ┌─────────────┼─────────────┐
          │             │             │
          ▼             ▼             ▼
    ┌─────────┐   ┌─────────┐   ┌──────────┐
    │PostgreSQL   │   Redis   │   │ External │
    │ + PostGIS   │  (Cache)  │   │   APIs   │
    └─────────┘   └─────────┘   └──────────┘
                                      │
                     ┌────────────────┼────────────────┐
                     │                │                │
                     ▼                ▼                ▼
                 Copernicus        NASA EONET    OpenWeatherMap
                 Sentinel-2         Weather          + WAQI
```

### Data Flow: Satellite Processing

```
Daily Schedule (Automated)
    │
    ├─▶ Fetch Sentinel-2 Imagery (Copernicus API)
    │
    ├─▶ Calculate 7 Vegetation Indices
    │   (NDVI, GNDVI, NDRE, EVI, SAVI, OSAVI, CVI)
    │
    ├─▶ Store in PostgreSQL + PostGIS
    │
    └─▶ Render Heatmaps & Time-Series Data
         (Cached for Performance)
```

### Subscription Model

```
User Tier          Data Points/Month    Satellite Analyses    Exports/Month
────────────────────────────────────────────────────────────────────────
Guest              30/month             1/month               3/month
Free               100/month            10/month              10/month
Pro                5,000/month          100/month             Unlimited
Enterprise         Unlimited            Unlimited             Unlimited
```

### Performance & Optimization

**Query Optimization**:
- JOIN-based queries (1 query vs 31 for map data)
- Eager loading throughout (zero N+1 queries)
- Database indexes on spatial and foreign key columns
- Comprehensive N+1 review completed (see [docs/N+1-PERFORMANCE-REVIEW.md](docs/N+1-PERFORMANCE-REVIEW.md))

**Caching Strategy**:
- Database cache with 5-minute TTL for map data
- Automatic invalidation on data changes via observers
- 4.7x faster map loads on subsequent requests

**Results**:
- Map loads: ~128ms (cached) vs ~606ms (uncached)
- 97% query reduction (31 → 1 query)
- Production-ready with Supabase PostgreSQL (EU)

---

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose (via [DDEV](https://ddev.readthedocs.io/))
- PostgreSQL 16 with PostGIS extension
- PHP 8.3+
- Node.js 18+

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/laravel-ecosurvey.git
   cd laravel-ecosurvey
   ```

2. **Start DDEV**
   ```bash
   ddev start
   ```
   This automatically:
   - Spins up PostgreSQL 16 with PostGIS
   - Runs Laravel migrations
   - Starts Vite dev server
   - Starts queue worker for background jobs

3. **Install dependencies**
   ```bash
   ddev composer install
   ddev npm install
   ```

4. **Configure environment**
   ```bash
   cp .env.example .env
   ddev artisan key:generate
   ```
   
   **For Production (Supabase PostgreSQL):**
   - Update `.env` with your Supabase credentials:
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=db.yourprojectref.supabase.co  # Direct connection (not pooler)
   DB_PORT=5432
   DB_DATABASE=postgres
   DB_USERNAME=postgres
   DB_PASSWORD=your-password
   DB_SSLMODE=require
   ```
   - **Important**: Use the **direct connection** endpoint (port 5432) for migrations
   - The pooler endpoint (port 6543) can be used for application queries in production
   - Supabase provides serverless PostgreSQL with automatic scaling and PostGIS support

5. **Set up database**
   ```bash
   # For DDEV (local development)
   ddev artisan migrate:fresh --seed
   
   # For Production (Supabase)
   # First enable PostGIS extension:
   psql 'your-supabase-connection-string' -c 'CREATE EXTENSION IF NOT EXISTS postgis;'
   # Then run migrations:
   php artisan migrate:fresh --seed --force
   ```

6. **Configure Stripe** (Optional for testing)
   - Add `STRIPE_PUBLIC_KEY` and `STRIPE_SECRET_KEY` to `.env`
   - Add webhook endpoint in Stripe Dashboard

7. **Access the application**
   ```
   https://ecosurvey.ddev.site
   ```

### Demo Credentials

```
Email:    test@example.com
Password: password
```

### First Steps

1. **View Dashboard** - See campaign overview and usage metrics
2. **Create Campaign** - Add a new environmental monitoring campaign
3. **Collect Data** - Add survey readings with GPS and photos
4. **View Map** - Visualize data on interactive map
5. **Analyze Satellite** - View Copernicus Sentinel-2 vegetation indices
6. **Export Report** - Generate PDF report with data and satellite analysis

---

## 🧪 Testing

EcoSurvey includes comprehensive test coverage with 200+ Pest tests.

### Run All Tests
```bash
ddev artisan test
```

### Run Specific Test File
```bash
ddev artisan test tests/Feature/SubscriptionTest.php
```

### Run Tests by Filter
```bash
ddev artisan test --filter=Subscription
```

### View Test Report
```bash
ddev artisan test --coverage
```

### Test Categories

| Category | Count | Coverage |
|----------|-------|----------|
| Subscription & Billing | 37 | 100% |
| Data Collection | 28 | 100% |
| Geospatial Queries | 22 | 100% |
| Satellite Processing | 18 | 100% |
| API Integration | 35 | 95% |
| Analytics & Reporting | 31 | 95% |
| Rate Limiting | 15 | 100% |
| **Total** | **200+** | **97%** |

---

## 🚀 Deployment

### Production Database: Supabase PostgreSQL

EcoSurvey uses **[Supabase](https://supabase.com)** as the production database - a serverless PostgreSQL platform with:

- **Location**: EU Frankfurt region (Germany - AWS)
- **Version**: PostgreSQL 15+ with PostGIS 3.3+
- **Features**:
  - ✅ Serverless with automatic scaling
  - ✅ Built-in connection pooling (PgBouncer)
  - ✅ Point-in-time recovery
  - ✅ Real-time database changes (optional)
  - ✅ SSL/TLS required connections
  - ✅ Free tier available (500 MB storage, 2 GB transfer)
  - ✅ Built-in authentication and storage APIs

### Database Configuration

**Important**: Supabase provides two connection modes:

1. **Direct Connection** (for migrations/schema changes):
   ```
   db.projectref.supabase.co:5432
   ```

2. **Connection Pooling** (for application queries - recommended):
   ```
   db.projectref.supabase.co:6543 (Transaction mode)
   ```

**Environment Setup**:
```env
# Use direct connection for migrations
DB_CONNECTION=pgsql
DB_HOST=db.yourprojectref.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-secure-password
DB_SSLMODE=require
```

### PostGIS Setup

PostGIS must be enabled before running migrations:

```bash
# Connect to Supabase database
psql 'postgresql://postgres:password@db.yourprojectref.supabase.co:5432/postgres'

# Enable PostGIS extension
CREATE EXTENSION IF NOT EXISTS postgis;

# Verify installation
SELECT PostGIS_version();
```

### Initial Deployment

```bash
# 1. Clear config cache
php artisan config:clear

# 2. Run migrations (production)
php artisan migrate:fresh --force

# 3. Seed initial data (optional)
php artisan db:seed --force

# 4. Optimize application
php artisan optimize
```

### Why Supabase?

- **All-in-one platform**: Database, authentication, storage, and real-time APIs
- **Developer-friendly**: Intuitive dashboard, automatic API generation
- **EU compliance**: Data sovereignty with Frankfurt region
- **PostGIS ready**: Full spatial database support out-of-the-box
- **Zero maintenance**: No server management, automatic backups
- **Generous free tier**: Perfect for development and small projects

### Alternative: Local PostgreSQL

For local development, DDEV provides PostgreSQL 16 with PostGIS automatically.

---

## 🤝 Contributing

````
laravel-ecosurvey/
├── app/
│   ├── Models/              # Eloquent models
│   │   ├── User.php
│   │   ├── Campaign.php
│   │   ├── Survey.php
│   │   ├── SatelliteImage.php
│   │   └── Subscription.php
│   ├── Http/Controllers/    # Route controllers
│   ├── Jobs/                # Queued jobs (satellite sync, etc.)
│   ├── Services/            # Business logic
│   │   ├── SatelliteService.php
│   │   ├── UsageMetersService.php
│   │   └── AnalyticsService.php
│   ├── Actions/             # Discrete actions
│   └── Listeners/           # Event listeners
├── resources/
│   ├── views/               # Blade templates
│   │   ├── components/      # Reusable components
│   │   ├── pages/           # Page templates
│   │   └── layouts/         # Layout templates
│   ├── js/                  # JavaScript & Livewire
│   │   ├── app.js
│   │   └── ckeditor-init.js
│   └── css/                 # Tailwind CSS
├── routes/
│   ├── web.php              # Web routes
│   └── console.php          # Console commands
├── database/
│   ├── migrations/          # Database schema
│   ├── factories/           # Model factories (testing)
│   └── seeders/             # Database seeders
├── tests/
│   ├── Feature/             # Feature tests (200+)
│   ├── Unit/                # Unit tests
│   └── Browser/             # Browser tests (Pest v4)
├── config/                  # Configuration files
├── bootstrap/
│   ├── app.php              # Bootstrap configuration
│   └── providers.php        # Service providers
└── docs/                    # Documentation
    ├── 01-project/          # Project overview
    ├── 02-architecture/     # System architecture
    ├── 03-integrations/     # API integration guides
    ├── 04-guides/           # How-to guides
    ├── 05-testing/          # Testing documentation
    └── 06-user-guide/       # User manual
```

---

## 🔌 API Integrations

### External Services

| Service | Purpose | Status |
|---------|---------|--------|
| **Copernicus Dataspace** | Sentinel-2 satellite imagery | ✅ Active |
| **NASA EONET** | Fire/flood/storm events | ✅ Active |
| **OpenWeatherMap** | Weather conditions | ✅ Active |
| **WAQI** | Air quality index | ✅ Active |
| **Stripe** | Payment processing | ✅ Active |

### Satellite Data Sources

- **Copernicus Sentinel-2**: 10m resolution, daily updates
- **Vegetation Indices**:
  - NDVI (Normalized Difference Vegetation Index)
  - GNDVI (Green Normalized Difference Vegetation Index)
  - NDRE (Normalized Difference Red Edge Index)
  - EVI (Enhanced Vegetation Index)
  - SAVI (Soil-Adjusted Vegetation Index)
  - OSAVI (Optimized Soil-Adjusted Vegetation Index)
  - CVI (Chlorophyll Vegetation Index)

### Rate Limits

| API | Limit | Window |
|-----|-------|--------|
| Copernicus | 100 requests | 1 hour |
| NASA EONET | 100 requests | 1 hour |
| OpenWeatherMap | 60 requests | 1 minute |
| Stripe | Variable | Per tier |

---

## 🔐 Security Features

- **Authentication**: Laravel Fortify with email verification
- **Authorization**: Policy-based access control per campaign
- **Rate Limiting**: Tier-based request limits (30-1000 req/hr)
- **Data Validation**: Form Request classes with custom rules
- **CSRF Protection**: Token-based form security
- **SQL Injection Protection**: Eloquent parameterized queries
- **XSS Protection**: Blade escaping and sanitization
- **Payment Security**: PCI-compliant Stripe integration with Cashier

---

## 📈 Performance

### Optimization Strategies

- **Database Indexing**: PostGIS indexes on spatial columns
- **Query Optimization**: Eager loading with `with()` to prevent N+1 queries
- **Caching**: Redis caching for computed analytics (1-hour TTL)
- **Asset Minification**: Vite bundling with tree-shaking
- **Lazy Loading**: Images and maps load on demand
- **Queue Workers**: Background processing for satellite sync and reports

### Benchmarks

- Page load time: ~800ms (with cold cache)
- API response time: ~150ms average
- Satellite sync: ~2 minutes per 100 locations
- Report generation: <5 seconds for CSV, <10 seconds for PDF

---

## 📚 Documentation

Comprehensive documentation available in `/docs/`:

- **[Architecture Overview](docs/02-architecture/ARCHITECTURE.md)** - System design and data flow
- **[API Reference](docs/03-integrations/API-REFERENCE.md)** - Available endpoints and integrations
- **[Installation Guide](docs/04-guides/INSTALLATION.md)** - Detailed setup instructions
- **[User Guide](docs/06-user-guide/USER-GUIDE.md)** - Feature walkthroughs
- **[Deployment Guide](docs/04-guides/DEPLOYMENT.md)** - Production deployment steps
- **[Testing Guide](docs/05-testing/TESTING.md)** - Writing and running tests
- **[Contributing](CONTRIBUTING.md)** - Development workflow

---

## 🛠️ Development

### Tech Stack Details

```bash
# Backend
Laravel 12.0+        # Modern PHP framework
PHP 8.3              # Server-side language
PostgreSQL 16        # Database with PostGIS
Redis                # Caching layer

# Frontend
Livewire 3           # Real-time reactive components
Volt                 # Single-file component syntax
Flux UI              # Beautiful component library
Tailwind CSS v4      # Utility-first styling
Alpine.js            # Lightweight JavaScript framework
Leaflet.js           # Interactive mapping

# Testing & Quality
Pest v4              # Modern PHP testing framework
PHPUnit 12           # Unit testing engine
Laravel Pint         # Code formatting
Larastan             # Static analysis

# DevOps
Docker + DDEV        # Local development environment
GitHub Actions       # CI/CD pipeline
Stripe Webhooks      # Payment event handling
```

### Code Standards

- PSR-12 code style (enforced with Laravel Pint)
- Type declarations on all methods
- PHPDoc comments for public APIs
- Test coverage for all features
- Clean architecture principles

### Running Pint (Code Formatter)

```bash
# Fix code style issues
ddev pint --dirty

# Check without fixing
ddev pint --test
```

---

## 🤝 Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

### Development Workflow

1. Create a feature branch: `git checkout -b feature/amazing-feature`
2. Make changes and write tests
3. Run tests: `ddev artisan test`
4. Format code: `ddev pint --dirty`
5. Commit with clear messages
6. Push to branch and create Pull Request

---

## 📄 License

This project is licensed under the MIT License - see [LICENSE](LICENSE) file for details.

---

## 🙋 Support & Questions

- **Documentation**: See `/docs` directory
- **Issues**: Open a GitHub Issue for bugs
- **Discussions**: Start a Discussion for questions
- **Email**: support@ecosurvey.app (in production)

---

## 🙌 Acknowledgments

- [Laravel](https://laravel.com) - The PHP framework used
- [Livewire](https://livewire.laravel.com) - Real-time components
- [Copernicus](https://www.copernicus.eu/) - Satellite data provider
- [Stripe](https://stripe.com) - Payment processing
- [Leaflet.js](https://leafletjs.com) - Mapping library
- [PostGIS](https://postgis.net) - Spatial database extension

---

## 📊 Project Stats

- **Lines of Code**: ~15,000
- **Tests**: 370+ passing (100% pass rate)
- **Test Assertions**: 1,470+
- **Code Coverage**: Comprehensive (all critical paths tested)
- **Database Migrations**: 20+
- **API Integrations**: 5
- **Component Library**: 40+ Livewire components
- **Dark Mode Support**: 100%
- **Mobile Responsive**: Yes

---

<div align="center">

**[⬆ Back to Top](#-ecosurvey---environmental-data-platform)**

Built with ❤️ for environmental scientists and researchers.

</div>
