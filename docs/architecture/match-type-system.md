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

Minimum and maximum entrants are enforced by `MatchCompetitorRequirements` only for the individual-side formats (Battle Royal and Royal Rumble). For every other format the values equal the side count, and the tag formats hold 4 to 10 wrestlers through their roster-member requirements.

## Enum methods

- `label()` returns the translated name from `matches.type.{value}`.
- `numberOfSides()` returns the side count, or `null` for Battle Royal and Royal Rumble.
- `usesIndividualCompetitorSides()` is true for Battle Royal and Royal Rumble.
- `getAllowedCompetitorTypes()`, `allowsWrestlers()` and `allowsTagTeams()` describe who may enter.
- `requiredRosterMembersPerSide()` and `requiredCompetitorEntriesPerSide()` return per-side counts, or `null` when the format sets none.
- `getMinimumCompetitors()` and `getMaximumCompetitors()` bound the entrant count (`null` maximum means unbounded).
- `options()` (from `ProvidesFilterOptions`) feeds selects and table filters.

## Where the enum is used

- **Match form** (`app/Livewire/Matches/Forms/CreateEditForm.php`): `matchType` is a nullable `MatchType` property validated with the `Enum` rule. `resetCompetitorsFor()` rebuilds the form's competitor list when the type changes: one side for Battle Royal and Royal Rumble (a single entrant list that `toData()` later expands to one side per entrant), otherwise `numberOfSides()` sides. `MatchCompetitorRuleSet` adds the competitor validation for the chosen type.
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

`generateFullMatch()` takes a `match_type` string in its configuration array. `resolveMatchType()` maps the accepted spellings (`singles`, `tagteam` or `tag-team`, `triple` or `triple-threat`, `fatal4way` or `fatal-4-way`, `battleroyal` or `battle-royal`, `royalrumble` or `royal-rumble`) to cases and falls back to `Singles`. `generateFullMatch()` cannot reach cases that `resolveMatchType()` does not list, and an unlisted string (for example `'triangle'`) silently becomes `Singles`. Use `withMatchType()` for those cases.

## Adding a match type

1. Add the case to `MatchType` and to every `match` in it that must handle the case (`numberOfSides()`, `getAllowedCompetitorTypes()`, the roster and entry requirements, `getMinimumCompetitors()`, `getMaximumCompetitors()`).
2. Add the label under `type.{value}` in `lang/en/matches.php` (`TranslationKeysTest` checks the literal keys).
3. Add the case to `MatchCompetitorRuleSet::rules()` (`app/Livewire/Matches/Support/`). A case not listed there falls to the default `mixedCompetitorSideRules()`.
4. If the format is a direct one-on-one or tag meeting, add it to the hard-coded type lists in `EventMatchBuilder::directMeetings()` and `BookingHistoryViewModel::directType()`.
5. Optionally map it in `CompetitorSelectionLayout::forMatchType()`; unmapped cases use `Generic`.
6. Add the spelling to `MatchFactory::resolveMatchType()` if factory configuration should accept it.
7. Add rows to `tests/Unit/Enums/Matches/MatchTypeTest.php` and `tests/Unit/Livewire/Matches/Enums/CompetitorSelectionLayoutTest.php`; the project requires 100% coverage.
8. Add a migration only if existing rows need changing; the stored value is a plain string.
9. Update the table above.

Other code compares against specific cases (`MatchEntryOrderRequirement` and `AddWrestlersToMatchAction` for Royal Rumble, tournament booking for Singles and Tag Team); a new case does not need to change them unless it shares that behaviour.
