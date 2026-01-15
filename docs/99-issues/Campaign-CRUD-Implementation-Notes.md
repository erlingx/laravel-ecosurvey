# Campaign Management CRUD - Implementation Notes

**Status:** PARTIAL - Structure created, requires Filament 4 pattern completion  
**Date:** January 15, 2026

---

## What Was Created

### Files Created:
1. `app/Filament/Admin/Resources/CampaignResource.php` - Main resource file
2. `app/Filament/Admin/Resources/CampaignResource/Pages/ListCampaigns.php` - List page
3. `app/Filament/Admin/Resources/CampaignResource/Pages/CreateCampaign.php` - Create page  
4. `app/Filament/Admin/Resources/CampaignResource/Pages/EditCampaign.php` - Edit page
5. `app/Filament/Admin/Resources/CampaignResource/Pages/ViewCampaign.php` - View page
6. `tests/Feature/Filament/CampaignResourceTest.php` - Test file

---

## Issue Encountered

This project uses **Filament 4** with a non-standard structure that differs from typical Filament resources:

**Standard Filament Pattern:**
```php
public static function form(Form $form): Form
public static function table(Table $table): Table
```

**This Project's Pattern:**
```php
public static function form(Schema $schema): Schema
public static function table(Table $table): Table
```

Additionally, this project extracts forms to separate schema classes (see `UserForm`, `UsersTable`).

---

## What Needs to Be Done

To complete the Campaign CRUD following this project's patterns:

### 1. Create Schema Classes

Create these files following the User resource pattern:

**app/Filament/Admin/Resources/CampaignResource/Schemas/CampaignForm.php**
```php
<?php

namespace App\Filament\Admin\Resources\CampaignResource\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            // Move form components here from CampaignResource
        ]);
    }
}
```

**app/Filament/Admin/Resources/CampaignResource/Tables/CampaignsTable.php**
```php
<?php

namespace App\Filament\Admin\Resources\CampaignResource\Tables;

use Filament\Tables;
use Filament\Tables\Table;

class CampaignsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            // Move table columns here from CampaignResource
        ]);
    }
}
```

### 2. Update CampaignResource

Then simplify the resource to:

```php
public static function form(Schema $schema): Schema
{
    return CampaignForm::configure($schema);
}

public static function table(Table $table): Table
{
    return CampaignsTable::configure($table);
}
```

### 3. Fix ViewCampaign Page

The View page needs to use Schema components, not Infolist. Study the Users resource to see if they have a View page, and replicate that pattern.

---

## Alternative: Use Livewire Volt Instead

Since the project already uses Volt extensively (satellite-viewer, zone-manager), consider creating a **Volt-based Campaign CRUD** instead:

**Benefits:**
- Consistent with existing project patterns
- Full control over UI
- No Filament version compatibility issues
- Already proven to work (zone-manager is Volt)

**Create:**
- `resources/views/livewire/campaigns/campaign-list.blade.php`
- `resources/views/livewire/campaigns/campaign-form.blade.php`
- Routes: `/campaigns`, `/campaigns/create`, `/campaigns/{id}/edit`

This would be faster and more consistent with the existing codebase.

---

## Current Campaign Access

For now, campaigns can be managed via:
1. **Database seeds** - Create campaigns programmatically
2. **Tinker** - `Campaign::create([...])`
3. **Survey Zone Manager** - Indirectly via campaign selection
4. **Direct SQL** - For admin users

---

## Recommendation

**Option 1 (Quick):** Create Volt-based Campaign CRUD (2-3 hours)
- Follows existing project patterns
- No Filament compatibility issues
- Full UI control

**Option 2 (Proper):** Complete Filament 4 integration (4-6 hours)
- Study existing User resource structure
- Extract schemas to separate classes
- Fix all type mismatches
- Requires deep Filament 4 knowledge

**Option 3 (Minimal):** Document current state and defer
- Add to roadmap as "Phase 5: Admin Interface Enhancement"
- Focus on core scientific features first
- Campaign management works via Tinker for now

---

## Files to Review

To understand this project's Filament 4 patterns:
- `app/Filament/Admin/Resources/Users/UserResource.php`
- `app/Filament/Admin/Resources/Users/Schemas/UserForm.php`
- `app/Filament/Admin/Resources/Users/Tables/UsersTable.php`
- `app/Filament/Admin/Resources/Users/Pages/`

---

**Next Action:** Choose approach and implement accordingly.

