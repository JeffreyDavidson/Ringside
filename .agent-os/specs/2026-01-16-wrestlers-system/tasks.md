# Wrestlers System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to wrestlers table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_wrestlers_table.php`
- `app/Models/Wrestler.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Wrestler model
- Add `promotion_id` to fillable array
- Update WrestlerFactory to include promotion
- Update all wrestler tests to set promotion context

**Acceptance Criteria:**
- [ ] Migration creates column with proper foreign key
- [ ] Cascade delete when promotion is deleted
- [ ] Model uses BelongsToPromotion trait
- [ ] Global scope filters by current promotion
- [ ] All existing tests pass with promotion context

**Reference:** See @.agent-os/specs/2026-01-16-promotions-system/tasks.md for full multi-tenant implementation details.

---

### Rename `previousMatches` to `completedMatches`

**Priority:** Low
**Type:** Refactor

Align match relationship terminology with EventStatus rename (`Past` → `Completed`). Matches are "completed" events.

| Current | New |
|---------|-----|
| `previousMatches` | `completedMatches` |

**Changes required:**
- Rename relationship method in `IsBookableCompetitor` trait
- Update `WrestlerBuilder` scopes if applicable
- Update all callers and tests

**Note:** This aligns with the Events System terminology change.

---

### Rename `SingleRosterMemberBuilder` to `IndividualRosterMemberBuilder`

**Priority:** Low
**Type:** Refactor

More explicit naming for the shared query builder covering individual roster members.

| Current | New |
|---------|-----|
| `SingleRosterMemberBuilder` | `IndividualRosterMemberBuilder` |

**Changes required:**
- Rename class file
- Update namespace references
- Update all models that extend this builder (Wrestler, Referee, Manager)

---

## Completed

_None yet_
