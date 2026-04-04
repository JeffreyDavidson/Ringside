# Spec Requirements Document

> Spec: Ringside Design System
> Created: 2025-08-28
> Updated: 2026-04-04
> Status: In Progress

## Overview

Build a custom admin panel design system for Ringside — a wrestling promotion management platform. The design system uses anonymous Blade components organized under a `ui/` namespace, implemented entirely in Tailwind CSS 4 with Heroicons. The visual identity is derived from Ringside's own brand (theringside.app), not from third-party templates.

## Architectural Decisions

### Custom Design System (No Template Dependency)

**Decision**: Ringside has its own visual identity. No Metronic, FluxUI, Tailwind UI, or other template libraries.

**Rationale**:
- Ringside's marketing site establishes a bold, wrestling-themed brand identity
- Third-party templates create conflicting patterns and maintenance overhead
- Building our own system gives full control over the visual language
- Tailwind 4's native features (semantic tokens, CSS-first config) make a custom system straightforward

**Implications**:
- All components are built from scratch using Tailwind 4 utility classes
- Heroicons for all iconography (already installed via blade-heroicons)
- No `kt-` prefixed classes, no Metronic CSS, no KeenIcons
- Livewire + Alpine.js handles all interactivity

### Hybrid Theme: Dark Shell, Light Content

**Decision**: Dark sidebar and header using Ringside brand colors, light content area for data readability.

**Rationale**:
- The sidebar/header is the brand touchpoint — matches theringside.app's dark aesthetic
- Data-heavy content (tables, forms, cards) needs a light background for daily use
- This pattern is proven in professional tools (Linear, Stripe Dashboard, GitHub)

### Semantic Token System

**Decision**: Use shadcn/ui-style CSS custom properties pointing to Tailwind 4's native color palette.

**Rationale**:
- Enables dark mode later by swapping variable values without touching components
- Keeps the color system simple — no hardcoded hex values in components
- Aligns with modern Tailwind 4 best practices

### Incremental Token Discovery

**Decision**: Build design tokens as we build each page element, not upfront.

**Rationale**:
- Avoids speculative token definitions that never get used
- Every token earns its place by being needed in a real component
- Prevents the "100 unused CSS variables" problem

## Brand Identity

### Colors

| Token | Value | Usage |
|-------|-------|-------|
| `--primary` | `#e62222` | Ringside red — primary actions, active states, brand accent |
| `--primary-foreground` | `white` | Text on primary backgrounds |
| `--accent` | `#d4a843` | Gold — premium highlights, special indicators |
| `--shell-bg` | `#0a0a0a` | Near-black — sidebar and header background |
| `--shell-text` | `#f5f5f5` | Off-white — sidebar and header text |
| `--shell-border` | `rgba(230, 34, 34, 0.1)` | Subtle red — shell border accent |

### Semantic Colors (Tailwind native)

| Token | Value | Usage |
|-------|-------|-------|
| `--success` | `green-500` | Positive states, employ, activate |
| `--warning` | `amber-500` | Caution states, suspend, retire |
| `--danger` | `red-600` | Destructive actions, release, delete |
| `--info` | `blue-500` | Informational states |

### Content Area (Tailwind defaults)

| Token | Value | Usage |
|-------|-------|-------|
| `--background` | `white` | Content area background |
| `--foreground` | `zinc-950` | Primary text |
| `--muted` | `zinc-100` | Subtle backgrounds |
| `--muted-foreground` | `zinc-500` | Secondary text |
| `--border` | `zinc-200` | Borders and dividers |
| `--card` | `white` | Card backgrounds |
| `--input` | `zinc-200` | Input borders |
| `--ring` | `zinc-400` | Focus rings |

### Typography

| Font | Usage |
|------|-------|
| **Oswald** | Sidebar brand/logo only |
| **Inter** | Everything else — headings, body, forms, tables, buttons |

### Icons

Heroicons via `blade-ui-kit/blade-heroicons`. Use `<x-heroicon-o-*>` (outline), `<x-heroicon-s-*>` (solid), `<x-heroicon-m-*>` (mini).

## Component Architecture

### Directory Structure

All design system components live under `ui/` with the `index.blade.php` convention:

