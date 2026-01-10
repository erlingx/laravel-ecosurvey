# Erik's Livewire & Volt F.A.Q.

Quick reference for Livewire v3 + Volt functional API concepts.

---

## **Core Concepts**

### **`state()` - Reactive Properties**
Declares properties that Livewire tracks and syncs between frontend/backend.

```php
state(['count' => 0, 'name' => '']);
// Access: $this->count, wire:model="count"
```

**When:** Initialized once, persists across requests until page reload.

---

### **`mount()` - Initialization**
Runs **once** when component first loads (like a constructor).

```php
mount(function ($userId) {
    $this->user = User::find($userId);
});
```

**When:** Only on initial page load, NOT on subsequent Livewire requests.

---

### **`computed()` - Cached Properties**
Creates calculated properties that are cached per-request.

```php
$users = computed(fn() => User::all());
// Access: $this->users
```

**When:** Calculated once per request when accessed, then cached.

---

### **Actions (Custom Functions)**
User-triggered methods.

```php
$save = function () {
    $this->validate([...]);
    // Save logic
};
```

**Usage:** `wire:click="save"`, `wire:submit="save"`

---

## **Execution Flow**

```
Page Load:
1. state() - Properties declared
2. mount() - Setup runs ONCE
3. computed() - Calculated when accessed
4. Template renders

User Interaction:
1. Action triggered (e.g., wire:click)
2. State updates
3. computed() re-calculated if accessed
4. Template re-renders
   (mount does NOT run again)
```

---

## **Quick Reference**

| Feature | Runs | Purpose |
|---------|------|---------|
| `state()` | Init | Declare reactive data |
| `mount()` | Once | Initialize component |
| `computed()` | Per request | Cached calculations |
| Actions | On trigger | User interactions |

---

## **Common Patterns**

### **Form Handling**
```php
state(['email' => '', 'password' => '']);

$submit = function () {
    $validated = $this->validate([
        'email' => 'required|email',
        'password' => 'required|min:8',
    ]);
    // Process form
};
```

### **Database Queries**
```php
// Use computed for data that updates per request
$posts = computed(fn() => Post::latest()->get());

// Use state for user input/selections
state(['categoryId' => null]);
```

### **JavaScript Interaction**
```php
// Set state from JS
@this.set('propertyName', value);

// Get state from JS
let value = @this.get('propertyName');
```

---

## **Volt vs Traditional Livewire**

### **Functional Volt:**
```php
<?php
use function Livewire\Volt\state;

state(['count' => 0]);
$increment = fn () => $this->count++;
?>
<button wire:click="increment">{{ $count }}</button>
```

### **Class-based Livewire:**
```php
class Counter extends Component {
    public $count = 0;
    
    public function increment() {
        $this->count++;
    }
}
```

Both work the same - Volt is just more concise!

---

*Add notes as you learn...*

