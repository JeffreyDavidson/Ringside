# Tag Teams System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to tag_teams table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_tag_teams_table.php`
- `app/Models/TagTeam.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to TagTeam model
- Add `promotion_id` to fillable array
- Update TagTeamFactory to include promotion
- Update all tag team tests to set promotion context

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
- Rename relationship method in `IsBookableCompetitor` trait (shared with Wrestlers)
- Update `TagTeamBuilder` scopes if applicable
- Update all callers and tests

**Note:** This aligns with the Events System and Wrestlers System terminology changes.

---

### Improve bookability consistency between model and builder

**Priority:** Medium
**Type:** Enhancement

The model's `isBookable()` method and the builder's `bookable()` scope check different things:

| Component | Checks |
|-----------|--------|
| Model `isBookable()` | All current wrestlers are bookable |
| Builder `bookable()` | Team available + minimum wrestlers |

For complete bookability, both conditions must be true. Options to improve:

**Option A:** Update model `isBookable()` to also check team availability
```php
public function isBookable(): bool
{
    return $this->isEmployed() &&
           ! $this->isSuspended() &&
           ! $this->isRetired() &&
           $this->currentWrestlers->count() >= self::NUMBER_OF_WRESTLERS_ON_TEAM &&
           $this->currentWrestlers->every(fn ($wrestler) => $wrestler->isBookable());
}
```

**Option B:** Update builder `bookable()` to include `withAvailableWrestlers()`
```php
public function bookable(): static
{
    return $this->available()
                ->withMinimumWrestlers()
                ->withAvailableWrestlers();
}
```

**Option C:** Keep separate (document as intentional separation of concerns)

**Recommendation:** Option A - model should be the source of truth for bookability.

---

### Add `retired()` scope to TagTeamBuilder

**Priority:** Low
**Type:** Enhancement

Add a `retired()` scope for consistency with other availability scopes. Should filter tag teams that are retired at the team level.

```php
public function retired(): static
{
    return $this->whereHas('retirements', fn ($q) => $q->whereNull('ended_at'));
}
```

**Note:** This is team-level retirement only. Teams with individually retired wrestlers are handled by bookability checks.

---

## Completed

_None yet_
