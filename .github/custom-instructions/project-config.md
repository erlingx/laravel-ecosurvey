# Project Configuration

**Project:** Laravel Listening Party  
**Last updated:** 2025-12-11

---

## Active Instruction Sets

This project uses the following instruction modules:

1. **Environment:** `environment-native.md` (Native PHP/Composer, no DDEV)
2. **Stack:** `stack-livewire-volt.md` (Livewire v3 + Volt)
3. **Frontend:** `frontend-tailwind-v4.md` (Tailwind CSS v4)
4. **Editor:** `editor-phpstorm.md` (PhpStorm on Windows 11)

---

## Project Summary

- **Framework:** Laravel 12
- **Runtime:** Native PHP 8.3 (not DDEV/Sail)
- **Frontend Stack:** Livewire v3 + Volt + Flux UI
- **Styling:** Tailwind CSS v4
- **Build Tool:** Vite
- **Testing:** Pest v4

---

## Development Workflow

### Starting Development
```powershell
# Start all services (server, queue, vite) in one command:
composer run dev

# Or manually:
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

### After Code Changes
- **Queue/Job changes:** `php artisan queue:restart` (fast!)
- **Never restart entire server** for queue changes

---

## Key Differences from DDEV Projects

- ✅ Use `php artisan` (not `ddev artisan`)
- ✅ Use `composer` (not `ddev composer`)
- ✅ Use `npm` (not `ddev npm`)
- ✅ Use `vendor/bin/pest` (not `ddev exec`)
- ✅ PowerShell commands work normally
- ✅ Can chain with `;` in PowerShell

---

## Priority Rules

1. **Laravel Boost guidelines ALWAYS take precedence**
2. **Just code** - no explanations unless asked
3. **Action-oriented** - do things instead of asking
4. **Never create/update .md files** unless explicitly requested
5. **Never commit git changes** without explicit confirmation

---

## Laravel 12 Specifics

### Framework Structure
- **No `app/Http/Kernel.php`** - Use `bootstrap/app.php` for middleware/routes/exceptions
- Routes: `routes/web.php`, `routes/auth.php`, `routes/console.php`
- Commands auto-register from `app/Console/Commands/`

### Coding Standards
- Use **Form Request classes** for validation (not inline `->validate()`)
- Use **route model binding** where possible
- Prefer **Eloquent** over raw queries
- Define **relationships with return types**
- Use **eager loading** to prevent N+1 queries (`with()`)
- Use **named routes**: `route('articles.edit', $article)`

---

## CKEditor 5 (Rich Text Editor)

**Important:** This project uses **CKEditor 5**, NOT CKEditor 4!

### Key Differences
- **No global `CKEDITOR` object** - editors stored in local Map
- **Event-based communication** - use custom DOM events
- **Initialization:** `resources/js/ckeditor-init.js`

### Available Features
- Bold, Italic, Headings, Links, Lists
- Images (with upload support)
- Tables, Block Quotes
- **Source Editing** - Users can view/edit raw HTML
- Undo/Redo

### Working with CKEditor 5
```javascript
// ❌ WRONG - CKEditor 4 API (doesn't exist)
if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['content']) {
    CKEDITOR.instances['content'].setData(text);
}

// ✅ CORRECT - Use custom events
const textarea = document.getElementById('content');
textarea.value = text;
const event = new CustomEvent('document-upload:fill', {
    detail: { content: text },
    bubbles: true
});
textarea.dispatchEvent(event);
```

### CKEditor 5 Events
- Editors listen for `document-upload:fill` event on their textarea
- Dispatch `ckeditor:change` event when content changes
- Auto-syncs to textarea on `change:data`

### Modifying CKEditor
- Edit `resources/js/ckeditor-init.js`
- Add event listeners in `.then(editor => { ... })` callback
- Remember to rebuild: `npm run build`

