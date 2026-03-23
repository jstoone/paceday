# Structured data extraction via LLM

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The sentence template UX should feel like writing, not filling out a form. Instead of separate input fields for thing, unit, amount, activity, and period, the user types naturally and a small language model extracts the structured fields.

This is a HITL slice — it requires decisions on model choice, prompt design, fallback behavior, and review of extraction accuracy before shipping.

This establishes:

- A service that takes freeform text input and returns structured fields:
  - For "how long": thing, unit, amount
  - For "how many": activity, period
- Integration with Haiku (or equivalent small/fast model) for extraction
- Fallback UX: if extraction fails or returns low-confidence results, the user can manually correct the fields
- The extracted fields are shown to the user for confirmation before the question is created — the LLM suggests, the user approves

### Decisions needed (HITL)

- **Model choice**: Haiku is the default candidate for cost and speed. Is the extraction quality sufficient, or do we need Sonnet?
- **Prompt design**: What prompt reliably extracts thing/unit/amount from sentences like "40 capsules of coffee", "750ml of kitchen cleaner", "8 rolls of toilet paper"?
- **Edge cases**: How does it handle ambiguous input like "a bag of coffee" (amount=1, unit=bag, thing=coffee) vs "coffee" (no amount/unit)?
- **Fallback UX**: Inline correction of extracted fields? Or full manual entry as fallback?
- **Latency budget**: Is the extraction fast enough to feel instant, or does it need a loading state?

## Acceptance criteria

- [ ] User can type a freeform description when creating a question
- [ ] System extracts structured fields (thing, unit, amount or activity, period) from the input
- [ ] Extracted fields are presented to the user for confirmation before question creation
- [ ] User can correct any misextracted field inline
- [ ] Fallback to manual field entry if extraction fails entirely
- [ ] Extraction latency is acceptable for the creation flow (ideally < 1s)
- [ ] Common input patterns extract correctly: "[number] [unit] of [thing]", "[thing] [number][unit]", etc.

## Blocked by

- Blocked by #1 (Ask a "how long" question and start the first round)

## User stories addressed

- User story 29 (partial): Question creation uses inline text fields within the sentence template — the LLM powers the natural input that feeds those fields
