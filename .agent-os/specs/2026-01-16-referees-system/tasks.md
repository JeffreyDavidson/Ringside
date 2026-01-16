# Referees System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to referees table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_referees_table.php`
- `app/Models/Referee.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Referee model
- Add `promotion_id` to fillable array
- Update RefereeFactory to include promotion
- Update all referee tests to set promotion context

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
- Rename relationship method in `OfficiatesMatches` trait
- Update all callers and tests

**Note:** This aligns with the Events System, Wrestlers System, and Tag Teams System terminology changes.

---

### Add `upcomingMatches` relationship to OfficiatesMatches trait

**Priority:** Low
**Type:** Enhancement

Add relationship to view scheduled match assignments for referees.

```php
public function upcomingMatches(): BelongsToMany
{
    return $this->matches()->whereHas('event', function ($query) {
        $query->where('date', '>=', now());
    });
}
```

**Changes required:**
- Add method to `OfficiatesMatches` trait
- Add tests for the new relationship

**Note:** Allows viewing referee's upcoming officiating schedule.

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
- Update `RefereeBuilder` extends declaration

**Note:** This is a shared task with Wrestlers System and Managers System. See Wrestlers System tasks.md for full details.

---

## Completed

_None yet_
