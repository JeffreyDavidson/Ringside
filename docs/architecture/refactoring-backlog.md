# Refactoring and Laravel Pattern Backlog

This is the living backlog for application-wide architecture improvements. New
items should be added here with a short rationale, affected boundary, and a
small implementation slice. A pattern should only be adopted when existing
code demonstrates a repeated need for it.

## Active refactoring slices

### Model status API boundary

**Priority:** High  
**Status:** In progress; employment and activity state reads are centralized in lifecycle readers, with projected-boolean inspection shared by both boundaries. The redundant `hasActivityPeriods()`, `hasFutureActivity()`, `isCurrentlyActive()`, `hasNoCurrentOrFutureEmployment()`, `isEmployed()`, `isRetired()`, `isReleased()`, and `employedOn()` predicates have been removed in favor of typed relationship queries, lifecycle builders, and the computed employment status enum. `isInjured()`, `isSuspended()`, `hasFutureEmployment()`, and `hasEmploymentHistory()` exist again as projection-aware accessors: they read the `availability_*_exists` / `status_*_exists` attribute when a query or `loadExists()` projected it and fall back to an `exists` query otherwise, so status badges avoid per-row queries (see `builders.md`).

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
**Status:** Ongoing; the promotion-scoped name-conflict query (`FiltersByNameInPromotion`) and the stable-join constraints (`joinableToStable()`, `mergeCandidatesFor()`) are shared builder scopes (see `builders.md`).

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

The database queue is configured and its `jobs`, `failed_jobs` and `job_batches` tables exist, so the first queued
job that fails will be recorded instead of throwing. The application has no queued jobs yet.

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
context identifiers, that Livewire components neither create records through
factories nor write directly through Eloquent models, and that
`MembershipRole::CONTENT_ABILITIES`, the policy methods and the lifecycle enums'
`ability()` values stay in agreement (`PolicyAbilityArchitectureTest`).

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

### Dead application code cleanup (phase 1)

**Priority:** Medium  
**Status:** Completed.

Removed code that only tests called: the `TitleChampionshipQuery` reporting
methods beyond `currentChampion()` and `reignLengthInDays()`, `Promotion::hasActiveMember()`
and `hasMemberWithRole()` (tests use helpers in `tests/Helpers/TestHelpers.php`), the
`LifecyclePeriodBuilder` instance scopes (the static `constrainTo*()` helpers remain),
`withActivityStatusState()` and `withAvailabilityState()` (use `withExists()` or
`loadExists()` with the `*_STATE` constants), `LifecycleStateReader::readProjectedBooleans()`,
`TagTeamMembershipData::combinedWeightInPounds()`, the Title `activate`/`deactivate`,
Promotion `forceDelete` and User `changeUserRoles`/`viewAuditLogs` policy abilities, and
Livewire events nothing listens to. The `'promotion_context'` closure scopes became the
`PromotionContextScope` and `EventMatchPromotionContextScope` classes.

Open follow-up: `BaseFormModal::openModal()` and `isModalOpen` are only called by tests
(the modal package mounts components with `mount()`), but about 250 modal test call sites
depend on them; remove them together with a rewrite of those tests.

### Dead application code cleanup (phase 2)

**Priority:** Medium  
**Status:** Step 5 completed.

Step 5 extracted the period-closing sequence the wrestler, manager, referee, and tag team
`Release` and `Retire` Actions each repeated into `CareerPeriodCloser`
(`app/Lifecycle/Periods`), a small typed collaborator beside `DeletionPeriodCloser`. This is
deliberately not the rejected generic base for per-entity Actions: each Action keeps its own
transaction, owner lock, eligibility check, retirement start, and cascade. The stable and
title `Retire` Actions close only an activity period and were left alone. The test-only
`retireMembers` flag on the tag team `RetireAction`, the `unretireMembers`, `employImmediately`
and `requireAvailablePartners` flags on the tag team `UnretireAction`, and the
`establishImmediately` and `requireFormerMembers` flags on the stable `UnretireAction` were
removed. Follow-up: the `requireAvailablePartners` and `requireFormerMembers` parameters on
`TagTeamRetirementEligibility` and `StableRetirementEligibility` are now only exercised by
eligibility tests; remove them after the in-flight eligibility query changes land.

Activity period history: `StartActivityPeriodAction` and `EndActivityPeriodAction` now accept an
optional `LifecycleTransitionType` (and a context array for notes or the planned end date) and
record the `Activity` transition in the period's own transaction, as the employment, injury,
suspension, and retirement period managers do. Stable `Establish`, `Disband`, and `Reunite` and
title `Debut`, `Pull`, and `Reinstate` no longer call `RecordLifecycleTransitionAction` themselves.
`MergeStablesAction` and `SplitStableAction` keep their manual calls: each records a pair of related
`Merged` or `Split` transitions on two stables, and only the secondary stable's activity period is
touched by a merge, so moving that one record onto the period end would reorder the pair.

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
  Unretire, Merge, Split, Reunite) on the detail page; merge, split and reunite
  open modals (see `stable-membership.md`).
- Matches can be deleted from the event matches table, but `DeleteAction` does
  not renumber; deleted numbers leave gaps by design.

### Promotion member invitations

**Priority:** Low  
**Status:** Shipped.

