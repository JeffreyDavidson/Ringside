# Page Patterns

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md

---

## Overview

This document defines common page layout patterns used throughout the application. Each pattern combines design system components into reusable page structures.

---

## Page Types

### 1. List Page

Displays a collection of entities with filtering and actions.

**Use Cases:** Wrestlers index, Events list, Titles list

**Structure:**
```
┌─────────────────────────────────────────────────────────────┐
│ Page Header                                                  │
│ ┌─────────────────────────────────┐  ┌───────────────────┐  │
│ │ Title + Breadcrumb              │  │ Primary Action    │  │
│ └─────────────────────────────────┘  └───────────────────┘  │
├─────────────────────────────────────────────────────────────┤
│ Filters Bar                                                  │
│ ┌─────────┐ ┌─────────┐ ┌─────────┐          ┌───────────┐ │
│ │ Search  │ │ Filter  │ │ Filter  │          │ View Mode │ │
│ └─────────┘ └─────────┘ └─────────┘          └───────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Content Area                                                 │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Table / Card Grid                                       │ │
│ │                                                         │ │
│ │ ┌─────┬─────────┬──────────┬──────────┬───────┐       │ │
│ │ │     │ Name    │ Status   │ Details  │ Actions│       │ │
│ │ ├─────┼─────────┼──────────┼──────────┼───────┤       │ │
│ │ │     │         │          │          │       │       │ │
│ │ └─────┴─────────┴──────────┴──────────┴───────┘       │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Pagination                                                   │
│                    ┌─────────────────┐                      │
│                    │ < 1 2 3 ... 10 >│                      │
│                    └─────────────────┘                      │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `layout.page-header`
- `form.input` (search)
- `form.select` (filters)
- `display.table` or `display.card` grid
- `navigation.pagination`
- `interactive.button`
- `interactive.dropdown` (row actions)

---

### 2. Detail Page

Shows comprehensive information about a single entity.

**Use Cases:** Wrestler profile, Event details, Title history

**Structure:**
```
┌─────────────────────────────────────────────────────────────┐
│ Page Header                                                  │
│ ┌─────────────────────────────────┐  ┌───────────────────┐  │
│ │ ← Back   Entity Name            │  │ Edit  │  Actions ▼│  │
│ └─────────────────────────────────┘  └───────────────────┘  │
├─────────────────────────────────────────────────────────────┤
│ ┌──────────────────────┐ ┌────────────────────────────────┐ │
│ │ Profile Card         │ │ Quick Stats                    │ │
│ │ ┌────┐               │ │ ┌──────┐ ┌──────┐ ┌──────┐    │ │
│ │ │    │ Name          │ │ │ Stat │ │ Stat │ │ Stat │    │ │
│ │ │    │ Status Badge  │ │ └──────┘ └──────┘ └──────┘    │ │
│ │ └────┘ Key Info      │ │                                │ │
│ └──────────────────────┘ └────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Tabs                                                         │
│ ┌─────────┬─────────┬─────────┬─────────┐                   │
│ │ Overview│ History │ Matches │ Related │                   │
│ └─────────┴─────────┴─────────┴─────────┘                   │
├─────────────────────────────────────────────────────────────┤
│ Tab Content                                                  │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Content varies by tab                                   │ │
│ │ - Overview: Description list, cards                     │ │
│ │ - History: Timeline                                     │ │
│ │ - Matches: Table                                        │ │
│ │ - Related: Card grid                                    │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `layout.page-header`
- `entity.profile-card`
- `display.stat-group`
- `interactive.tabs`
- `display.description-list`
- `entity.timeline`
- `display.table`

---

### 3. Form Page

Create or edit an entity.

**Use Cases:** Create wrestler, Edit event, Add match

