# Ubiquitous Language

## Questions & tracking

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Question** | An immutable, typed prompt that defines what the user is tracking — includes thing, unit, amount, and question type | Item, tracker, metric |
| **Question type** | The category of a question: either "how long" or "how many" | Tracking mode, category |
| **Thing** | The subject being tracked, stored as a structured field on the question | Item, product, subject |
| **Unit** | The unit of measurement for the thing (e.g. "capsules", "ml", "rolls") | Measure, metric |
| **Amount** | The quantity of units in a question (e.g. 30 capsules, 750ml) | Count, quantity, size |
| **Label** | The full human-readable sentence form of a question | Name, title, description |

## Rounds & entries

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Round** | One cycle of answering a question — for "how long" it's manually started and ended, for "how many" it's a lazy time-based period | Cycle, session, batch, instance |
| **Entry** | A single recorded event against a question, created via the record action | Tap, log, event, data point |
| **Check-in** | The start of a "how long" round — the moment a new batch is opened | Start, begin, open |
| **Check-out** | The end of a "how long" round — the moment a batch is used up | End, finish, close, stop |
| **Usage log** | An entry recorded against a "how many" question — a single occurrence | Tick, tally, count |
| **Period** | The declared time window for a "how many" question (daily, weekly, monthly) that defines lazy round boundaries | Cadence, interval, frequency, timeframe |

## Predictions & observations (updated)

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Guess** | The user's prediction for a question, stored on QuestionState and updateable at any time via its own event — compared against actual round outcomes | Estimate, prediction, forecast |
| **Note** | A timestamped piece of freeform text on a question's timeline — its own event, no direct relation to rounds or other entities | Comment, remark, memo, annotation |

## Round lifecycle

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Active** | A round that has been started but not yet ended | Open, in-progress, running |
| **Ended** | A round that has been completed with a check-out | Closed, finished, done |
| **Voided** | A round marked as invalid — still projected but visually distinct and excludable from trends | Deleted, cancelled, removed |

## Interaction surfaces

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Tag** | A 4-character alphanumeric code that resolves to a question — the universal recording interface for QR codes, NFC, scripts, and Shortcuts | QR code, sticker, link, token |
| **Recording surface** | Any interface scoped to recording entries (the tag URL, the record button) — write-only, not a reading surface | Input, form, endpoint |
| **Question page** | The dashboard for a question at `/q/{id}` — shows current state, round history, trends, and the primary action button | Dashboard, detail page, tracker page |
| **Record button** | The single primary action on the question page — changes color and text based on state | Action button, CTA |
| **Timeline** | The unified chronological view on the question page — shows rounds, notes, guess changes, and voids newest-first | History, feed, activity log |

## Actions (updated)

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Action** | An application-level orchestrator that encapsulates a domain operation — fires one or more events, calls `Verbs::commit()`, and is the only public API that Livewire touches | Command, service, handler |
| **AskQuestion** | Action: fires `QuestionAsked` + `RoundStarted` + optionally `GuessUpdated` + optionally `NoteAdded` | CreateItem, AddTracker |
| **StartRound** | Action: fires `RoundStarted` + optionally `GuessUpdated` + optionally `NoteAdded` — the check-in | CheckIn, OpenRound, Begin |
| **EndRound** | Action: fires `RoundEnded` + optionally `NoteAdded` — the check-out | CheckOut, CloseRound, Finish |
| **RecordEntry** | The generic quick-tap action — reads question type and round state, dispatches to the right action | Tap, Log, Track |
| **VoidRound** | Action: fires `RoundVoided` + optionally `NoteAdded` | DeleteRound, CancelRound |
| **UpdateGuess** | Action: fires `GuessUpdated` — changes the current guess on a question | ReviseEstimate, SetPrediction |
| **AddNote** | Action: fires `NoteAdded` — adds a standalone note to a question's timeline | AnnotateQuestion, Comment |
| **RetireQuestion** | Action: fires `QuestionRetired` — marks a question as no longer actively tracked | Archive, Delete, Deactivate |

## Events (new)

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **NoteAdded** | Event carrying `question_id`, `body`, `occurred_at`, `recorded_at` — projects to `timeline_entries` | AnnotationCreated, NoteCreated |
| **GuessUpdated** | Event carrying `question_id`, `guess` — mutates QuestionState, projects to `questions` and `timeline_entries` | GuessMade, PredictionSet |

