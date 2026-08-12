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
