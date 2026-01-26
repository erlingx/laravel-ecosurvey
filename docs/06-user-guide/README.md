# EcoSurvey User Guide

**Complete feature reference for environmental data collection**

---

## 🚀 Getting Started

### 1. Create Account & Subscribe
- Register at `/register`
- Choose subscription tier (Free/Pro/Enterprise)
- Upgrade anytime from dashboard

### 2. Create Campaign
1. Dashboard → **New Campaign**
2. Fill in: Name, description, location
3. Optional: Set campaign-specific parameters

### 3. Collect Data
1. Campaign → **Add Survey Reading**
2. GPS auto-detects location (or manual entry)
3. Enter environmental metrics
4. Upload photos (optional)
5. Submit

---

## 📊 Core Features

### Data Collection
**GPS-Tagged Readings**
- Automatic GPS detection with accuracy tracking
- Manual coordinate entry supported
- Timestamp captured automatically

**Environmental Metrics**
- Temperature, humidity, soil moisture
- pH, conductivity, nitrates
- Wind speed, precipitation
- Custom fields per campaign

**Photo Upload**
- Multiple photos per reading
- Automatic metadata (timestamp, location)
- Preview before upload

**Quality Control**
- Flag suspicious readings
- Approve/reject workflow
- Admin quality assurance dashboard

---

### Geospatial Analysis

**Interactive Maps**
- View all survey points
- Cluster mode for dense data
- Filter by date range, metrics
- Click markers for details

**Survey Zones**
- Draw polygons on map
- Define study area boundaries
- Auto-calculate area (hectares)
- Filter data within zones

**Spatial Queries**
- Find readings within X km of location
- Get all data in specific zone
- Distance calculations
- Proximity analysis

---

### Satellite Integration

**Copernicus Sentinel-2**
- 10m resolution imagery
- Daily automatic sync (2 AM UTC)
- Cloud coverage filtering (<20%)

**7 Vegetation Indices** (Auto-calculated)
```
NDVI  - Vegetation presence (0.2-0.8 = healthy)
GNDVI - Broader spectral range
NDRE  - Crop stress detection
EVI   - Enhanced sensitivity
SAVI  - Soil-adjusted
OSAVI - Optimized soil correction
CVI   - Chlorophyll levels
```

**Satellite Viewer**
- Time-series charts
- Heatmap overlay on maps
- Compare field data vs satellite
- Export satellite data with readings

---

### Analytics & Reporting

**Dashboard Metrics**
- Total data points
- Recent activity
- Usage meter (per tier)
- Campaign summaries

**Statistical Analysis**
- Min, max, mean, median
- Standard deviation
- Trend detection
- Anomaly flagging

**Charts & Visualizations**
- Time-series line charts
- Heatmap density maps
- Scatter plots (metric correlations)
- Error bars and confidence intervals

**Export Formats**
- **CSV** - Excel/R/Python compatible
- **JSON** - API integration
- **PDF** - Professional reports with maps, stats, satellite data

---

### Subscription System

**Tier Comparison**

| Feature | Free | Pro ($49/mo) | Enterprise |
|---------|------|--------------|------------|
| Data Points | 100/month | 5,000/month | Unlimited |
| Satellite Analyses | 10/month | 100/month | Unlimited |
| Exports | 10/month | Unlimited | Unlimited |
| Campaigns | 3 | Unlimited | Unlimited |
| Rate Limit | 60 req/hr | 300 req/hr | 1000 req/hr |

**Usage Tracking**
- Real-time progress bars
- Current cycle usage displayed
- Warnings at 80% and 100%
- Automatic reset each billing cycle

**Manage Subscription**
- View current plan and usage
- Upgrade/downgrade anytime
- Cancel subscription (immediate or end of period)
- Resume during grace period
- Update payment method (Stripe portal)
- View invoice history
- Download invoice PDFs

---

## 🔧 Common Workflows

