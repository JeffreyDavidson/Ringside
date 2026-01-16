# Match System Tasks

## Pending

### Fix incorrect pivot table name in IsBookableCompetitor trait

**Priority:** High
**Type:** Bug Fix

The `IsBookableCompetitor` trait references the wrong pivot table name.

**Current code:**
```php
return $this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors')
```

**Should be:**
```php
return $this->morphToMany(EventMatch::class, 'competitor', 'events_matches_competitors')
```

The migration creates `events_matches_competitors` (with underscores and plurals), but the trait uses `event_match_competitors`. The EventMatch model correctly uses `events_matches_competitors`.

**Changes required:**
- Update table name in `IsBookableCompetitor::matches()` method
- Verify all tests pass after fix

---

### Fix incorrect competitor creation in AddTagTeamsToMatchAction

**Priority:** High
**Type:** Bug Fix

The `AddTagTeamsToMatchAction` uses incorrect column names when creating competitors, inconsistent with the polymorphic pattern used in `AddWrestlersToMatchAction`.

**Current code:**
```php
$eventMatch->competitors()->create([
    'tag_team_id' => $tagTeam->id,
    'side_number' => $sideNumber,
]);
```

**Should be:**
```php
$eventMatch->competitors()->create([
    'competitor_id' => $tagTeam->id,
    'competitor_type' => TagTeam::class,
    'side_number' => $sideNumber,
]);
```

**Changes required:**
- Update `AddTagTeamsToMatchAction::handle()` to use polymorphic columns
- Verify tag team match creation works correctly after fix

---

### Add `upcomingMatches` relationship to IsBookableCompetitor trait

**Priority:** Low
**Type:** Enhancement

Add relationship to view scheduled match assignments for competitors (wrestlers and tag teams).

```php
public function upcomingMatches(): MorphToMany
{
    return $this->matches()->whereHas('event', function ($query) {
        $query->where('date', '>=', now());
    });
}
```

**Changes required:**
- Add method to `IsBookableCompetitor` trait
- Add tests for the new relationship

**Note:** This is a shared task with Referees System (OfficiatesMatches trait). See Referees System tasks.md for the parallel task.

---

### Implement proper competitor side assignment in AddMatchForEventAction

**Priority:** Medium
**Type:** Enhancement

The `transformCompetitorsStructure` method in `AddMatchForEventAction` currently assigns all competitors to side 1. This needs proper implementation based on match type and strategy.

**Current behavior:**
```php
// For now, assume single side (side 1) for all competitors
// This is a simplified transformation - a more complex implementation
// would need to handle side assignment based on match type and strategy
```

**Changes required:**
- Implement side assignment logic based on match type
- Consider match type's `numberOfSides()` for proper distribution
- Handle special cases like BattleRoyal (null sides)

---

### Implement event eligibility validation

**Priority:** Medium
**Type:** Enhancement

The `isEventEligibleForMatches` method in `AddMatchForEventAction` always returns `true`. Should validate event state.

**Current behavior:**
```php
private function isEventEligibleForMatches(Event $event): bool
{
    // Basic checks - event should be scheduled and not completed
    return true;
}
```

**Changes required:**
- Check event status (should be scheduled, not completed)
- Validate event date is in the future
- Consider event capacity limits if applicable

---

### Add competitor double-booking validation

**Priority:** High
**Type:** Enhancement

Currently there's no explicit validation preventing a wrestler or tag team from being booked in multiple matches at the same event.

**Business Rule:** Competitors can only compete in one match per event.

**Changes required:**
- Add validation in `AddWrestlersToMatchAction` to check for existing bookings
- Add validation in `AddTagTeamsToMatchAction` to check for existing bookings
- Consider adding a dedicated validation service/action

---

### Expand MatchType maximum competitors logic

**Priority:** Low
**Type:** Enhancement

The `getMaximumCompetitors()` method currently returns the same value as `getMinimumCompetitors()`.

**Current behavior:**
```php
public function getMaximumCompetitors(): int
{
    // For now, assume same as minimum unless specified otherwise
    return $this->getMinimumCompetitors();
}
```

**Changes required:**
- Define proper maximum competitor counts per match type
- Handle variable competitor counts (e.g., Battle Royal can have many participants)
- Consider adding validation against these limits

---

### Rename relationship methods for terminology consistency

**Priority:** Low
**Type:** Refactor

Align with Events System terminology where `Past` → `Completed`:

| Trait | Current | New |
|-------|---------|-----|
| `IsBookableCompetitor` | `previousMatches` | `completedMatches` |
| `OfficiatesMatches` | `previousMatches` | `completedMatches` |

**Note:** This is a shared task with Wrestlers System and Referees System. See those tasks.md files for the same item.

**Changes required:**
- Rename method in `IsBookableCompetitor` trait
- Rename method in `OfficiatesMatches` trait
- Update all callers and tests

---

## Completed

_None yet_