Owners invite a member by typing an email address: the invitation is saved in
`promotion_invitations`, keyed by the email (no account is needed, and the form
never reveals whether one exists), and the person joins only by accepting it in
the application after signing in with that email (see "Promotion Context and
Membership" in `core-capabilities.md`). No open gaps remain; platform
administrators without a membership see their invitations in the sidebar.

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
`docs/workflows/git-workflow.md`. Confirm the intended `develop` rules there. Since October 2026 `main` requires every
Application Quality job (including `MySQL tests`) and the `Ward security scan` job.

## Deferred from audit round 2

Shipped from the original list: searchable booking selects (v0.6.0, #1788) and trusted proxies for Cloudflare
(v0.6.1, #1795; ranges in `config/trustedproxy.php`, overridable with `TRUSTED_PROXIES`).

- **Search indexing.** Add a `pg_trgm` index for `ILIKE` search if the tables grow.
- **Booked members can still be retired or released.** Retiring or releasing a booked wrestler, referee or tag team is
  allowed, and the confirmation now lists the upcoming events they are booked in. Still open: mark booked competitors
  who are no longer bookable on the event page.
- **Previous-matches tables.** They sort and count through a correlated sub-select and scan `events` twice (the
  promotion scope plus the past-event constraint). Cost scales with a participant's own history. A fix means joining
  `events` once in `EventMatchBuilder`, which is shared by other callers, so it was skipped.
- **Table status counts are remembered, not live.** `DataTableComponent` keeps the status counts in a locked
  property and clears them on refresh and delete, so another user's changes show after the next refresh. The
  paginator still runs its own total count because reusing the remembered total could break page links.
- **Browser test timing.** The intermittent 5000 ms Playwright timeouts had two causes, both fixed: the app layout
  loaded a render-blocking Google Fonts stylesheet (Inter is now self-hosted and a test asserts no request leaves the
  app host), and Alpine UI's combobox refocused its search box a frame after an option was chosen, which stole
  keystrokes from the next field (#1807). Wait on page conditions with `waitForScript()` (tests/Pest.php), never fixed
  sleeps. If responsive-layout tests start timing out again under load, raise the Playwright timeout deliberately.
- **Production hardening (operator task, not code).** Referrer-Policy and Permissions-Policy are sent since v0.7.0.
  Still open: set `APP_URL` to `https://`, set `SESSION_SECURE_COOKIE=true` explicitly (the cookie is already sent as
  `secure`), add HSTS and a TLS 1.2 minimum in Cloudflare, and drop the duplicate `X-Content-Type-Options` line from
  the Forge nginx config. A Content-Security-Policy is a separate project because Livewire and Vite need nonces or
  hashes. The full operator checklist is `docs/workflows/production-operations.md`.

## Deferred from audit round 3

Round 3 (shipped in v0.7.0) made the application work on production's MySQL 8 and closed the findings of four audits.
These items were deliberately left open.

**MySQL**

- **MySQL collation.** MySQL's default collation is case- and accent-insensitive, so "Foo" and "foo", or "Café" and
  "Cafe", count as duplicates and surface as a validation error. This is expected and unlike PostgreSQL and SQLite.

**Booking form**

- The match form's title dropdown still lists every promotion's titles to an administrator without a membership. A
  foreign title is rejected by validation and by the action, so this is a convenience issue only.
- Unknown combobox labels show a neutral "Selected record" and are not resolved server-side. A forged non-array
  competitor side throws a `TypeError` instead of a validation error, like a tampered match type throws `ValueError`.
- The roster combobox opens on click or typing, not on focus. Opening on focus brought back the focus-stealing problem.
- Hardcoded English remains in the table delete confirmations ("Remove X?"), the modal header's "Close dialog" and some
  labels in the match form modal.
- `BaseFormModal` authorizes when the modal mounts, opens and saves. The save-time check is deliberate and tested: a
  member who is downgraded or suspended after opening a form cannot save it.

**Accounts**

- Every demote or deactivate request locks the (few) active administrator rows, even when the target is not an
  administrator, so the "keep an active administrator" decision never relies on a stale copy of the user.

**Tests and tooling**

- CI headroom, checked October 2026: the PostgreSQL job takes about 6 of its 15 minutes and the MySQL job about 8
  of its 15 (5 to 8, varying by run).

## Blocked dependency upgrades

Dependabot proposed these in October 2026; they are deferred on purpose, not forgotten.

- **Guzzle 8 stack and `brick/math` 1.0.** `guzzlehttp/guzzle` 7 to 8, `guzzlehttp/promises` 2 to 3 and
  `guzzlehttp/psr7` 2 to 3 (Dependabot PR #1777, closed) are deferred on purpose and ignored in
  `.github/dependabot.yml` until Laravel, Nightwatch or another dependency requires the new majors. The application
  makes no `Http::` calls; only the framework, Nightwatch (disabled in `.env.example`) and `ramsey/uuid` depend on
  them, and the Guzzle 8 changes (stricter request options, exception hierarchy, redirect and auth handling, native
  types) cannot be validated by this suite. The three move together, because Guzzle 8 forces promises 3 and psr7 3.
  `brick/math` 1.0 is identical to 0.20 apart from removing `UnsupportedPlatformException` and `of()` throwing
  `NumberFormatException` for `'2/0'`, so it is allowed through. If the Guzzle stack is ever upgraded, run
  `composer update guzzlehttp/guzzle guzzlehttp/promises guzzlehttp/psr7 --with-all-dependencies` in one PR, then the
  full suite, Larastan and Rector, a Nightwatch agent smoke test, and a real test mail if a mail transport is
  configured.

Resolved: ESLint 10 with `@eslint/js` 10 (#1791, #1800), Vite 8 with `laravel-vite-plugin` 3 (#1794), and the Pest 5.3
update (#1793). Vite and `laravel-vite-plugin` must move together because the plugin's 2.x line only supports Vite 7,
which is why the standalone Vite Dependabot PR failed on npm peer resolution. The two are now grouped in
`.github/dependabot.yml`, so they arrive as one pull request.

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
