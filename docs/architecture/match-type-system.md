# Match Type System

## Overview

A match's type is the string-backed enum `App\Enums\Matches\MatchType`. It is stored on `event_matches.match_type` and cast to the enum on `EventMatch`. There is no `MatchType` model, `match_types` table or `match_type_id` column.

The enum answers structural questions about a format: how many sides, which competitor types may enter, and how many entries or roster members each side needs. Rules that depend on other records (who is bookable, titles, scheduling) live in Actions and `app/Lifecycle/Matches`, not in the enum. See [Match System](match-system.md) for the competitor rules and [Enums](enums.md) for enum conventions.

## Cases

`MatchType` keeps its kebab-case stored values (see `.ai/rules/enums.md`).

| Case | Stored value | Sides | Allowed competitors | Minimum / maximum entrants | Fixed side shape |
| --- | --- | --- | --- | --- | --- |
| `Singles` | `singles` | 2 | Wrestler | 2 / 2 | 1 entry per side |
| `TagTeam` | `tag-team` | 2 | Wrestler, tag team | 2 / 2 | 2 roster members per side |
| `TripleThreat` | `triple-threat` | 3 | Wrestler, tag team | 3 / 3 | 1 entry per side |
| `Triangle` | `triangle` | 3 | Wrestler | 3 / 3 | 1 entry per side |
| `Fatal4Way` | `fatal-4-way` | 4 | Wrestler, tag team | 4 / 4 | 1 entry per side |
| `SixManTagTeam` | `6-man-tag-team` | 2 | Wrestler, tag team | 2 / 2 | 3 roster members per side |
| `EightManTagTeam` | `8-man-tag-team` | 2 | Wrestler, tag team | 2 / 2 | 4 roster members per side |
| `TenManTagTeam` | `10-man-tag-team` | 2 | Wrestler, tag team | 2 / 2 | 5 roster members per side |
| `TwoOnOneHandicap` | `two-on-one-handicap` | 2 | Wrestler, tag team | 2 / 2 | 1 and 2 roster members |
| `ThreeOnTwoHandicap` | `three-on-two-handicap` | 2 | Wrestler, tag team | 2 / 2 | 2 and 3 roster members |
| `BattleRoyal` | `battle-royal` | none | Wrestler | 3 / no maximum | each entrant is its own side |
| `RoyalRumble` | `royal-rumble` | none | Wrestler | 10 / 30 | each entrant is its own side |
| `TornadoTagTeam` | `tornado-tag-team` | 2 | Wrestler, tag team | 2 / 2 | 2 roster members per side |
| `Gauntlet` | `gauntlet` | 2 | Wrestler, tag team | 2 / 2 | none |

The table mirrors the enum; the enum is the source of truth.

## Enum methods

- `label()` returns the translated name from `matches.type.{value}`.
- `numberOfSides()` returns the side count, or `null` for Battle Royal and Royal Rumble.
- `usesIndividualCompetitorSides()` is true for Battle Royal and Royal Rumble.
- `getAllowedCompetitorTypes()`, `allowsWrestlers()` and `allowsTagTeams()` describe who may enter.
- `requiredRosterMembersPerSide()` and `requiredCompetitorEntriesPerSide()` return per-side counts, or `null` when the format sets none.
- `getMinimumCompetitors()` and `getMaximumCompetitors()` bound the entrant count (`null` maximum means unbounded).
- `options()` (from `ProvidesFilterOptions`) feeds selects and table filters.

## Where the enum is used

- **Match form** (`app/Livewire/Matches/Forms/CreateEditForm.php`): `matchType` is a nullable `MatchType` property validated with the `Enum` rule. `resetCompetitorsFor()` rebuilds the sides when the type changes, using `numberOfSides()` or one side per entrant. `MatchCompetitorRuleSet` adds the competitor validation for the chosen type.
- **Competitor layout** (`app/Livewire/Matches/Enums/CompetitorSelectionLayout.php`): `forMatchType()` maps each case to the layout of the competitor picker (singles, tag team, triple threat, fatal four way, battle royal or generic).
- **Booking rules** (`app/Lifecycle/Matches/MatchCompetitorRequirements.php`, `AddCompetitorsToMatchAction`): enforce the required entries and roster members per side.

## Factory

`database/factories/Matches/MatchFactory.php` sets `match_type` to the enum directly:

```php
EventMatch::factory()->withMatchType(MatchType::Gauntlet)->create();

// Shortcuts for the common formats
EventMatch::factory()->singles()->create();
EventMatch::factory()->tagTeam()->create();
EventMatch::factory()->tripleThreat()->create();
EventMatch::factory()->fatalFourWay()->create();
EventMatch::factory()->battleRoyal()->create();
```

`generateFullMatch()` takes a `match_type` string in its configuration array. `resolveMatchType()` maps the accepted spellings (`singles`, `tagteam` or `tag-team`, `triple` or `triple-threat`, `fatal4way` or `fatal-4-way`, `battleroyal` or `battle-royal`, `royalrumble` or `royal-rumble`) to cases and falls back to `Singles`. Other cases are set with `withMatchType()`.

## Adding a match type

1. Add the case to `MatchType` and every `match` expression that must handle it (`numberOfSides()`, the competitor and roster requirements, minimum and maximum).
2. Add the `matches.type.{value}` translation.
3. Map it in `CompetitorSelectionLayout::forMatchType()`.
4. Add a migration only if existing rows need changing; the stored value is a plain string.
5. Update the table above and add a spelling to `MatchFactory::resolveMatchType()` if factory configuration should accept it.
