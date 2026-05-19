# Tag Teams System - Quick Reference

> Paired wrestling talent management

## Core Entities

| Entity | Purpose |
|--------|---------|
| TagTeam | Paired wrestling talent unit |
| TagTeamWrestler | Wrestler partnership pivot |
| TagTeamEmployment | Employment periods |
| TagTeamSuspension | Suspension tracking |
| TagTeamRetirement | Retirement tracking |
| TagTeamManager | Manager relationship pivot |

## Tag Team Attributes

| Field | Type | Description |
|-------|------|-------------|
| name | string | Team name |
| signature_move | string\|null | Signature finishing move |
| status | EmploymentStatus | **Computed** from employment |
| combinedWeight | int | **Computed** sum of wrestlers' weights |

## Employment Status (Computed)

| Status | Condition |
|--------|-----------|
| Retired | Has active retirement |
| Employed | Has current employment |
| FutureEmployment | Signed but not started |
| Released | Previously employed, now without |
| Unemployed | Never employed |

## Bookability Rules

Tag team is bookable when ALL are true:
- Currently employed (not future)
- NOT suspended
- NOT retired
- Has minimum 2 current wrestlers
- ALL current wrestlers are bookable

> **Key Difference:** Tag teams don't have injuries—bookability depends on individual wrestler availability.

## Key Relationships

```
TagTeam
├── wrestlers (BelongsToMany → Wrestler)
│   ├── currentWrestlers - left_at IS NULL
│   └── previousWrestlers - left_at IS NOT NULL
├── employments (HasMany → TagTeamEmployment)
├── suspensions (HasMany → TagTeamSuspension)
├── retirements (HasMany → TagTeamRetirement)
├── managers (BelongsToMany → Manager)
├── stables (BelongsToMany → Stable)
├── titleChampionships (MorphMany → TitleChampionship)
└── matches (MorphToMany → EventMatch)
```

## Query Scopes

**Employment:** `employed()`, `unemployed()`, `released()`, `futureEmployed()`

**Availability:** `suspended()`, `available()`, `unavailable()`

**Booking:** `bookable()`, `readyForBooking()`, `availableOn($date)`

**Wrestlers:** `withAvailableWrestlers()`, `withMinimumWrestlers($count)`

## File Locations

- `app/Models/TagTeams/TagTeam.php`
- `app/Enums/Shared/EmploymentStatus.php`
- `app/Builders/Roster/TagTeamBuilder.php`
