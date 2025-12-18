# Stack: Livewire v3 + Volt

**Framework:** Livewire v3  
**Single-File Components:** Volt v1  
**UI Library:** Flux UI v2 (Free Edition)

---

## Core Concepts

### Livewire
- **State lives on the server** - UI reflects server state
- All Livewire requests hit Laravel backend (validate & authorize everything)
- Components require a **single root element**
- Use `wire:model.live` for real-time updates (deferred by default in v3)

### Volt
- **Single-file components** - PHP logic + Blade in one file
- Two styles: **Functional** or **Class-based**
- Check existing components to match project style

### Flux UI
- **Free edition** component library for Livewire
- Use Flux components when available
- Available components: avatar, badge, brand, breadcrumbs, button, callout, checkbox, dropdown, field, heading, icon, input, modal, navbar, otp-input, profile, radio, select, separator, skeleton, switch, text, textarea, tooltip

---

## Creating Components

### Make Livewire Component
```powershell
php artisan make:livewire Posts\CreatePost
```

Creates:
- `app/Livewire/Posts/CreatePost.php`
- `resources/views/livewire/posts/create-post.blade.php`

### Make Volt Component
```powershell
php artisan make:volt posts/create-post
php artisan make:volt posts/create-post --test
```

Creates single-file component in `resources/views/pages/` or `resources/views/livewire/`

---

## Volt Component Patterns

### Functional Volt (Recommended)
```php
<?php
use App\Models\Post;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state(['title' => '', 'content' => '']);

$save = function () {
    Post::create([
        'title' => $this->title,
        'content' => $this->content,
    ]);

    return redirect()->route('posts.index');
};
?>

<div>
    <flux:input wire:model="title" label="Title" />
    <flux:textarea wire:model="content" label="Content" />
    <flux:button wire:click="save">Save</flux:button>
</div>
```

### Class-Based Volt
```php
<?php
use App\Models\Post;
use Livewire\Volt\Component;

new class extends Component
{
    public string $title = '';

    public string $content = '';

    public function save(): void
    {
        Post::create([
            'title' => $this->title,
            'content' => $this->content,
        ]);

        $this->redirect(route('posts.index'));
    }
}; ?>

<div>
    <flux:input wire:model="title" label="Title" />
    <flux:textarea wire:model="content" label="Content" />
    <flux:button wire:click="save">Save</flux:button>
</div>
```

---

## Common Patterns

### Real-Time Search
```php
<?php
use App\Models\Podcast;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state(['search' => '']);

$podcasts = computed(fn () => Podcast::query()
    ->when($this->search, fn ($q) => $q->where('title', 'like', "%{$this->search}%"))
    ->get()
);
?>

<div>
    <flux:input 
        wire:model.live.debounce.300ms="search" 
        placeholder="Search podcasts..." 
    />
    
    <div class="mt-4 space-y-2">
        @foreach ($this->podcasts as $podcast)
            <div wire:key="podcast-{{ $podcast->id }}">
                {{ $podcast->title }}
            </div>
        @endforeach
    </div>
</div>
```

### CRUD Operations
```php
<?php
use App\Models\Episode;

use function Livewire\Volt\computed;
use function Livewire\Volt\state;

state(['editing' => null, 'title' => '', 'description' => '']);

$episodes = computed(fn () => Episode::latest()->get());

$edit = function (Episode $episode) {
    $this->editing = $episode->id;
    $this->title = $episode->title;
    $this->description = $episode->description;
};

$save = function () {
    Episode::find($this->editing)->update([
        'title' => $this->title,
        'description' => $this->description,
    ]);

    $this->editing = null;
    $this->title = '';
    $this->description = '';
};

$delete = fn (Episode $episode) => $episode->delete();
?>

<div>
    @foreach ($this->episodes as $episode)
        <div wire:key="episode-{{ $episode->id }}">
            @if ($editing === $episode->id)
                <flux:input wire:model="title" />
                <flux:button wire:click="save">Save</flux:button>
            @else
                {{ $episode->title }}
                <flux:button wire:click="edit({{ $episode->id }})">Edit</flux:button>
                <flux:button wire:click="delete({{ $episode->id }})">Delete</flux:button>
            @endif
        </div>
    @endforeach
</div>
```

