# Phase 5: Form Input Components

## Plan

### Missing components (referenced but don't exist)

1. **`form/inputs/text.blade.php`** (54 references) — thin wrapper: `<x-form.input type="text">`
2. **`form/inputs/date.blade.php`** (16 references) — thin wrapper: `<x-form.input type="date">`
3. **`form/inputs/textarea.blade.php`** (3 references) — proper `<textarea>` with field wrapper, same styling pattern as input
4. **`form/inputs/select.blade.php`** (51 references) — `<select>` with `:options`, `:selected`, `multiple` support
5. **`form/form-label.blade.php`** (4 references in auth password pages) — simple label+for wrapper

### Design approach

- Text and date are thin wrappers that delegate to the existing `form/input.blade.php`
- Textarea and select need their own implementations, following the same patterns (field wrapper, error handling, Metronic styling)
- All components support both Livewire (`wire:model`) and traditional (`name`/`:value`) usage
- Use the same CSS token approach as `form/input.blade.php` for visual consistency

## Tasks

- [x] Create `form/inputs/text.blade.php`
- [x] Create `form/inputs/date.blade.php`
- [x] Create `form/inputs/textarea.blade.php`
- [x] Create `form/inputs/select.blade.php`
- [x] Create `form/form-label.blade.php`
- [x] Run tests — pre-existing migration failure only, no new regressions

## Review

### Changes Summary

**New files (5):**
- `form/inputs/text.blade.php` — thin wrapper delegating to `<x-form.input type="text">` (satisfies 54 references)
- `form/inputs/date.blade.php` — thin wrapper delegating to `<x-form.input type="date">` (satisfies 16 references)
- `form/inputs/textarea.blade.php` — proper `<textarea>` with Metronic-styled classes, field wrapper, label/error support, configurable rows (satisfies 3 references)
- `form/inputs/select.blade.php` — `<select>` with `:options` array rendering, `:selected` state, `multiple` attribute, placeholder support (satisfies 51 references)
- `form/form-label.blade.php` — thin wrapper delegating to `<x-form.label>` with `name`/`label` props (satisfies 4 references)

### Design decisions
- Textarea and select follow the exact same pattern as `form/input.blade.php`: same CSS tokens, same field wrapper, same Livewire/traditional dual support
- Text and date are one-line delegators — no duplication of input logic
- Select handles both associative arrays (`[value => label]`) and `multiple` mode
- All 124 missing component references are now satisfied
