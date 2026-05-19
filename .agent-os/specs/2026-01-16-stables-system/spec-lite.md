# Stables System - Quick Reference

> Wrestling faction/stable management

## Core Entities

| Entity | Purpose |
|--------|---------|
| Stable | Wrestling faction with members |
| StableWrestler | Wrestler membership pivot |
| StableTagTeam | Tag team membership pivot |
| StableActivityPeriod | Establish/disband tracking |
| StableRetirement | Retirement tracking |

## Stable Status (Computed)

| Status | Condition |
|--------|-----------|
| Unformed | No activity periods |
| PendingEstablishment | Future activity scheduled |
| Active | Currently established |
| Inactive | Disbanded (was active) |
| Retired | Permanently retired |

## Key Relationships

```
Stable
├── wrestlers (BelongsToMany → Wrestler)
│   ├── currentWrestlers (left_at IS NULL)
│   └── previousWrestlers (left_at IS NOT NULL)
├── tagTeams (BelongsToMany → TagTeam)
│   ├── currentTagTeams (left_at IS NULL)
│   └── previousTagTeams (left_at IS NOT NULL)
├── activityPeriods (HasMany → StableActivityPeriod)
└── retirements (HasMany → StableRetirement)
```

## Member Counting

```
Total Members = (Tag Teams × 2) + Wrestlers + Managers
Minimum Required = 3
```

## Query Scopes

**Status:** `unestablished()`, `established()`, `disbanded()`, `withFutureEstablishment()`

**Members:** `withMinimumMembers()`, `belowMinimumMembers()`, `withAvailableMembers()`

**Availability:** `available()`, `unavailable()`, `availableForReunion()`

## File Locations

- `app/Models/Stables/Stable.php`
- `app/Models/Stables/StableWrestler.php`
- `app/Models/Stables/StableTagTeam.php`
- `app/Enums/Stables/StableStatus.php`
- `app/Builders/Roster/StableBuilder.php`