### Workflow 1: Field Data Collection
```
1. Open campaign on mobile
2. Click "Add Reading"
3. GPS auto-fills location
4. Enter temperature, humidity, etc.
5. Take photos
6. Submit → Data appears on map instantly
```

### Workflow 2: Satellite Analysis
```
1. Campaign → Satellite tab
2. View latest Sentinel-2 image
3. Select vegetation index (NDVI/EVI/etc.)
4. Compare with field readings
5. Export combined dataset
```

### Workflow 3: Generate Report
```
1. Campaign → Reports
2. Click "Generate PDF"
3. Report includes:
   - Stats summary
   - Maps with data points
   - Satellite index charts
   - Zone analysis
4. Download or email
```

### Workflow 4: Quality Assurance
```
1. Admin → Flagged Data
2. Review suspicious readings
3. Check photos and metadata
4. Approve or reject
5. Notify field team
```

---

## 📍 Map Features

**Navigation**
- Zoom: Mouse wheel or +/- buttons
- Pan: Click and drag
- Fullscreen: Top-right button

**Markers**
- Green = Approved readings
- Yellow = Pending review
- Red = Flagged for QA

**Layers**
- Survey points
- Survey zones (polygons)
- Heatmap overlay
- Satellite imagery

**Interactions**
- Click marker → View details
- Right-click → Add reading here
- Draw tool → Create survey zone

---

## 🛰️ Satellite Data

**Sync Schedule**
- Runs daily at 2:00 AM UTC
- Processes last 7 days
- Filters cloud coverage >20%
- Stores 7 vegetation indices

**Manual Sync**
- Campaign → Satellite → "Sync Now"
- Limited by subscription tier
- Takes ~2 minutes per campaign

**Interpreting Indices**
```
NDVI Values:
< 0.2  = No vegetation (water, bare soil)
0.2-0.5 = Sparse vegetation
0.5-0.7 = Moderate vegetation
> 0.7  = Dense, healthy vegetation
```

---

## 📤 Data Export

**CSV Export**
- All readings with GPS coordinates
- Includes satellite indices (if available)
- Header row with units
- Compatible with Excel, R, Python

**JSON Export**
- Nested structure with relationships
- Photos included as URLs
- Satellite data embedded
- API-ready format

**PDF Report**
- Professional formatting
- Campaign metadata
- Statistical summaries
- Maps and charts
- Zone analysis
- Satellite coverage

---

## 🔐 Account & Billing

**Update Profile**
- Settings → Profile
- Change email, password
- Upload profile photo

**Payment Methods**
- Settings → Billing → Update Payment
- Redirects to Stripe secure portal
- Update card details
- View payment history

**Invoices**
- Settings → Billing → Invoices
- List of all invoices
- Download PDF copies
- Automatic email receipts

**Cancel Subscription**
- Settings → Billing → Cancel
- Choose: End of period or Immediate
- Grace period if prepaid
- Can resume before period ends

---

## ⚡ Keyboard Shortcuts

```
Dashboard:
  N  - New campaign
  S  - Search campaigns

Map View:
  Z  - Draw zone
  M  - Toggle markers
  H  - Toggle heatmap
  F  - Fullscreen

Data Entry:
  Ctrl+S - Save draft
  Ctrl+Enter - Submit
```

---

## 🆘 Troubleshooting

**GPS Not Working**
- Enable browser location permission
- Try manual coordinate entry
- Check device GPS settings

**Upload Failed**
- Check file size (<5MB per photo)
- Supported formats: JPG, PNG
- Check internet connection

**Rate Limit Exceeded**
- Wait for 1-hour window reset
- Upgrade subscription for higher limits
- Check usage meter in dashboard

**Satellite Data Missing**
- Cloud coverage may be too high
- Try different date range
- Manual sync if auto-sync failed
- Check subscription limits

**Payment Issues**
- Verify card details in Stripe portal
- Check billing email for notices
- Grace period allows 3 days to update payment

