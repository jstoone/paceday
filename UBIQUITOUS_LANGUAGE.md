# Ubiquitous Language

## Questions & tracking

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Question** | An immutable, typed prompt that defines what the user is tracking — includes thing, unit, amount, and question type | Item, tracker, metric |
| **Question type** | The category of a question: either "how long" or "how many" | Tracking mode, category |
| **Thing** | The subject being tracked, stored as a structured field on the question | Item, product, subject |
| **Unit** | The unit of measurement for the thing (e.g. "capsules", "ml", "rolls") | Measure, metric |
| **Amount** | The quantity of units in a question (e.g. 30 capsules, 750ml) | Count, quantity, size |
| **Label** | The full human-readable sentence form of a question | Name, title, description |

## Rounds & entries

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Round** | One cycle of answering a question — for "how long" it's manually started and ended, for "how many" it's a lazy time-based period | Cycle, session, batch, instance |
| **Entry** | A single recorded event against a question, created via the record action | Tap, log, event, data point |
| **Check-in** | The start of a "how long" round — the moment a new batch is opened | Start, begin, open |
| **Check-out** | The end of a "how long" round — the moment a batch is used up | End, finish, close, stop |
| **Usage log** | An entry recorded against a "how many" question — a single occurrence | Tick, tally, count |
| **Period** | The declared time window for a "how many" question (daily, weekly, monthly) that defines lazy round boundaries | Cadence, interval, frequency, timeframe |

## Predictions & observations

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Guess** | The user's prediction, stored on the question and updateable at any time — compared against actual round outcomes | Estimate, prediction, forecast |
| **Note** | Optional freeform text attached to any entry, round start, round end, or void | Comment, remark, memo |
| **Annotation** | A timestamped note on the question itself, not tied to a specific round | Comment, observation, log entry |

## Round lifecycle

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Active** | A round that has been started but not yet ended | Open, in-progress, running |
| **Ended** | A round that has been completed with a check-out | Closed, finished, done |
| **Voided** | A round marked as invalid — still projected but visually distinct and excludable from trends | Deleted, cancelled, removed |

## Interaction surfaces

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **Tag** | A 4-character alphanumeric code that resolves to a question — the universal recording interface for QR codes, NFC, scripts, and Shortcuts | QR code, sticker, link, token |
| **Recording surface** | Any interface scoped to recording entries (the tag URL, the record button) — write-only, not a reading surface | Input, form, endpoint |
| **Question page** | The dashboard for a question at `/q/{id}` — shows current state, round history, trends, and the primary action button | Dashboard, detail page, tracker page |
| **Record button** | The single primary action on the question page — changes color and text based on state | Action button, CTA |

## Commands

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **AskQuestion** | Create a new question with label, thing, unit, amount, and type | CreateItem, AddTracker |
| **StartRound** | Begin a new round on a "how long" question — the check-in | CheckIn, OpenRound, Begin |
| **RecordEntry** | The generic quick-tap command — the system dispatches to the right outcome based on question type and round state | Tap, Log, Track |
| **VoidRound** | Mark a round as invalid with an optional note | DeleteRound, CancelRound |
| **AnnotateQuestion** | Add a timestamped note to a question outside of any round | AddNote, Comment |
| **UpdateGuess** | Change the current guess on a question | ReviseEstimate, SetPrediction |
| **RetireQuestion** | Mark a question as no longer actively tracked | Archive, Delete, Deactivate |

## Tags

| Term | Definition | Aliases to avoid |
|------|-----------|-----------------|
| **CreateTag** | Generate a new 4-char alphanumeric code | GenerateCode, MakeQR |
| **LinkTag** | Associate a tag code with a question | ConnectTag, AssignTag, CoupleTag |
| **UnlinkTag** | Remove the association between a tag and a question | DisconnectTag, DetachTag |

## Relationships

- A **Question** has zero or many **Rounds**
- A **Round** belongs to exactly one **Question**
- A **Round** has zero or many **Entries** (for "how many" with lazy rounds)
- A "how long" **Round** has exactly one **Check-in** and at most one **Check-out**
- A **Question** has zero or one current **Guess**
- A **Question** has zero or many **Annotations**
- A **Tag** is linked to zero or one **Question** (and can be relinked)
- A **Question** can have zero or many **Tags** linked to it

## Aggregate boundaries

- **Question** is an aggregate — holds label, thing, unit, amount, type, period, guess
- **Round** is a separate aggregate — holds its own lifecycle (active/ended/voided), linked to a question by questionId
- **Tag** is a lightweight entity — just a code and a questionId reference

## Example dialogue

> **Dev:** "When a user scans a **Tag**, what happens?"
> **Domain expert:** "We resolve the **Tag** code to a **Question**. If it's a 'how long' **Question** with an **Active Round**, we end the **Round** — that's the **Check-out**. If there's no **Active Round**, we redirect to the **StartRound** form."
> **Dev:** "And for 'how many'?"
> **Domain expert:** "We just **RecordEntry**. The **Round** is lazy — it's derived from the **Period** on the read side. The user never manually starts or ends it."
> **Dev:** "What if someone updates their **Guess** after a **Round** has ended?"
> **Domain expert:** "The **Guess** lives on the **Question**, not the **Round**. We always compare against the latest **Guess**. We're not building an audit trail — if they correct a typo, it should just flow through."
> **Dev:** "Can a **Tag** be moved between **Questions**?"
> **Domain expert:** "Yes. **UnlinkTag** then **LinkTag** to the new **Question**. The physical sticker survives across **Questions** — that's the point."

## Flagged ambiguities

- **"Entry" vs "Round"**: An **Entry** is a single recorded event (a tap, a log). A **Round** is a grouping of entries or a lifecycle (start→end). For "how long" questions, a **Round** contains exactly two implicit entries (check-in and check-out). For "how many" questions, a **Round** is a lazy time bucket that contains many **Entries**. Don't confuse the two — a user "records an entry," they don't "record a round."
- **"Start round" vs "Check-in"**: These are the same action — **StartRound** is the command name, **Check-in** is the domain language for the user-facing concept. Use **Check-in** in UI copy, **StartRound** in code.
- **"Description" was dropped**: Early in the conversation we had a per-round description field (e.g. "30 caffeinated capsules"). This was replaced by making the **Question** itself carry the full context (thing + unit + amount). Rounds no longer have descriptions — the **Question** label says it all.
- **"Snapshot" should not be used**: This is an overloaded term in event sourcing. When referring to the guess that was active when a round ended, say "the guess at the time" or "the current guess" — never "snapshot."
- **"Item" should not be used**: Early conversations used "Item" as the aggregate name. This was replaced by **Question** to match the domain — Paceday answers questions, it doesn't manage items.
