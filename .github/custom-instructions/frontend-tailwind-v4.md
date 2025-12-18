# Frontend: Tailwind CSS v4 + Vite

**Styling:** Tailwind CSS v4  
**Build Tool:** Vite  
**Plugin:** `@tailwindcss/vite`

---

## Design Philosophy

Modern, minimal, clean design with:
- Neutral palette with optional accent colors
- Soft shadows and `rounded-xl` corners
- Generous spacing (padding, margin, gap)
- Responsive by default (mobile-first)
- Clear visual hierarchy
- Zero clutter
- **Light mode by default** - only add dark mode if explicitly requested

---

## Tailwind v4 Configuration

### CSS-Based Config (Important!)
- Configuration lives in `resources/css/app.css` using `@theme` directive
- **NOT** in `tailwind.config.js` (kept for Vite compatibility only)
- Vite plugin handles compilation: `@tailwindcss/vite`

### CSS Setup
```css
@import "tailwindcss";
@plugin "@tailwindcss/forms";

@theme {
    --font-sans: 'Figtree', ui-sans-serif, system-ui, sans-serif;
    --color-primary: oklch(0.55 0.20 250);
    --color-accent: oklch(0.65 0.25 30);
}
```

### Custom Styling
Add custom utilities/components using `@layer`:
```css
@layer components {
    .btn-primary {
        @apply bg-indigo-600 text-white rounded-lg px-4 py-2 font-medium hover:bg-indigo-700 transition;
    }
}
```

---

## Tailwind v4 Key Changes

