<laravel-boost-guidelines>
=== .ai/design-language rules ===

# Paceday Design Language

Paceday is a personal consumption and habit tracker. It should feel like a **playful mobile app**, not a SaaS dashboard. The core aesthetic is **playful interactions with warm resting states** — think Duolingo's energy for active moments, cozy journal app for everything else.

## Aesthetic Identity

- **Mood**: Friendly, bubbly, lightweight. Never corporate, never clinical.
- **Palette**: Burnt autumn — rust, amber, warm brown, cream, sand. Blue is the only allowed cool accent, used for status indicators (e.g. "round in progress" badges), never for primary actions.
- **Mode**: Light-first. The gradient background (cream → sand) is the signature. Dark mode exists but is secondary.

## Typography

- **Headings**: `Fredoka` — round, chunky, game-like. This is what makes Paceday feel fun. Use it for all headings, labels, buttons, and any text that should feel playful.
- **Body**: `DM Sans` — clean, readable, slightly warm. Used for paragraphs, descriptions, and secondary text.
- **CSS vars**: `var(--font-heading)` and `var(--font-body)`. These are set in `app.css` and loaded from `fonts.bunny.net` in `partials/head.blade.php`.

## Color Tokens

Named colors are defined as CSS custom properties in `app.css`:

| Token | Hex | Usage |
|---|---|---|
| `--color-rust` | `#c2410c` | Primary actions, CTA buttons, active input accents |
| `--color-rust-dark` | `#9a3412` | Hover states for primary actions |
| `--color-bark` | `#5e4630` | Primary text (headings, strong content) |
| `--color-bark-light` | `#7d5f3e` | Secondary text (descriptions, metadata) |
| `--color-cream` | `#fdf8f3` | Page background (gradient top) |
| `--color-sand` | `#f5ebe0` | Page background (gradient bottom), subtle surface fills |

Flux's zinc scale is remapped to a warm brown/taupe ramp — use `zinc-*` classes and they'll render warm automatically.

## Layout

