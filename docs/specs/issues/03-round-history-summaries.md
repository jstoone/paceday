# Round history and summaries on the question page

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The question page becomes a real dashboard. After completing one or more rounds, the user sees a timeline of everything that has happened with this question — rounds with their summaries, newest first.

This establishes:

- The **question projection**: consumes events from both Question and Round aggregates, produces a unified read model
- **Round summaries**: each completed round shows description context (from the question), start date, end date, duration, and any notes from check-in or check-out
- The **timeline** on the question page: a condensed, evenly-spaced vertical list with newest entries first
- The timeline should include active rounds (in progress), ended rounds, and their associated notes
- Big typography, minimal chrome — the data speaks for itself

No trends, guess accuracy, voided rounds, or annotations yet — those are later slices. This is the raw history.

## Acceptance criteria

- [ ] Question page shows a timeline of rounds, newest first
- [ ] Each ended round shows: start date, end date, calculated duration, and any notes
- [ ] Active round (if any) shows: start date, days elapsed so far
- [ ] Timeline is visually condensed but evenly spaced
- [ ] Projection correctly consumes `RoundStarted` and `RoundEnded` events to build the read model
- [ ] Question page still shows the primary action button (from slice 2) above the timeline
- [ ] Empty state handles gracefully — question with no completed rounds shows only the active round or a prompt to start one

## Blocked by

- Blocked by #2 (End a round via the record button)

## User stories addressed

- User story 12: See the question page with current round status, round history, and primary action button
- User story 13: See a summary per round showing duration and notes
- User story 30: Unified timeline with rounds — newest first
