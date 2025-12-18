# Editor: PhpStorm

**IDE:** PhpStorm 2025.2+  
**OS:** Windows 11  
**Shell:** PowerShell

---

## Behavior Rules

### Response Style
- **Just code** - no explanations unless asked
- **Action-oriented** - do things instead of asking permission
- **Concise** - focus on what's important, not obvious details
- **After fixes** - Only provide headlines/bullet points, no long summaries or detailed recaps

### What NOT to Do
- ❌ **NEVER create/update .md files** unless explicitly requested
- ❌ **NEVER commit git changes** without explicit user confirmation
- ❌ **NEVER run `pint` automatically** - only remind user to run it
- ❌ **NEVER explain obvious changes** - just make them

---

## Code Quality

### Laravel Pint
Before finalizing changes, **remind user** to run:
```powershell
vendor/bin/pint --dirty
```

**Never run it automatically** - let user decide when.

### After Making Changes
1. **Run related tests** to verify changes work
2. **Check for errors** using IDE feedback
3. **Remind about Pint** if code formatting matters
4. **Keep it brief** - just confirm what was done

---

## Git Workflow

**ALWAYS ask for confirmation before:**
- `git commit`
- `git push`
- `git checkout` / `git branch`
- `git merge` / `git rebase`

Example:
> "Ready to commit these changes to the `feature/podcasts` branch?"

Wait for explicit user approval.

---

## File Operations

### When Creating Files
- Follow existing project structure
- Check sibling files for conventions
- Use `php artisan make:*` commands when available
- Don't create temporary or example files without asking

### When Editing Files
- Read the file first if not in context
- Use `replace_string_in_file` or `insert_edit_into_file`
- **Never show code blocks** to user - just apply the changes
- Validate with `get_errors` after editing

### Documentation Files
- **Do not create** `.md` files unless explicitly requested
- This includes: README, docs, guides, comments files
- Code comments are fine, but no separate documentation files

---

## Testing Workflow

### After Code Changes
1. **Identify affected tests** based on changes made
2. **Run minimum necessary tests** using filter:
   ```powershell
   php artisan test --filter=ListeningParty
   ```
3. **Fix any failures** immediately
4. **Ask about full suite** if needed:
   > "Tests passing. Run full suite to check for regressions?"

### Test Creation
- Create tests for every new feature
- Update tests for every behavioral change
- Use Pest syntax (not PHPUnit)
- Follow existing test conventions

---

## Laravel Boost MCP Tools

Use these tools when available:

| Task | Tool |
|------|------|
| Search Laravel docs | `search-docs` |
| List artisan commands | `list-artisan-commands` |
| Execute PHP/debug | `tinker` |
| Query database | `database-query` |
| Check browser logs | `browser-logs` |
| Get project URLs | `get-absolute-url` |

**Always check docs** before making changes to ensure correct approach.

### Using search-docs
- Use **simple, topic-based queries**: `['authentication', 'middleware']`
- **Don't add package names** - they're auto-detected
- Pass **multiple queries** for better results
- Search **before coding** to ensure correct approach

---

## File Generation

### Artisan Make Commands
```powershell
# Model with migration, factory, seeder
php artisan make:model Podcast -mfs

# Resource controller
php artisan make:controller PodcastController --resource

# Form request
php artisan make:request StorePodcastRequest

# Pest test
php artisan make:test PodcastTest --pest

# Generic PHP class
php artisan make:class Services/PodcastService

# Livewire component
php artisan make:livewire Podcasts\Create

# Volt component
php artisan make:volt podcasts/create --test
```

Use `list-artisan-commands` tool to check available options.

---

## Debugging

### Common Commands
```powershell
# Check logs
Get-Content storage/logs/laravel.log -Tail 50

# Search logs
Select-String -Pattern "error" -Path storage/logs/laravel.log

# Interactive Tinker
php artisan tinker

# Clear caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Database
# Use database-query tool or tinker
```

### Laravel Boost Debugging Tools
- **`tinker` tool** - Execute PHP to debug code or query models
- **`database-query` tool** - Read from database directly
- **`browser-logs` tool** - Read browser console errors (only recent logs useful)

---

## Error Handling

### When Errors Occur
1. **Read the error carefully**
2. **Use `get_errors`** to see file-specific errors
3. **Search docs** if it's a framework/package issue
4. **Fix and verify** - don't leave broken code
5. **Be concise** in explanation - just state what was fixed

### Common Errors
- **Vite manifest error** → Run `npm run build` or ensure `npm run dev` is running
- **Database connection fails** → Check `.env` configuration
- **Class not found** → Run `composer dump-autoload`
- **Route not found** → Check `routes/web.php` and run `php artisan route:list`

---

## PhpStorm Integration

### Code Inspection
- Pay attention to PhpStorm warnings/errors
- Use `get_errors` to see what IDE sees
- Fix type hints, unused imports, etc.

### Refactoring
- Prefer IDE-safe refactors when possible
- Update all references when renaming
- Keep namespace structure aligned with directory structure

---

## Communication Style

### Do Say
- "Created X, updated Y"
- "Tests passing"
- "Reminder: run `pint --dirty`"
- "Ready to commit?"

### Don't Say
- Long explanations of obvious changes
- Detailed step-by-step of what you did
- Asking permission for safe, obvious actions
- Explaining standard Laravel conventions

---

## Priority Order

When instructions conflict:

1. **Laravel Boost guidelines** (highest priority)
2. **Project-specific instructions** (environment, stack files)
3. **Editor preferences** (this file)
4. **User's explicit request** (overrides all)

---

## Working Efficiently

### Before Starting
- Check existing files for patterns
- Use `file_search` and `grep_search` to explore
- Read relevant files completely, not line-by-line

### During Work
- Group related changes by file
- Make multiple edits to same file efficiently
- Run tests incrementally, not after every tiny change

### After Completing
- Verify tests pass
- Check for errors
- Brief summary only
- Ask about next steps if unclear

---

## File Structure Awareness

### Check Before Creating
- Does similar component exist?
- What's the existing directory structure?
- What naming convention is used?

### Follow Conventions
- Model naming: `PascalCase` singular
- Controller naming: `PascalCase` + `Controller`
- View naming: `kebab-case`
- Component naming: `PascalCase` or `kebab-case` (check existing)

---

## Remember

- **Action over discussion** - do the obvious thing
- **Quality over speed** - but don't over-explain
- **Tests prove it works** - run them
- **User confirmation for git** - always ask
- **No markdown files** - unless requested

