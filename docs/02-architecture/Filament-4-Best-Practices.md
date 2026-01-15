# Filament 4 Best Practices - Project Standards

**Version:** Filament 4.5.2  
**Date:** January 15, 2026  
**Status:** ✅ Project Correctly Follows Filament 4 Standards

---

## ✅ Confirmation: This Project Uses Filament 4 Correctly

**Installed Version:** `filament/filament v4.5.2` (Latest stable, December 2024)

**Standard Filament 4 API (from vendor/filament/filament/src/Resources/Resource.php):**
```php
public static function form(Schema $schema): Schema
public static function infolist(Schema $schema): Schema  
public static function table(Table $table): Table
```

**This project correctly uses:**
- ✅ `Schema` type (not `Form`) - Standard Filament 4
- ✅ `Heroicon` enum for icons - Type-safe approach
- ✅ `recordActions` for row actions - Correct terminology
- ✅ `toolbarActions` for bulk actions - Correct terminology

---

## 🏗️ Project Architecture Pattern

### Clean Separation Pattern (Recommended)

```
app/Filament/Admin/Resources/
├── [Resource]Resource.php          # Orchestrator (delegates)
├── [Resource]/
    ├── Pages/
    │   ├── List[Resource]s.php     # Table view
    │   ├── Create[Resource].php    # Create form
    │   └── Edit[Resource].php      # Edit form (no separate View)
    ├── Schemas/
    │   └── [Resource]Form.php      # Form definition (extracted)
    └── Tables/
        └── [Resource]sTable.php    # Table definition (extracted)
```

### Why Extract Schemas/Tables?

**Benefits:**
1. **Single Responsibility** - Each class has one clear purpose
2. **Testability** - Test forms/tables in isolation
3. **Reusability** - Use same form in different contexts
4. **Maintainability** - Avoid 500+ line resource files
5. **Team Collaboration** - Multiple developers work without conflicts

**Example from UserResource:**
- `UserResource.php` - 49 lines (clean orchestrator)
- `UserForm.php` - 34 lines (form logic)
- `UsersTable.php` - 50 lines (table logic)

**Total:** 133 lines across 3 files vs 133 lines in 1 bloated file

---

## 📋 Standard Resource Template

```php
<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\[Resource]\Pages;
use App\Filament\Admin\Resources\[Resource]\Schemas\[Resource]Form;
use App\Filament\Admin\Resources\[Resource]\Tables\[Resource]sTable;
use App\Models\[Model];
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class [Resource]Resource extends Resource
{
    protected static ?string $model = [Model]::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Outlined[Icon];

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return [Resource]Form::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return [Resource]sTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Relation managers here
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\List[Resource]s::route('/'),
            'create' => Pages\Create[Resource]::route('/create'),
            'edit' => Pages\Edit[Resource]::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Optional: Show count in navigation
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
```

---

## 📝 Form Schema Template

```php
<?php

namespace App\Filament\Admin\Resources\[Resource]\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class [Resource]Form
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),

                        DatePicker::make('created_at')
                            ->native(false)
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }
}
```

---

## 📊 Table Definition Template

```php
<?php

namespace App\Filament\Admin\Resources\[Resource]\Tables;

use App\Models\[Model];
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class [Resource]sTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'active',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                    ])
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
```

---

## 🎯 Key Filament 4 Conventions

### 1. Schema (Not Form)

**Filament 4:**
```php
public static function form(Schema $schema): Schema
```

**Filament 3 (Old):**
```php
public static function form(Form $form): Form
```

### 2. Heroicon Enum (Not String)

**Filament 4:**
```php
protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;
```

**Filament 3 (Old):**
```php
protected static ?string $navigationIcon = 'heroicon-o-map';
```

### 3. Record Actions (Not Actions)

**Filament 4:**
```php
->recordActions([
    EditAction::make(),
])
```

**Filament 3 (Old):**
```php
->actions([
    EditAction::make(),
])
```

### 4. Toolbar Actions (Not Bulk Actions)

**Filament 4:**
```php
->toolbarActions([
    BulkActionGroup::make([
        DeleteBulkAction::make(),
    ]),
])
```

**Filament 3 (Old):**
```php
->bulkActions([
    BulkActionGroup::make([
        DeleteBulkAction::make(),
    ]),
])
```

---

## 📦 Page Classes

### List Page

```php
class List[Resource]s extends ListRecords
{
    protected static string $resource = [Resource]Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
```

### Create Page

```php
class Create[Resource] extends CreateRecord
{
    protected static string $resource = [Resource]Resource::class;
}
```

### Edit Page

```php
class Edit[Resource] extends EditRecord
{
    protected static string $resource = [Resource]Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
```

**Note:** No View page needed - Edit page combines view + edit functionality.

---

## ✅ Checklist for New Resources

When creating a new Filament resource:

- [ ] Use `Schema` type for forms
- [ ] Use `Heroicon` enum for icons
- [ ] Extract form to `Schemas/[Resource]Form.php`
- [ ] Extract table to `Tables/[Resource]sTable.php`
- [ ] Use `recordActions` for row actions
- [ ] Use `toolbarActions` for bulk actions
- [ ] Create List + Create + Edit pages (no View)
- [ ] Add navigation badge if useful
- [ ] Add filters for common queries
- [ ] Format with Pint
- [ ] Write tests

---

## 📚 Resources

**Official Documentation:**
- https://filamentphp.com/docs/4.x/panels/resources

**Filament 4 Upgrade Guide:**
- https://filamentphp.com/docs/4.x/panels/upgrade-guide

**This Project Examples:**
- `app/Filament/Admin/Resources/Users/UserResource.php`
- `app/Filament/Admin/Resources/Users/Schemas/UserForm.php`
- `app/Filament/Admin/Resources/Users/Tables/UsersTable.php`

---

**Follow these standards for all Filament resources in this project.**

