# "How many" question type

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The second question type. "How many" questions track frequency of an activity over a declared period. The write side is simpler than "how long" — no explicit rounds, just accumulated usage entries. Rounds are a read-side concern, lazily derived from the period.

This establishes:

- The "how many" sentence template: "How many times do I [activity] per [period]?"
- `AskQuestion` extended to support questionType `how_many` with a `period` field (daily, weekly, monthly)
- `UsageLogged` event: emitted when `RecordEntry` is dispatched for a "how many" question — carries `{ questionId, entryId, loggedAt, note }`
- The entry recording service extended with the second dispatch path: "how many" question → emit `UsageLogged`
- The record button for "how many" questions always shows the same state — always ready to log (e.g. "+1" or "Used"), no round toggling
- Lazy round projection: groups `UsageLogged` events by the question's declared period and displays counts per period
- The question page adapted for "how many": shows current period count, period history, and the record button
- Guess support: set via `UpdateGuess` on the question, compared against period counts (e.g. "you guessed 12 per month, you did 17")

## Acceptance criteria

- [ ] User can create a "how many" question via the sentence template
- [ ] Question aggregate accepts questionType `how_many` with a period field
- [ ] Period accepts: daily, weekly, monthly
- [ ] RecordEntry dispatches to `UsageLogged` for "how many" questions
- [ ] `UsageLogged` event carries questionId, entryId, loggedAt, and optional note
- [ ] Record button for "how many" is always in the same state — no round-dependent toggling
- [ ] Question page shows count for the current period
- [ ] Question page shows history of past periods with their counts
- [ ] Lazy rounds are derived from the period — no explicit RoundStarted/RoundEnded events for "how many"
- [ ] Guess (if set) is compared against period counts
- [ ] Optional note supported on usage logging
- [ ] Backdating supported — occurredAt overridable, entry lands in the correct period

## Blocked by

- Blocked by #2 (End a round via the record button)

## User stories addressed

- User story 3: Ask a "how many" question using the sentence template
- User story 8: Record button adapts based on question type
- User story 22: "How many" rounds created lazily based on the declared period
