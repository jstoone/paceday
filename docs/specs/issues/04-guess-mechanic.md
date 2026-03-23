# Guess mechanic

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The guess is Paceday's emotional hook. Users predict how long something will last, and the system shows them how wrong they were. The guess lives on the Question aggregate and can be updated at any time.

This establishes:

- `UpdateGuess` command on the Question aggregate, emitting `GuessUpdated { questionId, guess, updatedAt }`
- Optional guess input during the start round flow (from slice 1) — wired to `UpdateGuess` on the question, not stored on the round
- Guess is a duration for "how long" questions (e.g. "3 weeks")
- Guess vs actual comparison on round summaries in the timeline — "you guessed 3 weeks, it lasted 9 days"
- Guess changes appear as entries on the question timeline
- The projection always compares against the latest guess — no historical snapshotting

The guess should be updateable from the question page at any time, not only during round creation.

## Acceptance criteria

- [ ] User can optionally set a guess when starting a round (flows through as `UpdateGuess` on the question)
- [ ] User can update the guess from the question page at any time
- [ ] Question aggregate emits `GuessUpdated` with the new guess value and timestamp
- [ ] Round summaries show guess vs actual duration when a guess exists
- [ ] Guess changes appear on the question timeline as distinct entries
- [ ] The projection uses the latest guess when comparing — updating a guess retroactively changes displayed comparisons
- [ ] Guess is always optional — rounds complete fine without one
- [ ] Guess input accepts duration format (e.g. "3 weeks", "2 months", "10 days")

## Blocked by

- Blocked by #3 (Round history and summaries on the question page)

## User stories addressed

- User story 6: Optionally set a guess when starting a round
- User story 18: Update guess at any time
- User story 19: System compares round outcomes against latest guess
