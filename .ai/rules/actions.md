---
paths:
  - 'app/Actions/**'
---

# Actions

## Direct Eloquent access
Query Eloquent models directly. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent.

## Action entry points
Implement individual application operations as focused Action classes with a public handle() method.

## Inject action collaborators
Acquire Action and service collaborators through constructor injection inside application operations.

## Idempotent lifecycle periods
Create the current lifecycle or activity period through relationship updateOrCreate(), identifying the open record with ended_at => null.
