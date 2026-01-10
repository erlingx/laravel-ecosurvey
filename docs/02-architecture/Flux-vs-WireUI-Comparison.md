# Flux UI vs WireUI: Component Library Comparison

**Your Project Status:** Both installed (`livewire/flux: ^2.9.0` | `wireui/wireui: ^2.5`)

---

## Overview

| Feature | **Flux UI** | **WireUI** |
|---------|-------------|------------|
| **Developer** | Livewire (Caleb Porzio) | WireUI Team |
| **Focus** | Official Livewire components | Community-driven Alpine.js + Livewire |
| **Syntax** | `<flux:button>` | `<x-button>` |
| **Version** | v2.9 (Your project) | v2.5 (Your project) |
| **Pricing** | Free + Pro ($299) | Free + Premium features |
| **Primary Stack** | Livewire 3 + Tailwind v4 | Alpine.js + Livewire + Tailwind |
| **Philosophy** | Minimalist, server-first | Rich interactions, Alpine-heavy |

---

## Syntax Comparison

### Flux UI
```blade
{{-- Button --}}
<flux:button variant="primary" wire:click="save">
    Save
</flux:button>

{{-- Input with Livewire binding --}}
<flux:input 
    wire:model="email" 
    label="Email Address" 
    type="email" 
    required 
/>

{{-- Modal --}}
<flux:modal name="confirm">
    <flux:heading>Confirm Action</flux:heading>
    <p>Are you sure?</p>
</flux:modal>
```

### WireUI
```blade
{{-- Button --}}
<x-button primary wire:click="save">
    Save
</x-button>

{{-- Input with Livewire binding --}}
<x-input 
    wire:model="email" 
    label="Email Address" 
    type="email" 
    required 
/>

{{-- Modal --}}
<x-modal wire:model="confirmModal">
    <x-card title="Confirm Action">
        <p>Are you sure?</p>
    </x-card>
</x-modal>
```

---

## Component Availability

### Flux UI Free (What You Have)

**Forms:**
- `flux:input` - Text inputs
- `flux:textarea` - Multi-line text
- `flux:select` - Dropdowns
- `flux:checkbox` - Checkboxes
- `flux:radio` - Radio buttons
- `flux:field` - Form field wrapper

**UI Elements:**
- `flux:button` - Buttons with variants
- `flux:badge` - Status badges
- `flux:avatar` - User avatars
- `flux:icon` - Icon components
- `flux:modal` - Modals/dialogs
- `flux:dropdown` - Dropdown menus
- `flux:tooltip` - Tooltips

**Navigation:**
- `flux:navbar` - Navigation bars
- `flux:navlist` - Navigation lists
- `flux:breadcrumbs` - Breadcrumb trails

**Typography:**
- `flux:heading` / `flux:subheading`
- `flux:text`
- `flux:separator`

**Pro Only (Not Installed):**
- Tables, Cards, Tabs, Sidebars, Charts, Advanced layouts

---

### WireUI (Full Access)

**Forms:**
- `x-input` - Text inputs
- `x-textarea` - Multi-line text
- `x-select` - Dropdowns (native + searchable)
- `x-checkbox` - Checkboxes
- `x-radio` - Radio buttons
- `x-toggle` - Toggle switches
- `x-datetime-picker` - Date/time picker
- `x-color-picker` - Color picker
- `x-currency-input` - Currency formatting
- `x-masked-input` - Input masks
- `x-native-select` - Native select

**UI Elements:**
- `x-button` - Buttons (primary, secondary, etc.)
- `x-badge` - Badges
- `x-avatar` - Avatars
- `x-icon` - Icons (Heroicons)
- `x-card` - Card layouts
- `x-modal` - Modals
- `x-slide-over` - Slide-over panels
- `x-dropdown` - Dropdowns
- `x-dialog` - Confirmation dialogs

**Feedback:**
- `x-notifications` - Toast notifications
- `x-errors` - Error display
- `x-loading` - Loading indicators

**Advanced:**
- `x-time-picker` - Time selection
- `x-phone-input` - Phone number input
- `x-password-input` - Password with visibility toggle

---

## Key Differences

### 1. **Architecture**

**Flux:**
- **Server-first** - Heavy Livewire integration
- Minimal JavaScript (relies on Livewire)
- Blade-focused rendering
- Optimized for Livewire workflows

