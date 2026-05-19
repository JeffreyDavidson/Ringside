# Design System - Quick Reference

> Custom Ringside admin panel — dark shell, light content, wrestling brand identity

## Key Points
- Custom design system built from scratch — no Metronic, no third-party templates
- Tailwind 4 utility classes directly in Blade components — no CSS utility class layer
- Heroicons for all icons, Oswald for sidebar brand, Inter for everything else
- Semantic CSS tokens (shadcn-style) for colors — enables future dark mode
- Components under `ui/` namespace using `index.blade.php` directory convention
- Dark sidebar/header (#0a0a0a, #e62222 red accent), light content area (white, zinc palette)
- Build tokens incrementally — no speculative definitions
- Rebuild views only — keep all Livewire PHP classes, models, actions, tests as-is

## Brand Colors
- Primary: `#e62222` (Ringside red)
- Accent: `#d4a843` (gold)
- Shell: `#0a0a0a` (near-black)
- Semantic: `green-500` / `amber-500` / `red-600` / `blue-500`

## Component Naming
- `<x-ui.button>` / `<x-ui.card>` / `<x-ui.form.input>` — design system
- `<x-layouts.app>` / `<x-sidebar>` / `<x-topbar>` — app chrome
- `<x-wrestlers.show.general-info>` — domain components

## Build Order
Shell → Auth → Dashboard → Wrestlers (template) → Remaining entities
