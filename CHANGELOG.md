# Changelog

All notable changes to EcoSurvey will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial release preparation
- Portfolio documentation
- Comprehensive README
- Architecture diagrams

## [1.0.0] - 2026-01-26

### Added

#### Core Features
- Environmental data collection system with GPS-tagged readings
- Interactive map visualization with Leaflet.js
- PostgreSQL + PostGIS integration for spatial queries
- Photo upload and management system
- Campaign management with zone definitions

#### Satellite Integration
- Copernicus Sentinel-2 satellite data integration
- 7 vegetation indices calculation (NDVI, GNDVI, NDRE, EVI, SAVI, OSAVI, CVI)
- Automated daily satellite data sync
- Heatmap visualization for satellite data
- Time-series analysis for vegetation health

#### Analytics & Reporting
- Dashboard with real-time metrics
- Chart.js integration for data visualization
- Statistical analysis (mean, median, std deviation)
- CSV, PDF, and JSON export functionality
- Custom report generation

#### Subscription System
- Stripe integration with Laravel Cashier
- 3-tier subscription model (Free, Pro, Enterprise)
- Usage metering for data points, analyses, and exports
- Tier-based rate limiting (30-1000 req/hr)
- Subscription management UI
- Invoice history and PDF downloads
- Automatic billing cycle management

#### External API Integrations
- Copernicus Dataspace (Sentinel-2 imagery)
- NASA EONET (natural disaster tracking)
- OpenWeatherMap (weather data)
- WAQI (air quality index)
- Stripe (payment processing)

#### Admin Features (Filament)
- Campaign management dashboard
- User management with role-based access
- Quality assurance workflow for flagged data
- Subscription analytics
- API monitoring

#### Testing
- 200+ Pest tests covering all features
- 97% test coverage
- Feature tests for critical workflows
- Unit tests for services and models
- Browser tests for UI interactions

#### UI/UX
- Dark mode support across entire application
- Responsive design for mobile and tablet
- Livewire 3 + Volt for reactive components
- Flux UI component library
- Tailwind CSS v4 styling

#### Developer Experience
- DDEV development environment
- Automatic queue worker startup
- Automatic Vite dev server startup
- Comprehensive documentation in `/docs`
- GitHub Actions CI/CD workflow
- Laravel Pint code formatting
- Type-safe codebase with PHP 8.3

### Security
- Laravel Fortify authentication
- Policy-based authorization
- CSRF protection on all forms
- Rate limiting per subscription tier
- Secure Stripe webhook verification
- SQL injection prevention with Eloquent
- XSS protection with Blade escaping

### Performance
- Redis caching for computed analytics
- Database indexing on spatial columns
- Eager loading to prevent N+1 queries
- Asset minification with Vite
- Background job processing for heavy tasks
- 800ms average page load time

### Documentation
- Complete architecture documentation
- API integration reference
- Deployment guide (Railway, Render, Docker)
- Contributing guidelines
- User guide (coming soon)
- Code of conduct

## [0.9.0] - 2026-01-20 (Beta)

### Added
- Beta release for testing
- Core data collection features
- Basic satellite integration
- Subscription system foundation

### Fixed
- Database migration issues
- PostGIS extension setup
- Stripe webhook handling
- Queue worker reliability

## [0.5.0] - 2026-01-10 (Alpha)

### Added
- Initial project setup
- Database schema design
- Authentication system
- Basic CRUD operations

---

## Upgrade Guide

### From 0.9.0 to 1.0.0

1. Update dependencies:
   ```bash
   ddev composer update
   ddev npm update
   ```

2. Run migrations:
   ```bash
   ddev artisan migrate
   ```

3. Rebuild assets:
   ```bash
   ddev npm run build
   ```

4. Clear caches:
   ```bash
   ddev artisan optimize:clear
   ```

5. Update environment variables (see `.env.example`)

---

## Breaking Changes

### 1.0.0
- None (initial stable release)

---

## Contributors

- Erik Johnson - Initial development
- [Your Name] - Feature contributions

---

[Unreleased]: https://github.com/yourusername/laravel-ecosurvey/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/yourusername/laravel-ecosurvey/releases/tag/v1.0.0
[0.9.0]: https://github.com/yourusername/laravel-ecosurvey/releases/tag/v0.9.0
[0.5.0]: https://github.com/yourusername/laravel-ecosurvey/releases/tag/v0.5.0
