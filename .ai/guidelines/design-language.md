# Paceday Design Language

Paceday is a personal consumption and habit tracker. It should feel like a **playful mobile app**, not a SaaS dashboard. The core aesthetic is **playful interactions with warm resting states** — think Duolingo's energy for active moments, cozy journal app for everything else.

## Aesthetic Identity

- **Mood**: Friendly, bubbly, lightweight. Never corporate, never clinical.
- **Palette**: Burnt autumn — rust, amber, warm brown, cream, sand. Blue is the only allowed cool accent, used for status indicators (e.g. "round in progress" badges), never for primary actions.
- **Mode**: Light-first. The gradient background (cream → sand) is the signature. Dark mode exists but is secondary.

## Typography

- **Headings**: `Fredoka` — round, chunky, game-like. This is what makes Paceday feel fun. Use it for all headings, labels, buttons, and any text that should feel playful.
- **Body**: `DM Sans` — clean, readable, slightly warm. Used for paragraphs, descriptions, and secondary text.
- **CSS vars**: `var(--font-heading)` and `var(--font-body)`. These are set in `app.css` and loaded from `fonts.bunny.net` in `partials/head.blade.php`.

## Color Tokens

Named colors are defined as CSS custom properties in `app.css`:

| Token | Hex | Usage |
|---|---|---|
| `--color-rust` | `#c2410c` | Primary actions, CTA buttons, active input accents |
| `--color-rust-dark` | `#9a3412` | Hover states for primary actions |
| `--color-bark` | `#5e4630` | Primary text (headings, strong content) |
| `--color-bark-light` | `#7d5f3e` | Secondary text (descriptions, metadata) |
| `--color-cream` | `#fdf8f3` | Page background (gradient top) |
| `--color-sand` | `#f5ebe0` | Page background (gradient bottom), subtle surface fills |

Flux's zinc scale is remapped to a warm brown/taupe ramp — use `zinc-*` classes and they'll render warm automatically.

## Layout

- **No sidebar**. The app uses a simple **top bar** (Paceday branding left, user avatar right) with content centered below.
- **Content width**: `max-w-lg` (512px). This keeps the app feeling narrow and phone-like even on desktop.
- **Background**: Subtle gradient via the `.paceday-gradient` class on `<body>` — cream at top, sand at bottom.
- Layout lives in `resources/views/layouts/app/sidebar.blade.php` (name is legacy, it's now a top-bar layout).
- Pages using `Route::livewire()` get the layout applied automatically — **do not** wrap page content in `<x-layouts::app>` inside the template, or you'll get double-nesting.

## Surfaces (Cards)

- Use `.paceday-card` — `rounded-3xl`, white background, soft warm shadow. **No borders.**
- Elevation creates depth, not borders. Cards float on the gradient background.
- The shadow uses `shadow-zinc-900/[0.04]` for a barely-there warmth.
- Interactive cards (links) should add `hover:shadow-xl hover:-translate-y-0.5 transition` for a lift effect.

## Buttons

- **Shape**: Pill-shaped (`rounded-full`). This is set globally on all Flux buttons via `app.css`.
- **Primary**: Burnt orange (`bg-rust`), white text, warm shadow. Hover lifts up 1px and darkens. Use for the main action on every screen.
- **States/info**: Blue accent — use for badges and status indicators like "Day 5" or "Round in progress", never for CTAs.
- **Font**: Fredoka (set globally via CSS).

## Form Inputs

Standard Flux inputs (`<flux:input>`, `<flux:textarea>`) are globally styled in `app.css`:
- `rounded-xl`, subtle shadow, warm amber focus ring.
- Labels use Fredoka via `[data-flux-label]`.

## Inline Sentence Inputs

For the "How long does ___ of ___ last?" builder pattern, use raw `<input>` elements with:
- Class: `.sentence-input` inside a `.sentence-builder` container.
- These are invisible inputs — no border except a dashed underline, no background. Text matches the surrounding sentence in font, size, and weight.
- User input renders in rust color to visually distinguish editable parts.
- Focus state: solid underline + faint rust background tint.
- Use Alpine `x-data` with a `resize()` helper to auto-size inputs to their content width (see `questions/create` for the reference implementation).

## Status Badges

Active/in-progress states use a blue badge pattern:
```html
<span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">
    <span class="size-1.5 rounded-full bg-blue-500 animate-pulse"></span>
    Day 5
</span>
```

## Interaction Principles

- **Inputs should feel native to their context.** If an input is part of a sentence, style it as part of that sentence. If it's a standalone field, use Flux's form components. Don't default to generic form layouts when something more integrated is possible.
- **Hover/tap feedback matters.** Primary buttons lift on hover. Cards lift on hover. Interactive elements should always respond to touch.
- **Keep it lightweight.** Prefer subtle transitions (0.2s ease) over heavy animations. The app should feel responsive, not theatrical.
