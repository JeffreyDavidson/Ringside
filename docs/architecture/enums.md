# Enum Architecture

## Overview

Ringside uses native PHP enums for statuses, types, roles and operation names. Every enum lives in the folder of the domain that owns it under `app/Enums/`, with `Shared/` for values more than one domain stores. No enum sits directly in `App\Enums`; `ApplicationArchitectureTest` enforces this. The written rules are the shared `laravel-conventions` rule `enums.md` plus Ringside's enum facts in [codebase-conventions.md](codebase-conventions.md#enums).

## Folders

| Folder | Holds |
| --- | --- |
| `Crew/` | Crew roles |
| `Events/` | Event status, public announcements and sections, live show, talent booking, budget and sponsor commitment vocabularies |
| `Lifecycle/` | Lifecycle audit vocabulary (dimension, owner type, transition type) |
| `Matches/` | `MatchType`, `MatchFinish` |
| `Naming/` | Names guarded by `RecordNameLock` |
| `Promotions/` | Membership roles and statuses, invitation outcomes |
| `Roster/` | Roster entity kinds and lifecycle actions |
| `Shared/` | `EmploymentStatus`, `UnitedStatesState`, `DeletedFilter`, `BusinessRuleReason` |
| `Stables/` | Stable status, lifecycle actions and member unavailability |
| `Storylines/` | Storyline and step statuses |
| `Titles/` | `TitleStatus`, `TitleType`, title lifecycle transitions |
| `Tournaments/` | Tournament status, entrant kind and operations |
| `Users/` | `Role`, `UserStatus` |
| `Concerns/` | Traits shared by enums (`ProvidesFilterOptions`) |

## Stored values

Enums are string-backed with PascalCase cases and lowercase snake_case values. Four enums store kebab-case values that are already in existing rows: `MatchType`, `MatchFinish`, `RosterEntityType` and `TitleType` (for example `tag-team`, `time-limit-draw`). They keep those values; there is no data migration. Two lifecycle transition enums (`StableActivityTransition`, `TitleLifecycleTransition`) are unbacked because they are never stored. `UnitedStatesState` stores the display name (`New York`) as its value and has no `label()`.

Enum class names are not stored in the database. Models cast columns to enums, so moving an enum to another namespace needs no data change.

## Labels

An enum whose value a user sees has a `label(): string` that returns a translation. The text lives in `lang/en`, under a key built from the owning file, the enum and the case value, such as `titles.status.pending_debut` or `matches.type.tag-team`. Blade views and PHP call `->label()` and never build the key themselves.

```php
enum TitleStatus: string
{
    use ProvidesFilterOptions;

    case Undebuted = 'undebuted';
    case PendingDebut = 'pending_debut';
    case Active = 'active';

    public function label(): string
    {
        return __("titles.status.{$this->value}");
    }
}
```

Enums whose value is never shown (such as `PublicSection`, `LiveAttendanceMark`, `PromotionInvitationOutcome`, `BusinessRuleReason` and the lifecycle vocabulary) have no label. When the value comes from stored JSON, views use `Enum::tryFrom($value)?->label() ?? $value` so an old or hand-edited value renders as stored instead of failing.

`TranslationKeysTest` loads every enum with a `label()` method and fails when a case returns an untranslated key.

## Select and filter options

`App\Enums\Concerns\ProvidesFilterOptions` provides:

- `options()`: `value => label` for every case, for form selects.
- `filterOptions()`: the same with an `'' => All` row first, for table filters.

```php
TitleStatus::filterOptions(); // ['' => 'All', 'undebuted' => 'Not Yet Debuted', ...]
MatchType::options();          // ['singles' => 'Singles', 'tag-team' => 'Tag Team', ...]
```

## Behaviour on enums

Enums carry small, stateless behaviour that more than one caller needs: `MatchFinish::requiresWinningSide()`, `MatchType::numberOfSides()`, `Role::isAdministrator()`, `BudgetCategory::accepts()`, and the `ability()` mapping on lifecycle action enums (checked by `PolicyAbilityArchitectureTest`). Business rules that depend on other records live in Actions and `app/Lifecycle`, not in enums.

## Model integration

```php
protected function casts(): array
{
    return ['status' => EmploymentStatus::class];
}
```

Factories and tests pass enum cases, not strings: `'status' => EmploymentStatus::Employed`.

## Testing

- `tests/Integration/Enums/EnumLabelsTest.php` pins the text users see for every labelled enum.
- `TranslationKeysTest` catches a label whose translation key is missing.
- Enum behaviour that needs no framework is tested in `tests/Unit/Enums`.
