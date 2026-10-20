---
paths:
  - 'app/Enums/**'
---

# Enums

## String-backed domain enums
Define domain enums as string-backed enums with PascalCase cases. Store lowercase snake_case values (`pending_debut`, `future_employment`). Four enums keep the kebab-case values they already store, because the values are in existing rows and no data migration is planned: `MatchType`, `MatchFinish`, `RosterEntityType` and `TitleType`. Do not add a fifth; new enums use snake_case. Three more enums are known exceptions: `StableActivityTransition` and `TitleLifecycleTransition` are unbacked because they are never stored, and `UnitedStatesState` stores the display name (`New York`) as its value and has no `label()`.

## Group enums by domain
Place every enum in the folder of the domain that owns it (`Events`, `Matches`, `Titles`, `Roster`, `Shared`, ...). Use `Shared` only for values more than one domain stores. Never put an enum directly in `App\Enums`; `ApplicationArchitectureTest` fails when one appears there.

## Translate every enum label
An enum whose value a user can see has a `label(): string` that returns a translation, with the key `<domain>.<enum>.<case value>` in the matching `lang/en` file (`titles.status.pending_debut`, `matches.type.tag-team`). Where a flat key of that name already exists in the file, use the plural group (`titles.types`, `users.roles`). Labels that were already translated through flat keys other screens share (`storylines.active`, `event-budget.talent`, `live-show.pending`, `sponsors.pending`, `public-events.cancelled`, and the storyline source warnings such as `storylines.deleted_source`) keep those keys. Keep the English text in `lang/en`, never in the enum. Enums whose value is never shown to users (identifiers, lifecycle vocabulary, selector kinds, invitation outcomes, operation names used only in code, `BusinessRuleReason`) need no label. Blade and PHP call `->label()`; they do not build enum translation keys. When the value comes from a stored snapshot or JSON, use `Enum::tryFrom($value)?->label() ?? $value`; use `from()` only for values read through a model enum cast. `TranslationKeysTest` fails when a label returns an untranslated key.

## Decide through the enum, never its translated text
Code that has to tell cases apart (which warnings still allow a save, which statuses need a reason) calls a method on the enum, such as `StorylineSourceWarning::allowsCurrentSave()`. It never compares `__()` strings. A service that reports several cases returns the enum cases, and the Blade view that shows them calls `->label()`; its translation keys stay flat (`storylines.deleted_source`).

## Build select and filter options with the trait
Use `App\Enums\Concerns\ProvidesFilterOptions` for select options (`options()`) and for filter options with an "All" row (`filterOptions()`). Do not write another loop that maps cases to labels.

## Keep enums small
Add a method only when a caller in `app/` needs it. Leave plain value lists without methods, and delete a method when its last caller goes. Do not add trailing case comments or docblocks that restate a method name.

## Map policy abilities explicitly
Lifecycle action enums expose `ability()` that returns the policy ability name. `PolicyAbilityArchitectureTest` checks these names.
