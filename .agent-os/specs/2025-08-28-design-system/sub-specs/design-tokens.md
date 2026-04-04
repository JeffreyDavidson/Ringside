# Design Tokens

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md
> Updated: 2026-04-04

## Overview

Design tokens use CSS custom properties in a shadcn/ui-style semantic system. Tokens point to Tailwind 4's native color palette where possible, with custom values only for Ringside brand identity. Tokens are discovered incrementally — only add what a real component needs.

## Token Architecture

```css
:root {
  /* Shell (sidebar, header) — Ringside brand */
  --shell-bg: #0a0a0a;
  --shell-text: #f5f5f5;
  --shell-border: rgba(230, 34, 34, 0.1);

  /* Content area — clean defaults */
  --background: var(--color-white);
  --foreground: var(--color-zinc-950);
  --card: var(--color-white);
  --card-foreground: var(--color-zinc-950);
  --muted: var(--color-zinc-100);
  --muted-foreground: var(--color-zinc-500);
  --accent: var(--color-zinc-100);
  --accent-foreground: var(--color-zinc-900);
  --border: var(--color-zinc-200);
  --input: var(--color-zinc-200);
  --ring: var(--color-zinc-400);

  /* Brand */
  --primary: #e62222;
  --primary-foreground: var(--color-white);
  --accent-brand: #d4a843;

  /* Semantic */
  --success: var(--color-green-500);
  --success-foreground: var(--color-white);
  --warning: var(--color-amber-500);
  --warning-foreground: var(--color-white);
  --danger: var(--color-red-600);
  --danger-foreground: var(--color-white);
  --info: var(--color-blue-500);
  --info-foreground: var(--color-white);

  /* Shared */
  --radius: 0.5rem;
}
```

## Typography

| Font | Weight | Usage |
|------|--------|-------|
| Oswald | 700 | Sidebar logo/brand text only |
| Inter | 400 | Body text, table cells, form values |
| Inter | 500 | Labels, emphasis, secondary headings |
| Inter | 600 | Subheadings, buttons, card titles |
| Inter | 700 | Page headings |

### Font Sizes

Use Tailwind 4 defaults. Custom sizes only if needed:

| Class | Size | Usage |
|-------|------|-------|
| `text-xs` | 12px | Captions, badges |
| `text-sm` | 14px | Secondary text, table cells |
| `text-base` | 16px | Body text |
| `text-lg` | 18px | Subheadings |
| `text-xl` | 20px | Page headings |
| `text-2xl` | 24px | Dashboard headings |

Custom sizes (add to `@theme` only if needed):
| Token | Size | Usage |
|-------|------|-------|
| `text-2sm` | 0.8125rem | Between sm and base — used in Metronic patterns |
| `text-2xs` | 0.6875rem | Between xs and 2xs — compact labels |

## Spacing

Use Tailwind defaults. No custom spacing tokens unless a real component demands it.

## Borders

| Token | Class | Usage |
|-------|-------|-------|
| Radius | `rounded-md` (6px) | Buttons, inputs |
| Radius | `rounded-lg` (8px) | Cards, modals |
| Radius | `rounded-xl` (12px) | Large cards |
| Width | `border` | Standard borders |
| Color | `border-border` | Semantic border color |

## Shadows

Use Tailwind defaults:
| Class | Usage |
|-------|-------|
| `shadow-sm` | Subtle elevation (cards) |
| `shadow` | Default elevation (dropdowns) |
| `shadow-md` | Medium elevation (modals) |
| `shadow-lg` | High elevation (popovers) |

## Icons

Heroicons via `blade-ui-kit/blade-heroicons`:
- Outline: `<x-heroicon-o-{name} class="size-5" />`
- Solid: `<x-heroicon-s-{name} class="size-5" />`
- Mini: `<x-heroicon-m-{name} class="size-4" />`

Standard sizes: `size-4` (inline), `size-5` (default), `size-6` (emphasis)

## Incremental Discovery

Tokens are added to this document and `app.css` only when a real component needs them. Do not pre-define tokens speculatively. If a component needs a new token:

1. Check if a Tailwind default covers it
2. If not, add a CSS custom property to `:root` in `app.css`
3. Document it here with its usage context
