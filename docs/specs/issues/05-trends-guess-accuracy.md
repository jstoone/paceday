# Trends and guess accuracy

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

The payoff of tracking over time. The question page shows trends across completed rounds — average duration, consumption rate (amount per day), and guess accuracy. This is where "you're always 2x off" becomes visible.

This establishes:

- **Average duration** across completed rounds on the question page
- **Consumption rate**: calculated from the question's amount/unit and the round duration (e.g. "you use about 2.8 capsules per day")
- **Guess accuracy trend**: across rounds, how close were the guesses? Improving or consistently off?
- A visual representation of the trend — this can be a simple chart, sparkline, or even just a well-formatted list showing the progression

The projection aggregates data from all non-voided completed rounds for the question. Voided rounds (slice 6) should be excludable from trend calculations.

## Acceptance criteria

- [ ] Question page shows average duration across completed rounds
- [ ] Question page shows consumption rate (amount ÷ duration, using the question's unit)
- [ ] Question page shows guess accuracy per round — ratio of guessed vs actual duration
- [ ] A trend visualization shows how guess accuracy or duration changes over rounds
- [ ] Trends only calculate from ended rounds (not active, not voided)
- [ ] Trends handle edge cases: single completed round (show the value, no trend line), no completed rounds (no trend section)
- [ ] Consumption rate uses the question's structured fields (amount, unit) for meaningful display

## Blocked by

- Blocked by #4 (Guess mechanic)

## User stories addressed

- User story 14: See trends across rounds (guess accuracy over time, average duration or frequency)
