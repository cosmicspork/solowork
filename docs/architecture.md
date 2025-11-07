# Solowork - Technical Architecture

## Table of Contents

1. [Project Overview](#project-overview)
2. [Technical Stack](#technical-stack)
3. [Authentication & Security](#authentication--security)
4. [Application Structure](#application-structure)
5. [Data Model](#data-model)
6. [Billing System](#billing-system)
7. [Frontend Architecture](#frontend-architecture)
8. [Keyboard Navigation & Accessibility](#keyboard-navigation--accessibility)
9. [Command Palette System](#command-palette-system)
10. [Theming & Visual Design](#theming--visual-design)
11. [Data Export](#data-export)

---

## Project Overview

### Vision

A keyboard-first, terminal-inspired business management tool for solo consultants. Built on the principle of **speed over aesthetics** and **productivity over polish**, Solowork prioritizes efficient data entry, predictable muscle-memory navigation, and comprehensive work tracking.

### Core Principles

- **Accessibility First**: Screen reader compatible, semantic HTML, discoverable keyboard shortcuts
- **Efficency Second**: No unnecessary animations, no hand-holding, just work
- **Predictable Navigation**: Consistent form layouts build muscle memory
- **Information Density**: Terminal-inspired layouts with monospace fonts
- **Keyboard Everything**: Complete keyboard navigation with discoverable shortcuts

### Version 1 Scope

**Initial release focuses on**:

- Self-hosted, single-user deployment
- Desktop web interface
- Time tracking with flexible client/project hierarchy
- Task management with basic status tracking
- Expense and income tracking
- Software license tracking
- Invoice generation and tracking (HTML display)
- Email templates for common scenarios (copy/paste)
- RESTful JSON API for programmatic access
- Events and notifications system (toast notifications)
- JSON Lines data export for backups/migration

---

## Technical Stack

### Backend

- **Framework**: Laravel 12 with Octane (FrankenPHP)
- **Database**: SQLite
- **Cache/Queue/Session**: Redis
- **Authentication**: WebAuthn with recovery code fallback
- **Monitoring**: Laravel Nightwatch
- **PHP Version**: 8.4+
- **Deployment**: Kubernetes manifests

### Frontend

- **Templating**: Blade
- **Reactivity**: Livewire
- **Client-side**: Alpine.js
- **Styling**: Tailwind CSS
- **Font**: JetBrains Mono

### Development Dependencies

- **Code Quality**: Laravel Pint, PHPStan
- **Testing**: Pest PHP
- **Asset Building**: Vite

---

## Laravel Octane Considerations

### Why Octane + FrankenPHP

**Performance Benefits**:

- Application stays in memory between requests
- ~2-3x faster response times vs traditional PHP-FPM
- Lower resource usage (fewer process spawns)
- Single binary deployment (no separate web server)

**Single-User Safety**:

- Low concurrency = fewer edge cases
- Memory leaks matter less (one user vs hundreds)
- Static state issues are less likely to manifest
- Easy to restart workers if issues arise

**Livewire Components (Octane-Compatible)**:

- Livewire is fully Octane-compatible
- Component state is properly isolated
- Events and dispatching work correctly
- No additional changes needed

**Database Connections**:

- Laravel handles connection resets automatically
- SQLite connections are thread-safe
- No manual connection management needed

**Cache/Session**:

- Redis handles state
- Session data stored externally
- Cache is shared, not per-worker

### Octane Configuration

**Worker Settings** (for single-user):

```env
OCTANE_SERVER=frankenphp
OCTANE_WORKERS=1              # Single user, one worker sufficient
OCTANE_MAX_REQUESTS=1000      # Recycle worker every 1000 requests
OCTANE_WATCH=true             # DEVELOPMENT ONLY: Auto-reload on file changes
                              # NEVER enable in production
```

**Graceful Shutdown**:

- Kubernetes sends SIGTERM
- Octane finishes current requests
- Workers shutdown cleanly
- No dropped connections

### Best Practices

1. **Avoid Static Properties**: Use services with instance state, not static
2. **Reset Singletons**: If using custom singletons, reset them in `octane:tick`
3. **Don't Cache Config**: Always read from env/Redis, never store in static vars
4. **Test with Octane**: Run local dev with `php artisan octane:start`
5. **Monitor Memory**: Nightwatch will alert, but also check manually
6. **Max Requests**: Keep at 1000 to auto-recycle workers

---

## Authentication & Security

### WebAuthn Primary Authentication

**Implementation**: Use `asbiin/laravel-webauthn` or similar package

**Initial Registration Flow**:

1. App launches and checks if any users exist in database
2. If no users exist: redirect to `/register` (open, no auth required)
3. User registers WebAuthn credential (fingerprint, Face ID, hardware key, password manager)
4. On successful registration: Display 10 recovery codes in modal with "Save these now" warning
5. User must acknowledge saving codes before proceeding
6. `/register` route is now locked (middleware checks user count)

**Login Flow**:

1. WebAuthn challenge/response
2. If WebAuthn fails: Fallback to recovery code entry

**Recovery Code Regeneration**:

1. User must be authenticated to regenerate codes
2. New codes immediately invalidate all old codes
3. Use case: User has used most codes and wants fresh set
4. Display new codes with same "save these now" modal

### Recovery Code System

**Generation**:

- 10 single-use recovery codes generated on account setup
- Displayed once, user must save them securely
- Each code can only be used once
- New codes can be regenerated (invalidates old ones)

**Storage**:

```sql
recovery_codes:
- id
- user_id
- code (hashed, like passwords)
- used_at (nullable)
- created_at
```

### Security Considerations

- All recovery codes hashed before storage (bcrypt/argon2)
- Rate limiting on login attempts
- Session timeout for inactive users
- CSRF protection (Laravel default)
- Prepared statements for SQL (Eloquent default)

---

## Application Structure

### Laravel Organization

Following **traditional Laravel structure** (MVC pattern) for simplicity:

```text
app/
├── Http/
│   ├── Controllers/          # Thin controllers, delegate to Services
│   │   ├── ClientController.php
│   │   ├── ProjectController.php
│   │   ├── TimeEntryController.php
│   │   └── ...
│   ├── Requests/             # Form validation (traditional controllers only)
│   │   ├── StoreClientRequest.php
│   │   ├── UpdateClientRequest.php
│   │   └── ...
│   └── Resources/            # API response formatting
│       ├── ClientResource.php
│       └── ...
├── Models/                   # Eloquent models
│   ├── Client.php
│   ├── Project.php
│   ├── Task.php
│   ├── TimeEntry.php
│   ├── Expense.php
│   ├── Income.php
│   ├── SoftwareLicense.php
│   └── BillingRate.php
├── Services/                 # Business logic
│   ├── TimeTrackingService.php
│   ├── BillingService.php
│   ├── InvoiceService.php
│   └── ExportService.php
└── Traits/
    └── HasBillingRate.php
```

### Controller Pattern

**Standard CRUD**: Resource controllers with thin methods

```php
class ClientController extends Controller
{
    public function index()
    {
        return view('clients.index');
    }

    public function store(StoreClientRequest $request, ClientService $service)
    {
        $client = $service->create($request->validated());
        return redirect()->route('clients.show', $client);
    }
}
```

**Read-only endpoints**: Invokable controllers

```php
class ShowClientInvoices
{
    public function __invoke(Client $client)
    {
        return view('clients.invoices', [
            'client' => $client,
            'invoices' => $client->invoices()->latest()->get()
        ]);
    }
}
```

### Service Layer

Business logic extracted to reusable services. Services are:

- Stateless (Octane-safe)
- Single responsibility
- Dependency injection via method parameters
- No framework-specific code (testable)

**Example Pattern**:

```php
class TimeTrackingService
{
    public function createEntry(array $data): TimeEntry
    {
        // Calculate duration, determine rate hierarchy
        return TimeEntry::create([...]);
    }

    private function determineRate(array $data): ?float
    {
        // Rate hierarchy: Entry → Task → Project → Client → Default
    }
}
```

**See examples.md for complete implementations of:**

- TimeTrackingService
- ClientService
- BillingService
- Testing patterns (Livewire, Models, Services)

---

## Data Model

### Entity Relationship Overview

```text
User (single user, but modeled for future)
  │
  ├─→ Clients (nullable relationship for internal work)
  │     ├─→ Projects
  │     │     ├─→ Tasks
  │     │     ├─→ Time Entries
  │     │     ├─→ Expenses
  │     │     └─→ Income
  │     └─→ Invoices
  │
  ├─→ Projects (client-less, for internal/personal work)
  │     ├─→ Tasks
  │     │     └─→ Time Entries
  │     └─→ Invoices
  │
  ├─→ Tasks (project-less, for global todos)
  │     └─→ Time Entries
  │
  ├─→ Time Entries (can be task-less, project-less)
  │     └─→ Invoice (nullable, when billed)
  │
  ├─→ Expenses
  │     └─→ Invoice (nullable, when billed)
  │
  ├─→ Software Licenses
  │
  └─→ BillingRates (polymorphic, attached to Clients/Projects/Tasks/TimeEntries)
```

### Database Schema

#### users

```sql
id                  bigint primary key
name                varchar(255)
email               varchar(255) unique
email_verified_at   timestamp nullable
created_at          timestamp
updated_at          timestamp
```

#### clients

```sql
id                      bigint primary key
name                    varchar(255)
email                   varchar(255) nullable
phone                   varchar(255) nullable
company                 varchar(255) nullable
notes                   text nullable
active                  boolean default true
created_at              timestamp
updated_at              timestamp

indexes:
- active
- name
```

#### projects

```sql
id                      bigint primary key
client_id               bigint nullable foreign key → clients.id
name                    varchar(255)
description             text nullable
status                  varchar(50) default 'active'  -- active/archived/completed
created_at              timestamp
updated_at              timestamp

indexes:
- client_id
- status
- name
- client_id, status (composite for active client projects)
```

#### tasks

```sql
id                  bigint primary key
project_id          bigint nullable foreign key → projects.id
title               varchar(255)
description         text nullable
status              varchar(50) default 'todo'  -- todo/in_progress/done
due_date            date nullable
billable            boolean default true
hourly_rate         decimal(10,2) nullable  -- optional task-specific rate
created_at          timestamp
updated_at          timestamp

indexes:
- project_id
- status
- due_date
```

#### time_entries

```sql
id                  bigint primary key
user_id             bigint foreign key → users.id
project_id          bigint nullable foreign key → projects.id
task_id             bigint nullable foreign key → tasks.id
invoice_id          bigint nullable foreign key → invoices.id
started_at          timestamp
ended_at            timestamp
duration_minutes    integer
notes               text nullable
billable            boolean default true
billed              boolean default false
hourly_rate         decimal(10,2) nullable  -- denormalized for history
created_at          timestamp
updated_at          timestamp

indexes:
- user_id
- project_id
- task_id
- invoice_id
- started_at
- billable
- billed
- created_at
- project_id, billable, billed (composite for unbilled queries)
```

#### expenses

```sql
id                  bigint primary key
project_id          bigint nullable foreign key → projects.id
invoice_id          bigint nullable foreign key → invoices.id
amount              decimal(10,2)
description         text
expense_date        date
billable            boolean default true
billed              boolean default false
created_at          timestamp
updated_at          timestamp

indexes:
- project_id
- invoice_id
- expense_date
- billable
- billed
```

#### income

```sql
id                  bigint primary key
project_id          bigint nullable foreign key → projects.id
amount              decimal(10,2)
description         text
income_date         date
invoice_number      varchar(255) nullable
created_at          timestamp
updated_at          timestamp

indexes:
- project_id
- income_date
```

#### software_licenses

```sql
id                  bigint primary key
name                varchar(255)
vendor              varchar(255) nullable
license_type        varchar(50)  -- one_time/subscription
cost                decimal(10,2)
renewal_date        date nullable
notes               text nullable
active              boolean default true
created_at          timestamp
updated_at          timestamp

indexes:
- license_type
- renewal_date
- active
```

#### invoices

```sql
id                  bigint primary key
client_id           bigint nullable foreign key → clients.id
project_id          bigint nullable foreign key → projects.id
invoice_number      varchar(255) unique
invoice_date        date
due_date            date nullable
subtotal            decimal(10,2)
tax_amount          decimal(10,2) default 0
total_amount        decimal(10,2)
status              varchar(50) default 'draft'  -- draft/sent/paid/cancelled
notes               text nullable
created_at          timestamp
updated_at          timestamp

indexes:
- client_id
- project_id
- invoice_number
- invoice_date
- status
```

#### billing_rates (polymorphic)

```sql
id                  bigint primary key
billable_type       varchar(255)  -- Client, Project, Task, TimeEntry
billable_id         bigint
rate                decimal(10,2)
rate_type           varchar(50) default 'hourly'  -- hourly/fixed/daily
currency            varchar(3) default 'USD'
effective_from      date nullable
effective_until     date nullable
created_at          timestamp
updated_at          timestamp

indexes:
- billable_type, billable_id (composite)
- effective_from
- effective_until
```

### Model Relationships

**Client Model**:

```php
class Client extends Model
{
    use HasBillingRate;

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function timeEntries()
    {
        return $this->hasManyThrough(TimeEntry::class, Project::class);
    }
}
```

**Project Model**:

```php
class Project extends Model
{
    use HasBillingRate;

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function timeEntries()
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function income()
    {
        return $this->hasMany(Income::class);
    }
}
```

**TimeEntry Model**:

```php
class TimeEntry extends Model
{
    use HasBillingRate;

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'billable' => 'boolean',
        'billed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // Accessor for calculated amount
    public function getBillableAmountAttribute(): float
    {
        if (!$this->billable || !$this->hourly_rate) {
            return 0;
        }
        return ($this->duration_minutes / 60) * $this->hourly_rate;
    }
}
```

---

## Billing System

### Flexible Rate Hierarchy

The billing system supports multiple rate levels to accommodate different consulting scenarios:

**Rate Determination Order** (first match wins):

1. **Time Entry Rate** - Specific to individual entry (emergency/special rates)
2. **Task Rate** - Different rates for different types of work (design vs development)
3. **Project Rate** - Override client's default for specific projects
4. **Client Rate** - Default rate for all client work
5. **System Default** - Fallback (configured in settings)

### HasBillingRate Trait

```php
trait HasBillingRate
{
    public function billingRate()
    {
        return $this->morphOne(BillingRate::class, 'billable');
    }

    public function getHourlyRate(): ?float
    {
        return $this->billingRate?->rate;
    }

    public function setHourlyRate(float $rate, ?Carbon $effectiveFrom = null): void
    {
        $this->billingRate()->updateOrCreate(
            ['billable_type' => get_class($this), 'billable_id' => $this->id],
            [
                'rate' => $rate,
                'rate_type' => 'hourly',
                'effective_from' => $effectiveFrom ?? now(),
            ]
        );
    }
}
```

### Use Cases

#### Scenario 1: Simple Client Work

```text
Client: Acme Corp ($150/hr)
  └─ Project: Website Redesign (uses client rate)
      └─ Time Entry: 5 hours
          = 5 × $150 = $750
```

#### Scenario 2: Project Rate Override

```text
Client: Acme Corp ($150/hr)
  └─ Project: Emergency Support ($200/hr override)
      └─ Time Entry: 3 hours
          = 3 × $200 = $600
```

#### Scenario 3: Internal Work (No Client)

```text
Project: Solowork Development ($100/hr for accounting)
  └─ Time Entry: 10 hours
      = 10 × $100 = $1,000 (cost allocation)
```

#### Scenario 4: Shared Library

```text
Project: Auth Library (no client, no rate)
  └─ Time Entry: 40 hours
      Later: Allocate costs to Client A + Client B
```

---

## Frontend Architecture

### Livewire Component Strategy

**Page-level components** (one component per screen, keeps it simple and fast):

```text
app/Livewire/
├── Dashboard.php              # Main overview page
├── Clients/
│   ├── Index.php              # Client list
│   ├── Show.php               # Client detail view
│   └── Form.php               # Create/edit client
├── Projects/
│   ├── Index.php
│   ├── Show.php
│   └── Form.php
├── Tasks/
│   ├── Index.php
│   ├── Show.php
│   └── Form.php
├── TimeEntries/
│   ├── Index.php
│   ├── QuickEntry.php         # Modal for fast time logging
│   └── Form.php
├── Expenses/
│   └── Index.php
├── Income/
│   └── Index.php
├── SoftwareLicenses/
│   └── Index.php
└── Shared/
    ├── CommandPalette.php     # Global command palette
    ├── KeyboardReference.php  # Help modal (Ctrl held or ?)
    └── ThemeToggle.php        # Dark/light theme switcher
```

---

## Keyboard Navigation & Accessibility

### Design Philosophy

**Accessibility-First**: Build on patterns screen reader users already know, don't override browser/OS shortcuts, make discovery easy.

### Protected System Shortcuts

**Never override these**:

- `Ctrl/Cmd+C` - Copy
- `Ctrl/Cmd+V` - Paste
- `Ctrl/Cmd+A` - Select all
- `Ctrl/Cmd+F` - Find
- `Ctrl/Cmd+Z` - Undo
- `Ctrl/Cmd+R` - Refresh
- `Ctrl/Cmd+T` - New tab
- `Ctrl/Cmd+W` - Close tab
- `Tab` / `Shift+Tab` - Focus navigation (sacred!)

### Global Keyboard Shortcuts

**Always available**:

```text
Ctrl/Cmd+K         Command palette
Ctrl/Cmd+1         Navigate to Dashboard
Ctrl/Cmd+2         Navigate to Clients
Ctrl/Cmd+3         Navigate to Projects
Ctrl/Cmd+4         Navigate to Time Tracking
Ctrl/Cmd+Shift+T   Toggle theme (dark/light)
Hold Ctrl/Cmd      Show cheat sheet (after 2 seconds)
?                  Context help for current screen
Esc                Close modal/dialog/cancel
```

### Context Shortcuts

**Available on specific screens** (displayed at bottom of page):

**Client View**:

```text
Ctrl/Cmd+N         New project for this client
Ctrl/Cmd+T         New time entry for this client
Ctrl/Cmd+E         Edit client
Ctrl/Cmd+I         View invoices
```

**Time Entry List**:

```text
Ctrl/Cmd+N         New time entry
Ctrl/Cmd+T         Quick time entry (modal)
Ctrl/Cmd+F         Filter entries
```

### Accessibility Features

#### Skip Links

```html
<!-- At top of every page, visible on focus -->
<a href="#main-content" class="skip-link">Skip to main content</a>
<a href="#quick-actions" class="skip-link">Skip to quick actions</a>
```

#### Semantic HTML Structure

```html
<body>
    <header role="banner">
        <nav aria-label="Main navigation">
            <!-- Primary nav -->
        </nav>
    </header>

    <main id="main-content" role="main">
        <h1>Page Title</h1>
        <!-- Content -->
    </main>

    <nav id="quick-actions" aria-label="Quick actions">
        <h2 class="sr-only">Available Actions</h2>
        <ul>
            <li>
                <button accesskey="n">
                    <u>N</u>ew Project
                </button>
            </li>
            <li>
                <button accesskey="t">
                    <u>T</u>ime Entry
                </button>
            </li>
        </ul>
    </nav>
</body>
```

#### ARIA Labels & Live Regions

```html
<!-- Command palette with live results -->
<div role="combobox"
     aria-expanded="true"
     aria-controls="command-results">
    <input type="text"
           aria-label="Command palette"
           aria-autocomplete="list"
           placeholder="Type a command...">
</div>

<ul id="command-results"
    role="listbox"
    aria-live="polite">
    <!-- Results populated here -->
</ul>
```

#### Keyboard Reference for Screen Readers

Accessible via Command Palette search or direct navigation:

```text
Command palette → "keyboard" → Shows full reference
Or: /keyboard-reference route
Or: Navigate to Help section in main menu
```

### Clean Copy/Paste

**Use semantic HTML tables**, not ASCII art:

```html
<table class="font-mono terminal-table">
    <caption class="sr-only">Time entries for this week</caption>
    <thead>
        <tr>
            <th scope="col">Client</th>
            <th scope="col">Project</th>
            <th scope="col">Hours</th>
            <th scope="col">Amount</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Acme Corp</td>
            <td>Website Redesign</td>
            <td>40.5</td>
            <td>$4,050.00</td>
        </tr>
    </tbody>
</table>
```

When copied, browsers preserve as tab-separated values:

```text
Client       Project              Hours   Amount
Acme Corp    Website Redesign     40.5    $4,050.00
```

**For complex data**: Provide "Copy as CSV" / "Copy as TSV" buttons.

---

## Command Palette System

### Hybrid Approach

Commands can either **navigate** (full page load) or **execute actions** (in-place).

### Command Types

**Navigation Commands** (go to different pages):

```text
goto:dashboard         → /dashboard
goto:clients           → /clients
goto:projects          → /projects
goto:time              → /time-entries
client:list            → /clients
client:new             → /clients/create
```

**Action Commands** (execute without navigation):

```text
time:quick             → Open quick time entry modal
time:today             → Filter time entries to today
export:all             → Trigger data export
theme:toggle           → Switch dark/light theme
help                   → Show keyboard reference
```

**Smart Commands** (context-aware):

```text
client:edit            → Edit current client (if on client view)
project:switch         → Quick project switcher dropdown
```

---

## Theming & Visual Design

### Terminal Aesthetic

**Core Design Elements**:

- Monospace font (JetBrains Mono primary)
- Dense information layout
- Clear visual hierarchy without excessive styling
- Neon accent colors on dark backgrounds
- Minimal whitespace, maximum information density

### Color Themes

**Dark Theme (Default)**:
- Classic 80s terminal aesthetic
- Neon green primary text (#00ff00)
- Dark grey background (#1a1a1a)
- Magenta/cyan/yellow accents for errors/links/warnings

**Light Theme**:
- Inverted palette for bright environments
- Dark green text on off-white background
- Maintains terminal aesthetic with softer contrast

### Implementation

Use Tailwind CSS with custom color tokens and dark mode class strategy. Specific color values and CSS custom properties are implementation details left to the developer.

---

## RESTful JSON API

### Purpose

Provide programmatic access to all data for:
- Third-party integrations
- Mobile apps (future)
- Automation scripts
- External reporting tools

### Design

**Endpoint Structure**: `/api/v1/{resource}`

**Authentication**: Laravel Sanctum personal access tokens

**Resources**:
- `/api/v1/clients` - CRUD operations
- `/api/v1/projects` - CRUD operations
- `/api/v1/tasks` - CRUD operations
- `/api/v1/time-entries` - CRUD operations
- `/api/v1/expenses` - CRUD operations
- `/api/v1/income` - CRUD operations
- `/api/v1/invoices` - CRUD operations
- `/api/v1/export` - Trigger full data export (returns JSON Lines)

**Response Format**: Use Laravel API Resources for consistent formatting

**Versioning**: API versioned at URL level (`/v1/`) to allow future breaking changes

---

## Events & Notifications

### Event System

**Purpose**: Decouple actions from side effects, enable future webhooks/emails

**Domain Events**:
- `TimeEntryCreated`, `TimeEntryUpdated`, `TimeEntryDeleted`
- `ProjectCreated`, `ProjectUpdated`
- `ClientCreated`, `ClientUpdated`
- `InvoiceGenerated`
- `DataExported`

**Queue Driver**: Redis (for performance and reliability)

**Event Listeners**: Initially just for toast notifications, designed for future webhook/email support

### Toast Notifications

**Implementation**: Livewire component in corner of UI

**Notification Types**:
- Success (green) - "Time entry saved"
- Error (red) - "Failed to create invoice"
- Info (cyan) - "Export complete"
- Warning (yellow) - "Recovery codes running low"

**Auto-dismiss**: 5 seconds (dismissible earlier with ESC or click)

**Terminal Styling**: Monospace text, bordered boxes, consistent with overall aesthetic

### Email Templates

**Storage**: Database-backed templates with variable substitution

**Common Templates**:
- Invoice email (with payment details)
- Project summary (weekly/monthly)
- Payment reminder

**Functionality**: Copy-to-clipboard (no actual email sending in V1)

---

## Data Export

### JSON Lines Format

All data exported as **JSON Lines** (`.jsonl`) - one JSON object per line, ideal for streaming and CRDT compatibility.

### Export Service

```php
namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ExportService
{
    public function exportAll(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = "solowork_export_{$timestamp}.jsonl";

        $lines = [];

        // Export all entities
        $lines = array_merge($lines, $this->exportClients());
        $lines = array_merge($lines, $this->exportProjects());
        $lines = array_merge($lines, $this->exportTasks());
        $lines = array_merge($lines, $this->exportTimeEntries());
        $lines = array_merge($lines, $this->exportExpenses());
        $lines = array_merge($lines, $this->exportIncome());
        $lines = array_merge($lines, $this->exportSoftwareLicenses());

        Storage::put("exports/{$filename}", implode("\n", $lines));

        return Storage::path("exports/{$filename}");
    }

    private function exportClients(): array
    {
        return Client::all()->map(function ($client) {
            return json_encode([
                'type' => 'client',
                'id' => $client->id,
                'data' => $client->toArray(),
                'exported_at' => now()->toIso8601String(),
            ]);
        })->toArray();
    }

    // ... similar methods for other entities
}
```

### Export Format Documentation

**Example export file**:

```jsonl
{"type":"client","id":1,"data":{"id":1,"name":"Acme Corp","email":"contact@acme.com","company":"Acme Inc","created_at":"2025-01-15T10:00:00Z"},"exported_at":"2025-11-05T14:30:00Z"}
{"type":"project","id":1,"data":{"id":1,"client_id":1,"name":"Website Redesign","status":"active","created_at":"2025-01-16T09:00:00Z"},"exported_at":"2025-11-05T14:30:00Z"}
{"type":"time_entry","id":1,"data":{"id":1,"project_id":1,"task_id":5,"started_at":"2025-01-20T09:00:00Z","ended_at":"2025-01-20T12:30:00Z","duration_minutes":210,"notes":"Initial wireframes","billable":true,"hourly_rate":150.00},"exported_at":"2025-11-05T14:30:00Z"}
```

**Note**: This export format is for complete data portability (backup, migration). For programmatic access to data, use the RESTful JSON API documented separately.

### User Documentation

Create `/docs/export-format.md` with:

- Format specification (JSON Lines)
- Schema for each entity type
- Field descriptions and data types
- Import/migration guide for other tools
- Example parsing scripts (Python, JavaScript)

---

## Deployment

### Kubernetes (TrueNAS Scale)

Solowork is deployed via raw Kubernetes manifests using TrueNAS Scale's "Deploy with YAML" feature.

**Architecture**:

- **App Container**: Laravel Octane with FrankenPHP (single binary, no separate web server)
- **Redis Container**: Session, cache, queue driver (sidecar pattern)
- **Persistent Volume**: SQLite database + file storage
- **Ingress**: HTTPS will be terminated before hitting the container via a reverse proxy, need to accept standard headers

**Manifest Structure**:

```text
kubernetes/
├── namespace.yaml
├── configmap.yaml             # Laravel environment config + Octane settings
├── secret.yaml                # APP_KEY, sensitive values
├── pvc.yaml                   # Persistent volume claims
├── deployment.yaml            # FrankenPHP + Redis sidecar
├── service.yaml
└── ingress.yaml
```

**Persistent Volume Claims**:

- **Database**: `/app/database/database.sqlite` (10Gi)
- **Storage**: `/app/storage` (logs, cache files, file uploads)

**Deployment Configuration**:

- **Sidecar pattern**: Redis runs alongside FrankenPHP in same pod
- **Init container**: Runs migrations on startup
- **Health checks**: Liveness and readiness probes (via Octane)
- **Resource limits**: CPU/memory constraints
- **Octane workers**: 1-2 workers (single-user, low concurrency)
- **Graceful shutdown**: SIGTERM handling for worker cleanup

**Redis Configuration**:

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=localhost  # Sidecar pattern - same pod
REDIS_PORT=6379
```

**Octane Configuration**:

```env
OCTANE_SERVER=frankenphp
OCTANE_WORKERS=1
OCTANE_MAX_REQUESTS=1000  # Recycle workers to prevent memory leaks
```

**Monitoring (Laravel Nightwatch)**:

- Memory usage tracking (detects worker memory leaks)
- Slow query detection
- Exception monitoring
- Worker health checks

**Benefits**:

- Declarative infrastructure (GitOps-ready)
- Persistent data across pod restarts
- Redis for performance from day 1
- Simple deployment (paste YAML into TrueNAS UI)
- Easy updates (modify YAML and re-apply)
- Simpler container (FrankenPHP = one binary vs PHP-FPM + Nginx)
- Better performance (app stays in memory between requests)
- Built-in monitoring (Nightwatch catches Octane issues)

---

## Appendix

### Key Architectural Decisions

| Decision | Rationale |
|----------|-----------|
| WebAuthn over passwords | Modern, secure, frictionless for single-user |
| SQLite over PostgreSQL | Simpler for self-hosted, single-user deployment |
| Traditional MVC over DDD | Reduces complexity for personal project scope |
| Livewire over full SPA | Server-side rendering aligns with speed/simplicity goals |
| Page components over nested | Faster, simpler, less state management |
| Nullable relationships | Flexible work tracking beyond just client billing |
| Polymorphic billing rates | Future-proof for varied consulting scenarios |
| JSON Lines export | Standard format, CRDT-compatible, well-documented |
| Dark theme default | Terminal aesthetic, developer preference |
| A11y-first keyboard nav | Inclusive design, power user alignment |

### Development Principles

1. **Start Simple**: Ship working features over perfect abstractions
2. **Defer Complexity**: Don't add features "just in case"
3. **Measure Impact**: Only optimize what's measurably slow
4. **Document Decisions**: Explain why, not just what
5. **Accessible Always**: A11y is not optional
6. **Keyboard First**: Mouse should be unnecessary
7. **Fast Feedback**: Sub-second interactions wherever possible
8. **Own Your Data**: Export should be trivial and complete

### Success Metrics

**User Experience**:

- Log a full day's work in < 60 seconds
- Navigate entire app without mouse
- Find any information in < 3 keystrokes
- Screen reader compatible throughout

**Technical Performance**:

- Page load < 1 second
- Time to interactive < 2 seconds
- Zero accessibility violations (automated tests)
- All data exportable with documentation