**WireUI:**
- **Alpine.js-first** - Rich client-side interactions
- JavaScript-heavy (Alpine.js components)
- Works standalone or with Livewire
- More client-side state management

---

### 2. **Component Philosophy**

**Flux:**
```blade
{{-- Minimalist, declarative --}}
<flux:button variant="primary">Save</flux:button>
<flux:input wire:model="name" label="Name" />
```

**WireUI:**
```blade
{{-- Expressive, feature-rich --}}
<x-button primary icon="check">Save</x-button>
<x-input wire:model.debounce="name" label="Name" placeholder="Enter your name" />
```

---

### 3. **Interactivity**

**Flux:**
- Relies on Livewire for most interactions
- Server round-trips for state changes
- Less client-side JavaScript

**WireUI:**
- Heavy Alpine.js usage for instant feedback
- Client-side validation and interactions
- Notifications without page reload

---

### 4. **Styling & Customization**

**Flux:**
- Built with Tailwind v4
- Clean, minimal design
- Follows Livewire design language
- Easy to extend with Tailwind classes

**WireUI:**
- Built with Tailwind CSS (any version)
- More opinionated styling
- Configurable themes in `config/wireui.php`
- Color palette customization

---

### 5. **Advanced Features**

| Feature | Flux | WireUI |
|---------|------|--------|
| **Date Picker** | ❌ (Pro only) | ✅ `x-datetime-picker` |
| **Toast Notifications** | ❌ | ✅ `x-notifications` |
| **Slide-over Panels** | ❌ (Pro only) | ✅ `x-slide-over` |
| **Color Picker** | ❌ | ✅ `x-color-picker` |
| **Phone Input** | ❌ | ✅ `x-phone-input` |
| **Currency Input** | ❌ | ✅ `x-currency-input` |
| **Tables** | ❌ (Pro: $299) | ❌ (Use Livewire Tables) |
| **Dark Mode** | ✅ Auto | ✅ Configurable |

---

## Performance Comparison

### Flux UI
- **Lighter JavaScript bundle** (Livewire-focused)
- Fewer client-side dependencies
- Server-side rendering優先
- Best for: Traditional server-side apps

### WireUI
- **Heavier JavaScript bundle** (Alpine.js + plugins)
- More client-side processing
- Better offline/instant feedback
- Best for: Rich interactive UIs

---

## Real-World Examples

### Example 1: Data Collection Form (EcoSurvey)

**Flux Version:**
```blade
<form wire:submit="submitReading">
    <flux:field>
        <flux:label>GPS Coordinates</flux:label>
        <flux:input wire:model="latitude" type="number" step="0.000001" />
        <flux:error name="latitude" />
    </flux:field>
    
    <flux:button type="submit" variant="primary">
        Submit Reading
    </flux:button>
</form>
```

**WireUI Version:**
```blade
<form wire:submit="submitReading">
    <x-input 
        wire:model.defer="latitude" 
        label="Latitude"
        type="number"
        step="0.000001"
        icon="location-marker"
        placeholder="Enter latitude"
    />
    
    <x-button primary type="submit" spinner>
        Submit Reading
    </x-button>
</form>

{{-- Toast notification on success --}}
<x-notifications />
```

---

### Example 2: Interactive Map Controls

**Flux Version:**
```blade
<div>
    <flux:select wire:model.live="metricType" label="Metric">
        <option value="aqi">Air Quality</option>
        <option value="temp">Temperature</option>
    </flux:select>
    
    <flux:button wire:click="refreshMap">
        Refresh
    </flux:button>
</div>
```

**WireUI Version:**
```blade
<div>
    <x-select 
        wire:model.live="metricType" 
        label="Metric"
        :options="[
            ['value' => 'aqi', 'label' => 'Air Quality'],
            ['value' => 'temp', 'label' => 'Temperature']
        ]"
    />
    
    <x-button 
        wire:click="refreshMap" 
        icon="refresh" 
        spinner="refreshMap"
    >
        Refresh
    </x-button>
</div>

{{-- Instant loading indicator (Alpine.js) --}}
<div x-show="$wire.loading">Loading...</div>
```

---

## When to Use Each?

### Use **Flux UI** When:
- ✅ Building traditional server-rendered apps
- ✅ You want official Livewire components
- ✅ Minimalist design is preferred
- ✅ Less JavaScript overhead is important
- ✅ You're heavily invested in Livewire ecosystem
- ✅ Budget allows Pro version ($299 for advanced components)

