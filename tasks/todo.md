# Phases 8-10

## Phase 8: Show Pages
- [x] Reviewed all show page views, general-info components, match components
- [x] Fixed 2 files: replaced `card-flush mb-xl-9` with `border-0 shadow-none mb-6 lg:mb-9` in events/matches-list and events/matches-table

## Phase 9: Auth + Dashboard
- [x] Reviewed all auth pages and dashboard — already clean, no changes needed

## Phase 10: Cleanup

### Delete (verified safe — zero external references):
- [ ] `resources/css/keenicons.css` — remove import from app.js and auth.js first
- [ ] `resources/css/fonts/` — all KeenIcon font files
- [ ] `resources/views/components/menu/` (18 files) — unused menu system
- [ ] `resources/views/components/header/` (4 files) — Metronic mega-menu demo
- [ ] `resources/views/components/docs/` (3 files) — documentation examples
- [ ] `resources/views/components/layout/` (6 files) — duplicate of layouts/
- [ ] `resources/views/blank.blade.php` — empty file
- [ ] `resources/views/layout-test.blade.php` — 35KB test file
- [ ] `resources/views/components/button-tabs.blade.php` — unused
- [ ] `docs/frontend/` — Metronic reference docs
- [ ] `docs/metronic-reference/` — Metronic reference docs

### Keep (actively used):
- `resources/js/auth.js` — used by auth layout, just remove keenicons import
- `resources/js/bootstrap.js` — imported by app.js (axios config)
- `resources/js/app.js` — just remove keenicons import line
