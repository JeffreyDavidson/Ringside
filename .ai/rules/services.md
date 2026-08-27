---
paths:
  - 'app/Services/**'
---

# Services

## Direct Eloquent access
Query Eloquent models directly. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent.

## Inject service collaborators
Acquire Action and service collaborators through constructor injection inside services.

## Do not rewrite open lifecycle periods
Services that persist lifecycle history must not use updateOrCreate() to rewrite an open period. Start a new period only when none is open, and rely on the database uniqueness constraint plus the coordinating Action transaction for exclusivity.

## Membership services preserve relationship history
Keep membership Services focused on reading, establishing, and synchronizing relationship records while dating ended pivots instead of deleting history. Return typed membership data for coordinating Actions instead of adding DTO construction or lifecycle member-selection helpers to Eloquent models. Validate eligibility before Actions, and orchestrate employment or other lifecycle cascades through typed Actions rather than membership Services.

## Organize services by responsibility domain
Keep Services organized by technical layer first and responsibility domain second. Place event, match, title, and venue services under their domains; place roster services under Roster with Individuals, TagTeams, Stables, and Relationships subdomains. Do not create a parallel Lifecycle service namespace; lifecycle persistence managers remain under app/Lifecycle.
