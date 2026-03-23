# PRD: Paceday — Personal consumption tracker

## Problem Statement

I consistently underestimate how quickly I consume everyday products — coffee, toothpaste, toilet paper, trash bags. My guesses are roughly 2x too optimistic. I've been tracking this manually in Apple Notes with start dates, end dates, and guesses, but Notes gives me no structure, no trends, and no feedback loop. I want a tool that makes recording effortless and shows me my actual consumption patterns over time, so I can make better purchasing decisions and calibrate my intuition.

## Solution

Paceday is a mobile-first web application (Laravel/PHP, PostgreSQL, event-sourced) that lets users ask questions about their consumption and answer them through repeated rounds of tracking.

The core interaction is a single "record" button that adapts to context. The system knows what type of question you're tracking and what state you're in, so one tap does the right thing. Questions are phrased as natural sentences — "How long does it take to use 40 capsules of coffee?" — and the structured data (thing, unit, amount) is extracted from the sentence.

Physical QR codes (and later NFC stickers) extend the interaction surface. A tag is a 4-character code that resolves to a question. Scanning it is a recording action — the tag URL is a write-only surface, not a reading surface. Tags can also be hit programmatically via POST for automations, Shortcuts, and scripts.

The application uses Laravel's built-in auth scaffolding with magic link authentication. The website is heavily mobile-first with big typography where the interface *is* the content — inline text fields that form the question sentence, tappable dates, color-coded action buttons.

## User Stories

1. As a user, I want to sign up with a magic link so that I don't have to manage a password.
2. As a user, I want to ask a "how long" question using a sentence template ("How long does it take to use [amount] [unit] of [thing]?") so that the question reads naturally and captures structured data.
3. As a user, I want to ask a "how many" question using a sentence template ("How many times do I [activity] per [period]?") so that frequency tracking has a time dimension baked in.
4. As a user, I want creating a question and starting the first round to feel like one flow so that I don't have to do two separate steps when I start tracking something new.
5. As a user, I want to start a round on a "how long" question so that I can begin tracking a new batch.
6. As a user, I want to optionally set a guess when starting a round so that I can test my prediction against reality.
7. As a user, I want to record an entry by pressing a single big button so that logging is effortless.
8. As a user, I want the record button to adapt based on question type and round state (end round for "how long" with active round, log usage for "how many", prompt to start round for "how long" with no active round) so that I never have to think about what action to take.
9. As a user, I want the record button to change color depending on the action so that I have ambient awareness of the current state.
10. As a user, I want to add an optional note when recording any entry so that I can capture observations in the moment (e.g. "wouldn't buy this taste again 2/5").
11. As a user, I want to backdate a check-in or check-out by tapping a "today" label that opens a date picker so that I can correct for delayed recording.
12. As a user, I want to see the question page with the current round status, round history, and the primary action button so that I have a single place to manage a question.
13. As a user, I want to see a summary per round showing duration, guess vs actual, and any notes so that I can review individual rounds.
14. As a user, I want to see trends across rounds (guess accuracy over time, average duration or frequency) so that I can observe my consumption patterns and calibration improvement.
15. As a user, I want to void a round with an optional note so that bad data doesn't pollute my trends while I still remember why it was voided.
16. As a user, I want voided rounds to still appear on the timeline (visually distinct) so that I see the full history.
17. As a user, I want to annotate a question with a timestamped note outside of any round so that I can record observations that aren't tied to a specific round (e.g. "store brand is garbage, go back to Nespresso").
18. As a user, I want to update my guess at any time so that my prediction reflects my latest thinking.
19. As a user, I want the system to always compare round outcomes against my latest guess so that correcting a typo flows through naturally.
20. As a user, I want to adjust the start or end date of a round after the fact so that I can correct mistakes.
21. As a user, I want to retire a question so that it no longer appears in my active list but its data is preserved.
22. As a user, I want "how many" rounds to be created lazily based on the declared period so that I never have to manually start or end a period.
23. As a user, I want to create a tag (4-char alphanumeric code) and link it to a question so that I can generate a QR code for physical items.
24. As a user, I want to unlink and relink a tag to a different question so that a physical sticker can survive across questions.
25. As a user, I want scanning a tag QR code (`pace.day/t/{code}`) to show a minimal confirmation page with the question label, round status, optional note field, and a confirm button so that I can record quickly without navigating the full app.
26. As a user, I want `POST pace.day/t/{code}` to record an entry directly so that I can use tags from iOS Shortcuts, scripts, cron jobs, and other automations.
27. As a user, I want the tag confirmation page (GET) to not auto-record anything so that link previews, browser prefetchers, and accidental visits don't create phantom entries.
28. As a user, I want the tag recording surface to show only the question label, current round status, and the record action — no history, no stats, no guess — so that anonymous access exposes minimal data.
29. As a user, I want the question creation flow to use inline text fields within the sentence template so that it feels like completing a sentence rather than filling out a form.
30. As a user, I want the full question page to show a unified timeline with rounds (with status), notes, annotations, and guess changes — newest first — so that I see the complete story of a question.

## Implementation Decisions

### Aggregate boundaries

