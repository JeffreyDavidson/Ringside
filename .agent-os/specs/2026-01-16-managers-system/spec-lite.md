# Managers System - Quick Reference

> Talent manager roster management (NOT bookable)

## Core Entities

| Entity | Purpose |
|--------|---------|
| Manager | Talent representative |
| ManagerEmployment | Employment periods |
| ManagerInjury | Injury tracking |
| ManagerSuspension | Suspension tracking |
| ManagerRetirement | Retirement tracking |

## Manager Attributes

| Field | Type | Description |
|-------|------|-------------|
| first_name | string | First name |
| last_name | string | Last name |
| displayName | string | **Computed** "FirstName LastName" |
| status | EmploymentStatus | **Computed** from employment |

## Employment Status (Computed)

| Status | Condition |
|--------|-----------|
| Retired | Has active retirement |
| Employed | Has current employment |
| FutureEmployment | Signed but not started |
| Released | Previously employed, now without |
| Unemployed | Never employed |

## Key Distinction: NOT Bookable

Managers **cannot** be booked for matches. They manage wrestlers and tag teams but do not compete. No `isBookable()` method or booking scopes exist.

## Key Relationships

```
Manager
├── employments (HasMany → ManagerEmployment)
├── injuries (HasMany → ManagerInjury)
├── suspensions (HasMany → ManagerSuspension)
├── retirements (HasMany → ManagerRetirement)
├── wrestlers (BelongsToMany → Wrestler)
│   ├── currentWrestlers - fired_at IS NULL
│   └── previousWrestlers - fired_at IS NOT NULL
└── tagTeams (BelongsToMany → TagTeam)
    ├── currentTagTeams - fired_at IS NULL
    └── previousTagTeams - fired_at IS NOT NULL
```

## Client Assignment Rules

Both manager AND client must be:
- Currently employed, OR
- Have future employment scheduled

This allows managers and clients to debut together.

## Stable Association

Managers are **indirectly** associated with stables through wrestlers/tag teams they manage. See Stables System spec.

## Query Scopes

**Employment:** `employed()`, `unemployed()`, `released()`, `futureEmployed()`

**Availability:** `injured()`, `suspended()`, `retired()`, `available()`, `unavailable()`

**No booking scopes** (managers are not bookable)

## File Locations

- `app/Models/Managers/Manager.php`
- `app/Enums/Shared/EmploymentStatus.php`
- `app/Builders/Roster/ManagerBuilder.php`