```
resources/views/components/
├── ui/                              # Design system (domain-agnostic)
│   ├── button/
│   │   └── index.blade.php          # <x-ui.button>
│   ├── badge/
│   │   └── index.blade.php          # <x-ui.badge>
│   ├── card/
│   │   ├── index.blade.php          # <x-ui.card>
│   │   ├── header.blade.php         # <x-ui.card.header>
│   │   ├── body.blade.php           # <x-ui.card.body>
│   │   └── footer.blade.php         # <x-ui.card.footer>
│   ├── modal/
│   │   ├── index.blade.php          # <x-ui.modal>
│   │   ├── header.blade.php
│   │   ├── body.blade.php
│   │   └── footer.blade.php
│   ├── form/
│   │   ├── input.blade.php          # <x-ui.form.input>
│   │   ├── select.blade.php
│   │   ├── textarea.blade.php
│   │   ├── checkbox.blade.php
│   │   ├── label.blade.php
│   │   └── error.blade.php
│   ├── dropdown/
│   │   └── index.blade.php          # <x-ui.dropdown>
│   ├── tabs/
│   │   └── index.blade.php          # <x-ui.tabs>
│   ├── table/
│   │   └── index.blade.php          # <x-ui.table>
│   ├── page/
│   │   ├── header.blade.php
│   │   ├── heading.blade.php
│   │   └── description.blade.php
│   ├── stats/
│   │   └── index.blade.php          # <x-ui.stats>
│   ├── tooltip/
│   │   └── index.blade.php          # <x-ui.tooltip>
│   └── route-link/
│       └── index.blade.php          # <x-ui.route-link>
│
├── layouts/                         # Page shells
│   ├── app.blade.php                # <x-layouts.app>
│   ├── auth.blade.php               # <x-layouts.auth>
│   └── show-page.blade.php          # <x-layouts.show-page>
│
├── sidebar/                         # App navigation
│   ├── index.blade.php
│   ├── menu.blade.php
│   ├── menu-item.blade.php
│   ├── menu-accordion.blade.php
│   └── menu-heading.blade.php
│
├── topbar/                          # Header bar
│   ├── index.blade.php
│   └── profile.blade.php
│
├── wrestlers/                       # Domain components
├── managers/
├── referees/
├── tag-teams/
├── stables/
├── titles/
├── venues/
├── events/
├── matches/
└── users/
```

### Component Rules

1. Every component is a directory with `index.blade.php` (even standalone components)
2. If a component could be used in any Laravel app, it goes in `ui/`
3. If it's specific to Ringside (entity forms, general-info cards), it stays outside `ui/`
4. Styling lives directly in components as Tailwind classes — no CSS utility class layer
5. All components support attribute forwarding via `{{ $attributes }}`
6. Named slots for composition, props for configuration

## Rebuild Strategy

### Approach

- Fresh branch from development
- Delete all existing blade views
- Keep all Livewire PHP classes, models, controllers, actions, policies, tests
- Rebuild views to match existing Livewire class APIs
- Build design tokens incrementally as each component is created

### Build Order

1. **Page shell** — `layouts/app.blade.php`, sidebar, header, footer, containers
2. **Auth pages** — login, register, forgot password (so we can get into the app)
3. **Dashboard** — real dashboard with stats, not a placeholder
4. **Wrestlers** — complete entity flow (index table, show page, form modal, actions) as the template
5. **Remaining entities** — stamp out the wrestlers pattern for all other entities

## Tech Stack

- PHP 8.4 / Laravel 12 / Livewire 3
- Tailwind CSS 4 (CSS-first configuration)
- Alpine.js (included with Livewire)
- Heroicons (blade-heroicons package)
- Inter + Oswald (Google Fonts)
- Pest (testing)

## Out of Scope

- Dark mode (deferred — semantic tokens enable easy addition later)
- Metronic template, KeenIcons, FluxUI, or any third-party UI library
- Livewire PHP class refactoring (views only — refactor PHP classes later)
- Mobile app or PWA
- Animation/motion design system beyond basic transitions

## Spec Documentation

- Tasks: @.agent-os/specs/2025-08-28-design-system/tasks.md
- Technical Specification: @.agent-os/specs/2025-08-28-design-system/sub-specs/technical-spec.md
- Design Tokens: @.agent-os/specs/2025-08-28-design-system/sub-specs/design-tokens.md
- Component Inventory: @.agent-os/specs/2025-08-28-design-system/sub-specs/component-inventory.md
- Page Patterns: @.agent-os/specs/2025-08-28-design-system/sub-specs/page-patterns.md
