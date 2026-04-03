# Phase 7: Form Modals + Action Components

## Plan

### 1. Rebuild action component views (5 files)
All 5 were placeholder stubs — restored proper `@can` gated action buttons matching each Livewire class's methods.

### 2. Replace Bootstrap grid classes in entity form components (3 files)
- `managers/form.blade.php`, `referees/form.blade.php`, `venues/form.blade.php`

### 3. Fix tag-team form modal raw select
- Raw `<select>` for managers → `<x-form.inputs.select>`

## Tasks

- [x] Rebuild wrestlers action view (employ, release, suspend, reinstate, injure, heal, retire, unretire, restore)
- [x] Rebuild managers action view (same actions as wrestlers)
- [x] Rebuild referees action view (same actions as wrestlers)
- [x] Rebuild tag-teams action view (employ, release, suspend, reinstate, retire, unretire, delete, restore)
- [x] Rebuild titles action view (debut, retire, unretire, deactivate, reinstate, restore)
- [x] Replace Bootstrap grid in managers/form, referees/form, venues/form
- [x] Fix tag-team form modal raw select → x-form.inputs.select
- [x] Run tests — pre-existing migration failure only, no new regressions

## Review

### Changes Summary

**Modified files (9):**

Action views (5) — replaced placeholder stubs with proper `@can`-gated buttons:
- `livewire/wrestlers/components/actions.blade.php` — 9 actions
- `livewire/managers/components/actions.blade.php` — 9 actions
- `livewire/referees/components/actions.blade.php` — 9 actions
- `livewire/tag-teams/components/actions.blade.php` — 8 actions (no injure/heal, has delete)
- `livewire/titles/components/actions.blade.php` — 6 actions (debut/retire/unretire/deactivate/reinstate/restore)

Entity forms (3) — Bootstrap `row gx-10` / `col-lg-*` → Tailwind `grid grid-cols-*`:
- `components/managers/form.blade.php`
- `components/referees/form.blade.php`
- `components/venues/form.blade.php`

Form modal (1) — raw HTML select → component:
- `livewire/tag-teams/modals/form-modal.blade.php` — managers field now uses `<x-form.inputs.select>`

### Zero Bootstrap grid classes remaining in blade files
### Zero raw HTML selects remaining where component should be used
