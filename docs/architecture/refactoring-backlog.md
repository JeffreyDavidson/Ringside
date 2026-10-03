# Refactoring and Laravel Pattern Backlog

This is the living backlog for application-wide architecture improvements. New
items should be added here with a short rationale, affected boundary, and a
small implementation slice. A pattern should only be adopted when existing
code demonstrates a repeated need for it.

## Active refactoring slices

### Model status API boundary

**Priority:** High  
**Status:** In progress; employment and activity state reads are centralized in lifecycle readers, with projected-boolean inspection shared by both boundaries. The redundant `hasActivityPeriods()`, `hasFutureActivity()`, `isCurrentlyActive()`, `hasFutureEmployment()`, `hasNoCurrentOrFutureEmployment()`, `hasEmploymentHistory()`, `isEmployed()`, `isRetired()`, `isSuspended()`, `isInjured()`, `isReleased()`, and `employedOn()` predicates have been removed in favor of typed relationship queries, lifecycle builders, and the computed employment status enum.

Review `IsEmployable`, `IsInjurable`, `IsSuspendable`, `IsRetirable`, and
`HasActivityPeriods`. Their relationships and current-state accessors are used
throughout Actions, Services, Livewire, Rules, and tests. Eligibility classes
and status resolvers already own transition decisions, so removing predicates
incrementally would create a breaking API without a clear replacement.

Next step: migrate remaining callers to resolver and relationship-backed state
facts, then review the remaining lifecycle convenience predicates for the same
relationship-focused boundary.

### Lifecycle status consistency

**Priority:** High  
**Status:** In progress; roster booking now delegates type-specific decisions to
focused strategies while preserving the shared status and relationship checks.
Eligibility guards that Action ordering makes unreachable (for example a retired
subject reaching release, or a stable unretire name conflict blocked by the
`stables_active_name_unique` index) have been deleted rather than tested; the
ordering guarantee is recorded in `lifecycle-operation-boundaries.md`.

Audit services that combine employment, injury, suspension, retirement, and
activity checks. Keep domain-specific policies in their existing Lifecycle
boundaries, but identify any repeated multi-period decision that deserves one
typed coordinator.

### Custom Eloquent collections

**Priority:** Medium
**Status:** In progress; `MatchCompetitorsCollection` now owns typed wrestler and
tag-team partitions for loaded competitor entries.

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

Use an Action for every state-changing application operation. Services are
read-only collaborators for retrieval, calculation, or validation. Lifecycle
persistence and eligibility remain under `app/Lifecycle`; do not create a
write-oriented service merely to wrap one Action call. Existing write-oriented
services should be folded into their coordinating Actions when touched.

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

`App\ViewModels\DashboardViewModel` is the first ViewModel: the Overview page
combines upcoming events, roster availability counts and current champions.
It queries Eloquent directly (no repository layer), relying on the promotion
global scope, and the controller passes it to the view as `dashboard`.

### Architecture tests

**Priority:** High  
**Status:** In progress; `tests/Feature/Architecture` now enforces controller
structure, exception construction, morph aliases, roster model namespaces, test
suite boundaries, translation-key resolution, orphaned docblocks, locked Livewire
context identifiers, and that Livewire components neither create records through
factories nor write directly through Eloquent models.

Still to enforce from the decisions above:

- model concerns expose persistence relationships, not new workflow commands;
- Lifecycle eligibility classes remain in `app/Lifecycle`;
- Services remain under their responsibility domains;
- custom collections are tied to Eloquent models and remain type-safe;
- no new repository layer is introduced around Eloquent.

### CI and test feedback

**Priority:** High  
**Status:** Completed for current workflow slice.

CI quality checks now run in parallel, browser tests are gated behind required
checks, and TIA no longer installs frontend tooling unnecessarily. Continue
monitoring runtime and required-check names before further workflow changes.

### Coverage policy

**Priority:** High  
**Status:** Completed; enforced.

`composer test:coverage` requires 100% line coverage of `app/` (Livewire,
Console, and the service providers included, Browser suite excluded) and the
`Coverage (100%)` CI job runs it on every pull request. The gate runs
non-parallel because parallel runs lose `match` header line attribution and are
not deterministic. There are no `@codeCoverageIgnore` markers: unreachable code
is deleted instead of tested, and randomized inputs in tests are pinned (fixed
distinctive values or `forceFakerBoolean()`). Keep the threshold at 100 and treat
a new uncovered line as either a missing behavior test or dead code.

