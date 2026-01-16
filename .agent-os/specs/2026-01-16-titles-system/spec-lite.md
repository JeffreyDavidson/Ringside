# Titles System - Quick Reference

> Championship management with activity lifecycle and reign tracking

## Core Entities

| Entity | Purpose |
|--------|---------|
| Title | Championship belt entity |
| TitleChampionship | Championship reign record |
| TitleActivityPeriod | Active/inactive period tracking |
| TitleRetirement | Retirement period tracking |
| TitleStatusChange | Status change history |

## Title Attributes

| Field | Type | Description |
|-------|------|-------------|
| name | string | Title name |
| type | TitleType | Singles or TagTeam |
| status | TitleStatus | **Computed** from activity periods |

## Championship Attributes

| Field | Type | Description |
|-------|------|-------------|
| title_id | int | Foreign key to title |
| champion_type | string | Polymorphic type (Wrestler/TagTeam) |
| champion_id | int | Polymorphic ID |
| won_at | datetime | Reign start date |
| lost_at | datetime\|null | Reign end date (null = current) |
| won_match_id | int\|null | Match where title was won |
| lost_match_id | int\|null | Match where title was lost |

## Title Status (Computed)

| Status | Condition |
|--------|-----------|
| New | No activity periods (never debuted) |
| PendingDebut | Future activity period scheduled |
| Active | Current activity period exists |
| Inactive | Previous activity but no current |

## Title Type

| Type | Champion Entity |
|------|-----------------|
| Singles | Wrestler |
| TagTeam | TagTeam |

## Key Relationships

```
Title
├── activityPeriods (HasMany → TitleActivityPeriod)
│   ├── currentActivityPeriod - ended_at IS NULL AND started_at <= now
│   ├── futureActivityPeriod - ended_at IS NULL AND started_at > now
│   └── previousActivityPeriods - ended_at IS NOT NULL
├── championships (HasMany → TitleChampionship)
│   ├── currentChampionship - lost_at IS NULL
│   └── previous - lost_at IS NOT NULL
├── retirements (HasMany → TitleRetirement)
│   └── currentRetirement - ended_at IS NULL
└── statusChanges (HasMany → TitleStatusChange)

TitleChampionship
├── title (BelongsTo → Title)
├── champion (MorphTo → Wrestler|TagTeam)
├── wonEventMatch (BelongsTo → EventMatch)
└── lostEventMatch (BelongsTo → EventMatch)
```

## Query Scopes

**Title:**
- `active()` - Currently active
- `inactive()` - Previously active but not now
- `neverDebuted()` - No activity periods
- `withPendingDebut()` - Future debut scheduled
- `retired()` - Currently retired
- `unretired()` - Not retired
- `competable()` - Active and available
- `vacant()` - Active but no current champion
- `defended()` - Has championship history
- `newTitles()` - No championship history

**TitleChampionship:**
- `current()` - Active reigns (lost_at IS NULL)
- `previous()` - Ended reigns (lost_at IS NOT NULL)
- `latestWon()` - Ordered by won_at DESC
- `latestLost()` - Ordered by lost_at DESC
- `withReignLength()` - Include computed reign length

## Title Lifecycle Actions

| Action | Purpose |
|--------|---------|
| CreateAction | Create new title |
| DebutAction | First activation (new title) |
| PullAction | Temporarily deactivate |
| ReinstateAction | Reactivate pulled title |
| RetireAction | Permanently retire |
| UnretireAction | Bring back retired title |
| ActivateAction | Smart activate (debut/reinstate/unretire) |
| DeactivateAction | Alias for PullAction |

## File Locations

- `app/Models/Titles/Title.php`
- `app/Models/Titles/TitleChampionship.php`
- `app/Models/Titles/TitleActivityPeriod.php`
- `app/Models/Titles/TitleRetirement.php`
- `app/Models/Titles/TitleStatusChange.php`
- `app/Enums/Titles/TitleStatus.php`
- `app/Enums/Titles/TitleType.php`
- `app/Builders/Titles/TitleBuilder.php`
- `app/Builders/Titles/TitleChampionshipBuilder.php`
