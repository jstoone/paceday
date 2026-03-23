# End a round via the record button

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The first recording interaction. When a user visits a "how long" question page with an active round and presses the record button, the system ends the round. This is the `RecordEntry` application service dispatching to `EndRound` on the Round aggregate.

This establishes:

- The **entry recording service**: reads the question projection (type, active round state), dispatches to the correct aggregate command
- The **record button** as a context-aware primary action — for "how long" with an active round, it shows as a colored button (coral/red) with text like "Done" or "Finished"
- After ending a round, the button switches state to prompt starting a new round (green/teal, "New batch" or "Start")
- Optional note field on the recording confirmation
- Backdating via a tappable "today" label that opens a date picker
- Both `recordedAt` (system time) and `occurredAt` (user-overridable) on the `RoundEnded` event
- After a successful recording, the user stays on the question page and sees the confirmation

The "no active round" case redirects to `/q/{questionId}/round` — the start round form built in slice 1.

## Acceptance criteria

- [ ] Record button on question page shows "end round" state when an active round exists (distinct color, clear label)
- [ ] Pressing the button ends the active round — Round aggregate emits `RoundEnded` with questionId, roundId, occurredAt, note
- [ ] Record button switches to "start new round" state after ending a round
- [ ] Pressing "start new round" navigates to the start round form at `/q/{questionId}/round`
- [ ] Optional note field is available when recording
- [ ] Date defaults to "today" but is overridable via tappable date picker
- [ ] `RoundEnded` event carries both `recordedAt` and `occurredAt`
- [ ] Entry recording service correctly reads question type and round state before dispatching
- [ ] If no active round exists, the record action redirects to the start round form

## Blocked by

- Blocked by #1 (Ask a "how long" question and start the first round)

## User stories addressed

- User story 7: Record an entry by pressing a single big button
- User story 8: Record button adapts based on question type and round state
- User story 10: Add an optional note when recording
- User story 11: Backdate a check-in or check-out via tappable "today" date picker
