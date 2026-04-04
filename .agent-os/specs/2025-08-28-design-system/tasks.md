# Design System Tasks

> Reference: @.agent-os/specs/2025-08-28-design-system/spec.md
> Updated: 2026-04-04
> Status: Ready for Implementation

## Pre-work

- [ ] Create fresh branch from development
- [ ] Delete all existing blade views (resources/views/)
- [ ] Rewrite resources/css/app.css with new semantic token system
- [ ] Remove console.log debug statements from resources/js/app.js and auth.js
- [ ] Add Oswald font to Google Fonts link
- [ ] Run npm build to verify clean slate

## Phase 1: Shell + Auth

Build the app chrome and authentication pages so we can see and log into the app.

- [ ] 1.1 Create `layouts/app.blade.php` — HTML shell, dark body, sidebar + wrapper structure
- [ ] 1.2 Create `sidebar/index.blade.php` — dark sidebar with brand logo (Oswald), collapse toggle, mobile drawer
- [ ] 1.3 Create `sidebar/menu.blade.php` — Ringside navigation (Dashboard, Roster section, Events, Titles, Venues, Users)
- [ ] 1.4 Create `sidebar/menu-item.blade.php` — nav link with Heroicon, active state
- [ ] 1.5 Create `sidebar/menu-accordion.blade.php` — expandable section (Roster)
- [ ] 1.6 Create `sidebar/menu-heading.blade.php` — section label
- [ ] 1.7 Create `topbar/index.blade.php` — header bar with mobile hamburger
- [ ] 1.8 Create `topbar/profile.blade.php` — user profile dropdown with logout
- [ ] 1.9 Create `layouts/partials/header.blade.php` — fixed header, responds to sidebar state
- [ ] 1.10 Create `layouts/partials/footer.blade.php` — simple copyright footer
- [ ] 1.11 Create `layouts/auth.blade.php` — auth page layout with dark branded panel
- [ ] 1.12 Create auth views — login, register, forgot-password, verify, password reset
- [ ] 1.13 Create `ui/button/index.blade.php` — button component (needed by auth forms)
- [ ] 1.14 Create `ui/form/input.blade.php` — text input (needed by auth forms)
- [ ] 1.15 Create `ui/form/label.blade.php` — form label
- [ ] 1.16 Create `ui/form/error.blade.php` — validation error display
- [ ] 1.17 Create `ui/form/checkbox.blade.php` — checkbox (remember me)
- [ ] 1.18 Create `ui/card/index.blade.php` — card container (used by auth layout)
- [ ] 1.19 Verify: login flow works end to end
- [ ] 1.20 Verify: sidebar navigation works, collapse/expand, mobile drawer

## Phase 2: Dashboard

- [ ] 2.1 Create `ui/page/heading.blade.php` — page title
- [ ] 2.2 Create `ui/page/description.blade.php` — page subtitle
- [ ] 2.3 Create `ui/page/header.blade.php` — page header wrapper
- [ ] 2.4 Create `ui/stats/index.blade.php` — stat card component
- [ ] 2.5 Create dashboard view with real stats (wrestler count, event count, title count, etc.)
- [ ] 2.6 Verify: dashboard renders with stats after login

## Phase 3: Wrestlers (Template Entity)

Build the complete wrestler flow as the pattern for all other entities.

- [ ] 3.1 Create `ui/badge/index.blade.php` — status badges
- [ ] 3.2 Create `ui/modal/index.blade.php` + sub-components — modal dialog
- [ ] 3.3 Create `ui/dropdown/index.blade.php` — context menu for table row actions
- [ ] 3.4 Create `ui/form/select.blade.php` — select dropdown
- [ ] 3.5 Create `ui/form/textarea.blade.php` — multi-line text
- [ ] 3.6 Create remaining `ui/card/` sub-components (header, body, footer, title)
- [ ] 3.7 Create wrestlers index view (table-pre + Livewire table)
- [ ] 3.8 Create wrestlers show view (general-info sidebar + Livewire history tables)
- [ ] 3.9 Create wrestlers form modal view
- [ ] 3.10 Create wrestlers actions view (employ, release, retire, etc.)
- [ ] 3.11 Create table column components (action-column, status-column, etc.)
- [ ] 3.12 Verify: full wrestlers CRUD flow works

## Phase 4: Remaining Entities

Stamp out the wrestlers pattern for all other entities.

- [ ] 4.1 Managers — index, show, form modal, actions
- [ ] 4.2 Referees — index, show, form modal, actions
- [ ] 4.3 Tag Teams — index, show, form modal, actions
- [ ] 4.4 Stables — index, show, form modal, actions
- [ ] 4.5 Titles — index, show, form modal, actions
- [ ] 4.6 Venues — index, show, form modal
- [ ] 4.7 Events — index, show, form modal, matches list
- [ ] 4.8 Matches — form modal (complex dynamic UI)
- [ ] 4.9 Users — index, show, form modal
- [ ] 4.10 Verify: all entity CRUD flows work

## Phase 5: Polish

- [ ] 5.1 Create `ui/tabs/index.blade.php` if needed
- [ ] 5.2 Create `ui/tooltip/index.blade.php` if needed
- [ ] 5.3 Responsive audit — verify all pages work on mobile
- [ ] 5.4 Accessibility audit — ARIA, keyboard navigation, focus management
- [ ] 5.5 Remove any dead code, empty files
- [ ] 5.6 Final visual consistency pass
