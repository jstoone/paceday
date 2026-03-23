# Corrections — void, adjust, annotate

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

Real life is messy. Users need to fix mistakes, mark bad data, and add context after the fact. This slice adds all the correction commands.

This establishes:

- `VoidRound` command on the Round aggregate — marks a round as voided with an optional note. Emits `RoundVoided { questionId, roundId, voidedAt, note }`
- `AdjustRoundStart` command — changes the occurredAt of a round's start. Emits `RoundStartAdjusted { roundId, oldOccurredAt, newOccurredAt }`
- `AdjustRoundEnd` command — changes the occurredAt of a round's end. Emits `RoundEndAdjusted { roundId, oldOccurredAt, newOccurredAt }`
- `AnnotateQuestion` command on the Question aggregate — adds a timestamped note to the question outside of any round. Emits `QuestionAnnotated { questionId, note, annotatedAt }`
- Voided rounds appear on the timeline visually distinct (faded, struck through) with their void note visible
- Annotations appear on the timeline as their own entries, alongside rounds and guess changes
- Round status now has three states: active, ended, voided

Voided rounds remain in projections (they are not skipped) but carry their status so the frontend can display them distinctly. Trend calculations (slice 5) should exclude voided rounds by default.

## Acceptance criteria

- [ ] User can void an active or ended round with an optional note
- [ ] Round aggregate emits `RoundVoided` and transitions to voided status
- [ ] Voided rounds appear on the timeline visually distinct from ended rounds
- [ ] Void note is visible on the voided round's timeline entry
- [ ] Voiding a round does not delete it — events are preserved
- [ ] User can adjust the start date of a round after the fact
- [ ] User can adjust the end date of a round after the fact
- [ ] Duration recalculates when dates are adjusted
- [ ] User can annotate a question with a timestamped note
- [ ] Annotations appear on the question timeline alongside rounds
- [ ] Annotations can be added whether or not a round is active
- [ ] Trend calculations exclude voided rounds

## Blocked by

- Blocked by #3 (Round history and summaries on the question page)

## User stories addressed

- User story 15: Void a round with an optional note
- User story 16: Voided rounds still appear on the timeline visually distinct
- User story 17: Annotate a question with a timestamped note outside of any round
- User story 20: Adjust the start or end date of a round after the fact
