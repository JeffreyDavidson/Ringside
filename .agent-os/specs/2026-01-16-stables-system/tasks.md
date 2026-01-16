# Stables System Tasks

## Pending

### Add promotion_id foreign key for multi-tenant support

**Priority:** Critical
**Type:** Migration
**Depends on:** Promotions System

Add `promotion_id` foreign key to stables table for multi-tenant architecture.

**Files:**
- `database/migrations/YYYY_MM_DD_add_promotion_id_to_stables_table.php`
- `app/Models/Stable.php`

**Changes required:**
- Create migration adding `promotion_id` column with foreign key constraint
- Add index on `promotion_id`
- Add `BelongsToPromotion` trait to Stable model
- Add `promotion_id` to fillable array
- Update StableFactory to include promotion
- Update all stable tests to set promotion context

**Acceptance Criteria:**
- [ ] Migration creates column with proper foreign key
- [ ] Cascade delete when promotion is deleted
- [ ] Model uses BelongsToPromotion trait
- [ ] Global scope filters by current promotion
- [ ] All existing tests pass with promotion context

**Reference:** See @.agent-os/specs/2026-01-16-promotions-system/tasks.md for full multi-tenant implementation details.

---

### Refactor member counting to wrestlers only

**Priority:** Medium
**Type:** Refactor

Member counting should be based on wrestlers only. Tag teams are historical annotation and should not count toward the minimum membership requirement.

**Current behavior:**
```
Total = (Tag Teams × 2) + Wrestlers
```

**New behavior:**
```
Total = Wrestlers
```

**Changes required:**
- Update `StableBuilder::withMinimumMembers()` scope to count wrestlers only
- Update `StableBuilder::belowMinimumMembers()` scope
- Update `Stable::getCurrentMemberCount()` method
- Update any related validation logic
- Update tests

**Rationale:**
Tag teams are groupings of wrestlers who are already members. The Outsiders (Nash + Hall) in NWO = 2 wrestlers, not 2 wrestlers + 1 tag team (4). Counting tag teams separately causes double-counting.

---

### Remove direct manager-stable relationship

**Priority:** Medium
**Type:** Refactor

Managers should be associated with stables indirectly through the wrestlers/tag teams they manage, not directly employed by stables.

**Changes required:**
- Remove `stables_managers` table (migration to drop)
- Remove `StableManager` pivot model
- Remove direct manager relationships from Stable model (`managers`, `currentManagers`, `previousManagers`)
- Add computed relationship to get managers via wrestlers/tag teams they manage
- Update member counting to exclude managers
- Update any Livewire components or views that reference direct manager relationships
- Update tests

**Rationale:**
In wrestling, managers manage wrestlers/tag teams, not stables. A manager is "part of" a stable because they manage someone who is in the stable.

---

### Rename StableStatus enum cases for clarity

**Priority:** Low
**Type:** Refactor

Rename enum cases for clearer, more consistent terminology:

| Current | New | Reason |
|---------|-----|--------|
| `Unformed` | `Draft` | Consistency with EventStatus |
| `PendingEstablishment` | `Scheduled` | Shorter, same meaning |
| `Inactive` | `Disbanded` | More specific to stables |

**Changes required:**
- `StableStatus::Unformed` → `StableStatus::Draft`
- `StableStatus::PendingEstablishment` → `StableStatus::Scheduled`
- `StableStatus::Inactive` → `StableStatus::Disbanded`
- Update related scopes: `unestablished()` → `draft()`, `disbanded()` stays
- Update all test references
- Update Livewire table filters

---

### Rename methods to use establishment terminology

**Priority:** Low
**Type:** Refactor

Methods currently use activation terminology but domain uses establishment terminology.

| Current | New | Reason |
|---------|-----|--------|
| `activate()` | `establish()` | Domain terminology |
| `deactivate()` | `disband()` | Domain terminology |
| `scheduleActivation()` | `scheduleEstablishment()` | Domain terminology |

**Changes required:**
- Rename methods in Stable model and related traits
- Update all callers
- Consider keeping old methods as aliases for backward compatibility

---

### Rename StableActivityPeriod to StableActivePeriod

**Priority:** Low
**Type:** Refactor

Rename for consistency with `TitleActivePeriod`.

**Changes required:**
- `StableActivityPeriod` → `StableActivePeriod`
- `activityPeriods` relationship → `activePeriods`
- `currentActivityPeriod` → `currentActivePeriod`
- `futureActivityPeriod` → `futureActivePeriod`
- Update all references in traits, tests, and documentation

---

## Completed

_None yet_
