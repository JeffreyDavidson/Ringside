# Refactoring and Laravel Pattern Backlog

This is the living backlog for application-wide architecture improvements. New
items should be added here with a short rationale, affected boundary, and a
small implementation slice. A pattern should only be adopted when existing
code demonstrates a repeated need for it.

## Active refactoring slices

### Model status API boundary

**Priority:** High  
**Status:** In progress; employment and activity state reads are centralized in lifecycle readers, with projected-boolean inspection shared by both boundaries. The redundant `hasActivityPeriods()` predicate has been removed in favor of the typed relationship query.

Review `IsEmployable`, `IsInjurable`, `IsSuspendable`, `IsRetirable`, and
`HasActivityPeriods`. Their relationships and current-state accessors are used
throughout Actions, Services, Livewire, Rules, and tests. Eligibility classes
and status resolvers already own transition decisions, so removing predicates
incrementally would create a breaking API without a clear replacement.

Next step: migrate remaining callers to resolver and relationship-backed state
facts, then remove the remaining redundant activity predicates in dedicated
changes.

### Lifecycle status consistency

**Priority:** High  
**Status:** Candidate.

Audit services that combine employment, injury, suspension, retirement, and
activity checks. Keep domain-specific policies in their existing Lifecycle
boundaries, but identify any repeated multi-period decision that deserves one
typed coordinator.

### Custom Eloquent collections

**Priority:** Medium
**Status:** Candidate; do not introduce until repetition is proven.

`MatchCompetitorsCollection` is the existing custom collection. Review it and
other model collections for repeated domain operations such as grouping by
side, resolving competitors, or validating sequence rules. A custom collection
should expose behavior that belongs to a homogeneous Eloquent result set; it
should not become a general-purpose service or DTO container.

Compatibility warning: Laravel documents custom collections, but the framework
issue tracker has recorded relationship type failures when custom collection
implementations are returned from Eloquent relationships. Any new collection
must have relationship eager-loading and `BelongsTo` regression tests.

### Builder scopes and relationship queries

**Priority:** Medium  
**Status:** Ongoing.

Prefer typed Eloquent Builders and Laravel relationship constraints for reused
database predicates. Keep collection-level comparisons in lifecycle validation
when the data is already loaded; do not replace them with database queries that
alter transaction or history semantics.

### Value Objects and casts

**Priority:** Medium  
**Status:** Audit candidate.

Review every repeated primitive boundary (dates, names, identifiers, weights,
addresses, phone numbers, and match configuration). Use a cast when the value
belongs to one model attribute and a Value Object when validation and behavior
are shared across boundaries. Do not create a Value Object for a single simple
field.

### Validation and authorization boundaries

**Priority:** Medium  
**Status:** Mostly established; continue auditing.

Keep request validation in Form Requests, reusable domain constraints in Rules,
and model authorization in Policies or route middleware. Livewire actions must
continue to authorize on the server immediately before protected operations.

### Actions, Services, and Lifecycle managers

**Priority:** Medium  
**Status:** Audit ongoing.

Use an Action for a discrete application operation and a Service for a
coordinating capability used by multiple operations. Lifecycle persistence and
eligibility remain under `app/Lifecycle`; do not create generic services merely
to wrap one Action call.

### Pipelines and composable workflows

**Priority:** Medium
**Status:** Evaluated; no immediate extraction identified.

Laravel's `Pipeline` is appropriate when an operation is a genuinely
composable sequence of independently reusable stages that receive and pass a
shared context. Ringside's current lifecycle operations are intentionally
fixed orchestration: Actions validate and mutate one boundary, while typed
collaborators perform specific cascades. Converting those sequences into a
pipeline would hide domain ordering and weaken type boundaries.

Revisit this pattern when a workflow gains optional or configurable stages
that are reused across multiple entry points. Until then, keep fixed
orchestration in typed Actions or focused workflow components and do not add a
generic pipeline merely to remove sequential method calls.

### Jobs and queues

**Priority:** Low
**Status:** Defer until asynchronous work exists.

