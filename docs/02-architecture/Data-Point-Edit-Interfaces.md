# Data Point Edit Interfaces - Documentation

**Date:** January 16, 2026  
**Status:** CLARIFIED

---

## Two Edit Interfaces Explained

The EcoSurvey system has **TWO separate edit interfaces** for data points. This is **intentional design**, not a bug or conflict.

---

## 1. Admin Panel Edit (Phase 8)

**Location:** `/admin/data-points/{id}/edit`  
**Access:** Admin users only  
**Component:** Filament Resource  
**Purpose:** Full data quality management

### Features:
- ✅ All fields editable
- ✅ Status control (draft/pending/approved/rejected)
- ✅ Campaign reassignment
- ✅ User reassignment
- ✅ GPS coordinates (lat/lon manual entry)
- ✅ Device and sensor information
- ✅ Photo upload/replacement
- ✅ Delete/restore actions
- ✅ Full audit trail

### Use Cases:
- Quality assurance review
- Data corrections by admins
- Status management (approve/reject)
- Bulk data management
- Reassign misclassified data

### Access Control:
- Requires admin role
- Full permissions
- Can change any field
- Can delete permanently

---

## 2. Map Edit Modal (Phase 3)

**Location:** `/maps/survey` → Click data point marker  
**Access:** All authenticated users  
**Component:** Livewire Volt Modal  
**Purpose:** Field user corrections

### Features:
- ✅ Simplified form
- ✅ GPS auto-capture
- ✅ Manual GPS entry
- ✅ Photo upload
- ✅ Notes editing
- ✅ Device/sensor info
- ✅ Limited to own data

### Use Cases:
- Quick corrections in the field
- Update notes after submission
- Replace photo
- Fix GPS coordinates

### Access Control:
- Users can edit own data
- Cannot change status to "approved"
- Cannot reassign to different campaign
- Cannot change user assignment
- Limited permissions

---

## Comparison Table

| Feature | Admin Panel Edit | Map Edit Modal |
|---------|------------------|----------------|
| **Location** | `/admin/data-points/{id}/edit` | `/maps/survey` (popup) |
| **Access** | Admin only | All users |
| **Interface** | Filament full page | Livewire modal |
| **Campaign** | Can change | Cannot change |
| **User** | Can reassign | Cannot change |
| **Status** | Full control | Limited |
| **GPS** | Manual lat/lon | Auto-capture + manual |
| **Photo** | Upload | Upload |
| **Delete** | Yes (soft/hard) | No |
| **Bulk** | Yes | No |
| **Use Case** | QA & management | Field corrections |

---

## Why Two Interfaces?

### Separation of Concerns:
**User Interface (Map Modal):**
- Simple and focused
- Quick edits in the field
- GPS-centric workflow
- Limited to safe operations

**Admin Interface (Admin Panel):**
- Comprehensive control
- Quality assurance workflow
- Batch operations
- Full data management

### Security:
- Users can't approve their own data
- Users can't reassign data maliciously
- Admins have full control for QA
- Clear audit trail of changes

### User Experience:
- Field users don't need complex admin UI
- Map modal is contextual (shows on map)
- Admin panel has filters, bulk actions, etc.
- Each interface optimized for its use case

---

## Database Consistency

**Both interfaces update the same `data_points` table:**
- Same PostGIS location column handling
- Same validation rules
- Same status workflow
- Same relationships (campaign, metric, user)

**No conflicts because:**
- Different routes (no overlap)
- Different permissions (role-based)
- Same underlying model (`App\Models\DataPoint`)
- Both handle GPS coordinates correctly

---

## GPS Coordinate Handling

**Database Column:** `location geometry(POINT, 4326)` (PostGIS geometry, not geography)

**Admin Panel:**
```php
// EditDataPoint.php
mutateFormDataBeforeFill() 
- Extracts lat/lon from PostGIS using ST_Y/ST_X
- Query: ST_Y(location::geometry), ST_X(location::geometry)

mutateFormDataBeforeSave()
- Converts lat/lon to PostGIS geometry point
- Query: ST_SetSRID(ST_MakePoint(lon, lat), 4326)
- Note: Uses geometry, not geography (matches column type)
```

**Map Modal:**
```php
// reading-form.blade.php
Uses Livewire state for latitude/longitude
Auto-captures from browser geolocation API
Stores via DataPoint model mutators
```

Both methods correctly update the PostGIS `location` geometry column.

---

## Testing Both Interfaces

**Admin Panel Edit:**
- Test via `/admin/data-points`
- Click Edit button
- Verify 4 form sections
- Test all field types
- Save and verify

**Map Edit Modal:**
- Test via `/maps/survey`
- Click data point marker
- Click "Edit" in popup
- Verify modal form
- Save and verify map updates

---

## Future Enhancements

**Potential improvements (not needed now):**
- Audit log showing which interface was used
- Admin can see "Last edited via: Map / Admin Panel"
- Version history comparing edits
- Conflict detection if edited simultaneously

**Current status:** No enhancements needed. System works as designed.

---

## Conclusion

✅ **Two edit interfaces is intentional design**  
✅ **No conflict exists**  
✅ **Both serve different purposes**  
✅ **Both update database correctly**  
✅ **Security and UX optimized for each role**

**Status:** Working as intended. No action required.

---

**Documentation updated:** January 16, 2026  
**Tested:** Both interfaces functional  
**Issue:** RESOLVED (not an issue, by design)
