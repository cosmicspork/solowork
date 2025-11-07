# Examples & Code Patterns

This document provides concrete examples of common patterns used in Solowork.

## Table of Contents

- [Routes](#routes)
- [Livewire Components](#livewire-components)
- [Services](#services)
- [Models](#models)
- [Testing](#testing)
  - [Livewire Component Tests](#livewire-component-tests)
  - [Model Tests](#model-tests)
  - [Service Tests](#service-tests)
  - [Factories](#factories)
  - [Test Helpers](#test-helpers)
  - [Best Practices](#best-practices)

## Routes

### `routes/web.php`

```php
<?php

use App\Livewire;
use Illuminate\Support\Facades\Route;

// Authentication routes (WebAuthn)
Route::middleware('guest')->group(function () {
    Route::get('/login', Livewire\Auth\Login::class)->name('login');
    Route::get('/register', Livewire\Auth\Register::class)->name('register');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/', Livewire\Dashboard::class)->name('dashboard');

    // Clients (Livewire)
    Route::get('/clients', Livewire\Clients\Index::class)->name('clients.index');
    Route::get('/clients/create', Livewire\Clients\Form::class)->name('clients.create');
    Route::get('/clients/{client}', Livewire\Clients\Show::class)->name('clients.show');
    Route::get('/clients/{client}/edit', Livewire\Clients\Form::class)->name('clients.edit');

    // Projects (Livewire)
    Route::get('/projects', Livewire\Projects\Index::class)->name('projects.index');
    Route::get('/projects/create', Livewire\Projects\Form::class)->name('projects.create');
    Route::get('/projects/{project}', Livewire\Projects\Show::class)->name('projects.show');
    Route::get('/projects/{project}/edit', Livewire\Projects\Form::class)->name('projects.edit');

    // Time Entries (Livewire)
    Route::get('/time', Livewire\TimeEntries\Index::class)->name('time.index');

    // Export (invokable controller)
    Route::get('/export', \App\Http\Controllers\Exports\ExportAllData::class)->name('export');
});

// Keyboard shortcuts reference (accessible without auth for help)
Route::get('/keyboard-shortcuts', Livewire\Shared\KeyboardReference::class)->name('keyboard.reference');
```

## Livewire Components

### Page Component: `app/Livewire/Clients/Index.php`

```php
<?php

namespace App\Livewire\Clients;

use App\Models\Client;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // Public properties for filtering
    public string $search = '';
    public bool $showInactive = false;

    // Query string parameters
    protected $queryString = [
        'search' => ['except' => ''],
        'showInactive' => ['except' => false],
    ];

    /**
     * Reset pagination when search changes
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open command palette for new client
     */
    public function createClient(): void
    {
        $this->redirect(route('clients.create'));
    }

    public function render()
    {
        $clients = Client::query()
            ->when(!$this->showInactive, fn ($q) => $q->where('active', true))
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('projects')
            ->orderBy('name')
            ->paginate(20);

        return view('livewire.clients.index', [
            'clients' => $clients,
        ]);
    }
}
```

### Form Component: `app/Livewire/TimeEntries/QuickEntry.php`

```php
<?php

namespace App\Livewire\TimeEntries;

use App\Models\TimeEntry;
use App\Services\TimeTrackingService;
use Livewire\Component;

/**
 * Quick time entry form with keyboard shortcuts
 */
class QuickEntry extends Component
{
    // Public properties (bound to form)
    public ?int $project_id = null;
    public ?int $task_id = null;
    public string $started_at = '';
    public string $ended_at = '';
    public string $notes = '';
    public bool $billable = true;

    /**
     * Validation rules
     */
    protected function rules(): array
    {
        return [
            'started_at' => 'required|date',
            'ended_at' => 'required|date|after:started_at',
            'notes' => 'nullable|string',
            'billable' => 'boolean',
        ];
    }

    /**
     * Save time entry via service
     */
    public function save(TimeTrackingService $service): void
    {
        $this->validate();

        $service->createEntry(auth()->id(), [
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'notes' => $this->notes,
            'billable' => $this->billable,
        ]);

        $this->dispatch('time-entry-created');
        $this->reset();
    }

    public function render()
    {
        return view('livewire.time-entries.quick-entry');
    }
}
```

### Blade Template: `resources/views/livewire/clients/index.blade.php`

```blade
<div class="min-h-screen bg-terminal-bg text-terminal p-6">
    {{-- Header with search and filter --}}
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-mono">CLIENTS</h1>

        <div class="flex gap-4">
            {{-- Search input --}}
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search clients..."
                class="bg-terminal-bg-secondary border border-terminal-border text-terminal px-4 py-2 font-mono"
            />

            {{-- Show inactive toggle --}}
            <label class="flex items-center gap-2 text-terminal-secondary">
                <input
                    type="checkbox"
                    wire:model.live="showInactive"
                />
                SHOW INACTIVE
            </label>

            {{-- New client button (Ctrl+N) --}}
            <button
                wire:click="createClient"
                class="bg-terminal-text text-terminal-bg px-4 py-2 border border-terminal-border hover:bg-terminal-text-secondary"
            >
                NEW CLIENT
            </button>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div wire:loading class="mb-4 text-terminal-yellow">
        LOADING...
    </div>

    {{-- Client list --}}
    @if($clients->isEmpty())
        <div class="text-terminal-secondary text-center py-12">
            NO CLIENTS FOUND
        </div>
    @else
        <div class="border border-terminal-border">
            <table class="w-full font-mono">
                <thead class="bg-terminal-bg-secondary border-b border-terminal-border">
                    <tr>
                        <th class="text-left px-4 py-3">NAME</th>
                        <th class="text-left px-4 py-3">COMPANY</th>
                        <th class="text-right px-4 py-3">PROJECTS</th>
                        <th class="text-right px-4 py-3">RATE</th>
                        <th class="text-center px-4 py-3">STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($clients as $client)
                        <tr class="border-b border-terminal-border-subtle hover:bg-terminal-bg-tertiary">
                            <td class="px-4 py-3">
                                <a href="{{ route('clients.show', $client) }}" class="block text-terminal hover:text-terminal-text">
                                    {{ $client->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-terminal-secondary">
                                <a href="{{ route('clients.show', $client) }}" class="block text-terminal-secondary hover:text-terminal">
                                    {{ $client->company ?? '-' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right text-terminal-cyan">
                                <a href="{{ route('clients.show', $client) }}" class="block text-terminal-cyan hover:text-terminal">
                                    {{ $client->projects_count }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-right text-terminal-pink">
                                <a href="{{ route('clients.show', $client) }}" class="block text-terminal-pink hover:text-terminal">
                                    {{ $client->getHourlyRate() ? '$' . number_format($client->getHourlyRate(), 2) : '-' }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('clients.show', $client) }}" class="block">
                                    @if($client->active)
                                        <span class="text-terminal-text">ACTIVE</span>
                                    @else
                                        <span class="text-terminal-red">INACTIVE</span>
                                    @endif
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $clients->links() }}
        </div>
    @endif

    {{-- Quick actions (visible at bottom) --}}
    <nav aria-label="Quick actions" class="mt-8 border-t border-terminal-border pt-4">
        <h2 class="sr-only">Available Actions</h2>
        <div class="flex gap-4 text-terminal-secondary">
            <button wire:click="createClient" class="hover:text-terminal">
                (<u>N</u>)ew Client
            </button>
            <span>|</span>
            <a href="{{ route('dashboard') }}" class="hover:text-terminal">
                (<u>D</u>)ashboard
            </a>
        </div>
    </nav>
</div>
```

## Services

### `app/Services/TimeTrackingService.php`

```php
<?php

namespace App\Services;

use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\Task;
use Carbon\Carbon;

class TimeTrackingService
{
    /**
     * Create a time entry with calculated duration, rate, and billable status
     */
    public function createEntry(int $userId, array $data): TimeEntry
    {
        $startedAt = Carbon::parse($data['started_at']);
        $endedAt = Carbon::parse($data['ended_at']);

        $duration = $startedAt->diffInMinutes($endedAt);
        $hourlyRate = $this->determineRate($data);
        $billable = $this->determineBillable($data);

        return TimeEntry::create([
            'user_id' => $userId,
            'project_id' => $data['project_id'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => $duration,
            'notes' => $data['notes'] ?? null,
            'billable' => $billable,
            'hourly_rate' => $hourlyRate,
        ]);
    }

    /**
     * Determine billable status: inherit from task if present, otherwise use provided value
     */
    private function determineBillable(array $data): bool
    {
        if (isset($data['task_id'])) {
            $task = Task::find($data['task_id']);
            if ($task) {
                return $task->billable;
            }
        }

        return $data['billable'] ?? true;
    }

    /**
     * Determine hourly rate from hierarchy: Entry → Task → Project → Client
     *
     * Note: Caller should eager load task and project with relationships to avoid N+1 queries
     */
    private function determineRate(array $data): ?float
    {
        // Explicit rate on time entry (highest priority)
        if (isset($data['hourly_rate'])) {
            return $data['hourly_rate'];
        }

        // Task-specific rate (expect pre-loaded model)
        if (isset($data['task']) && $data['task'] instanceof Task) {
            if ($data['task']->hourly_rate) {
                return $data['task']->hourly_rate;
            }
        } elseif (isset($data['task_id'])) {
            // Fallback: load if not provided (causes query)
            $task = Task::find($data['task_id']);
            if ($task?->hourly_rate) {
                return $task->hourly_rate;
            }
        }

        // Check polymorphic billing rates (Project → Client)
        if (isset($data['project']) && $data['project'] instanceof Project) {
            // Project billing rate
            if ($rate = $data['project']->getHourlyRate()) {
                return $rate;
            }

            // Client billing rate (expect eager loaded)
            if ($rate = $data['project']->client?->getHourlyRate()) {
                return $rate;
            }
        } elseif (isset($data['project_id'])) {
            // Fallback: load if not provided (causes queries)
            $project = Project::with('client.billingRate', 'billingRate')->find($data['project_id']);

            if ($rate = $project?->getHourlyRate()) {
                return $rate;
            }

            if ($rate = $project?->client?->getHourlyRate()) {
                return $rate;
            }
        }

        return null;
    }

    /**
     * Get unbilled time entries for a project
     */
    public function getUnbilledHours(Project $project): float
    {
        $minutes = $project->timeEntries()
            ->where('billable', true)
            ->where('billed', false)
            ->sum('duration_minutes');

        return round($minutes / 60, 2);
    }

    /**
     * Mark time entries as billed
     */
    public function markAsBilled(array $timeEntryIds): int
    {
        return TimeEntry::whereIn('id', $timeEntryIds)
            ->update(['billed' => true]);
    }
}
```

### `app/Services/ClientService.php`

```php
<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\DB;

class ClientService
{
    /**
     * Create a new client
     */
    public function create(array $data): Client
    {
        return DB::transaction(function () use ($data) {
            $client = Client::create($data);

            // Set billing rate if provided
            if (isset($data['hourly_rate'])) {
                $client->setHourlyRate($data['hourly_rate']);
            }

            return $client;
        });
    }

    /**
     * Update an existing client
     */
    public function update(Client $client, array $data): Client
    {
        return DB::transaction(function () use ($client, $data) {
            $client->update($data);

            // Update billing rate if changed
            if (isset($data['hourly_rate'])) {
                $currentRate = $client->getHourlyRate();
                if ($data['hourly_rate'] != $currentRate) {
                    $client->setHourlyRate($data['hourly_rate']);
                }
            }

            return $client->fresh();
        });
    }

    /**
     * Soft delete a client (mark as inactive)
     */
    public function delete(Client $client): bool
    {
        return $client->update(['active' => false]);
    }

    /**
     * Get client summary with statistics
     */
    public function getSummary(Client $client): array
    {
        return [
            'total_projects' => $client->projects()->count(),
            'active_projects' => $client->projects()->where('status', 'active')->count(),
            'total_hours' => $client->projects()
                ->join('time_entries', 'projects.id', '=', 'time_entries.project_id')
                ->sum('time_entries.duration_minutes') / 60,
            'unbilled_hours' => $client->projects()
                ->join('time_entries', 'projects.id', '=', 'time_entries.project_id')
                ->where('time_entries.billable', true)
                ->where('time_entries.billed', false)
                ->sum('time_entries.duration_minutes') / 60,
        ];
    }
}
```

## Models

### `app/Models/Client.php`

```php
<?php

namespace App\Models;

use App\Traits\HasBillingRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, HasBillingRate;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Client has many projects
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Scope to filter active clients
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to filter inactive clients
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Get total hours across all projects
     */
    public function getTotalHoursAttribute(): float
    {
        $minutes = $this->projects()
            ->join('time_entries', 'projects.id', '=', 'time_entries.project_id')
            ->sum('time_entries.duration_minutes');

        return round($minutes / 60, 2);
    }
}
```

### `app/Models/TimeEntry.php`

```php
<?php

namespace App\Models;

use App\Traits\HasBillingRate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasFactory, HasBillingRate;

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'notes',
        'billable',
        'billed',
        'hourly_rate',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'billable' => 'boolean',
        'billed' => 'boolean',
        'hourly_rate' => 'decimal:2',
    ];

    /**
     * Belongs to user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Belongs to project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Belongs to task (optional)
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Scope unbilled entries
     */
    public function scopeUnbilled($query)
    {
        return $query->where('billed', false);
    }

    /**
     * Scope billable entries
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Get billable amount accessor
     */
    public function getBillableAmountAttribute(): float
    {
        if (!$this->billable || !$this->hourly_rate) {
            return 0;
        }
        return ($this->duration_minutes / 60) * $this->hourly_rate;
    }
}
```

### `app/Traits/HasBillingRate.php`

```php
<?php

namespace App\Traits;

use App\Models\BillingRate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasBillingRate
{
    /**
     * Polymorphic relationship to billing rate
     */
    public function billingRate(): MorphOne
    {
        return $this->morphOne(BillingRate::class, 'billable');
    }

    /**
     * Get the hourly rate for this entity
     */
    public function getHourlyRate(): ?float
    {
        return $this->billingRate?->rate;
    }

    /**
     * Set the hourly rate for this entity
     */
    public function setHourlyRate(float $rate, ?Carbon $effectiveFrom = null): void
    {
        $this->billingRate()->updateOrCreate(
            [
                'billable_type' => get_class($this),
                'billable_id' => $this->id,
            ],
            [
                'rate' => $rate,
                'rate_type' => 'hourly',
                'effective_from' => $effectiveFrom ?? now(),
            ]
        );
    }
}
```

---

## Testing

Solowork uses **Pest PHP** for testing with a focus on behavior-driven development. Tests demonstrate what the application does, not how it works internally.

### Livewire Component Tests

Test Livewire components by simulating user interactions and asserting state changes.

**Pattern**: `tests/Feature/Livewire/TimeEntryFormTest.php`

```php
<?php

use App\Livewire\TimeEntries\QuickEntry;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders successfully', function () {
    Livewire::test(QuickEntry::class)
        ->assertStatus(200);
});

it('creates time entry with valid data', function () {
    $project = Project::factory()->create();

    Livewire::test(QuickEntry::class)
        ->set('project_id', $project->id)
        ->set('started_at', '2025-01-15 09:00:00')
        ->set('ended_at', '2025-01-15 12:30:00')
        ->set('notes', 'Worked on feature X')
        ->set('billable', true)
        ->call('save')
        ->assertDispatched('time-entry-created');

    expect(TimeEntry::count())->toBe(1);
    expect(TimeEntry::first()->duration_minutes)->toBe(210);
});

it('validates required fields', function () {
    Livewire::test(QuickEntry::class)
        ->set('started_at', '')
        ->set('ended_at', '')
        ->call('save')
        ->assertHasErrors(['started_at', 'ended_at']);
});

it('resets form after successful save', function () {
    $project = Project::factory()->create();

    Livewire::test(QuickEntry::class)
        ->set('project_id', $project->id)
        ->set('started_at', '2025-01-15 09:00:00')
        ->set('ended_at', '2025-01-15 10:00:00')
        ->call('save')
        ->assertSet('notes', '')
        ->assertSet('project_id', null);
});
```

**Key patterns**:
- Use `beforeEach` for authentication setup
- Test rendering, data creation, validation, and state resets
- One behavior per test
- Use factories for test data

### Model Tests

Test model relationships, scopes, and computed attributes.

**Pattern**: `tests/Unit/Models/TimeEntryTest.php`

```php
<?php

use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\User;

it('calculates billable amount correctly', function () {
    $entry = TimeEntry::factory()->create([
        'duration_minutes' => 150, // 2.5 hours
        'hourly_rate' => 100,
        'billable' => true,
    ]);

    expect($entry->billable_amount)->toBe(250.0);
});

it('returns zero for non-billable entries', function () {
    $entry = TimeEntry::factory()->create([
        'duration_minutes' => 150,
        'hourly_rate' => 100,
        'billable' => false,
    ]);

    expect($entry->billable_amount)->toBe(0.0);
});

it('belongs to user', function () {
    $user = User::factory()->create();
    $entry = TimeEntry::factory()->create(['user_id' => $user->id]);

    expect($entry->user)->toBeInstanceOf(User::class);
    expect($entry->user->id)->toBe($user->id);
});

it('scopes unbilled entries', function () {
    TimeEntry::factory()->create(['billed' => false]);
    TimeEntry::factory()->create(['billed' => true]);

    $unbilled = TimeEntry::unbilled()->get();

    expect($unbilled)->toHaveCount(1);
    expect($unbilled->first()->billed)->toBeFalse();
});
```

**Key patterns**:
- Test accessors and computed attributes
- Verify relationships return correct types
- Test query scopes isolate data correctly
- Use descriptive test names

### Service Tests

Test business logic in isolation from controllers and Livewire components.

**Pattern**: `tests/Unit/Services/TimeTrackingServiceTest.php`

```php
<?php

use App\Services\TimeTrackingService;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeEntry;

it('creates time entry with calculated duration', function () {
    $user = User::factory()->create();
    $service = new TimeTrackingService();

    $entry = $service->createEntry($user->id, [
        'started_at' => '2025-01-15 09:00:00',
        'ended_at' => '2025-01-15 11:30:00',
        'notes' => 'Test work',
    ]);

    expect($entry->duration_minutes)->toBe(150);
    expect($entry->notes)->toBe('Test work');
    expect($entry->user_id)->toBe($user->id);
});

it('determines rate from hierarchy: task > project > client', function () {
    $user = User::factory()->create();

    $client = Client::factory()->create();
    $client->setHourlyRate(100);

    $project = Project::factory()->create(['client_id' => $client->id]);
    $project->setHourlyRate(150);

    $task = Task::factory()->create([
        'project_id' => $project->id,
        'hourly_rate' => 200,
    ]);

    // Eager load relationships to avoid N+1 queries
    $task->load('project.client.billingRate', 'project.billingRate');

    $service = new TimeTrackingService();
    $entry = $service->createEntry($user->id, [
        'task' => $task,
        'project' => $task->project,
        'started_at' => '2025-01-15 09:00:00',
        'ended_at' => '2025-01-15 10:00:00',
    ]);

    // Task rate takes precedence
    expect($entry->hourly_rate)->toBe(200.0);
});

it('inherits billable status from task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['billable' => false]);

    $service = new TimeTrackingService();
    $entry = $service->createEntry($user->id, [
        'task_id' => $task->id,
        'started_at' => '2025-01-15 09:00:00',
        'ended_at' => '2025-01-15 10:00:00',
    ]);

    // Billable inherited from task
    expect($entry->billable)->toBeFalse();
});

it('calculates unbilled hours for project', function () {
    $project = Project::factory()->create();

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'duration_minutes' => 120,
        'billable' => true,
        'billed' => false,
    ]);

    TimeEntry::factory()->create([
        'project_id' => $project->id,
        'duration_minutes' => 90,
        'billable' => true,
        'billed' => true, // Already billed
    ]);

    $service = new TimeTrackingService();
    $hours = $service->getUnbilledHours($project);

    expect($hours)->toBe(2.0); // Only unbilled entry counts
});
```

**Key patterns**:
- Test service methods in isolation
- Verify calculations and business logic
- Test rate determination hierarchy
- Use factories to set up test scenarios

### Factories

Use factories with states for flexible test data generation.

**Pattern**: `database/factories/TimeEntryFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $endedAt = (clone $startedAt)->modify('+' . $this->faker->numberBetween(30, 480) . ' minutes');

        return [
            'user_id' => User::factory(),
            'project_id' => Project::factory(),
            'task_id' => null,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_minutes' => ($endedAt->getTimestamp() - $startedAt->getTimestamp()) / 60,
            'notes' => $this->faker->sentence(),
            'billable' => true,
            'billed' => false,
            'hourly_rate' => $this->faker->randomElement([100, 125, 150, 175, 200]),
        ];
    }

    public function unbilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'billed' => false,
        ]);
    }

    public function billed(): static
    {
        return $this->state(fn (array $attributes) => [
            'billed' => true,
        ]);
    }

    public function nonBillable(): static
    {
        return $this->state(fn (array $attributes) => [
            'billable' => false,
        ]);
    }
}
```

**Usage in tests**:

```php
// Create unbilled entry
$entry = TimeEntry::factory()->unbilled()->create();

// Create multiple billed entries
$entries = TimeEntry::factory()->billed()->count(3)->create();

// Combine states
$entry = TimeEntry::factory()->nonBillable()->unbilled()->create();
```

**Key patterns**:
- Calculate realistic duration from start/end times
- Use factory states for common scenarios
- Allow chaining states for flexibility
- Default to sensible values

### Test Helpers

Create reusable helpers in `tests/Pest.php`:

```php
<?php

use App\Models\User;

/**
 * Authenticate as a user
 */
function actingAsUser(?User $user = null): User
{
    $user = $user ?? User::factory()->create();
    test()->actingAs($user);
    return $user;
}

/**
 * Create a time entry with defaults
 */
function createTimeEntry(array $attributes = [])
{
    return \App\Models\TimeEntry::factory()->create($attributes);
}

/**
 * Create a project with defaults
 */
function createProject(array $attributes = [])
{
    return \App\Models\Project::factory()->create($attributes);
}

/**
 * Create a client with defaults
 */
function createClient(array $attributes = [])
{
    return \App\Models\Client::factory()->create($attributes);
}
```

**Usage in tests**:

```php
it('calculates total hours', function () {
    $user = actingAsUser();
    $project = createProject();

    createTimeEntry(['project_id' => $project->id, 'duration_minutes' => 120]);
    createTimeEntry(['project_id' => $project->id, 'duration_minutes' => 90]);

    expect($project->total_hours)->toBe(3.5);
});
```

### Best Practices

**Structure**:
- **Arrange-Act-Assert**: Set up data, perform action, verify result
- **One assertion per test**: Focus on single behavior
- **Descriptive names**: `it('validates end time after start time')` not `test_validation()`

**Data Management**:
- **Use factories**: Never manually create test data
- **Database transactions**: Pest automatically wraps tests in transactions
- **Test edge cases**: Null values, empty strings, boundary conditions

**Focus**:
- **Behavior over implementation**: Test what users do, not how code works
- **Integration over unit**: Prioritize Livewire/service tests over trivial model tests
- **Critical paths**: Test billing calculations, time tracking, and data integrity

**Performance**:
- **Parallel execution**: Pest runs tests in parallel by default
- **Minimal database hits**: Use `create()` only when needed, prefer `make()` for simple tests
- **Fast feedback**: Keep test suite under 30 seconds