### Promotion gate extraction

**Priority:** Medium  
**Status:** Completed.

The promotion-scoped `Gate::before` closure moved out of `AppServiceProvider`
into `App\Policies\PromotionGate::before()`, covered by the characterization
suite in `tests/Integration/Policies/PromotionGateTest.php`. The promotion
context middleware is registered as Livewire persistent middleware so update
requests keep the same scope as page loads.

Known quirk, deliberately frozen: the gate inspects only the first ability
argument, so `[Wrestler::class, $foreignWrestler]` is authorized as a class
string. `PromotionPolicy` instance abilities (`view`, `manageMembers`, and so
on) are unreachable through the Gate because the gate always answers first for a
`Promotion` subject; they are kept, and covered by direct calls, to document the
ability surface.

### Lifecycle UI parity and detail-page refresh

**Priority:** Medium  
**Status:** Completed for wrestlers, managers, referees, stables, tag teams, and
titles.

Detail pages render `Components/Actions` components whose `canPerform()` combines
the Gate ability with domain eligibility, and the General Info card is wrapped
in the shared `App\Livewire\Components\GeneralInfo` component so status and
related rows refresh after each action. The index tables no longer carry
unreachable lifecycle methods. Modal titles are unified (`core.modal.add` and
`core.modal.edit`: "Add X" and "Edit {name}"). Open follow-ups verified against the code:

- Stables render a lifecycle actions component (Establish, Disband, Retire,
  Unretire) on the detail page. `MergeStablesAction`, `SplitStableAction`, and
  `ReuniteAction` remain unwired (kept intentionally as planned features).
- Matches can be deleted from the event matches table, but `DeleteAction` does
  not renumber; deleted numbers leave gaps by design.

### Promotion member invitations

**Priority:** Low  
**Status:** Deferred feature.

Owners add promotion members by typing the exact email of an existing active
account; the member is attached immediately and no message is sent. The Manage
component answers every non-match (unknown, inactive, partial) with one generic
message so it does not confirm which accounts exist. An account that is already
a member gets its own message: the owner already sees every member in the list
on that page, so naming the case reveals nothing new. Adding a member, saving a
role, and suspending or reactivating a member confirm the change through the
shared flash message. A real
invitation flow (an invitation record, an email, and an accept page, using the
existing `MembershipStatus::Invited` state) is deferred.

Business rules enforced in the membership and user Actions: a promotion always
keeps at least one active owner (`EnsureAnotherActiveOwnerAction`), and the
platform always keeps at least one active administrator
(`EnsureAnotherActiveAdministratorAction`). Global administrators may still
repair a promotion that has no owner.

### Branch protection observation

**Priority:** Low  
**Status:** Operations observation.

`main` reported required checks and signed commits when last queried through the
GitHub API, while `develop` reported "Branch not protected". Protection lives in
GitHub settings and cannot be verified from the repository; see
`docs/workflows/git-workflow.md`. Confirm the intended `develop` rules there.

## Deferred from audit round 2

