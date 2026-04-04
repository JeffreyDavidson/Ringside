# Page Patterns

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md
> Updated: 2026-04-04

## Overview

Common page layout patterns for the Ringside admin panel. Each pattern combines UI components into reusable page structures. The hybrid theme applies: dark sidebar/header, light content area.

## Page Types

### 1. Index Page (List)

Displays a collection of entities with a Livewire table.

**Use Cases:** Wrestlers, Events, Titles, etc.

```
┌─────────────────────────────────────────────────────────────┐
│ [Dark Sidebar]  │  [Dark Header]                            │
│                 ├───────────────────────────────────────────┤
│  Dashboard      │  Page Header                              │
│  ─────────      │  ┌─────────────────────┐  ┌───────────┐  │
│  Roster ▼       │  │ Title + Stats       │  │ Add Button │  │
│   Wrestlers     │  └─────────────────────┘  └───────────┘  │
│   Tag Teams     ├───────────────────────────────────────────┤
│   Managers      │  Livewire DataTable                       │
│   Referees      │  ┌─────────────────────────────────────┐  │
│   Stables       │  │ Search │ Filters                    │  │
│  ─────────      │  ├────┬────────┬────────┬──────┬───────┤  │
│  Events         │  │    │ Name   │ Status │ Date │ ••• ▼ │  │
│  Titles         │  ├────┼────────┼────────┼──────┼───────┤  │
│  Venues         │  │    │        │        │      │       │  │
│  ─────────      │  └────┴────────┴────────┴──────┴───────┘  │
│  Users          │  Pagination                                │
│                 ├───────────────────────────────────────────┤
│                 │  Footer                                    │
└─────────────────┴───────────────────────────────────────────┘
```

**Components Used:**
- `<x-layouts.app>` → sidebar + header + content + footer
- `<x-ui.page.heading>`, `<x-ui.page.description>`
- `<x-ui.button>` (Add entity)
- Livewire `DataTableComponent` (renders its own table)

### 2. Show Page (Detail)

Comprehensive view of a single entity with sidebar info and tabbed data.

**Use Cases:** Wrestler profile, Event details, Title history

```
┌─────────────────────────────────────────────────────────────┐
│ [Dark Sidebar]  │  [Dark Header]                            │
│                 ├───────────────────────────────────────────┤
│                 │  ┌──────────────┐ ┌─────────────────────┐ │
│                 │  │ General Info │ │ Livewire Tables     │ │
│                 │  │              │ │                     │ │
│                 │  │ Name         │ │ Previous Matches    │ │
│                 │  │ Status ●     │ │ Previous Tag Teams  │ │
│                 │  │ Height       │ │ Previous Managers   │ │
│                 │  │ Weight       │ │ Previous Stables    │ │
│                 │  │ Start Date   │ │                     │ │
│                 │  │              │ │                     │ │
│                 │  │ [Actions]    │ │                     │ │
│                 │  └──────────────┘ └─────────────────────┘ │
│                 ├───────────────────────────────────────────┤
│                 │  Footer                                    │
└─────────────────┴───────────────────────────────────────────┘
```

**Components Used:**
- `<x-layouts.show-page>` (sidebar + content grid)
- `<x-ui.card>` with general-info sub-components
- `<x-ui.badge>` (status)
- `<x-ui.button>` (action buttons)
- Livewire table components for historical data

### 3. Dashboard

Overview with key metrics and quick access.

```
┌─────────────────────────────────────────────────────────────┐
│ [Dark Sidebar]  │  [Dark Header]                            │
│                 ├───────────────────────────────────────────┤
│                 │  Dashboard                                 │
│                 │                                           │
│                 │  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐ │
│                 │  │ Stat  │ │ Stat  │ │ Stat  │ │ Stat  │ │
│                 │  │  42   │ │  12   │ │   8   │ │   3   │ │
│                 │  │Wrestl.│ │Events │ │Titles │ │Venues │ │
│                 │  └───────┘ └───────┘ └───────┘ └───────┘ │
│                 │                                           │
│                 │  ┌─────────────────┐ ┌─────────────────┐  │
│                 │  │ Upcoming Events │ │ Recent Activity  │  │
│                 │  │                 │ │                  │  │
│                 │  │ Event Card      │ │ Activity Item    │  │
│                 │  │ Event Card      │ │ Activity Item    │  │
│                 │  │ Event Card      │ │ Activity Item    │  │
│                 │  └─────────────────┘ └─────────────────┘  │
│                 ├───────────────────────────────────────────┤
│                 │  Footer                                    │
└─────────────────┴───────────────────────────────────────────┘
```

**Components Used:**
- `<x-layouts.app>`
- `<x-ui.stats>`
- `<x-ui.card>`
- `<x-ui.page.heading>`

### 4. Auth Page

Authentication pages with Ringside branding.

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  ┌─────────────────────────┐  ┌─────────────────────────┐  │
│  │                         │  │ [Dark branded panel]    │  │
│  │    ┌───────────────┐    │  │                         │  │
│  │    │  Sign In       │    │  │  Ringside logo         │  │
│  │    │               │    │  │  (Oswald, red accent)   │  │
│  │    │  Email        │    │  │                         │  │
│  │    │  Password     │    │  │  Tagline text           │  │
│  │    │  [Sign In]    │    │  │                         │  │
│  │    │               │    │  │                         │  │
│  │    └───────────────┘    │  │                         │  │
│  │                         │  │                         │  │
│  └─────────────────────────┘  └─────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `<x-layouts.auth>`
- `<x-ui.card>`
- `<x-ui.form.input>`
- `<x-ui.form.checkbox>`
- `<x-ui.button>`

### 5. Form Modal

Create/edit entity via Livewire modal overlay.

```
┌─────────────────────────────────────────────────────────────┐
│                    ┌────────────────────┐                    │
│                    │ Modal Header    ✕  │                    │
│                    ├────────────────────┤                    │
│  [dimmed page]     │                    │                    │
│                    │  Form Fields       │                    │
│                    │  ┌──────────────┐  │                    │
│                    │  │ Name         │  │                    │
│                    │  └──────────────┘  │                    │
│                    │  ┌──────────────┐  │                    │
│                    │  │ Start Date   │  │                    │
│                    │  └──────────────┘  │                    │
│                    │                    │                    │
│                    ├────────────────────┤                    │
│                    │  [Clear] [Save]    │                    │
│                    └────────────────────┘                    │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `<x-ui.modal>`
- `<x-ui.form.*>` components
- `<x-ui.button>`

## Layout Grid

Standard content area uses Tailwind grid utilities:

```blade
{{-- Full width --}}
<div class="grid grid-cols-1 gap-6">

{{-- Two columns --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

{{-- Sidebar + Main (show pages) --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">
    <div class="col-span-1">sidebar</div>
    <div class="col-span-2">content</div>
</div>

{{-- Stats row --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5">
```

## Responsive Breakpoints

Use Tailwind 4 defaults:

| Breakpoint | Width | Layout Behavior |
|------------|-------|-----------------|
| Default | < 1024px | Single column, sidebar hidden (mobile drawer) |
| `lg` | 1024px+ | Sidebar visible, multi-column layouts |
| `xl` | 1280px+ | Container max-width applied |