---

## 📞 Support

**Documentation**
- Full docs: `/docs`
- API Reference: `/docs/api`
- Architecture: `/docs/architecture`

**Help Resources**
- Dashboard → Help icon
- Email: support@ecosurvey.app (coming soon)
- GitHub Issues: Report bugs

**Feature Requests**
- GitHub Discussions
- Email suggestions
- Vote on roadmap

---

## 🎓 Best Practices

**Field Data Collection**
- Calibrate instruments before use
- Take photos for context
- Add notes for anomalies
- Flag questionable readings immediately

**Campaign Organization**
- Use descriptive campaign names
- Set clear boundaries with zones
- Regular data exports for backup
- Review flagged data weekly

**Satellite Analysis**
- Wait 2-3 days after field visit for satellite sync
- Compare multiple indices (NDVI + EVI)
- Account for seasonal variations
- Export combined datasets for deeper analysis

**Subscription Management**
- Monitor usage meter regularly
- Upgrade before hitting limits
- Download invoices for records
- Plan data collection around billing cycles

---

**Last Updated:** January 26, 2026  
**Version:** 1.0.0

| Task | Guide |
|------|-------|
| Submit first reading | [Submit Data](Submit-Data-Guide.md) |
| Measurement best practices | [Field Data Collection](Field-Data-Collection-Guide.md) |
| Create new campaign | [Campaign Management](Campaign-Management-Guide.md) |
| Draw survey zones | [Survey Zone Manager](Survey-Zone-Manager-Guide.md) |
| View all measurements | [Survey Map](Survey-Map-Guide.md) |
| Compare satellite data | [Satellite Viewer](Satellite-Viewer-Guide.md) |
| Find hotspots | [Heatmap Analytics](Heatmap-Guide.md) |
| Analyze trends | [Trend Analysis](Trend-Analysis-Guide.md) |
| **Generate PDF report** | **[PDF Reports](PDF-Reports-Guide.md)** ← NEW |
| Export data (JSON/CSV) | [Data Export](Data-Export-Guide.md) |
| Satellite index formulas | [Satellite Indices Reference](Satellite-Indices-Reference.md) |
| Metric units & ranges | [Environmental Metrics Reference](Environmental-Metrics-Reference.md) |

---

## Getting Started

**New Users:**
1. [Campaign Management](Campaign-Management-Guide.md) - Create campaign
2. [Survey Zone Manager](Survey-Zone-Manager-Guide.md) - Define study area
3. [Submit Data](Submit-Data-Guide.md) - Collect measurements
4. [Survey Map](Survey-Map-Guide.md) - View data

**Researchers:**
1. [Heatmap Analytics](Heatmap-Guide.md) - Identify patterns
2. [Trend Analysis](Trend-Analysis-Guide.md) - Temporal analysis
3. [Satellite Viewer](Satellite-Viewer-Guide.md) - Validate with satellite (7 indices)
4. **[PDF Reports](PDF-Reports-Guide.md)** - Generate publication-ready reports
5. [Data Export](Data-Export-Guide.md) - Export for statistical analysis

---

## What's New

**Phase 7 (January 16, 2026):**
- ✅ PDF report generation with one click
- ✅ Export dropdown in campaign management
- ✅ Professional formatting for publications
- ✅ Comprehensive campaign statistics
- ✅ Satellite index documentation

**Phase 6 (January 14, 2026):**
- ✅ 5 new satellite indices (NDRE, EVI, MSI, SAVI, GNDVI)
- ✅ Enhanced satellite viewer with 7 total indices
- ✅ Analysis panels for all indices

**Phase 5 (December 2025):**
- ✅ Heatmap analytics
- ✅ Trend analysis with confidence intervals
- ✅ Advanced statistics

---

## Support

Each guide is self-contained and focused on essential tasks. For detailed technical documentation, see `/docs/02-architecture/`.
