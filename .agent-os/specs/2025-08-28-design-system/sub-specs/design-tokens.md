# Design Tokens

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md

---

## Overview

Design tokens are the foundational visual design decisions that ensure consistency across the entire application. These tokens are implemented as Tailwind CSS custom properties and utility classes.

---

## Color Palette

### Brand Colors

| Token | Tailwind Class | Usage |
|-------|---------------|-------|
| Primary | `bg-primary`, `text-primary` | Primary actions, links, focus states |
| Secondary | `bg-secondary`, `text-secondary` | Secondary actions, subtle emphasis |
| Accent | `bg-accent`, `text-accent` | Highlights, notifications |

### Semantic Colors

| Token | Tailwind Class | Usage |
|-------|---------------|-------|
| Success | `bg-success`, `text-success` | Positive states, confirmations |
| Warning | `bg-warning`, `text-warning` | Caution states, alerts |
| Danger | `bg-danger`, `text-danger` | Error states, destructive actions |
| Info | `bg-info`, `text-info` | Informational states |

### Neutral Colors

| Token | Usage |
|-------|-------|
| `gray-50` - `gray-950` | Backgrounds, borders, text |
| `white` | Card backgrounds, light mode base |
| `black` | Text, dark mode elements |

### Dark Mode

All color tokens support dark mode via Tailwind's `dark:` variant:
```blade
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
```

---

## Typography

### Font Family

| Token | Class | Usage |
|-------|-------|-------|
| Sans | `font-sans` | Body text, UI elements |
| Mono | `font-mono` | Code, technical data |

### Font Sizes

| Token | Class | Size | Usage |
|-------|-------|------|-------|
| xs | `text-xs` | 12px | Labels, captions |
| sm | `text-sm` | 14px | Secondary text, table cells |
| base | `text-base` | 16px | Body text |
| lg | `text-lg` | 18px | Lead text, subheadings |
| xl | `text-xl` | 20px | Section headings |
| 2xl | `text-2xl` | 24px | Page headings |
| 3xl | `text-3xl` | 30px | Hero headings |

### Font Weights

| Token | Class | Usage |
|-------|-------|-------|
| Normal | `font-normal` | Body text |
| Medium | `font-medium` | Emphasis, labels |
| Semibold | `font-semibold` | Subheadings, buttons |
| Bold | `font-bold` | Headings, strong emphasis |

---

## Spacing

### Spacing Scale

Based on Tailwind's default 4px base unit:

| Token | Class | Value | Usage |
|-------|-------|-------|-------|
| 0 | `p-0`, `m-0` | 0px | Reset |
| 1 | `p-1`, `m-1` | 4px | Tight spacing |
| 2 | `p-2`, `m-2` | 8px | Compact elements |
| 3 | `p-3`, `m-3` | 12px | Default small |
| 4 | `p-4`, `m-4` | 16px | Default medium |
| 5 | `p-5`, `m-5` | 20px | Comfortable |
| 6 | `p-6`, `m-6` | 24px | Section spacing |
| 8 | `p-8`, `m-8` | 32px | Large spacing |
| 10 | `p-10`, `m-10` | 40px | Extra large |
| 12 | `p-12`, `m-12` | 48px | Page sections |

### Component Spacing Guidelines

| Context | Recommended |
|---------|-------------|
| Card padding | `p-4` to `p-6` |
| Form field gap | `gap-4` |
| Section gap | `gap-6` to `gap-8` |
| Page padding | `p-6` to `p-8` |
| Inline element gap | `gap-2` |

---

## Borders

### Border Radius

| Token | Class | Value | Usage |
|-------|-------|-------|-------|
| None | `rounded-none` | 0px | Sharp corners |
| sm | `rounded-sm` | 2px | Subtle rounding |
| Default | `rounded` | 4px | Standard elements |
| md | `rounded-md` | 6px | Buttons, inputs |
| lg | `rounded-lg` | 8px | Cards, modals |
| xl | `rounded-xl` | 12px | Large cards |
| full | `rounded-full` | 9999px | Avatars, pills |

### Border Width

| Token | Class | Usage |
|-------|-------|-------|
| Default | `border` | Standard borders |
| 2 | `border-2` | Emphasis borders |
| 0 | `border-0` | No border |

### Border Colors

| Token | Class | Usage |
|-------|-------|-------|
| Default | `border-gray-200 dark:border-gray-700` | Standard borders |
| Focus | `border-primary` | Focus states |
| Error | `border-danger` | Validation errors |

---

## Shadows

| Token | Class | Usage |
|-------|-------|-------|
| sm | `shadow-sm` | Subtle elevation |
| Default | `shadow` | Cards, dropdowns |
| md | `shadow-md` | Modals, popovers |
| lg | `shadow-lg` | Floating elements |
| xl | `shadow-xl` | High elevation |
| none | `shadow-none` | Flat elements |

---

## Transitions

### Duration

| Token | Class | Usage |
|-------|-------|-------|
| 75 | `duration-75` | Micro interactions |
| 150 | `duration-150` | Default transitions |
| 200 | `duration-200` | Standard animations |
| 300 | `duration-300` | Emphasis animations |

### Easing

| Token | Class | Usage |
|-------|-------|-------|
| Default | `ease-in-out` | Standard transitions |
| In | `ease-in` | Exit animations |
| Out | `ease-out` | Enter animations |

### Common Transition Patterns

```blade
<!-- Button hover -->
<button class="transition-colors duration-150">

<!-- Card hover -->
<div class="transition-shadow duration-200 hover:shadow-lg">

<!-- Dropdown -->
<div x-show="open" x-transition:enter="transition ease-out duration-200">
```

---

## Z-Index

| Token | Class | Usage |
|-------|-------|-------|
| 0 | `z-0` | Base layer |
| 10 | `z-10` | Raised elements |
| 20 | `z-20` | Dropdowns |
| 30 | `z-30` | Fixed headers |
| 40 | `z-40` | Modals backdrop |
| 50 | `z-50` | Modals, dialogs |

---

## Icons

### KeenIcons Integration

Icons use KeenIcons with consistent sizing:

| Size | Class | Usage |
|------|-------|-------|
| Small | `text-sm` | Inline with small text |
| Default | `text-lg` | Standard UI icons |
| Large | `text-xl` | Emphasis, headers |

```blade
<i class="ki-outline ki-search text-lg text-gray-500"></i>
```

---

## Implementation Notes

### Tailwind Config

Design tokens are defined in `tailwind.config.ts` and CSS custom properties in the main stylesheet.

### Usage in Components

Components should use these tokens consistently:

```blade
<!-- Good: Using design tokens -->
<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">

<!-- Avoid: Arbitrary values -->
<div class="bg-[#ffffff] rounded-[8px] p-[24px]">
```

### Extending Tokens

New tokens should be added to Tailwind config and documented here before use in components.
