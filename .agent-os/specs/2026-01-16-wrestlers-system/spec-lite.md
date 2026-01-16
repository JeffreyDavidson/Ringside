# Wrestlers System - Quick Reference

> Individual wrestling talent management

## Core Entity

| Entity | Purpose |
|--------|---------|
| Wrestler | Individual wrestling talent |
| WrestlerEmployment | Employment periods |
| WrestlerInjury | Injury tracking |
| WrestlerSuspension | Suspension tracking |
| WrestlerRetirement | Retirement tracking |
| WrestlerManager | Manager relationship pivot |

## Wrestler Attributes

| Field | Type | Description |
|-------|------|-------------|
| name | string | Display name |
| height | Height | Height value object |
| weight | int | Weight in pounds |
| hometown | string | Hometown |
| signature_move | string\|null | Signature finishing move |
| status | EmploymentStatus | **Computed** from employment |

## Employment Status (Computed)

| Status | Condition |
|--------|-----------|
| Retired | Has active retirement |
| Employed | Has current employment |
| FutureEmployment | Signed but not started |
| Released | Previously employed, now without |
| Unemployed | Never employed |

## Bookability Rules

Wrestler is bookable when ALL are true:
- Currently employed (not future)
- NOT injured
- NOT suspended
- NOT retired

## Key Relationships

```
Wrestler
├── employments (HasMany → WrestlerEmployment)
├── injuries (HasMany → WrestlerInjury)
├── suspensions (HasMany → WrestlerSuspension)
├── retirements (HasMany → WrestlerRetirement)
├── managers (BelongsToMany → Manager)
├── tagTeams (BelongsToMany → TagTeam)
├── stables (BelongsToMany → Stable)
├── titleChampionships (MorphMany → TitleChampionship)
└── matches (MorphToMany → EventMatch)
```

## Query Scopes

**Employment:** `employed()`, `unemployed()`, `released()`, `futureEmployed()`

**Availability:** `injured()`, `suspended()`, `retired()`

**Booking:** `available()`, `unavailable()`, `bookable()`, `availableOn($date)`

## File Locations

- `app/Models/Wrestlers/Wrestler.php`
- `app/Enums/Shared/EmploymentStatus.php`
- `app/Builders/Roster/WrestlerBuilder.php`
