# Managers System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to managers table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_managers_table.php`
- `app/Models/Manager.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Manager model
- Add `promotion_id` to fillable array
- Update ManagerFactory to include promotion
- Update all manager tests to set promotion context

**Acceptance Criteria:**
- [ ] Migration creates column with proper foreign key
- [ ] Cascade delete when promotion is deleted
- [ ] Model uses BelongsToPromotion trait
- [ ] Global scope filters by current promotion
- [ ] All existing tests pass with promotion context

**Reference:** See @.agent-os/specs/2026-01-16-promotions-system/tasks.md for full multi-tenant implementation details.

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
- Update `ManagerBuilder` extends declaration

**Note:** This is a shared task with Wrestlers System and Referees System. See Wrestlers System tasks.md for full details.

---

## Completed

_None yet_
