# Phase 4: Core UI Primitives

## Plan

### 1. Button System (main work)

**Create `components/button.blade.php`** — single canonical button component:
- Props: `variant` (primary/success/danger/warning/info/light/secondary/link), `size` (xs/sm/md/lg), `iconOnly`
- Uses existing CSS utility classes (`btn-primary-default btn-primary-states`, etc.)
- Passes through all attributes (wire:click, @click, type, etc.)

**Create `components/buttons/` wrappers** — backward-compat for the 35+ references:
- `primary.blade.php`, `danger.blade.php`, `success.blade.php`, `warning.blade.php`
- `info.blade.php`, `light.blade.php`, `link.blade.php`
- `delete-selected.blade.php` (special bulk action component)
- Each just delegates to `<x-button variant="X">`

### 2. KeenIcon Cleanup (incidental)
- Modal header close button (`modal/header.blade.php`) — `ki-cross` → Heroicon `x-mark`
- Menu form remove button (`menu/menu-form.blade.php`) — `ki-trash` → Heroicon `trash`
- Form input password toggle (`form/input.blade.php`) — `ki-eye`/`ki-eye-slash` → Heroicons

### 3. Remove Dead UI Components
- Remove `ui/button.blade.php` (KeenIcon-based, replaced by new button)
- Remove `ui/icon.blade.php` (KeenIcon wrapper, no longer needed)
- Update 5 auth pages from `<x-ui.button>` → `<x-button>`

### 4. Card Cleanup
- Remove `card/index.blade.php` (dead code — `card.blade.php` takes precedence)
- Remove `card/footer-borderless.blade.php` (unused anywhere)
- Remove `card/toolbar/` directory (unused anywhere)
- **Keep**: `card.blade.php`, `card/header.blade.php`, `card/body.blade.php`, `card/footer.blade.php`, `card/title.blade.php`, `card/general-info/`

### 5. Components That Are Already Good (no changes needed)
- **Badge** (`badge.blade.php`) — 6 colors, 2 sizes, clean implementation
- **Route link** (`route-link.blade.php`) — simple, works
- **Application logo** (`application-logo.blade.php`) — SVG, fine
- **Page components** (`page/header.blade.php`, `page/heading.blade.php`, `page/description.blade.php`) — clean

## Tasks

- [x] Create `button.blade.php` — canonical button with variant/size props
- [x] Create `buttons/` wrappers (primary, danger, success, warning, info, light, link, delete-selected)
- [x] Update modal header close — KeenIcon → Heroicon
- [x] Update menu-form — KeenIcon → Heroicon
- [x] Update auth pages from `x-ui.button` → `x-button`
- [x] Replace `x-ui.icon` in form/input.blade.php with Heroicons
- [x] Remove dead `ui/button.blade.php` and `ui/icon.blade.php`
- [x] Clean up dead card files (card/index, footer-borderless, toolbar)
- [x] Replace remaining KeenIcons in table action columns, menu, and header mega-menu components
- [x] Run tests — pre-existing migration failure only, no new regressions

## Review

### Changes Summary

**New files (10):**
- `components/button.blade.php` — canonical button with variant/size/iconOnly props, uses CSS utility classes
- `components/buttons/primary.blade.php` — wrapper → `<x-button variant="primary">`
- `components/buttons/danger.blade.php` — wrapper → `<x-button variant="danger">`
- `components/buttons/success.blade.php` — wrapper → `<x-button variant="success">`
- `components/buttons/warning.blade.php` — wrapper → `<x-button variant="warning">`
- `components/buttons/info.blade.php` — wrapper → `<x-button variant="info">`
- `components/buttons/light.blade.php` — wrapper → `<x-button variant="light">`
- `components/buttons/link.blade.php` — wrapper → `<x-button variant="link">`
- `components/buttons/delete-selected.blade.php` — bulk delete action component

**Modified files (12):**
- `modal/header.blade.php` — `ki-cross` → `heroicon-m-x-mark`
- `menu/menu-form.blade.php` — `ki-trash` → `heroicon-m-trash`
- `form/input.blade.php` — `x-ui.icon eye/eye-slash` → `heroicon-s-eye/eye-slash`
- `auth/login.blade.php` — `x-ui.button` → `x-button`
- `auth/forgot-password.blade.php` — `x-ui.button` → `x-button`
- `auth/register.blade.php` — `x-ui.button` → `x-button`
- `auth/passwords/reset.blade.php` — `x-ui.button` → `x-button`
- `auth/passwords/email.blade.php` — `x-ui.button` → `x-button`
- `tables/columns/action-column.blade.php` — all KeenIcons → Heroicons
- `tables/columns/wrestler-actions.blade.php` — all KeenIcons → Heroicons
- `menu/menu-toggle.blade.php`, `menu-icon.blade.php`, `menu-arrow.blade.php` — KeenIcons → Heroicons
- `header/mega-menu-dropdown.blade.php`, `mega-menu-dropdown-item.blade.php`, `mega-menu-dropdown-submenu.blade.php` — KeenIcons → Heroicons

**Deleted files (5):**
- `ui/button.blade.php` — replaced by canonical `button.blade.php`
- `ui/icon.blade.php` — KeenIcon wrapper, no longer needed
- `card/index.blade.php` — dead code (overridden by `card.blade.php`)
- `card/footer-borderless.blade.php` — unused
- `card/toolbar/` directory — unused (index.blade.php, actions.blade.php)

### Zero KeenIcon references remaining in blade files
### Pre-existing test failure (MatchType migration) — unrelated to this work
