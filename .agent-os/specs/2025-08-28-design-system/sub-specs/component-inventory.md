# Component Inventory

> Design System
> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md
> Updated: 2026-04-04

## Naming Convention

All UI components use the `ui/` namespace with directory + `index.blade.php` pattern:
- `<x-ui.button>` resolves to `components/ui/button/index.blade.php`
- `<x-ui.card.header>` resolves to `components/ui/card/header.blade.php`

## UI Components (`ui/`)

### Button (`ui/button/index.blade.php`)
**Tag**: `<x-ui.button>`
**Purpose**: Action buttons with multiple variants and states
**Props**: `variant` (primary, secondary, danger, warning, success, ghost, link), `size` (sm, md, lg), `iconOnly`, `tag` (button, a)
**Slots**: Default (label text)
**Styling**: Tailwind classes directly — no CSS utility layer

### Badge (`ui/badge/index.blade.php`)
**Tag**: `<x-ui.badge>`
**Purpose**: Status and category indicators
**Props**: `variant` (default, primary, success, danger, warning, info), `size` (sm, md)
**Slots**: Default (label text)

### Card (`ui/card/`)
**Tag**: `<x-ui.card>`
**Purpose**: Content containers
**Sub-components**: `header`, `body`, `footer`, `title`
**Props**: `class` for overrides
**Slots**: Default, named slots for sub-components

### Modal (`ui/modal/`)
**Tag**: `<x-ui.modal>`
**Purpose**: Dialog windows for forms and confirmations
**Sub-components**: `header`, `body`, `footer`
**Props**: `size` (sm, md, lg)

### Form Components (`ui/form/`)
**Components**:
- `<x-ui.form.input>` — text, email, password, number inputs
- `<x-ui.form.select>` — dropdown with options, multiple support
- `<x-ui.form.textarea>` — multi-line text
- `<x-ui.form.checkbox>` — checkbox with label
- `<x-ui.form.label>` — form label
- `<x-ui.form.error>` — validation error display

**Common Props**: `name`, `label`, `wire:model` support, attribute forwarding

### Dropdown (`ui/dropdown/index.blade.php`)
**Tag**: `<x-ui.dropdown>`
**Purpose**: Dropdown menus and context menus
**Props**: `position`, `width`
**Slots**: `trigger`, default (menu content)

### Tabs (`ui/tabs/index.blade.php`)
**Tag**: `<x-ui.tabs>`
**Purpose**: Tabbed content organization
**Props**: `defaultTab`
**Slots**: Tab nav items, tab panels

### Table (`ui/table/index.blade.php`)
**Tag**: `<x-ui.table>`
**Purpose**: Data table styling (works with Livewire DataTableComponent)
**Sub-components**: As needed — headers, rows, pagination

### Page Header Components (`ui/page/`)
**Components**:
- `<x-ui.page.header>` — page header wrapper
- `<x-ui.page.heading>` — h1 page title
- `<x-ui.page.description>` — subtitle/description text

### Stats (`ui/stats/index.blade.php`)
**Tag**: `<x-ui.stats>`
**Purpose**: Numerical stat display with label and optional trend
**Props**: `value`, `label`, `trend`, `icon`

### Tooltip (`ui/tooltip/index.blade.php`)
**Tag**: `<x-ui.tooltip>`
**Purpose**: Hover/focus contextual information
**Props**: `content`, `position`

### Route Link (`ui/route-link/index.blade.php`)
**Tag**: `<x-ui.route-link>`
**Purpose**: Styled navigation links
**Props**: `route`, `label`

## App Chrome Components

### Sidebar (`sidebar/`)
- `<x-sidebar>` — desktop sidebar + mobile drawer
- `<x-sidebar.menu>` — navigation menu with Ringside routes
- `<x-sidebar.menu-item>` — single nav link with icon
- `<x-sidebar.menu-accordion>` — expandable nav section
- `<x-sidebar.menu-heading>` — section label

### Topbar (`topbar/`)
- `<x-topbar>` — header bar content
- `<x-topbar.profile>` — user profile dropdown

### Layouts (`layouts/`)
- `<x-layouts.app>` — main authenticated layout (sidebar + header + content + footer)
- `<x-layouts.auth>` — authentication page layout
- `<x-layouts.show-page>` — detail page with sidebar + content grid

## Domain Components

Each entity has its own directory outside `ui/`:

```
components/{entity}/
├── index/
│   └── table-pre.blade.php      # Table header with "Add" button
├── show/
│   └── general-info.blade.php   # Show page sidebar card
└── form.blade.php               # Traditional form (if applicable)
```

Entities: wrestlers, managers, referees, tag-teams, stables, titles, venues, events, matches, users

## Implementation Priority

### Phase 1: Shell + Auth
- `layouts/app.blade.php`, `layouts/auth.blade.php`
- `sidebar/` components
- `topbar/` components
- `ui/button/`, `ui/form/input`, `ui/form/label`, `ui/form/error`
- `ui/card/`

### Phase 2: Dashboard + Core Display
- `ui/stats/`
- `ui/page/` header components
- Dashboard content

### Phase 3: Entity Template (Wrestlers)
- `ui/table/` (works with existing DataTableComponent)
- `ui/badge/`
- `ui/modal/`
- `ui/dropdown/`
- `ui/form/select`, `ui/form/textarea`, `ui/form/checkbox`
- Wrestler domain components

### Phase 4: Remaining Entities
- Stamp out the wrestlers pattern for all other entities

### Phase 5: Polish
- `ui/tabs/`
- `ui/tooltip/`
- Responsive refinement
- Accessibility audit
