# Match System - Quick Reference

> Core competitive unit connecting competitors, officials, titles, and results

## Core Entities

| Entity | Purpose |
|--------|---------|
| EventMatch | Core match record within an event |
| MatchCompetitor | Polymorphic pivot linking competitors to matches |
| MatchStipulation | Special match rules (Steel Cage, Ladder, etc.) |
| MatchResult | Outcome of a match with decision type |
| MatchWinner | Individual winner record |
| MatchLoser | Individual loser record |

## EventMatch Attributes

| Field | Type | Description |
|-------|------|-------------|
| event_id | int | Parent event |
| match_number | int | Order in event card |
| match_type | MatchType | Type enum (Singles, TagTeam, etc.) |
| match_stipulation_id | int\|null | Optional special rules |
| preview | string\|null | Promotional text |

## Match Types (Enum)

| Type | Sides | Competitor Types |
|------|-------|------------------|
| Singles | 2 | Wrestler only |
| TagTeam | 2 | Wrestler + TagTeam |
| TripleThreat | 3 | Wrestler + TagTeam |
| Fatal4Way | 4 | Wrestler + TagTeam |
| SixManTagTeam | 2 | Wrestler + TagTeam |
| BattleRoyal | null | Wrestler + TagTeam |

## Match Decisions (Enum)

| Decision | Has Winners/Losers |
|----------|-------------------|
| Pinfall | Yes |
| Submission | Yes |
| Disqualification | Yes |
| Countout | Yes |
| Knockout | Yes |
| Stipulation | Yes |
| Forfeit | Yes |
| TimeLimitDraw | No (draw) |
| NoDecision | No |
| ReverseDecision | No |

## Key Relationships

```
EventMatch
├── event (BelongsTo → Event)
├── matchStipulation (BelongsTo → MatchStipulation)
├── competitors (HasMany → MatchCompetitor)
│   └── competitor (MorphTo → Wrestler|TagTeam)
├── wrestlers (MorphToMany → Wrestler)
├── tagTeams (MorphToMany → TagTeam)
├── referees (BelongsToMany → Referee)
├── titles (BelongsToMany → Title)
├── result (HasOne → MatchResult)
├── winners (HasManyThrough → MatchWinner)
└── losers (HasManyThrough → MatchLoser)
```

## Side Assignment Pattern

Competitors are grouped by `side_number` for team-based matches:
- Side 1: Team A competitors
- Side 2: Team B competitors
- Side 3+: Additional sides for multi-way matches

## Booking Rules

**Competitors (Wrestlers/TagTeams):**
- Must be bookable (employed, not injured/suspended/retired)
- One match per event (double-booking restriction)

**Referees:**
- Must be bookable
- Multiple matches per event allowed

## File Locations

- `app/Models/Matches/EventMatch.php`
- `app/Models/Matches/MatchCompetitor.php`
- `app/Enums/MatchType.php`
- `app/Enums/MatchDecision.php`
- `app/Actions/Matches/AddMatchForEventAction.php`
