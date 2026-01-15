# Survey Zone Manager - User Guide

**Last Updated:** January 15, 2026  
**Feature Version:** Phase 4 Enhancement  
**Target Audience:** Campaign Managers, Research Coordinators

---

## Overview

The Survey Zone Manager allows you to visually define and manage geographic boundaries for your research campaigns. Survey zones help you:
- Define primary study areas
- Organize data collection efforts
- Calculate spatial statistics by zone
- Visualize campaign coverage

**Access:** `/campaigns/{campaign-id}/zones/manage`

---

## Quick Start (60 seconds)

1. Navigate to a campaign's zone management page
2. Click the **polygon icon** (⬡) in the map toolbar
3. Click on the map to draw your zone boundary
4. Double-click to complete the polygon
5. Enter a zone name and optional description
6. Zone is automatically saved with calculated area

---

## Interface Overview

### Map Editor (Left 2/3)

The interactive map shows:
- **Your drawing tools** (top-right toolbar)
- **Existing survey zones** (blue dashed polygons)
- **Data points** (green circles) to verify coverage
- **Base map** (OpenStreetMap)

### Zone List (Right 1/3)

Displays all zones for the campaign with:
- Zone name and description
- Calculated area in km²
- Edit and Delete buttons
- Total zone count

---

## Creating a Survey Zone

### Step-by-Step

1. **Start Drawing**
   - Click the **polygon icon** (⬡) in the toolbar
   - Your cursor changes to a crosshair (+)

2. **Draw the Boundary**
   - Click points on the map to define the boundary
   - Connect as many points as needed for accuracy
   - The polygon fills in as you draw

3. **Complete the Polygon**
   - Double-click the last point, OR
   - Click the first point again
   - A prompt appears asking for zone details

4. **Name Your Zone**
   - Enter a descriptive name (required)
   - Examples: "North Field", "Urban Area", "Control Zone A"

5. **Add Description** (Optional)
   - Provide additional context
   - Examples: "Primary vegetation study area", "High traffic zone"

6. **Save**
   - Zone is automatically saved
   - Area is calculated using PostGIS
   - Zone appears in the sidebar list

### Tips for Drawing Zones

✅ **Do:**
- Use enough points to capture the true boundary
- Verify the zone covers your data points
- Use descriptive names that identify the area
- Check the auto-calculated area for accuracy

❌ **Avoid:**
- Self-intersecting polygons (lines crossing each other)
- Too few points (minimum 3 required)
- Overly complex shapes (use 10-20 points typically)
- Generic names like "Zone 1" (be specific)

---

## Editing a Survey Zone

You can update a zone's name and description (but not the boundary):

1. Find the zone in the sidebar list
2. Click the **Edit** button
3. Modify the name or description
4. Click **Save**

**Note:** To change the boundary, you must delete and recreate the zone.

---

## Deleting a Survey Zone

1. Find the zone in the sidebar list
2. Click the **Delete** button
3. Confirm deletion in the modal
4. Zone is permanently removed

**Warning:** This action cannot be undone. Data points are NOT deleted—they just won't be associated with this zone anymore.

---

## Understanding Zone Areas

Areas are automatically calculated using PostGIS `ST_Area()`:
- Displayed in **square kilometers (km²)**
- Calculated on save (no manual entry needed)
- Based on WGS84 geographic coordinates
- Accounts for Earth's curvature

**Example:**
- Small urban plot: 0.05 km² (50,000 m²)
- City park: 2.5 km²
- Large nature reserve: 100+ km²

---

## Visual Features

### Zone Display

Zones are shown as:
- **Blue dashed border** (easy to distinguish from data)
- **Light blue fill** (10% opacity to see map beneath)
- **Interactive popups** (click to see details)

### Data Point Overlay

Green circle markers show where data has been collected:
- Helps verify your zone covers the intended area
- Click markers to see measurement details
- Identifies gaps in coverage

---

## Common Use Cases

### 1. Urban vs. Rural Comparison
Create two zones:
- "Urban Core" - city center area
- "Rural Buffer" - surrounding countryside

Compare environmental metrics between zones using spatial statistics.

### 2. Multi-Site Study
Create zones for each distinct study site:
- "Site A - Forest"
- "Site B - Wetland"
- "Site C - Grassland"

### 3. Treatment Areas
For experimental studies:
- "Control Area"
- "Treatment Area 1"
- "Treatment Area 2"