### Use **WireUI** When:
- ✅ You need rich client-side interactions
- ✅ Toast notifications are required
- ✅ Date/time pickers, color pickers needed
- ✅ Alpine.js is already in your stack
- ✅ You want more "batteries included" features
- ✅ Free advanced components are important

---

## EcoSurvey Recommendation

**For your project, use both strategically:**

### **Flux UI For:**
- ✅ Main layout and navigation (`flux:navbar`, `flux:navlist`)
- ✅ Form fields in admin panel
- ✅ Settings pages
- ✅ Authentication forms (already implemented)

### **WireUI For:**
- ✅ **Date/time pickers** for campaign dates
- ✅ **Toast notifications** for reading submissions
- ✅ **Color picker** for map marker customization
- ✅ **Slide-over panels** for quick data preview
- ✅ **Currency input** for subscription pricing

---

## Migration Path

### If You Choose One Library:

**Option 1: Standardize on Flux**
```powershell
# Remove WireUI
composer remove wireui/wireui
npm uninstall --save-dev wireui

# Update all x-* components to flux:*
```

**Option 2: Standardize on WireUI**
```powershell
# Remove Flux (not recommended - already in use)
composer remove livewire/flux

# Update all flux:* components to x-*
```

**Option 3: Use Both (Current Setup)**
- Keep both installed
- Use Flux for structure/layout
- Use WireUI for rich interactions
- Avoid mixing for the same component type

---

## Code Examples Side-by-Side

### Complex Form with Validation

**Flux:**
```blade
<flux:field>
    <flux:label>Email</flux:label>
    <flux:input 
        wire:model="email" 
        type="email" 
        placeholder="user@example.com"
    />
    <flux:error name="email" />
    <flux:description>
        We'll never share your email.
    </flux:description>
</flux:field>
```

**WireUI:**
```blade
<x-input 
    wire:model="email" 
    label="Email"
    placeholder="user@example.com"
    hint="We'll never share your email"
    icon="mail"
    corner-hint="Required"
/>
```

---

### Modal Dialog

**Flux:**
```blade
<flux:modal name="delete-confirm">
    <flux:heading>Delete Reading?</flux:heading>
    <flux:subheading>This action cannot be undone.</flux:subheading>
    
    <flux:button wire:click="delete" variant="danger">
        Delete
    </flux:button>
</flux:modal>

{{-- Trigger --}}
<flux:button wire:click="$toggle('delete-confirm')">
    Delete
</flux:button>
```

**WireUI:**
```blade
<x-modal wire:model="showDeleteModal" align="center">
    <x-card title="Delete Reading?">
        <p>This action cannot be undone.</p>
        
        <x-slot name="footer">
            <x-button flat label="Cancel" wire:click="$set('showDeleteModal', false)" />
            <x-button negative label="Delete" wire:click="delete" />
        </x-slot>
    </x-card>
</x-modal>

{{-- Trigger --}}
<x-button negative wire:click="$set('showDeleteModal', true)">
    Delete
</x-button>
```

---

## Installation Commands

### If You Need to Add Later:

```powershell
# Install Flux
composer require livewire/flux

# Install WireUI
composer require wireui/wireui
php artisan wireui:install
```

---

## Final Verdict for EcoSurvey

**Hybrid Approach (Current Setup is Best):**

```blade
{{-- Use Flux for structure --}}
<flux:navbar>
    <flux:navlist>
        <flux:navlist.item href="/dashboard">Dashboard</flux:navlist.item>
    </flux:navlist>
</flux:navbar>

{{-- Use WireUI for rich interactions --}}
<form wire:submit="submitReading">
    {{-- Flux for basic inputs --}}
    <flux:input wire:model="title" label="Title" />
    
    {{-- WireUI for advanced inputs --}}
    <x-datetime-picker wire:model="readingDate" label="Reading Date" />
    <x-color-picker wire:model="markerColor" label="Map Marker Color" />
    
    {{-- Flux button --}}
    <flux:button type="submit" variant="primary">Submit</flux:button>
</form>

{{-- WireUI notifications --}}
<x-notifications />
```

**Summary:**
- **Flux** = Structural components, forms, navigation
- **WireUI** = Advanced inputs, notifications, rich interactions
- **Both together** = Maximum flexibility with minimal redundancy

---

**Your project already has the best of both worlds installed. Use them strategically!**

