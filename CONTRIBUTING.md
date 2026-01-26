# Contributing to EcoSurvey

Thank you for considering contributing to EcoSurvey! This document outlines the development workflow and guidelines.

---

## 🎯 Getting Started

### Prerequisites

- Docker & DDEV installed
- Git configured
- Node.js 18+
- PHP 8.3+

### Development Setup

1. **Fork the repository**
   ```bash
   git clone https://github.com/yourusername/laravel-ecosurvey.git
   cd laravel-ecosurvey
   ```

2. **Start development environment**
   ```bash
   ddev start
   ddev composer install
   ddev npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   ddev artisan key:generate
   ddev artisan migrate:fresh --seed
   ```

4. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

---

## 📝 Development Workflow

### 1. Write Code

Follow the coding standards:
- PSR-12 style (enforced by Pint)
- Type hints on all methods
- PHPDoc blocks for public APIs
- Descriptive variable names

### 2. Write Tests

**Every feature must have tests:**

```bash
# Create test
ddev artisan make:test Feature/YourFeatureTest --pest

# Run tests
ddev artisan test --filter=YourFeature
```

### 3. Format Code

```bash
# Auto-fix style issues
ddev pint --dirty
```

### 4. Check for Errors

```bash
# Run full test suite
ddev artisan test

# Check for type errors (if using Larastan)
ddev composer analyse
```

### 5. Commit Changes

Use conventional commit messages:

```
feat: Add satellite heatmap visualization
fix: Correct NDVI calculation formula
docs: Update API reference for Copernicus
test: Add coverage for subscription limits
refactor: Extract satellite service logic
```

### 6. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Open a PR on GitHub with:
- Clear description of changes
- Screenshots (if UI changes)
- Related issue number (if applicable)

---

## 🧪 Testing Guidelines

### Test Coverage Requirements

- **New features**: 100% coverage
- **Bug fixes**: Add regression test
- **Refactoring**: Maintain existing coverage

### Writing Good Tests

```php
// Good ✅
test('pro subscription allows 100 satellite analyses per month', function () {
    $user = User::factory()->withProSubscription()->create();
    
    expect($user->canRequestSatelliteAnalysis())->toBeTrue();
    
    // Use 100 analyses
    for ($i = 0; $i < 100; $i++) {
        $user->trackUsage('satellite_analysis', 1);
    }
    
    expect($user->canRequestSatelliteAnalysis())->toBeFalse();
});

// Bad ❌
test('subscription works', function () {
    $user = User::factory()->create();
    expect($user)->toBeInstanceOf(User::class);
});
```

### Running Tests

```bash
# All tests
ddev artisan test

# Specific file
ddev artisan test tests/Feature/SubscriptionTest.php

# By filter
ddev artisan test --filter=Subscription

# With coverage
ddev artisan test --coverage
```

---

## 🎨 Code Style

### PHP

We use **Laravel Pint** with PSR-12 standard:

```bash
# Check style
ddev pint --test

# Auto-fix
ddev pint --dirty
```

### JavaScript

```bash
# Format JS files
ddev npm run format
```

### Blade Templates

- Use 4 spaces for indentation
- Keep logic minimal (use Livewire components)
- Extract reusable parts into components

---

## 🏗️ Architecture Decisions

### When to Use...

**Livewire Component**: Interactive UI with state
```php
// Good for forms, real-time updates
```

**Blade Component**: Reusable static UI
```php
// Good for buttons, cards, layouts
```

**Service Class**: Complex business logic
```php
// Good for satellite processing, analytics
```

**Job**: Long-running tasks
```php
// Good for email sending, report generation
```

**Action**: Single-purpose operations
```php
// Good for discrete actions like "CreateCampaign"
```

---

## 🐛 Bug Reports

### Before Submitting

1. Check if issue already exists
2. Test on latest `main` branch
3. Gather reproduction steps

### Bug Report Template

```markdown
**Description:**
Clear description of the bug

**Steps to Reproduce:**
1. Go to '...'
2. Click on '...'
3. See error

**Expected Behavior:**
What should happen

**Actual Behavior:**
What actually happens

**Environment:**
- DDEV version:
- PHP version:
- Browser (if applicable):

**Logs:**
```
Paste relevant logs
```

**Screenshots:**
If applicable
```

---

## ✨ Feature Requests

### Feature Request Template

```markdown
**Problem:**
What problem does this solve?

**Proposed Solution:**
How should it work?

**Alternatives Considered:**
Other approaches you've thought about

**Additional Context:**
Screenshots, mockups, examples
```

---

## 📚 Documentation

### When to Update Docs

- New features → Update `/docs` and README
- API changes → Update `API-REFERENCE.md`
- Architecture changes → Update `ARCHITECTURE.md`
- Deployment changes → Update `DEPLOYMENT.md`

### Documentation Style

- Use clear, concise language
- Include code examples
- Add screenshots for UI features
- Keep diagrams up to date

---

## 🔄 Pull Request Process

### Checklist

- [ ] Tests pass (`ddev artisan test`)
- [ ] Code formatted (`ddev pint --dirty`)
- [ ] No merge conflicts
- [ ] Documentation updated
- [ ] Screenshots included (if UI changes)
- [ ] Descriptive commit messages
- [ ] PR description explains changes

### Review Process

1. Automated checks run (tests, style)
2. Maintainer reviews code
3. Feedback addressed
4. Approved and merged

### After Merge

- Delete your feature branch
- Update your local main:
  ```bash
  git checkout main
  git pull origin main
  ```

---

## 🎖️ Recognition

Contributors are listed in:
- GitHub contributors page
- CONTRIBUTORS.md (coming soon)
- Release notes (for significant contributions)

---

## ❓ Questions?

- **GitHub Discussions**: Ask questions
- **Discord**: Join our community (link TBD)
- **Email**: dev@ecosurvey.app

---

## 📜 Code of Conduct

### Our Pledge

We are committed to providing a welcoming and inclusive environment for all contributors.

### Expected Behavior

- Be respectful and constructive
- Accept feedback gracefully
- Focus on what's best for the project
- Show empathy towards others

### Unacceptable Behavior

- Harassment or discrimination
- Trolling or insulting comments
- Publishing private information
- Unprofessional conduct

### Enforcement

Violations may result in:
1. Warning
2. Temporary ban
3. Permanent ban

Report issues to: conduct@ecosurvey.app

---

Thank you for contributing to EcoSurvey! 🌱