- **Question** is an aggregate holding: label, thing, unit, amount, questionType, period (for "how many"), guess (optional, updateable). Commands: `AskQuestion`, `AnnotateQuestion`, `UpdateGuess`, `RetireQuestion`.
- **Round** is a separate aggregate holding its own lifecycle. Linked to a question by questionId. Commands: `StartRound`, `EndRound`, `VoidRound`, `AdjustRoundStart`, `AdjustRoundEnd`. Status: active, ended, voided.
- **Tag** is a lightweight entity: a 4-char alphanumeric code linked to an optional questionId. Commands: `CreateTag`, `LinkTag`, `UnlinkTag`.

### Entry recording dispatch

`RecordEntry` is an application-service-level command, not an aggregate command. It reads the question's projection (type, current round state) and dispatches:

- "How long" + active round → `EndRound` on the round aggregate
- "How long" + no active round → redirect to start round form (no recording)
- "How many" → emit `UsageLogged` event (round boundaries are a read-side concern)

### Round lifecycle for "how many"

"How many" questions do not have explicit rounds on the write side. The write side only produces `UsageLogged` events with timestamps. The read side groups entries into period-based lazy rounds derived from the question's declared period (daily, weekly, monthly). No background jobs or cron — rounds are a projection concern.

### Guess semantics

The guess is a single value on the Question aggregate, updateable at any time via `UpdateGuess`. For "how long" it's a duration ("3 weeks"). For "how many" it's a count ("12"). When a round completes, the projection compares the outcome against the current guess — not a historical snapshot. Guess is always optional.

### Event timestamps

All recording commands carry two timestamps: `recordedAt` (when the user tapped — set by the system) and `occurredAt` (when it actually happened — defaults to now, overridable via date picker). Projections use `occurredAt` for duration calculations.

### URL structure

- `/q/{questionId}` — question page (dashboard + primary action)
- `/q/{questionId}/round` — start new round form
- `/t/{code}` — tag recording surface (GET = confirmation page, POST = record)

### Tag security model

Tags are capability tokens. The 4-char alphanumeric code (36⁴ ≈ 1.6M combinations) is effectively unguessable for a personal tool. Rate limiting on the `/t/` endpoint handles brute-force if it ever becomes a concern. Anonymous users via tag can only record entries — no voiding, no round management, no history access.

### Auth

Laravel auth scaffolding with magic link (passwordless) authentication.

### Structured data extraction

Question sentence input is parsed into structured fields (thing, unit, amount, activity, period) using Haiku or equivalent small model. This keeps the input feeling conversational while producing queryable data.

### Question immutability

Once a question is asked, its core identity (thing, unit, amount, type) does not change. A different amount of the same thing is a different question. This enables meaningful comparison: "Do I consume differently from a 30-pack vs a 10-pack?" Questions can be retired but not mutated.

## Testing Decisions

A good test verifies external behavior through the public interface — it sends commands and asserts against events emitted or projection state. It does not test internal aggregate implementation details.

### Modules to test

- **Round aggregate**: the richest state machine. Test lifecycle transitions (active → ended, active → voided), guard violations (ending a round that isn't active, voiding an already-voided round), and date adjustments.
- **Entry recording service**: test the dispatch logic — given a question type and round state, assert the correct downstream command or redirect is produced.
- **Question projections**: test that round summaries, lazy round bucketing for "how many", guess accuracy calculations, and timeline assembly produce correct read models from event streams.
- **Tag resolution**: test code → question resolution, recording via POST, and that anonymous access is scoped to recording only.

### Modules that don't need tests yet

- **Question aggregate**: too thin — just validation on creation and simple field updates.
- **Tag entity**: trivial CRUD.

## Out of Scope

- **"How much" (price tracking / receipt scanning)**: third question type deferred to a future phase.
- **NFC sticker writing**: requires a companion native app. QR codes cover the physical interaction surface for now.
- **Cross-question similarity via embeddings**: interesting for surfacing related questions, but a read-side enhancement that can be added without domain changes.
- **Spaces / households / teams**: organizational grouping. Tags with anonymous recording solve the shared-household use case for now.
- **Progress checkpoints within a round**: the "90 caffeinated capsules" with intermediate counts at 30, 60, 90. Revisit if the pattern recurs.
- **Explicit question derivation / ancestry**: similarity is better derived from embeddings on the read side than enforced as a write-side relationship.
- **Native mobile app**: the mobile-first web app served over HTTPS is the product. QR codes and tags work through the browser.

## Further Notes

- The core insight from prototype data is that guesses are ~2x too optimistic. The guess feedback loop is the emotional hook of the product — every round completion should surface whether you were close or wildly off.
- The sentence template UX ("How long does it take to use [amount] [unit] of [thing]?") is central to the product feel. It should feel like writing, not filling out a form. Big typography, inline editable fields.
- The record button is the single most important UI element. One button, always visible, always does the right thing. Color-coded by action. This is the Paceday equivalent of a Wordle square — simple, satisfying, repeatable.
- The timeline on the question page should be condensed but evenly spaced, newest first. Rounds, annotations, guess changes, and voids all appear on it.
- The tag system (`pace.day/t/{code}`) is deliberately generic — it's a URL that records. QR codes, NFC, Shortcuts, scripts, webhooks — all just hit the same endpoint. The tag is Paceday's API surface.