Introduce Jobs only for work that is slow, retryable, scheduled, or safely
asynchronous. Current synchronous lifecycle orchestration should not be moved
to queues merely to apply a pattern.

When asynchronous work is introduced, review whether it belongs in a queued
Job, a queued event listener, or `dispatchAfterResponse`. Preserve database
consistency by dispatching after commit when a job depends on newly persisted
state, and require idempotency, retry behavior, and failure handling for every
queued operation.

### Configuration and localization boundaries

**Priority:** Low
**Status:** Audit candidate.

Move deployment- or environment-specific values into `config/` and read them
through `config()` outside configuration files. Move user-facing and
validation text into the existing `lang/en` groups. Do not move domain facts,
database values, or developer-only exception diagnostics into configuration or
translations merely to remove literals.

The current scan found established translation groups and no application Jobs,
Events, Listeners, or Notifications directories. This is an opportunity to
audit remaining user-facing strings and operational constants, not a reason to
create empty framework directories.

### Caching and concurrency

**Priority:** Low
**Status:** Audit candidate.

Review repeated expensive read paths and competing writes for an evidence-based
cache or lock boundary. Prefer Laravel cache locks and concurrency controls
when a real contention or repeated-query problem is measured. Do not cache
mutable lifecycle state without an invalidation or consistency plan.

### Events and observers

**Priority:** Low  
**Status:** Defer unless fan-out is required.

Use domain events when multiple independent consumers need the same occurrence.
Use observers for model-event concerns that must remain attached to persistence.
Do not replace explicit synchronous workflows with events or observers.

### Resources and ViewModels

**Priority:** Low  
**Status:** Defer; no API surface currently exists.

Use API Resources for an actual API response boundary and ViewModels only when
page payload assembly becomes nontrivial or reusable. Existing Blade and
Livewire payloads should not gain ceremonial layers.

### Architecture tests

**Priority:** High  
**Status:** Candidate.

Extend the existing architecture suite to enforce the decisions above:

- model concerns expose persistence relationships, not new workflow commands;
- Lifecycle eligibility classes remain in `app/Lifecycle`;
- Services remain under their responsibility domains;
- custom collections are tied to Eloquent models and remain type-safe;
- Livewire components delegate protected writes to Actions or Services;
- no new repository layer is introduced around Eloquent.

### CI and test feedback

**Priority:** High  
**Status:** Completed for current workflow slice.

CI quality checks now run in parallel, browser tests are gated behind required
checks, and TIA no longer installs frontend tooling unnecessarily. Continue
monitoring runtime and required-check names before further workflow changes.

## Research notes

The following Laravel sources informed this backlog:

- [Laravel 13 Eloquent resources](https://github.com/laravel/docs/blob/13.x/eloquent-resources.md)
- [Laravel Eloquent collections and custom collections](https://laravel.com/docs/master/eloquent-collections)
- [Laravel Eloquent APIs and query scopes](https://laravel.com/docs/13.x/eloquent)
- [Laravel framework issue: custom collections and BelongsTo relations](https://github.com/laravel/framework/issues/53241)
- [Livewire actions and server-side authorization](https://livewire.laravel.com/docs/3.x/actions)
- [Laravel Pipelines API](https://api.laravel.com/docs/13.x/Illuminate/Support/Facades/Pipeline.html)
- [Laravel events and queued listeners](https://laravel.com/framework/docs/events)
- [Laravel application structure, Jobs, Events, and configuration](https://laravel.com/docs/13.x/structure)
- [Laravel queue dispatching and after-commit behavior](https://api.laravel.com/docs/13.x/Illuminate/Contracts/Bus/QueueingDispatcher.html)
- [Laravel cache locks and concurrency controls](https://api.laravel.com/docs/13.x/Illuminate/Support/Facades/Cache.html)

These references support using Laravel-native Builders, scopes, casts,
collections, Policies, Form Requests, Resources, Pipelines, Jobs, Events,
configuration, localization, and cache controls where the application has an
actual need. They do not justify adding repositories, generic service
wrappers, event buses, pipelines, or queues without a concrete use case.