Shipped from the original list: searchable booking selects (v0.6.0, #1788) and trusted proxies for Cloudflare
(v0.6.1, #1795; ranges in `config/trustedproxy.php`, overridable with `TRUSTED_PROXIES`).

- **Database backstops.** CHECK and unique constraints for: one open reign per title, unique match numbers per
  event, unique referee and title per match, and date-order checks. Each needs a pre-flight migration that finds
  and repairs existing violating rows before the constraint is added.
- **Tag team availability badge.** It should reflect injured or suspended members.
- **Search indexing.** Add a `pg_trgm` index for `ILIKE` search if the tables grow.
- **Not-yet-started members.** Removing a member who has not started yet through the stable, tag team or manager
  forms can still set `left_at` before `joined_at`.
- **Booked members can still be retired or released.** Deleting a wrestler or tag team booked in an upcoming or
  unresulted match is blocked, but retiring or releasing one leaves them on the card. Decide whether that should be
  blocked too, or whether the booking should be cleaned up.
- **Previous-matches tables.** They sort and count through a correlated sub-select and scan `events` twice (the
  promotion scope plus the past-event constraint). Cost scales with a participant's own history. A fix means joining
  `events` once in `EventMatchBuilder`, which is shared by other callers, so it was skipped.
- **Table status counts are remembered, not live.** `DataTableComponent` keeps the status counts in a locked
  property and clears them on refresh and delete, so another user's changes show after the next refresh. The
  paginator still runs its own total count because reusing the remembered total could break page links.
- **Delete rejection wording.** The "cannot be deleted because it is booked in a match" message is plain text in the
  exception, like the other delete messages, and not a translation key.
- **Duplicate authorization in `BaseFormModal`.** `openModal` and `submitForm` authorize again now that
  `BaseModal::mount()` authorizes. Harmless, but one of them could go.
- **Browser test timing.** Responsive-layout tests hit intermittent 5000 ms Playwright timeouts under load. If the
  timeouts keep blocking pushes, raise the Playwright timeout deliberately. The match form tests wait for page
  conditions with `waitForScript()` (tests/Pest.php) instead of fixed sleeps; use it for new browser waits.
- **Production hardening (operator task, not code).** Set `APP_URL` to `https://`, set `SESSION_SECURE_COOKIE=true`
  explicitly (the cookie is already sent as `secure`), and add HSTS in Cloudflare. A Content-Security-Policy and a
  Referrer-Policy are separate projects.

The real invitation flow is tracked under "Promotion member invitations" above.

## Blocked dependency upgrades

Dependabot proposed these in October 2026; they are deferred on purpose, not forgotten.

- **Guzzle 8 stack and `brick/math` 1.0.** `guzzlehttp/guzzle` 7 to 8, `guzzlehttp/promises` 2 to 3,
  `guzzlehttp/psr7` 2 to 3 and `brick/math` 0.19 to 1.0 arrived inside a dev-dependency Dependabot group PR (#1777,
  closed). They are production packages with major versions, so review each on its own, with the HTTP client and any
  big-number usage checked, and not as a side effect of a tooling bump.
- **`@eslint/js` 10.** Unblocked: ESLint 10 is installed (#1791) and `@eslint/js` 10 requires `eslint ^10`. The
  project is still on `@eslint/js` 9.39.5, which works with ESLint 10 but should be moved up to match.

Resolved: ESLint 10 (#1791), Vite 8 with `laravel-vite-plugin` 3 (#1794), and the Pest 5.3 update (#1793). Vite and
`laravel-vite-plugin` must move together because the plugin's 2.x line only supports Vite 7, which is why the
standalone Vite Dependabot PR failed on npm peer resolution. Consider grouping the two in `.github/dependabot.yml`.

## Considered and rejected

These were evaluated during the September 2026 refactor series and deliberately
not done. Revisit only if the stated reason stops being true.

### Generic base for per-entity roster Actions

Wrestler, Manager, and Referee `Suspend`, `ClearFromInjury`, `Retire`, and
`Release` Actions are near-identical apart from the model type. Each body is
about five lines (transaction, owner lock, eligibility check, period write), and
the Actions rules require typed per-entity Actions with owner locking. A shared
base would save little code and weaken the typed boundaries those rules protect.

### Removing the all-`false` policy methods

Most policy methods return `false` because `PromotionGate` (via `Gate::before`)
makes the real decision. They look redundant, but `.ai/rules/policies.md`
requires conventional signatures and they document the ability surface for each
model. Related: `PromotionPolicy` instance abilities can never be reached through
the Gate, because `PromotionGate` always decides for a `Promotion` subject. They
are kept and covered by direct policy tests.

## Research notes

The following Laravel sources informed this backlog:

- [Laravel 13 Eloquent resources](https://github.com/laravel/docs/blob/13.x/eloquent-resources.md)
- [Laravel Eloquent collections and custom collections](https://laravel.com/docs/master/eloquent-collections)
- [Laravel Eloquent APIs and query scopes](https://laravel.com/docs/13.x/eloquent)
- [Laravel framework issue: custom collections and BelongsTo relations](https://github.com/laravel/framework/issues/53241)
- [Livewire actions and server-side authorization](https://livewire.laravel.com/docs/4.x/actions)
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
