# AGENTS.md

This file gives **just enough** guidance for AI coding assistants working in this repo.
For detailed conventions, see the docs in `/docs`:

- **Architecture**: `/docs/architecture.md`
- **Code Style**: `/docs/code-style.md`
- **Examples & Testing Patterns**: `/docs/examples.md`

## Project Overview

**Solowork** — Laravel 12 with Octane (FrankenPHP) + Livewire 3 + Alpine.js terminal-inspired business management tool for solo consultants. Built on the principle of **speed over aesthetics** and **keyboard-first navigation**. Self-hosted on Kubernetes, single-user (v1), with WebAuthn authentication. Uses Redis for cache/queue/sessions and Nightwatch for monitoring.

## Rules of Engagement

> If in doubt, follow `/docs/code-style.md`.

### Backend (Laravel + Livewire)

- **Controllers**: Thin controllers; delegate logic to **Services**. Use resource controllers for CRUD, invokable for single actions.
- **Livewire Components**: Page-level components (one per screen). Keep properties public only for form bindings. Use `wire:model.lazy` or `wire:model.live.debounce` appropriately.
- **Services**: Single responsibility. Business logic lives here, not in controllers or Livewire components.
- **Form Requests**: Use for all validation. Never validate in controllers or components directly.
- **Models**: Use Eloquent relationships, scopes, and accessors. Add proper casts and fillable properties.
- **Database**: SQLite for single-user. Nullable relationships everywhere (clients can have no projects, projects can have no client, etc.). Polymorphic `BillingRate` model for flexible billing.
- **Migrations**: Edit existing migrations pre-deploy; only add new ones for shipped schemas.
- **Testing**: Use Pest. Test Livewire components, services, and models. See testing patterns in `/docs/examples.md`.
- **Caching**: Cache expensive queries with `Cache::remember()`. Clear cache on model updates via observers.
- **Octane**: App runs with Laravel Octane (FrankenPHP). Watch for static state leaks, reset singletons between requests. Laravel Nightwatch monitors for memory leaks.

### Frontend (Livewire + Alpine.js + Tailwind)

- **Livewire**: Handles 95% of interactivity. Server-side rendering for everything except client-side flourishes.
- **Alpine.js**: Client-side enhancements only (5% - modals, command palette, theme toggle, keyboard shortcuts).
- **Styling**: Tailwind CSS with **terminal theme**. Use CSS custom properties for colors. Monospace fonts everywhere. Dark mode by default (neon green terminal), light mode inverted.
- **Keyboard-first**: Every feature must be keyboard accessible. No mouse should be required. Global shortcuts (Ctrl+1-4 for navigation), context shortcuts (Ctrl+N for new, Ctrl+T for time entry, etc.).
- **Command Palette**: Artisan-style commands (`client:new`, `time:quick`, `goto:dashboard`). Hybrid navigation (routes) + actions (Livewire events).
- **Accessibility**: Screen reader compatible. Skip links, ARIA labels, semantic HTML. `accesskey` for common actions. Clean copy/paste (real HTML tables, not ASCII art).
- **No mouse required**: Tab navigation works everywhere. Keyboard shortcuts for all actions. Command palette for power users.
- **Terminal aesthetic**: Dense information layout, minimal whitespace, monospace fonts, neon accent colors, uppercase labels.
- **Theme toggle**: `Ctrl+Shift+T` or manual button. Persisted in localStorage. CSS variables swap entire palette.
- **Blade Components**: Create reusable components in `resources/views/components/ui/`. Use `@props` and merge classes.

### Testing Philosophy

- **Behavior over implementation**: Test what users do, not how code works internally.
- **Integration over unit**: Focus on Livewire component tests and service tests. Skip trivial model tests.
- **Factories for data**: Never manually create test data. Use factories with states.
- **Descriptive names**: `it('calculates unbilled hours correctly')` not `test_calculation()`.
- **One assertion per test**: Keep tests focused on single behavior.

### Authentication

- **WebAuthn primary**: Passkey/biometric/hardware key login. No passwords.
- **Recovery codes fallback**: 10 single-use codes generated on registration. Hashed before storage.
- **Single-user**: No multi-tenant in v1. User model exists for future extensibility.

### Data Model Philosophy

- **Flexible relationships**: Everything is nullable. Client-less projects, project-less time entries, task-less entries all supported.
- **Billing hierarchy**: Polymorphic `BillingRate` on Client/Project/Task/TimeEntry. Rate determination: Entry → Task → Project → Client → Default.
- **Work tracking, not just billing**: Track internal work, personal projects, unbillable time. Not just client work.

### Code Style Highlights

- **Laravel Pint**: Run `./vendor/bin/pint` before commits.
- **Types everywhere**: Scalar and return types on all methods. Nullable where appropriate.
- **No business logic in controllers**: Controllers call services. Services contain logic.
- **Livewire validation**: Use `rules()` method or Form Requests, never inline validation.
- **Terminal theme classes**: `bg-terminal-bg`, `text-terminal`, `border-terminal-border`, etc.
- **Uppercase labels**: Terminal aesthetic. "CLIENT NAME" not "Client Name".
- **Monospace everything**: `font-mono` class on all data fields, tables, forms.

### Commands Reference

- `composer run dev` - Start development (Laravel + Vite)
- `php artisan test` - Run Pest tests
- `./vendor/bin/pint` - Format PHP code
- `./vendor/bin/phpstan` - Run static analysis
- `php artisan migrate:fresh --seed` - Reset database with sample data

### What NOT to Do

- ❌ Don't use animations or transitions
- ❌ Don't add features "for delight" or "to be helpful"
- ❌ Don't create multi-step wizards (quick entry forms only)
- ❌ Don't hide features or make discovery hard
- ❌ Don't break keyboard navigation
- ❌ Don't use colors that aren't in the terminal theme
- ❌ Don't add padding/whitespace for "breathing room" (information density)
- ❌ Don't make data export difficult

### Success Metrics to Keep in Mind

- Time to log full day's work: < 60 seconds
- Keyboard shortcut for everything: Yes
- Navigate entire app without mouse: Yes
- Find any information: < 3 keystrokes
- Screen reader compatible: Yes
- Page load: < 1 second
- Data export: One command, well documented

### Priority Order

1. **Speed** - Fast data entry, instant feedback, no loading states
2. **Keyboard** - Every action has a shortcut
3. **Accessibility** - Screen readers, semantic HTML, ARIA
4. **Information density** - See everything you need, no scrolling
5. **Predictability** - Same workflow every time, muscle memory

When in doubt, ask: "Does this make data entry faster?" and "Can I do this without touching the mouse?"
