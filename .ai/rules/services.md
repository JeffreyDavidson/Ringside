---
paths:
  - 'app/Services/**'
---

# Services

## Direct Eloquent access
Query Eloquent models directly. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent.

## Inject service collaborators
Acquire Action and service collaborators through constructor injection inside services.

## Idempotent lifecycle periods
Create the current lifecycle or activity period through relationship updateOrCreate(), identifying the open record with ended_at => null.

## Membership services preserve relationship history
Keep membership Services focused on establishing and synchronizing relationship records while dating ended pivots instead of deleting history. Validate eligibility before Actions, and orchestrate employment or other lifecycle cascades through typed Actions rather than membership Services.
