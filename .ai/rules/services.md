---
paths:
  - 'app/Services/**'
---

# Services

## Direct Eloquent access
Query Eloquent models directly. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent.

## Inject service collaborators
Acquire read-only collaborators (other services, lifecycle readers, eligibility classes) through constructor injection. Services never depend on or invoke Actions.

## Lifecycle history is written outside services
Services never persist lifecycle periods. Period writes belong to the lifecycle managers under app/Lifecycle, coordinated by Actions inside their transaction; those writers start a new period only when none is open and never use updateOrCreate() to rewrite an open period.

## Membership services read relationship history
Keep membership Services focused on reading current and historical relationship records and returning typed membership data to coordinating Actions; do not add DTO construction or lifecycle member-selection helpers to Eloquent models. Establishing, synchronizing, and ending memberships (dating ended pivots instead of deleting history) and any employment or lifecycle cascades belong to typed Actions.

## Organize services by responsibility domain
Keep Services organized by technical layer first and responsibility domain second. Place event, match, title, and venue services under their domains; place roster services under Roster with Individuals, TagTeams, Stables, and Relationships subdomains. Do not create a parallel Lifecycle service namespace; lifecycle persistence managers remain under app/Lifecycle.

## Keep services read-only
Services provide read-only retrieval, calculation, or validation. Actions own transactions, persistence, and other state-changing operations. Shared lifecycle mechanics belong in focused lifecycle components coordinated by Actions.
