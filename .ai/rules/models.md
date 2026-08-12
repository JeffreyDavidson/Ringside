---
paths:
  - 'app/Models/**'
---

# Models

## Attribute-based fillable declarations
Declare intentionally mass-assignable fields with #[Fillable(...)] on every Eloquent model, including custom pivot models. Do not use the $fillable property.

## Custom Eloquent builders
Put reusable model query operations on typed custom Eloquent Builder classes and bind them with #[UseEloquentBuilder]; do not add local model scopes.

## Cast string columns to enums
Cast enum-backed string columns to their PHP enum classes in model casts().

## Use shared polymorphic employment records
Store employment periods in App\Models\Lifecycle\Employment through the polymorphic employable owner. Wrestlers, managers, referees, and tag teams share this record model; do not add entity-specific employment models or infer them from naming conventions. Migrate other lifecycle dimensions only through their own reviewed changes.

## Use shared polymorphic injury records
Store injury periods in App\Models\Lifecycle\Injury through the polymorphic injurable owner. Wrestlers, managers, and referees share this record model; keep eligibility and transitions in their existing typed concerns and Actions rather than adding entity-specific injury models.

## Use shared polymorphic suspension records
Store suspension periods in App\Models\Lifecycle\Suspension through the polymorphic suspendable owner. Wrestlers, managers, referees, and tag teams share this record model; keep eligibility, locking, transactions, and cascades in their existing typed concerns and Actions rather than adding entity-specific suspension models.

## Use shared polymorphic retirement records
Store retirement periods in App\Models\Lifecycle\Retirement through the polymorphic retirable owner. Wrestlers, managers, referees, tag teams, stables, and titles share this record model; keep eligibility, locking, transactions, and cascades in their typed concerns and Actions rather than adding entity-specific retirement models.

## Use shared polymorphic activity periods
Store stable and title operational periods in App\Models\Lifecycle\ActivityPeriod through the polymorphic activeable owner. Keep wrestling-specific transitions in typed Actions such as DebutAction, PullAction, ReinstateAction, EstablishAction, DisbandAction, and ReuniteAction; use the shared lifecycle start/end Actions only for persistence, locking, and period integrity.

## Separate lifecycle state from transition auditing
Use lifecycle period models as the authoritative source of current and historical state. Use the immutable polymorphic LifecycleTransition model only as an audit log of named transitions, effective dates, actors, and optional context; never derive current state from transition records.