## Tags

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **CreateTag** | Generate a new 4-char alphanumeric code | GenerateCode, MakeQR |
| **LinkTag** | Associate a tag code with a question | ConnectTag, AssignTag, CoupleTag |
| **UnlinkTag** | Remove the association between a tag and a question | DisconnectTag, DetachTag |

## Projections (new)

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Timeline entry** | A row in `timeline_entries` — the read model for the question page timeline, projected from domain events | Activity, history item, log entry |

## Relationships (updated)

- A **Question** has zero or many **Rounds**
- A **Round** belongs to exactly one **Question**
- A **Round** has zero or many **Entries** (for "how many" with lazy rounds)
- A "how long" **Round** has exactly one **Check-in** and at most one **Check-out**
- A **Question** has zero or one current **Guess** (stored on QuestionState, updateable via **GuessUpdated**)
- A **Question** has zero or many **Notes** (each is its own event, no relation to rounds)
- A **Question** has zero or many **Timeline entries** (projected from all domain events)
- A **Tag** is linked to zero or one **Question** (and can be relinked)
- A **Question** can have zero or many **Tags** linked to it

## Aggregate boundaries

- **Question** is an aggregate — holds label, thing, unit, amount, type, period, guess
- **Round** is a separate aggregate — holds its own lifecycle (active/ended/voided), linked to a question by questionId
- **Tag** is a lightweight entity — just a code and a questionId reference

## Architectural conventions (new)

- **Actions** are the only public API for domain operations. Livewire components call actions, never events or Verbs directly.
- **Events** are fired inside actions. An action may fire multiple events (e.g. `RoundStarted` + `GuessUpdated` + `NoteAdded`).
- **Actions call `Verbs::commit()`** at the end, so callers don't need to know about event sourcing mechanics.
- **Notes and guesses are always separate events**, never inline properties on other events like `RoundStarted` or `RoundEnded`.

## Example dialogue (updated)

> **Dev:** "When a user ends a round and adds a note, how does that work?"
> **Domain expert:** "The **EndRound** action fires two events: **RoundEnded** to close the round, and **NoteAdded** to put the note on the timeline. The note doesn't belong to the round — it's just a timestamped entry on the **Question**'s **Timeline**."
> **Dev:** "So if I look at the timeline, the round ending and the note are separate entries that happen to be at the same time?"
> **Domain expert:** "Exactly. The **Timeline** is chronological. A **Note** added during a check-out appears right next to the round ending, but they're independent. You could also add a **Note** without any round action."
> **Dev:** "And the **Guess** works the same way?"
> **Domain expert:** "Yes. When you start a round with a guess, the **StartRound** action fires **RoundStarted** plus **GuessUpdated**. The guess lives on the **Question**, not the **Round**. You can update it anytime via **UpdateGuess** — it's always the latest value."
> **Dev:** "What does Livewire know about all this?"
> **Domain expert:** "Nothing about events. Livewire calls an **Action** with data, gets back what it needs. The action handles events, commits, everything. Livewire is just a thin UI shell."

## Flagged ambiguities

- **"Entry" vs "Round"**: An **Entry** is a single recorded event (a tap, a log). A **Round** is a grouping of entries or a lifecycle (start->end). For "how long" questions, a **Round** contains exactly two implicit entries (check-in and check-out). For "how many" questions, a **Round** is a lazy time bucket that contains many **Entries**. Don't confuse the two — a user "records an entry," they don't "record a round."
- **"Start round" vs "Check-in"**: These are the same action — **StartRound** is the code name, **Check-in** is the domain language for the user-facing concept. Use **Check-in** in UI copy, **StartRound** in code.
- **"Annotation" is retired (updated)**: Previously a separate concept for "note not tied to a round." Since notes have no relation to rounds at all, **Annotation** is now just an alias to avoid. Use **Note** everywhere.
- **"Command" vs "Action" (new)**: The PRD uses "Command" (DDD terminology). The codebase uses **Action** (Laravel convention). They mean the same thing. Use **Action** in code and conversation. Avoid "Command" to prevent confusion with Artisan commands.
- **"Description" was dropped**: Early in the conversation we had a per-round description field. This was replaced by making the **Question** itself carry the full context (thing + unit + amount).
- **"Snapshot" should not be used**: This is an overloaded term in event sourcing. When referring to the guess at a point in time, say "the guess at the time" or "the current guess" — never "snapshot."
- **"Item" should not be used**: Early conversations used "Item" as the aggregate name. This was replaced by **Question**.
