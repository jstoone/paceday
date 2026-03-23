# Ask a "how long" question and start the first round

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The foundational vertical slice. A user signs up via magic link, creates a "how long" question using the sentence template ("How long does it take to use [amount] [unit] of [thing]?"), and starts their first round — all in one flow.

This establishes:

- Laravel auth scaffolding with magic link authentication
- The **Question** aggregate: accepts `AskQuestion`, emits `QuestionAsked` with label, thing, unit, amount, and questionType
- The **Round** aggregate: accepts `StartRound`, emits `RoundStarted` with questionId, roundId, guess (optional), note (optional), occurredAt
- The question page at `/q/{questionId}` showing the active round status and the primary action button
- The combined creation flow where asking a question and starting the first round feels like one step
- The sentence template UX with inline text fields — big typography, the form *is* the sentence

The question page should show the record button in its "active round" state (colored, showing "Done" or equivalent) but the button doesn't need to function yet — that's slice 2.

## Acceptance criteria

- [ ] User can sign up and log in via magic link (email, no password)
- [ ] User can create a "how long" question via the sentence template
- [ ] Question aggregate emits `QuestionAsked` with structured fields: label, thing, unit, amount, questionType
- [ ] First round is started as part of the creation flow
- [ ] Round aggregate emits `RoundStarted` with questionId, roundId, occurredAt, and optional guess and note
- [ ] Guess and note are optional during round creation
- [ ] Question page at `/q/{questionId}` shows the question label, active round status, and the primary action button
- [ ] The creation flow feels like one step — user does not have to separately create a question then start a round
- [ ] Question is immutable after creation — no command to change thing, unit, amount, or type
- [ ] Event store persists all events (event-sourced write side)

## Blocked by

None — can start immediately

## User stories addressed

- User story 2: Ask a "how long" question using the sentence template
- User story 4: Creating a question and starting the first round feels like one flow
- User story 5: Start a round on a "how long" question
- User story 29: Question creation uses inline text fields within the sentence template
