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

### Replay Safety

Do **not** use `#[Once]` or `Verbs::unlessReplaying()` to skip `handle()` on replay. Instead, make `handle()` idempotent so it produces the correct projection state on every run — first fire or replay. See the **Projections** section for the pattern.

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

`handle()` methods **must be idempotent** so that event replay produces the correct state without errors. Use `updateOrCreate`, `upsert`, or similar idempotent operations — never plain `create`, which will fail on replay with duplicate key violations.

```php
// Correct — idempotent, safe to replay
public function handle(): void
{
    Round::updateOrCreate(
        ['id' => $this->round_id],
        [
            'question_id' => $this->question_id,
            'started_at' => $this->started_at,
        ],
    );
}

// Wrong — will throw on replay
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