**Structure:**
```
┌─────────────────────────────────────────────────────────────┐
│ Page Header                                                  │
│ ┌─────────────────────────────────┐                         │
│ │ ← Back   Create/Edit Entity     │                         │
│ └─────────────────────────────────┘                         │
├─────────────────────────────────────────────────────────────┤
│ Form                                                         │
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Section: Basic Information                              │ │
│ │ ┌───────────────────────┐ ┌───────────────────────┐    │ │
│ │ │ Label                 │ │ Label                 │    │ │
│ │ │ ┌───────────────────┐ │ │ ┌───────────────────┐ │    │ │
│ │ │ │ Input             │ │ │ │ Input             │ │    │ │
│ │ │ └───────────────────┘ │ │ └───────────────────┘ │    │ │
│ │ │ Hint text             │ │ Error message         │    │ │
│ │ └───────────────────────┘ └───────────────────────┘    │ │
│ ├─────────────────────────────────────────────────────────┤ │
│ │ Section: Additional Details                             │ │
│ │ ...                                                     │ │
│ └─────────────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ Actions                                                      │
│                              ┌──────────┐ ┌──────────────┐  │
│                              │ Cancel   │ │ Save Entity  │  │
│                              └──────────┘ └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `layout.page-header`
- `layout.section`
- `form.group`
- `form.input`, `form.select`, `form.textarea`, etc.
- `form.error`
- `form.hint`
- `interactive.button`

---

### 4. Dashboard Page

Overview with key metrics and recent activity.

**Use Cases:** Main dashboard, Roster overview

**Structure:**
```
┌─────────────────────────────────────────────────────────────┐
│ Page Header                                                  │
│ ┌─────────────────────────────────┐                         │
│ │ Dashboard                       │                         │
│ └─────────────────────────────────┘                         │
├─────────────────────────────────────────────────────────────┤
│ Stats Row                                                    │
│ ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐    │
│ │ Stat Card │ │ Stat Card │ │ Stat Card │ │ Stat Card │    │
│ │   123     │ │    45     │ │    67     │ │    89     │    │
│ │  Label    │ │  Label    │ │  Label    │ │  Label    │    │
│ └───────────┘ └───────────┘ └───────────┘ └───────────┘    │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────┐ ┌─────────────────────────┐ │
│ │ Recent Activity             │ │ Upcoming Events         │ │
│ │ ┌─────────────────────────┐ │ │ ┌─────────────────────┐ │ │
│ │ │ Activity Item           │ │ │ │ Event Card          │ │ │
│ │ │ Activity Item           │ │ │ │ Event Card          │ │ │
│ │ │ Activity Item           │ │ │ │ Event Card          │ │ │
│ │ └─────────────────────────┘ │ │ └─────────────────────┘ │ │
│ └─────────────────────────────┘ └─────────────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Quick Actions or Alerts                                 │ │
│ └─────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `layout.page-header`
- `display.stat-group`
- `display.card`
- `entity.activity-feed`
- `display.list`
- `feedback.alert`

---

### 5. Auth Page

Authentication pages (login, register, forgot password).

**Use Cases:** Login, Register, Password reset

**Structure:**
```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                     ┌───────────────────┐                   │
│                     │ Logo              │                   │
│                     └───────────────────┘                   │
│                                                             │
│                     ┌───────────────────┐                   │
│                     │ Auth Card         │                   │
│                     │                   │                   │
│                     │ ┌───────────────┐ │                   │
│                     │ │ Title         │ │                   │
│                     │ └───────────────┘ │                   │
│                     │ ┌───────────────┐ │                   │
│                     │ │ Email Input   │ │                   │
│                     │ └───────────────┘ │                   │
│                     │ ┌───────────────┐ │                   │
│                     │ │ Password      │ │                   │
│                     │ └───────────────┘ │                   │
│                     │ ┌───────────────┐ │                   │
│                     │ │ Submit Button │ │                   │
│                     │ └───────────────┘ │                   │
│                     │                   │                   │
│                     │ Links (register,  │                   │
│                     │ forgot password)  │                   │
│                     └───────────────────┘                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Components Used:**
- `display.card`
- `form.input`
- `form.checkbox`
- `form.error`
- `interactive.button`

---

## Layout Grid

### Standard Page Grid

```blade
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Full width -->
    <div class="lg:col-span-12">...</div>

    <!-- Two columns -->
    <div class="lg:col-span-6">...</div>
    <div class="lg:col-span-6">...</div>

    <!-- Sidebar + Main -->
    <div class="lg:col-span-4">...</div>
    <div class="lg:col-span-8">...</div>

    <!-- Main + Sidebar -->
    <div class="lg:col-span-8">...</div>
    <div class="lg:col-span-4">...</div>
</div>
```

### Card Grid

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <x-display.card>...</x-display.card>
    <x-display.card>...</x-display.card>
    <x-display.card>...</x-display.card>
</div>
```

---

## Responsive Breakpoints

| Breakpoint | Width | Usage |
|------------|-------|-------|
| Default | < 640px | Mobile, single column |
| `sm` | 640px+ | Large mobile |
| `md` | 768px+ | Tablet, 2 columns |
| `lg` | 1024px+ | Desktop, sidebar visible |
| `xl` | 1280px+ | Wide desktop, more columns |
| `2xl` | 1536px+ | Ultra-wide |

---

## Page Component Template

```blade
<x-layout.main>
    <x-layout.page-header>
        <x-slot:title>Page Title</x-slot:title>
        <x-slot:breadcrumb>
            <x-navigation.breadcrumb :items="$breadcrumbs" />
        </x-slot:breadcrumb>
        <x-slot:actions>
            <x-interactive.button variant="primary">Action</x-interactive.button>
        </x-slot:actions>
    </x-layout.page-header>

    <x-layout.section>
        <!-- Page content -->
    </x-layout.section>
</x-layout.main>
```
