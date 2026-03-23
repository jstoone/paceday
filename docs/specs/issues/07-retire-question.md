# Retire a question

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

Questions can be retired when the user no longer wants to track them. A retired question is hidden from the active list but its data is fully preserved. This is a soft lifecycle boundary, not a deletion.

This establishes:

- `RetireQuestion` command on the Question aggregate — emits `QuestionRetired { questionId, retiredAt }`
- Retired questions are excluded from the main question list / dashboard
- Retired questions remain accessible via direct URL (`/q/{questionId}`) and show a "retired" indicator
- A way to see retired questions if needed (e.g. a filter or separate section)
- No "unretire" for now — keep it simple

If a question with an active round is retired, the active round should be handled gracefully. Either prevent retiring while a round is active, or void the active round as part of retirement.

## Acceptance criteria

- [ ] User can retire a question from the question page
- [ ] Question aggregate emits `QuestionRetired` with timestamp
- [ ] Retired questions are hidden from the main active question list
- [ ] Retired questions are still accessible via direct URL
- [ ] Retired questions show a clear "retired" visual indicator
- [ ] All historical data (rounds, annotations, guesses) is preserved on retired questions
- [ ] Tags linked to a retired question are handled gracefully (either unlinked or the tag recording surface shows the question is retired)
- [ ] Retiring a question with an active round either prevents retirement or voids the active round

## Blocked by

- Blocked by #1 (Ask a "how long" question and start the first round)

## User stories addressed

- User story 21: Retire a question so it no longer appears in the active list but data is preserved
