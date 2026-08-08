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
