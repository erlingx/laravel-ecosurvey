# Custom Instructions - Quick Reference

This directory contains modular AI instruction files that can be mixed and matched per project.

---

## File Structure

### Core Config
- **`project-config.md`** - Main entry point, references all active modules for this project

### Environment Modules (Choose One)
- **`environment-native.md`** - Native PHP/Composer (no containers)
- **`environment-ddev.md`** - DDEV (Docker-based development)

### Stack Modules (Choose One)
- **`stack-livewire-volt.md`** - Livewire v3 + Volt + Flux UI
- **`stack-inertia-vue.md`** - Inertia.js + Vue 3 *(not created yet)*

### Frontend Modules
- **`frontend-tailwind-v4.md`** - Tailwind CSS v4 + Vite

### Editor Modules
- **`editor-phpstorm.md`** - PhpStorm on Windows 11

---

## How to Use for Different Projects

### This Project (Laravel Listening Party)
- Environment: Native PHP
- Stack: Livewire + Volt
- Frontend: Tailwind v4

**Reference in `.github/copilot-instructions.md`:**
```markdown
**Load custom instructions from:** `.github/custom-instructions/project-config.md`
```

### Example: DDEV + Livewire Project
Edit `project-config.md` to reference:
- `environment-ddev.md`
- `stack-livewire-volt.md`
- `frontend-tailwind-v4.md`
- `editor-phpstorm.md`

### Example: Native + Inertia + Vue Project
Edit `project-config.md` to reference:
- `environment-native.md`
- `stack-inertia-vue.md` *(create this file)*
- `frontend-tailwind-v4.md`
- `editor-phpstorm.md`

---

## Creating New Modules

### Stack Module Template
Create `stack-[name].md` with sections:
1. Core concepts
2. Component patterns
3. Common code examples
4. Testing approach
5. Best practices

### Environment Module Template
Create `environment-[name].md` with sections:
1. Command execution rules
2. Development workflow
3. Testing approach
4. Debugging tools
5. Common issues

---

## Updating for a Project

1. **Copy the `custom-instructions/` directory** to your project's `.github/`
2. **Edit `project-config.md`** to reference the right modules
3. **Add reference line** at top of `.github/copilot-instructions.md`
4. **Customize if needed** - edit individual module files

---

## Benefits

- ✅ **Reusable across projects** - same modules, different combinations
- ✅ **Easy to maintain** - update one file, affects all projects using it
- ✅ **Clear separation** - environment vs stack vs editor concerns
- ✅ **Mix and match** - choose what fits your project
- ✅ **Version controlled** - instructions live with code

---

## Priority

**Laravel Boost guidelines ALWAYS take precedence** over custom instructions.

Custom instructions should complement, not contradict, Boost guidelines.