### Loading States
```blade
<flux:button wire:click="save" wire:loading.attr="disabled">
    <span wire:loading.remove>Save</span>
    <span wire:loading>Saving...</span>
</flux:button>

<div wire:loading wire:target="save">
    Processing...
</div>
```

### Validation
```php
<?php
use function Livewire\Volt\rules;
use function Livewire\Volt\state;

state(['email' => '', 'password' => '']);

rules([
    'email' => 'required|email',
    'password' => 'required|min:8',
]);

$submit = function () {
    $this->validate();

    // Process...
};
?>

<flux:field>
    <flux:label>Email</flux:label>
    <flux:input wire:model="email" type="email" />
    <flux:error name="email" />
</flux:field>
```

---

## Flux UI Component Examples

### Form Components
```blade
<flux:field>
    <flux:label>Title</flux:label>
    <flux:input wire:model="title" placeholder="Enter title..." />
    <flux:error name="title" />
</flux:field>

<flux:field>
    <flux:label>Category</flux:label>
    <flux:select wire:model="category">
        <option value="">Choose...</option>
        <option value="tech">Technology</option>
        <option value="business">Business</option>
    </flux:select>
</flux:field>

<flux:checkbox wire:model="published">
    <flux:label>Published</flux:label>
</flux:checkbox>

<flux:button variant="primary" wire:click="save">
    Save Changes
</flux:button>
```

### UI Components
```blade
<flux:badge color="green">Active</flux:badge>

<flux:modal name="confirm-delete">
    <div class="p-6">
        <flux:heading size="lg">Confirm Delete</flux:heading>
        <p>Are you sure?</p>
        <div class="mt-4 flex gap-2">
            <flux:button wire:click="$dispatch('close')">Cancel</flux:button>
            <flux:button variant="danger" wire:click="delete">Delete</flux:button>
        </div>
    </div>
</flux:modal>

<flux:dropdown>
    <flux:button>Actions</flux:button>
    <flux:menu>
        <flux:menu.item wire:click="edit">Edit</flux:menu.item>
        <flux:menu.item wire:click="delete">Delete</flux:menu.item>
    </flux:menu>
</flux:dropdown>
```

---

## Testing Livewire/Volt

### Feature Tests
```php
use Livewire\Volt\Volt;

test('can create podcast', function () {
    $user = User::factory()->create();
    
    Volt::test('pages.podcasts.create')
        ->actingAs($user)
        ->set('title', 'Test Podcast')
        ->set('description', 'Test Description')
        ->call('create')
        ->assertHasNoErrors();
    
    expect(Podcast::where('title', 'Test Podcast')->exists())->toBeTrue();
});

test('validates required fields', function () {
    Volt::test('pages.podcasts.create')
        ->set('title', '')
        ->call('create')
        ->assertHasErrors(['title' => 'required']);
});
```

### Testing Component Renders
```php
test('podcast page renders', function () {
    $this->get('/podcasts/create')
        ->assertSeeLivewire('pages.podcasts.create');
});
```

---

## Important Notes

- **Always use wire:key in loops** to prevent DOM issues
- **Validate in actions** - all requests hit the server
- **Use computed properties** for derived state
- **Debounce real-time inputs** to reduce server requests
- **Alpine.js is included** with Livewire v3 (don't add separately)
- **Check existing components** before creating new ones
- **Follow project's Volt style** (functional vs class-based)

---

## Livewire v3 Key Changes

- Namespace: `App\Livewire` (not `App\Http\Livewire`)
- Events: `$this->dispatch()` (not `emit()`)
- Layout: `components.layouts.app` (not `layouts.app`)
- Model binding: `wire:model` is deferred by default (use `wire:model.live` for real-time)
- New directives: `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`

---

## Alpine.js Integration

Alpine is included with Livewire v3:

```blade
<div x-data="{ open: false }">
    <flux:button @click="open = !open">Toggle</flux:button>
    
    <div x-show="open" x-transition>
        Content here...
    </div>
</div>
```

Available Alpine plugins: persist, intersect, collapse, focus

