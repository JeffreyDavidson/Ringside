# Venues System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to venues table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_venues_table.php`
- `app/Models/Venue.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Venue model
- Add `promotion_id` to fillable array
- Update VenueFactory to include promotion
- Update all venue tests to set promotion context

**Acceptance Criteria:**
- [ ] Migration creates column with proper foreign key
- [ ] Cascade delete when promotion is deleted
- [ ] Model uses BelongsToPromotion trait
- [ ] Global scope filters by current promotion
- [ ] All existing tests pass with promotion context

**Future Consideration:** Venues might eventually be shared across promotions (e.g., Madison Square Garden can host events for multiple promotions). This would require:
- Making `promotion_id` nullable
- Adding a `shared` boolean flag
- Creating a `venue_promotion` pivot table for shared venues
- Updating the global scope to include shared venues

For initial implementation, venues are promotion-specific.

**Reference:** See @.agent-os/specs/2026-01-16-promotions-system/tasks.md for full multi-tenant implementation details.

---

### Rename event relationship methods for consistency

**Priority:** Low
**Type:** Refactor

Align terminology with EventStatus rename (`Past` → `Completed`).

| Current | New |
|---------|-----|
| `previousEvents` | `completedEvents` |
| `futureEvents` | Keep as-is (or `upcomingEvents`?) |
| `withPastEvents()` | `withCompletedEvents()` |

**Changes required:**
- Rename methods in `HoldsEvents` trait
- Update `VenueBuilder` scopes
- Update all callers and tests

**Note:** This depends on EventStatus rename being completed first.

---

## Completed

_None yet_
