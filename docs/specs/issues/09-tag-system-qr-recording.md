# Tag system and QR recording surface

## Parent PRD

See PRD: Paceday — Personal consumption tracker

## What to build

Tags are Paceday's universal recording interface. A tag is a 4-character alphanumeric code that resolves to a question. The tag URL (`pace.day/t/{code}`) is a write-only recording surface — it can be printed as a QR code, programmed into an NFC sticker, called from an iOS Shortcut, or hit from a script.

This establishes:

- **Tag entity**: a 4-char alphanumeric code linked to an optional questionId
- `CreateTag` command — generates a unique code, emits `TagCreated { tagCode }`
- `LinkTag` command — associates a tag with a question, emits `TagLinked { tagCode, questionId }`
- `UnlinkTag` command — removes the association, emits `TagUnlinked { tagCode, questionId }`
- Tags can be relinked — unlink from one question, link to another. The physical sticker survives.
- `GET /t/{code}` — the confirmation page for humans. Shows the question label, current round status, optional note field, and a confirm button. Does NOT auto-record. Minimal UI — no history, no stats, no guess.
- `POST /t/{code}` — actually records the entry. Delegates to the entry recording service. Returns a success response. This is the endpoint for scripts and automations.
- QR code generation from the question page — user can create a tag, link it, and generate a printable QR code
- Anonymous access: no authentication required to view the confirmation page or POST a recording via tag

Tag recording follows the same dispatch logic as the record button:
- "How long" + active round → ends the round
- "How long" + no active round → redirects to `/q/{questionId}/round` (requires auth)
- "How many" → logs usage

## Acceptance criteria

- [ ] User can create a tag from the question page
- [ ] Tag code is 4-char alphanumeric, unique
- [ ] User can link a tag to a question
- [ ] User can unlink a tag and relink it to a different question
- [ ] `GET /t/{code}` renders a minimal confirmation page with question label, round status, note field, and confirm button
- [ ] `GET /t/{code}` does not auto-record anything (safe from prefetchers and link previews)
- [ ] `POST /t/{code}` records an entry via the entry recording service
- [ ] `POST /t/{code}` returns a structured response (for programmatic use)
- [ ] Tag recording surface requires no authentication
- [ ] Tag confirmation page shows only: question label, current round status, record action — no history, stats, or guess
- [ ] "How long" with no active round: tag redirects to start round form (which requires auth)
- [ ] QR code can be generated from the question page for a linked tag
- [ ] Rate limiting on the `/t/` endpoint
- [ ] Invalid or unlinked tag codes show a clear error

## Blocked by

- Blocked by #2 (End a round via the record button)

## User stories addressed

- User story 23: Create a tag and link it to a question for QR code generation
- User story 24: Unlink and relink a tag to a different question
- User story 25: Scanning a tag QR shows a minimal confirmation page
- User story 26: POST to tag endpoint records directly for automations
- User story 27: GET does not auto-record
- User story 28: Tag recording surface shows minimal data
