# Titles System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to titles table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_titles_table.php`
- `app/Models/Title.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Title model
- Add `promotion_id` to fillable array
- Update TitleFactory to include promotion
- Update all title tests to set promotion context

**Acceptance Criteria:**
- [ ] Migration creates column with proper foreign key
- [ ] Cascade delete when promotion is deleted
- [ ] Model uses BelongsToPromotion trait
- [ ] Global scope filters by current promotion
- [ ] All existing tests pass with promotion context

**Reference:** See @.agent-os/specs/2026-01-16-promotions-system/tasks.md for full multi-tenant implementation details.

---

### Rename `previousActivityPeriods` to `completedActivityPeriods`

**Priority:** Low
**Type:** Refactor

Align with the `previousMatches` → `completedMatches` rename pattern from other systems.

| Current | New |
|---------|-----|
| `previousActivityPeriods` | `completedActivityPeriods` |
| `previousActivityPeriod` | `completedActivityPeriod` |

**Changes required:**
- Rename methods in `HasActivityPeriods` trait
- Update all callers (Title model, etc.)
- Update tests

**Note:** This aligns with the Wrestlers, Tag Teams, Referees, and Match System terminology changes.

---

### Consider adding `Retired` to TitleStatus enum

**Priority:** Low
**Type:** Enhancement (Consideration)

Currently, `TitleStatus` has: New, PendingDebut, Active, Inactive. Retirement is tracked separately via the `IsRetirable` trait but not reflected in the status enum.

**Consideration:**
Should `Retired` be added as a fifth status value?

**Pros:**
- Status fully represents all title states
- Simpler status checks in UI/views
- Consistent with other entities that include retirement in status

**Cons:**
- Adds complexity to status computation
- Retirement is conceptually different from activity status
- Current separation works well

**Decision needed:** Is including retirement in status enum worth the complexity?

---

### Add validation for title type and champion entity match

**Priority:** Medium
**Type:** Enhancement

Ensure Singles titles can only be won by Wrestlers and Tag Team titles can only be won by TagTeams.

**Changes required:**
- Add validation in championship assignment actions
- Validate champion type matches title type
- Add appropriate exception handling

---

### Consider renaming `titles_activations` table to `titles_activity_periods`

**Priority:** Low
**Type:** Refactor

The model is `TitleActivityPeriod` but the table is `titles_activations`. Consider aligning table name with model name for consistency.

| Current | Potential |
|---------|-----------|
| `titles_activations` | `titles_activity_periods` |

**Note:** This would require a migration and updates to the model's `$table` property.

---

## Completed

_None yet_