### Import Method
```css
/* ✅ Tailwind v4 */
@import "tailwindcss";

/* ❌ Tailwind v3 (don't use) */
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### Replaced Utilities
Use modern utilities, not deprecated ones:

| ❌ Deprecated | ✅ Replacement |
|--------------|----------------|
| `bg-opacity-*` | `bg-black/*` |
| `text-opacity-*` | `text-black/*` |
| `border-opacity-*` | `border-black/*` |
| `flex-shrink-*` | `shrink-*` |
| `flex-grow-*` | `grow-*` |
| `overflow-ellipsis` | `text-ellipsis` |

---

## Coding Standards

### Always Use Utilities
- ✅ Use Tailwind utility classes
- ❌ No inline styles
- ❌ No custom CSS unless absolutely necessary

### Spacing with Gap
When listing items, use `gap-*` for spacing (not margins):

```blade
<!-- ✅ Correct -->
<div class="flex gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
    <div>Item 3</div>
</div>

<!-- ❌ Wrong -->
<div class="flex">
    <div class="mr-4">Item 1</div>
    <div class="mr-4">Item 2</div>
    <div>Item 3</div>
</div>
```

### Spacing Scale
Use Tailwind's spacing scale consistently:
- `gap-4` for component spacing
- `px-6 py-4` for padding inside containers
- `mb-6` for vertical separation between sections
- **Never use margins for layout** — use `gap` in flex/grid containers

### Responsive Design
Mobile-first approach:
- default: mobile (< 640px)
- sm: 640px
- md: 768px
- lg: 1024px
- xl: 1280px
- 2xl: 1536px

```blade
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- Cards -->
</div>
```

### Common Layout Patterns

**Container & Grid:**
```blade
<div class="mx-auto max-w-7xl px-6 py-12">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Content -->
    </div>
</div>
```

**Flexbox:**
```blade
<div class="flex items-center justify-between gap-4">
    <h1>Title</h1>
    <button>Action</button>
</div>
```

**Responsive Patterns:**
```blade
<!-- Navigation -->
<nav class="hidden md:flex"><!-- Desktop nav --></nav>
<button class="md:hidden"><!-- Mobile menu toggle --></button>

<!-- Text Sizing -->
<h1 class="text-2xl md:text-3xl lg:text-4xl font-bold">Heading</h1>

<!-- Padding -->
<div class="px-4 md:px-6 lg:px-12 py-6 md:py-8 lg:py-12">Content</div>
```

---

---

## Common Component Patterns

### Card Component
```blade
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Title
    </h3>
    <p class="mt-2 text-gray-600 dark:text-gray-400">
        Description text
    </p>
</div>
```

### Button Variants
```blade
<!-- Primary -->
<button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium transition">
    Primary
</button>

<!-- Secondary -->
<button class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-900 dark:text-white px-4 py-2 rounded-lg font-medium transition">
    Secondary
</button>

<!-- Danger -->
<button class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition">
    Delete
</button>
```

### Form Input
```blade
<input 
    type="text" 
    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
>
```

### Grid Layouts
```blade
<!-- Two column responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>Column 1</div>
    <div>Column 2</div>
</div>

<!-- Auto-fit cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <!-- Cards -->
</div>
```

### Flexbox Layouts
```blade
<!-- Horizontal with spacing -->
<div class="flex items-center gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
</div>

<!-- Justify between -->
<div class="flex items-center justify-between">
    <div>Left</div>
    <div>Right</div>
</div>

<!-- Vertical stack -->
<div class="flex flex-col gap-4">
    <div>Item 1</div>
    <div>Item 2</div>
</div>
```

---

## Vite Development

### Development Mode
```powershell
# Hot module replacement (HMR)
npm run dev

# Part of composer run dev
composer run dev
```

Vite will:
- Watch for file changes
- Auto-reload browser on changes
- Inject CSS without page refresh
- Show build errors in browser overlay

### Production Build
```powershell
npm run build
```

Creates optimized assets in `public/build/`

### Vite Manifest Error
If you see "Unable to locate file in Vite manifest":
- Run `npm run build` for production
- Or ensure `npm run dev` is running for development

---

## Component Extraction

When you see repeated patterns, extract them into Blade components:

### Example: Card Component
```blade
<!-- resources/views/components/card.blade.php -->
@props(['title', 'description'])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6']) }}>
    @if(isset($title))
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ $title }}
        </h3>
    @endif
    
    @if(isset($description))
        <p class="mt-2 text-gray-600 dark:text-gray-400">
            {{ $description }}
        </p>
    @endif
    
    <div class="mt-4">
        {{ $slot }}
    </div>
</div>
```

Usage:
```blade
<x-card title="Podcast Title" description="Episode description">
    <div class="flex gap-2">
        <button>Play</button>
        <button>Share</button>
    </div>
</x-card>
```

---

## Color Palette Guidelines

### Primary Colors
- Use Tailwind's built-in color scales: `indigo`, `blue`, `purple`, `green`
- Consistent shades: `*-50` (lightest) to `*-950` (darkest)

### Semantic Colors
```blade
<!-- Success -->
<div class="bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400">
    Success message
</div>

<!-- Warning -->
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-400">
    Warning message
</div>

<!-- Error -->
<div class="bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400">
    Error message
</div>

<!-- Info -->
<div class="bg-blue-50 border border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400">
    Info message
</div>
```

---

## Typography

### Headings
```blade
<h1 class="text-4xl font-bold text-gray-900 dark:text-white">
    Page Title
</h1>

<h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
    Section Title
</h2>

<h3 class="text-xl font-medium text-gray-900 dark:text-white">
    Subsection Title
</h3>
```

### Body Text
```blade
<p class="text-base text-gray-700 dark:text-gray-300">
    Regular paragraph text
</p>

<p class="text-sm text-gray-600 dark:text-gray-400">
    Smaller supporting text
</p>

<p class="text-xs text-gray-500 dark:text-gray-500">
    Tiny meta information
</p>
```

---

---

## Color Palette

### Neutral Colors (Primary Use)
- `gray-50`, `gray-100`, `gray-200` - Light backgrounds
- `gray-600`, `gray-700`, `gray-800`, `gray-900` - Text colors
- `gray-200` - Borders
- `gray-100` - Subtle backgrounds

### Accent Color (Optional)
- `indigo-600` - Primary actions
- `indigo-700` - Hover states
- `indigo-50` - Light backgrounds

### Semantic Color Usage
- Success: `green-600` text / `green-50` background
- Warning: `amber-600` text / `amber-50` background  
- Error: `red-600` text / `red-50` background
- Info: `blue-600` text / `blue-50` background

---

## Shadows & Borders

### Shadows
- `shadow-sm` - Subtle depth (cards, inputs)
- `shadow-md` - Moderate depth (dropdowns, popovers)
- `hover:shadow-lg` - Hover states for emphasis
- **Keep it minimal** - Don't over-use shadows

### Borders
- `border border-gray-200` - Container borders
- `divide-y divide-gray-200` - Section dividers
- `ring-1 ring-gray-200` - Subtle focus states
- `focus:ring-2 focus:ring-indigo-600` - Active focus
- Radius: `rounded-lg` (default), `rounded-xl` (cards)

---

## Forms & Inputs

### Form Plugin
Use `@tailwindcss/forms` plugin (pre-configured):
- Consistent input styling across browsers
- Standard checkbox/radio button styling
- Select dropdown styling

### Form Components
```blade
<!-- Input with Label & Error -->
<div class="space-y-2">
    <label class="block text-sm font-medium text-gray-700">Email</label>
    <input 
        type="email" 
        class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 placeholder-gray-400 focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600"
        placeholder="you@example.com"
    />
    <p class="text-sm text-red-600">Error message here</p>
</div>

<!-- Checkbox -->
<label class="flex items-center gap-2">
    <input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
    <span class="text-sm text-gray-700">Remember me</span>
</label>

<!-- Select -->
<select class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-indigo-600 focus:ring-indigo-600">
    <option>Choose option</option>
</select>

<!-- Textarea -->
<textarea 
    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-900 focus:border-indigo-600 focus:ring-indigo-600" 
    rows="4"
></textarea>
```

---

## Buttons & CTAs

### Button Variants
```blade
<!-- Primary -->
<button class="rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white hover:bg-indigo-700 transition">
    Primary Action
</button>

<!-- Secondary -->
<button class="rounded-lg border border-gray-300 px-4 py-2 font-medium text-gray-700 hover:bg-gray-50 transition">
    Secondary
</button>

<!-- Tertiary (Text Link) -->
<button class="text-indigo-600 hover:text-indigo-700 font-medium transition">
    Text Link
</button>

<!-- Danger -->
<button class="rounded-lg bg-red-600 px-4 py-2 font-medium text-white hover:bg-red-700 transition">
    Delete
</button>

<!-- Disabled -->
<button class="rounded-lg bg-gray-300 px-4 py-2 font-medium text-gray-600 cursor-not-allowed" disabled>
    Disabled
</button>
```

---

## Tables

### Data Table Structure
```blade
<div class="overflow-x-auto rounded-lg border border-gray-200">
    <table class="w-full">
        <thead>
            <tr class="border-b border-gray-200 bg-gray-50">
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Name</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-sm text-gray-900">John Doe</td>
                <td class="px-6 py-4 text-sm text-gray-600">Active</td>
                <td class="px-6 py-4 text-sm">
                    <button class="text-indigo-600 hover:text-indigo-700">Edit</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

---

## Modals & Overlays

### Modal Template (Alpine.js)
```blade
<div x-data="{ open: false }">
    <button @click="open = true" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
        Open Modal
    </button>
    
    <!-- Backdrop -->
    <div x-show="open" 
         x-transition.opacity
         class="fixed inset-0 z-40 bg-black/50"
         @click="open = false">
    </div>
    
    <!-- Modal -->
    <div x-show="open"
         x-transition
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="rounded-xl bg-white shadow-lg w-full max-w-md" @click.stop>
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Confirm Action</h2>
            </div>
            <!-- Content -->
            <div class="px-6 py-4">
                <p class="text-gray-600">Are you sure you want to proceed?</p>
            </div>
            <!-- Footer -->
            <div class="border-t border-gray-200 flex justify-end gap-3 px-6 py-4">
                <button @click="open = false" class="px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg">
                    Cancel
                </button>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
```

---

## Navigation

### Header Navigation
```blade
<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-6 py-4 flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-900">Logo</h1>
        <nav class="hidden md:flex items-center gap-6">
            <a href="#" class="text-gray-600 hover:text-gray-900 font-medium">Dashboard</a>
            <a href="#" class="text-gray-600 hover:text-gray-900 font-medium">Settings</a>
        </nav>
        <button class="md:hidden text-gray-600">
            <!-- Mobile menu icon -->
        </button>
    </div>
</header>
```

---

## Accessibility

### ARIA Attributes
- Add `aria-label` to buttons without visible text (icon buttons)
- Use `aria-current="page"` for active navigation links
- Implement `aria-live` regions for dynamic content
- Use `aria-describedby` to link error messages to inputs

### Semantic HTML
- Use `<button>` for clickable actions, not `<div>` with onclick
- Use `<nav>` for navigation menus
- Use `<article>`, `<section>`, `<aside>` appropriately
- Always use `<label for="input-id">` with form inputs

### Color Contrast
- Maintain WCAG AA contrast ratios (4.5:1 for text, 3:1 for large text)
- Test text colors against backgrounds
- Don't rely solely on color to convey information

### Keyboard Navigation
- All interactive elements must be keyboard accessible
- Maintain visible focus indicators: `focus:ring-2 focus:ring-indigo-600`
- Ensure logical tab order (follows visual flow)
- Test with keyboard only (no mouse)

---

## Typography

### Font Sizes & Weights
- Headings: `text-3xl font-bold`, `text-2xl font-semibold`, `text-xl font-semibold`
- Body: `text-base` (default), `text-sm` for secondary
- Use `font-semibold` for emphasis, `font-bold` for headings
- Maintain proper hierarchy

### Text Color Guidelines
- Primary text: `text-gray-900`
- Secondary text: `text-gray-600`
- Muted/meta text: `text-gray-500`
- Links: `text-indigo-600 hover:text-indigo-700`

---

## Transitions & Animations

### Simple Transitions
```blade
<button class="bg-indigo-600 hover:bg-indigo-700 transition duration-150">
    Button
</button>

<div class="opacity-0 hover:opacity-100 transition-opacity duration-200">
    Fade in on hover
</div>
```

### Transition Classes
- `transition` - Smooth property changes
- `duration-150`, `duration-200`, `duration-300` - Timing
- `ease-in`, `ease-out`, `ease-in-out` - Easing functions
- Keep animations **subtle and purposeful**

### What to Avoid
- ❌ Excessive animations on every interaction
- ❌ Motion that could cause accessibility issues (respect `prefers-reduced-motion`)
- ❌ Animations longer than 300ms
- ❌ Distracting or unnecessary movement

---
```blade
<div class="animate-pulse">
    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
    <div class="mt-2 h-4 bg-gray-200 rounded w-1/2"></div>
</div>

<div class="animate-spin h-5 w-5 border-2 border-indigo-600 border-t-transparent rounded-full"></div>
```

---

---

## Code Output Requirements

When generating code:

### ✅ Do
- Deliver complete, copy-ready code
- Include all markup for the design
- Use Tailwind v4 utilities only
- Follow mobile-first responsive design
- Include accessibility considerations (ARIA labels, semantic HTML)
- Provide full components ready to use
- Use consistent spacing and color patterns

### ❌ Don't
- Use placeholder CSS or custom CSS (unless explicitly requested)
- Use inline styles
- Use outdated Tailwind v3 utilities
- Provide incomplete code snippets
- Include unnecessary comments
- Over-complicate layouts
- Add dark mode unless explicitly requested

---

## Common Mistakes to Avoid

1. **Don't use margins for layout** → Use `gap` in flex/grid containers
2. **Don't hardcode colors** → Use Tailwind color utilities
3. **Don't use inline styles** → Use Tailwind classes only
4. **Don't skip responsive design** → Always design mobile-first
5. **Don't forget accessibility** → Include labels and ARIA attributes
6. **Don't over-engineer components** → Keep them simple and reusable
7. **Don't use `@apply` everywhere** → Use utility classes directly in markup

---

## Best Practices

1. **Check existing components** before creating new ones
2. **Extract repeated patterns** into Blade components
3. **Use gap utilities** for spacing, not margins
4. **Light mode by default** - only add dark mode if explicitly requested
5. **Mobile-first responsive** design
6. **Semantic HTML** with proper ARIA labels
7. **Consistent spacing scale** (4, 6, 8, 12, 16, 24...)
8. **Limit custom CSS** - use Tailwind utilities

---

## Resources

- Use Laravel Boost's `search-docs` tool for Tailwind documentation
- Check `resources/views/components/` for existing reusable components
- Review sibling pages for consistent patterns

---

## Quick Reference Checklist

When generating a component:
- [ ] Mobile-first responsive (`grid-cols-1 md:grid-cols-2`)
- [ ] Proper spacing (`gap-6`, `px-6`, `py-4`)
- [ ] Accessibility (`aria-*`, labels, semantic HTML)
- [ ] Tailwind v4 utilities only
- [ ] Rounded corners (`rounded-lg`, `rounded-xl`)
- [ ] Soft shadows (`shadow-sm`, `shadow-md`)
- [ ] Color contrast compliant
- [ ] Complete, production-ready code
- [ ] Proper heading hierarchy
- [ ] Focus states on interactive elements
- [ ] Light mode by default (no dark mode unless requested)

