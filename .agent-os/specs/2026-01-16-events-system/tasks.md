# Events System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to events table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_events_table.php`
- `app/Models/Event.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Event model
- Add `promotion_id` to fillable array
- Update EventFactory to include promotion
- Update all event tests to set promotion context

**Acceptance Criteria:**
- [ ] Migration creates column with proper foreign key
- [ ] Cascade delete when promotion is deleted
- [ ] Model uses BelongsToPromotion trait
- [ ] Global scope filters by current promotion
- [ ] All existing tests pass with promotion context

**Note:** Matches inherit promotion context from their parent Event, so no separate promotion_id needed on matches table.

**Reference:** See @.agent-os/specs/2026-01-16-promotions-system/tasks.md for full multi-tenant implementation details.

---

### Consider renaming EventStatus::Past to EventStatus::Completed

**Priority:** Low
**Type:** Refactor (Consideration)

Other systems have tasks to rename `previousMatches` to `completedMatches` for terminology consistency. Consider whether `EventStatus::Past` should follow the same pattern.

| Current | Potential |
|---------|-----------|
| `EventStatus::Past` | `EventStatus::Completed` |

**Pros:**
- Consistent terminology across systems
- "Completed" is more explicit than "Past"

**Cons:**
- "Past" is clear and functional
- Change would require updates to all status checks
- May be over-engineering for consistency's sake

**Decision needed:** Is terminology consistency worth the refactoring effort?

---

### Add validation to prevent scheduling past dates

**Priority:** Low
**Type:** Enhancement

Currently, events can be created or updated with dates in the past. Consider adding validation to prevent scheduling events for past dates.

**Changes required:**
- Add validation in `CreateAction` to reject past dates
- Add validation in `UpdateAction` to reject past dates
- Allow exception for administrative corrections

**Note:** This may already be handled at the form/request level. Verify before implementing.

---

### Rename HoldsEvents::previousEvents to completedEvents

**Priority:** Low
**Type:** Refactor

Align with the `previousMatches` → `completedMatches` rename pattern from other systems.

| Current | New |
|---------|-----|
| `previousEvents` | `completedEvents` |

**Changes required:**
- Rename method in `HoldsEvents` trait
- Update all callers (Venue model, etc.)
- Update tests

**Note:** This aligns with the Wrestlers System, Tag Teams System, Referees System, and Match System terminology changes.

---

## Completed

_None yet_
