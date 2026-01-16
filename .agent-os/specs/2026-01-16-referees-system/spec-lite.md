# Referees System - Quick Reference

> Match officials roster management (bookable for officiating)

## Core Entities

| Entity | Purpose |
|--------|---------|
| Referee | Match official |
| RefereeEmployment | Employment periods |
| RefereeInjury | Injury tracking |
| RefereeSuspension | Suspension tracking |
| RefereeRetirement | Retirement tracking |

## Referee Attributes

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

## Bookability Rules

Referee is bookable (for officiating) when ALL are true:
- Currently employed (not future)
- NOT injured
- NOT suspended
- NOT retired

## Key Relationships

```
Referee
├── employments (HasMany → RefereeEmployment)
├── injuries (HasMany → RefereeInjury)
├── suspensions (HasMany → RefereeSuspension)
├── retirements (HasMany → RefereeRetirement)
└── matches (BelongsToMany → EventMatch)
    └── previousMatches - event date < now
```

## Key Distinction: Officials vs Competitors

| Aspect | Referee | Wrestler |
|--------|---------|----------|
| Role | Officiates matches | Competes in matches |
| Interface | `BookableOfficial` | `BookableCompetitor` |
| Trait | `OfficiatesMatches` | `IsBookableCompetitor` |
| Pivot Table | `events_matches_referees` | Polymorphic |

## Query Scopes

**Employment:** `employed()`, `unemployed()`, `released()`, `futureEmployed()`

**Availability:** `injured()`, `suspended()`, `retired()`, `available()`, `unavailable()`

**Booking:** `bookable()` (alias for `available()`)

## File Locations

- `app/Models/Referees/Referee.php`
- `app/Enums/Shared/EmploymentStatus.php`
- `app/Builders/Roster/RefereeBuilder.php`