- **No sidebar**. The app uses a simple **top bar** (Paceday branding left, user avatar right) with content centered below.
- **Content width**: `max-w-lg` (512px). This keeps the app feeling narrow and phone-like even on desktop.
- **Background**: Subtle gradient via the `.paceday-gradient` class on `<body>` — cream at top, sand at bottom.
- Layout lives in `resources/views/layouts/app/sidebar.blade.php` (name is legacy, it's now a top-bar layout).
- Pages using `Route::livewire()` get the layout applied automatically — **do not** wrap page content in `<x-layouts::app>` inside the template, or you'll get double-nesting.

## Surfaces (Cards)

- Use `.paceday-card` — `rounded-3xl`, white background, soft warm shadow. **No borders.**
- Elevation creates depth, not borders. Cards float on the gradient background.
- The shadow uses `shadow-zinc-900/[0.04]` for a barely-there warmth.
- Interactive cards (links) should add `hover:shadow-xl hover:-translate-y-0.5 transition` for a lift effect.

## Buttons

- **Shape**: Pill-shaped (`rounded-full`). This is set globally on all Flux buttons via `app.css`.
- **Primary**: Burnt orange (`bg-rust`), white text, warm shadow. Hover lifts up 1px and darkens. Use for the main action on every screen.
- **States/info**: Blue accent — use for badges and status indicators like "Day 5" or "Round in progress", never for CTAs.
- **Font**: Fredoka (set globally via CSS).

## Form Inputs

Standard Flux inputs (`<flux:input>`, `<flux:textarea>`) are globally styled in `app.css`:
- `rounded-xl`, subtle shadow, warm amber focus ring.
- Labels use Fredoka via `[data-flux-label]`.

## Inline Sentence Inputs

For the "How long does ___ of ___ last?" builder pattern, use raw `<input>` elements with:
- Class: `.sentence-input` inside a `.sentence-builder` container.
- These are invisible inputs — no border except a dashed underline, no background. Text matches the surrounding sentence in font, size, and weight.
- User input renders in rust color to visually distinguish editable parts.
- Focus state: solid underline + faint rust background tint.
- Use Alpine `x-data` with a `resize()` helper to auto-size inputs to their content width (see `questions/create` for the reference implementation).

## Status Badges

Active/in-progress states use a blue badge pattern:
```html
<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
    <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
    Day 5
</span>
```

## Interaction Principles

- **Inputs should feel native to their context.** If an input is part of a sentence, style it as part of that sentence. If it's a standalone field, use Flux's form components. Don't default to generic form layouts when something more integrated is possible.
- **Hover/tap feedback matters.** Primary buttons lift on hover. Cards lift on hover. Interactive elements should always respond to touch.
- **Keep it lightweight.** Prefer subtle transitions (0.2s ease) over heavy animations. The app should feel responsive, not theatrical.

=== .ai/paceday-rules rules ===

# Paceday Rules

## Guidelines

Project-specific AI guidelines live in `.ai/guidelines/`. The `CLAUDE.md` file is auto-generated by Laravel Boost — never edit it directly. Add or update guidelines in `.ai/guidelines/` instead.

## Architecture Conventions

- **Actions** are domain-specific operations. They encapsulate a single business operation and are the primary entry point for domain logic.
- **Jobs** are orchestrators. They coordinate multiple actions, handle sequencing, and manage cross-cutting concerns. They are not domain logic themselves.

=== .ai/verbs-rules rules ===

# Verbs Rules

Verbs is the event sourcing framework used in this project. Full documentation is available at `docs/verbs-docs/` for reference. Examples are available at `docs/verbs-examples/`.

## Firing Events

Always use the `verb()` helper with a new event instance. Never use the static `Event::fire()` syntax.

```php
// Correct
verb(new RoundStarted(
    question_id: $questionId,
    started_at: now(),
));

// Wrong — never do this
RoundStarted::fire(question_id: $questionId, started_at: now());
```

This is a hard rule — in application code, actions, jobs, and tests. One way to fire events, everywhere.

## Event Lifecycle

This is the most important concept in Verbs. Events pass through these hooks in order:

### On First Fire

1. **`__construct()`** — Called once when the event is first created. Must receive all data the event needs.
2. **`authorize()`** — Verify the current user is allowed to fire this event. Works like Laravel form request authorization.
3. **`validate()`** — Assert the event is valid given current state. Methods prefixed with `validate` are validation methods. They receive a type-hinted state as parameter. Use `$this->assert()` to enforce conditions. Multiple validation methods are supported (e.g. `validateQuestion()`, `validateRound()`).
4. **`apply()`** — Mutate state in memory. Methods prefixed with `apply` update states. Receives type-hinted state. Can have multiple apply methods. This runs immediately — state is updated before `handle()`.
5. **`fired()`** — Runs after apply but before commit. Typically used for firing additional events.
6. **`handle()`** — Runs after the event is committed (saved to the event store). This is where side effects go: writing to Eloquent models, sending notifications, dispatching jobs.

### On Replay

Only two hooks run during replay:

1. **`apply()`** — Rebuilds state from the event stream.
2. **`handle()`** — Runs again unless prevented.

The `authorize()`, `validate()`, `fired()`, and `__construct()` hooks do **not** run during replay. This distinction is critical — anything that should only happen once must be protected.

### Preventing Re-execution on Replay

Use either approach to prevent `handle()` from re-running during replay:

```php
// Option A: #[Once] attribute
#[Once]
public function handle(): void
{
    // This only runs on first commit, not on replay
}

// Option B: Verbs::unlessReplaying() helper
public function handle(): void
{
    Verbs::unlessReplaying(function () {
        // One-time side effects here
    });
}
```

## States

States are simple PHP objects that accumulate data from events over time. They are loaded once and kept in memory.

- Generate with `php artisan verbs:state StateName`.
- Extend `State` for entity states (identified by ID) or `SingletonState` for app-wide states.
- Load with `State::load($id)` or `State::loadOrFail($id)` (throws 404).
- Singleton states load with `State::singleton()`.
- Keep states lean — they track properties, not business logic.
- States must **never** reference Eloquent models. This will throw an exception.
- If a state starts holding complex nested data (arrays of objects, collections), that's a sign you need a separate state.
- Pair states to models: the state manages event data in memory, the model serves the UI.

## IDs

This project uses **ULIDs** instead of Verbs' default snowflake IDs. This is configured in `config/verbs.php` (`id_type` = `ulid`). All state and event IDs are ULID strings.

In migrations, use `$table->ulid('id')->primary()` for primary keys and `$table->foreignUlid('column_name')` for foreign keys.

## Linking Events to States

Use `#[StateUlid]` (from `App\Support\Verbs\StateUlid`) on event properties to link events to states. Do **not** use the built-in `#[StateId]` — it does not handle ULIDs correctly.

```php
use App\Support\Verbs\StateUlid;

class RoundStarted extends Event
{
    public function __construct(
        #[StateUlid(QuestionState::class)]
        public string $question_id,

        #[StateUlid(RoundState::class)]
        public ?string $round_id = null,
    ) {}
}
```

Setting a state ID property to `null` enables autofill — Verbs will generate a ULID automatically. Use this for events that create new entities.

## Projections

Eloquent model writes are projections — they happen in the `handle()` method after an event is committed.

```php
public function handle(): void
{
    Round::create([
        'id' => $this->round_id,
        'question_id' => $this->question_id,
        'started_at' => $this->started_at,
    ]);
}
```

Events are the write model. Eloquent models are the read model for the UI. All models created via events can be rebuilt by replaying the event stream.

## Committing Events

Events are queued in memory and committed together at the end of a request via `Verbs::commit()`. This saves them to the event store and triggers `handle()` methods.

In application code, committing happens automatically at the end of the request lifecycle. In tests, you must call `Verbs::commit()` manually.

## Testing

### Commit Strategy

Always use manual `Verbs::commit()` in tests. Do not use `Verbs::commitImmediately()`.

```php
it('ends a round', function () {
    // Arrange: set up state
    verb(new QuestionAsked(
        question_id: $questionId,
        label: 'How long does 40 capsules of coffee last?',
    ));
    verb(new RoundStarted(
        question_id: $questionId,
        round_id: $roundId,
    ));
    Verbs::commit();

    // Act
    verb(new RoundEnded(
        round_id: $roundId,
        ended_at: now(),
    ));
    Verbs::commit();

    // Assert against state
    $state = RoundState::load($roundId);
    expect($state->status)->toBe('ended');
});
```

### Test Approaches

- **Feature tests** test through HTTP routes — fire events implicitly through controllers and actions, assert against HTTP responses and models. This tests the full stack.
- **State/event tests** fire events directly with `verb(new Event(...))`, assert against state properties. This tests state transitions and validation rules in isolation.

### State Factories

Use `State::factory()` to bootstrap state for tests without manually firing setup events:

```php
$state = RoundState::factory()->create([
    'status' => 'active',
    'started_at' => now()->subDays(3),
]);
```

### Assertion Helpers

Use `Verbs::fake()` to set up an isolated event store, then assert:

```php
Verbs::assertCommitted(RoundEnded::class);
Verbs::assertNotCommitted(RoundVoided::class);
Verbs::assertNothingCommitted();
```

## Serialization

Verbs uses Symfony Serializer to convert event and state properties to JSON. Default normalizers handle typical Laravel types. For custom objects, implement the `SerializedByVerbs` interface with the `NormalizeToPropertiesAndClassName` trait. Configure custom normalizers in `config/verbs.php`.

## Metadata

Attach metadata to all events via a service provider or middleware:

```php
Verbs::createMetadataUsing(function (Metadata $metadata, Event $event) {
    $metadata->user_id = auth()->id();
});
```

Access metadata on any event with `$event->metadata()` or `$event->metadata('key', default)`.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/flux (FLUXUI_FREE) - v2
- livewire/flux-pro (FLUXUI_PRO) - v2
- livewire/livewire (LIVEWIRE) - v4
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `fluxui-development` — Use this skill for Flux UI development in Livewire applications only. Trigger when working with <flux:*> components, building or customizing Livewire component UIs, creating forms, modals, tables, or other interactive elements. Covers: flux: components (buttons, inputs, modals, forms, tables, date-pickers, kanban, badges, tooltips, etc.), component composition, Tailwind CSS styling, Heroicons/Lucide icon integration, validation patterns, responsive design, and theming. Do not use for non-Livewire frameworks or non-component styling.
- `livewire-development` — Use for any task or question involving Livewire. Activate if user mentions Livewire, wire: directives, or Livewire-specific concepts like wire:model, wire:click, wire:sort, or islands, invoke this skill. Covers building new components, debugging reactivity issues, real-time form validation, drag-and-drop, loading states, migrating from Livewire 3 to 4, converting component formats (SFC/MFC/class-based), and performance optimization. Do not use for non-Livewire reactive UI (React, Vue, Alpine-only, Inertia.js) or standard Laravel forms without Livewire.
- `pest-testing` — Use this skill for Pest PHP testing in Laravel projects only. Trigger whenever any test is being written, edited, fixed, or refactored — including fixing tests that broke after a code change, adding assertions, converting PHPUnit to Pest, adding datasets, and TDD workflows. Always activate when the user asks how to write something in Pest, mentions test files or directories (tests/Feature, tests/Unit, tests/Browser), or needs browser testing, smoke testing multiple pages for JS errors, or architecture tests. Covers: it()/expect() syntax, datasets, mocking, browser testing (visit/click/fill), smoke testing, arch(), Livewire component tests, RefreshDatabase, and all Pest 4 features. Do not use for factories, seeders, migrations, controllers, models, or non-test PHP code.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.
- `ai-sdk-development` — Builds AI agents, generates text and chat responses, produces images, synthesizes audio, transcribes speech, generates vector embeddings, reranks documents, and manages files and vector stores using the Laravel AI SDK (laravel/ai). Supports structured output, streaming, tools, conversation memory, middleware, queueing, broadcasting, and provider failover. Use when building, editing, updating, debugging, or testing any AI functionality, including agents, LLMs, chatbots, text generation, image generation, audio, transcription, embeddings, RAG, similarity search, vector stores, prompting, structured output, or any AI provider (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).
- `fortify-development` — Laravel Fortify headless authentication backend development. Activate when implementing authentication features including login, registration, password reset, email verification, two-factor authentication (2FA/TOTP), profile updates, headless auth, authentication scaffolding, or auth guards in Laravel applications.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`, `php artisan tinker --execute "..."`).
- Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `php artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `php artisan config:show [key]`.
- To inspect routes, run `php artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== laravel/ai rules ===

## Laravel AI SDK

- This application uses the Laravel AI SDK (`laravel/ai`) for all AI functionality.
- Activate the `developing-with-ai-sdk` skill when building, editing, updating, debugging, or testing AI agents, text generation, chat, streaming, structured output, tools, image generation, audio, transcription, embeddings, reranking, vector stores, files, conversation memory, or any AI provider integration (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).

=== laravel/fortify rules ===

# Laravel Fortify

- Fortify is a headless authentication backend that provides authentication routes and controllers for Laravel applications.
- IMPORTANT: Always use the `search-docs` tool for detailed Laravel Fortify patterns and documentation.
- IMPORTANT: Activate `developing-with-fortify` skill when working with Fortify authentication features.

</laravel-boost-guidelines>
