# Code Style (Laravel + Livewire)

## PHP / Laravel

- **Laravel Pint**: run `./vendor/bin/pint` after completing a round of changes.
- **PHPStan**: level 5 for static analysis; run `./vendor/bin/phpstan analyse`.
- **Types**: add scalar/return types everywhere; nullable where appropriate.
- **DocBlocks**: public classes/methods require concise DocBlocks.
- **Controllers**: thin controllers (under 60 lines); delegate to Services.
- **Resource Controllers**: use for RESTful routes; invokable for single-action.
- **Form Requests**: per action for **traditional controllers only**; never validate in controllers.
- **API Resources**: use for JSON endpoints only (data exports).
- **Services**: single responsibility; keep business logic here.
- **Testing**: Pest for feature/unit; name tests by intent. See **examples.md** for testing patterns.
- **Migrations**: Prefer **editing** existing migrations pre-deploy; only add new ones for shipped schemas.

## Octane Safety

This project uses Laravel Octane (FrankenPHP), which keeps the application in memory between requests. **Avoid these anti-patterns:**

- **❌ Static Properties for State**: Static variables persist across requests and leak data
- **❌ In-Memory Config Caching**: Don't store config/env values in static variables
- **❌ Singleton State**: Custom singletons must be reset between requests

**✅ Safe Patterns:**

```php
// ✅ Good: Stateless service (Octane-safe)
class TimeTrackingService
{
    public function createEntry(array $data): TimeEntry
    {
        // No static properties, no internal state
        return TimeEntry::create([...]);
    }
}

// ❌ Bad: Static state leaks between requests
class BadService
{
    private static array $cache = [];  // This persists!

    public function getData()
    {
        if (!isset(self::$cache['data'])) {
            self::$cache['data'] = expensive_operation();
        }
        return self::$cache['data'];
    }
}
```

**Best Practices:**

- Always use dependency injection
- Store state in Redis/database, never in-memory
- Livewire components are automatically safe
- See architecture.md for detailed Octane considerations

## Livewire Components

### Validation in Livewire

Use one of these patterns:

- **`rules()` method**: Define validation rules as a protected method
- **`#[Validate]` attribute**: Livewire 3 attribute-based validation (cleaner)

```php
// Option 1: rules() method (traditional)
protected function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email',
    ];
}

// Option 2: #[Validate] attributes (Livewire 3)
#[Validate('required|string|max:255')]
public string $name = '';

#[Validate('required|email')]
public string $email = '';
```

### Component Organization

- **Page Components**: One per screen (`ClientsIndex`, `TimeEntryForm`)
- **File location**: `app/Livewire/` (Livewire 3)
- **Naming**: PascalCase matching class name
- **Blade views**: `resources/views/livewire/` (kebab-case)

### Livewire Best Practices

- **Public properties**: Only for form bindings; everything else private/protected
- **Validation**: Use `rules()` method
- **Events**: Use `dispatch()` for component communication
- **Services**: Inject via method parameters, not constructor
- **Reset forms**: Call `$this->reset()` after successful save
- **Loading states**: Use `wire:loading` in Blade for feedback

## Blade Templates

### Blade Conventions

- **Uppercase labels**: Terminal aesthetic
- **Error display**: `@error` directive below each field
- **Loading states**: `wire:loading` for visual feedback
- **Accessibility**: Proper labels and IDs
- **Terminal classes**: Use custom Tailwind terminal theme
- **Monospace inputs**: `font-mono` for data fields

## Alpine.js Integration

Alpine handles lightweight client-side interactions.

### Alpine Best Practices

- **Small interactions**: Modals, dropdowns, theme toggle
- **Use `x-cloak`**: Hide before Alpine loads
- **Entangle**: `@entangle('property')` for two-way binding with Livewire
- **Keyboard events**: `@keydown.window` for global shortcuts
- **Focus management**: `x-ref` and `$refs` for manual focus

## Service Layer

Extract business logic to dedicated services. Services should:

- Have single responsibility
- Be stateless (Octane-safe)
- Inject dependencies via method parameters
- Contain no Livewire-specific code

**See examples.md for detailed service implementations and testing patterns.**

## Performance

- **Query optimization**: Use `with()` for eager loading, avoid N+1 queries.
- **Caching**: Cache slow queries, clear on model updates.
- **Database indexes**: Index foreign keys and frequently queried columns.
- **Livewire optimization**: Use `wire:model.lazy` for large forms, avoid polling unless needed.

## Documentation

- **DocBlocks**: Required for public methods
- **Inline comments**: Explain "why", not "what"