### 4. Temporal Expansion
Document how study area grew:
- "Phase 1 Area (Jan-Mar)"
- "Phase 2 Expansion (Apr-Jun)"

---

## Technical Details

### Data Storage

- **Format:** PostGIS `geography(POLYGON, 4326)`
- **Coordinate System:** WGS84 (same as GPS)
- **Precision:** ~10cm accuracy for boundaries
- **Size:** No practical limit on zone complexity

### Permissions

- Zone management requires authentication
- Only authorized campaign members can create/edit zones
- Zones are campaign-specific (isolated between campaigns)

### Performance

- Map handles 50+ zones without lag
- Drawing tools are client-side (instant response)
- Calculations done server-side via PostgreSQL

---

## Troubleshooting

### "Zone name is required" Error
**Cause:** You cancelled the name prompt  
**Solution:** Redraw the polygon and enter a name when prompted

### Zone Doesn't Display
**Cause:** Geometry may be invalid  
**Solution:** Ensure polygon doesn't self-intersect, check browser console for errors

### Can't See Data Points
**Cause:** Campaign may have no data yet  
**Solution:** Verify campaign has approved data points, check campaign filter

### Area Seems Wrong
**Cause:** Area is in km², might expect m²  
**Solution:** Multiply by 1,000,000 to convert to m² (1 km² = 1,000,000 m²)

---

## Best Practices

### Naming Conventions

Good examples:
- ✅ "Fælledparken North Meadow"
- ✅ "Urban Traffic Zone (City Center)"
- ✅ "Coastal Monitoring Area A"

Poor examples:
- ❌ "Zone1"
- ❌ "Test"
- ❌ "asdf"

### Zone Design

- **Keep it simple:** 8-15 boundary points is usually enough
- **Be consistent:** Use similar shapes for comparable zones
- **Document purpose:** Use the description field
- **Verify coverage:** Check that data points fall inside the zone

### Workflow Integration

1. **Plan First:** Sketch zones on paper/map before creating
2. **Create Zones:** Use this tool to define boundaries
3. **Collect Data:** Gather measurements within zones
4. **Analyze:** Use spatial statistics to compare zones
5. **Report:** Reference zones in publications/reports

---

## Related Features

- **Satellite Viewer:** See zones overlaid on satellite imagery
- **Spatial Statistics:** Analyze data by zone (in development)
- **Data Export:** Export zone boundaries as GeoJSON

---

## Glossary

**Survey Zone:** Defined geographic boundary for a research area  
**Polygon:** Closed shape with 3+ vertices  
**WGS84:** World Geodetic System 1984, standard GPS coordinate system  
**PostGIS:** PostgreSQL extension for spatial data  
**km²:** Square kilometers (area unit)  
**GeoJSON:** Standard format for geographic data  

---

## Examples

### Example 1: Park Study

**Campaign:** "Urban Green Spaces 2026"  
**Zones:**
1. "North Lawn" (0.15 km²) - Open grass area
2. "Forest Section" (0.45 km²) - Dense tree coverage
3. "Pond Area" (0.08 km²) - Water feature zone

**Result:** Compare air quality and temperature across different park environments.

### Example 2: Coastal Monitoring

**Campaign:** "Coastal Erosion Study"  
**Zones:**
1. "Beach Zone" (2.3 km²) - Sandy beach area
2. "Dune System" (1.8 km²) - Protected dune habitat
3. "Wetland Buffer" (3.5 km²) - Transitional wetland

**Result:** Track environmental changes across coastal ecosystems.

---

## FAQ

**Q: Can I edit a zone's boundary after creation?**  
A: No, you must delete and recreate. This ensures data integrity.

**Q: How many zones can I create?**  
A: No hard limit, but 5-10 zones per campaign is typical.

**Q: Do data points need to be inside zones?**  
A: No, points outside zones are fine (they provide context).

**Q: Can zones overlap?**  
A: Yes, overlapping is allowed (useful for nested study areas).

**Q: Are zones shared across campaigns?**  
A: No, each campaign has its own isolated zones.

**Q: Can I export zone boundaries?**  
A: Yes, zones can be exported as GeoJSON (feature in development).

---

## Support

For technical issues or questions:
- Check the troubleshooting section above
- Contact your campaign coordinator
- Report bugs to the system administrator

---

**Last Updated:** January 15, 2026  
**Next Review:** Phase 5 Implementation

