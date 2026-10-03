# Livewire Component Architecture Standards

## Component Standardization

Livewire components follow one naming and layout convention across domains. The folder
supplies the component type, so class names do not repeat it.

```
app/Livewire/{Domain}/
├── Components/
│   └── Actions.php              (lifecycle actions for a detail page)
├── Forms/
│   └── CreateEditForm.php
├── Modals/
│   └── FormModal.php
└── Tables/
    ├── Main.php                 (primary index table)
    └── Previous{Entity}.php     (relationship history tables on detail pages)
```

Not every domain has every folder. For example, Stables and Promotions have no
`Components/Actions.php` (see the refactoring backlog), Matches uses
`Tables/MatchesTable.php`, and the title page uses `Titles/Tables/TitleHistory.php`
because it lists every reign (current reign first, shown as "Current"), not only
previous ones.

Relationship tables use `ShowTableTrait`, which gives each table a heading derived from
its `$resourceName` (for example "Title championships") and, when a table has no
records and no search, a one-line "No {resource} yet." message instead of the full
search, table and pager chrome.

## Component Naming Conventions

### Class to View Mapping:
- Class: `App\Livewire\Wrestlers\Tables\Main` → Blade view: `livewire/wrestlers/tables/main.blade.php`
- Class: `App\Livewire\Wrestlers\Modals\FormModal` → Blade view: `livewire/wrestlers/modals/form-modal.blade.php`
- **Pattern:** PascalCase class → kebab-case with namespace dots

### Avoid Redundant Domain Prefixes:
- ❌ `WrestlerActions` (inside `app/Livewire/Wrestlers/Components/`)
- ✅ `Actions` (directory context makes domain clear)
- ❌ `WrestlerFormModal` → ✅ `FormModal` (when inside Wrestlers directory)
- **Rule:** Domain context from directory structure eliminates need for domain prefix in class names

## Lifecycle Actions Components

Each detail page (wrestlers, managers, referees, tag teams, stables, titles) renders a
`Components/Actions` component (for example `livewire:wrestlers.components.actions`).
These are the only Livewire entry points for lifecycle transitions; the index tables
expose row actions such as delete but no lifecycle methods.

- Each transition method resolves its typed Action from the container and runs it
  through `ExecutesRosterActions::executeAuthorizedRosterAction()` (titles use
  `ExecutesBusinessActions`), which authorizes with `Gate::authorize()`, executes the
  Action, translates `BaseBusinessException` into a localized failure message, and
  dispatches `{entity}-updated` plus a `flash-message` for the toast.
- `canPerform(RosterLifecycleAction $action)` decides which buttons render. It combines
  the Gate ability with the domain eligibility check
  (`ChecksIndividualLifecycleEligibility` for wrestlers, managers and referees). Titles
  take a `TitleLifecycleTransition` instead. The Blade view never re-implements the rule.
- Stables offer Establish, Disband, Retire and Unretire. They take a
  `StableLifecycleAction` in `canPerform()` (Gate ability plus `StableActivityEligibility` /
  `StableRetirementEligibility`) and, like titles, run through `ExecutesBusinessActions`, so
  a rejected action shows the domain exception's message. Merge, split and reunite remain
  unwired.
- Destructive transitions (Release, Suspend, Injure, Retire, Disband, and Deactivate for
  titles) ask for confirmation with `wire:confirm`, using a `core.lifecycle_confirmations.*`
  message that names the record. Bind it as `:wire:confirm="__(...)"` so names with
  apostrophes are escaped once. Every action button also carries
  `wire:loading.attr="disabled"` and a `wire:target` for its own method, so a second click
  cannot queue the transition again while it runs.

## Form Modals

`x-form-modal` renders the shared `x-form.footer`. Its Save, Clear and Auto fill buttons
are disabled while `save`, `clear` or `fillDummyFields` runs. The footer stores the form
as it was when the modal opened. Clear does nothing while the form is unchanged and asks
for confirmation before it discards changes (typed, auto-filled, or kept after a failed
save).

Fields that the form's rules require take a `required` attribute; the form components
pass it to the control and show the label's `*` marker. The first field of each modal
takes `initial-focus`: once the dialog has opened (and focused its close button), the
field focuses itself unless the user has already moved into another field. The vendor
`autofocus` handling is not used because it moves focus unconditionally after a delay,
which can pull typing into the wrong field. Numeric text fields (height, weight, zip
code) use `inputmode="numeric"`.

## General Info Card

`App\Livewire\Components\GeneralInfo` wraps the General Info card of a show page so
lifecycle changes appear without a reload.

- `modelClass` and `modelId` are `#[Locked]`; the client cannot change what renders.
- A private `CARDS` map, keyed by model class, defines the refresh event
  (`wrestler-updated`, `manager-updated`, `referee-updated`, `stable-updated`,
  `tag-team-updated`, `title-updated`), the anonymous Blade component that renders the card, that
  component's model prop, and the relationships to eager load.
- On each render it re-queries the model with those relationships and renders the card
  through `x-dynamic-component`.

Adding another entity means adding one `CARDS` entry, not a new Livewire component.

## Modal Titles

`BaseModal::getModalTitle()` resolves the model from the form's locked `modelId` on
every call and reads `$modelTitleField` (default `name`; Managers and Referees use
`full_name`). It uses `core.modal.edit` (`Edit :name`) and `core.modal.add`
(`Add :model`). Modals with their own wording override `getModalTitle()` and use
`<domain>.modal.*` keys. The base modal says "Add" while several domain modals say
"Create"; this inconsistency is tracked in the refactoring backlog.

## Model Keys

`App\Support\ModelKey::of(Model $model): int|string` narrows Eloquent's untyped
primary key and throws `LogicException` for a missing or non-scalar key. Use it instead
of ad hoc `getKey()` guards (for example in `BaseForm::setModel()` and the shared
activity period Actions).

## Trait Naming Guidelines

### Avoid redundant names:
- ❌ `IsBookableReferee` (redundant if only used by Referee model)
- ✅ Define referee-only persistence relationships directly on `Referee`

### Use descriptive verbs:
- `HasMatchParticipations` - for entities with persisted competitor-match relationships
- Define manager and stable membership relationships directly on Wrestler and Tag Team so their distinct pivot mappings remain explicit

Manager defines its inverse Wrestler and Tag Team relationships directly because no other model owns that exact relationship set.

## Interface Implementation Strategy

**When to use traits vs direct implementation:**

### Use Traits When:
- Multiple models need the same functionality
- Code would be duplicated across models
- Behavior is cohesive and reusable

### Direct Implementation When:
- Only one model uses the interface
- Implementation is model-specific
- Trait would be overly specific

**Example:** `EventMatchPolicy` is implemented directly since only EventMatch needs it, while `HasMatchParticipations` is a trait since multiple competitor types expose match participation relationships.
