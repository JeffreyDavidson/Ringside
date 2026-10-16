---
paths:
  - 'app/Services/**'
---

# Services

## Services only read
A Service answers a question about records without changing anything: a cross-record calculation, a lookup that spans several models, or the request's promotion context (PromotionContextService). It takes no row lock, opens no transaction, writes nothing, validates nothing and throws no business exception. Guards that throw, locks and lifecycle period writes live in app/Lifecycle; validation lives in the Form Request or Livewire component that feeds the Action; transactions, locks and persistence live in Actions. A class that mostly refuses an operation belongs in Lifecycle even when it is named `...Service`.

## Services never depend on Lifecycle or Actions
Lifecycle may read through a Service (StableActivityEligibility reads current members through StableMembershipService, TournamentMatchRequirements reads the bracket through TournamentBracketService), so the dependency runs from Lifecycle to Services only. A Service never injects a Lifecycle guard, writer or lock, an Action, or anything from the view layer. ServiceArchitectureTest fails when a Service uses the Validator, ValidationException, App\Exceptions, App\Lifecycle, App\Actions, the database connection or the DB facade.

## Direct Eloquent access
Query Eloquent models directly. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent. Inject other Services through the constructor.

## Membership services read relationship history
Keep membership Services focused on reading current and historical relationship records and returning typed membership data to coordinating Actions; do not add DTO construction or lifecycle member-selection helpers to Eloquent models. Establishing, synchronizing, and ending memberships (dating ended pivots instead of deleting history) and any employment or lifecycle cascades belong to typed Actions.

## Organize services by responsibility domain
Keep Services organized by technical layer first and responsibility domain second (Events, Promotions, Roster/Stables, Titles, Tournaments) and name each class after what it computes followed by `Service`. Do not create a parallel Lifecycle service namespace.
