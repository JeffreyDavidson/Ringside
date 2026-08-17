# Lifecycle Operation Boundaries

## Purpose

This document defines the architectural boundaries for roster lifecycle operations. It establishes where logic belongs before the existing implementation is refactored.

This is a structural decision, not a redesign of the underlying business rules. Existing transition eligibility, cascade behavior, historical records, dates, and transaction guarantees must remain unchanged until each rule is reviewed separately.

## Core Decision

Ringside will share lifecycle mechanics without collapsing entity-specific behavior into a universal abstraction.

Wrestlers, managers, referees, tag teams, stables, and titles may share operations such as starting an employment period or ending a suspension. Their eligibility rules and related-entity consequences can still differ. Shared persistence mechanics and entity-specific orchestration therefore belong at different boundaries.

## Lifecycle Dimensions

Roster lifecycle data is not one mutually exclusive state machine. It contains multiple dimensions that can overlap:

- Employment records describe the entity's relationship with the promotion.
- Retirement records describe whether a career or entity is retired.
- Suspension records describe disciplinary availability.
- Injury records describe medical availability.
- Deletion describes record lifecycle and cleanup.

For example, an employed wrestler may also be injured or suspended. Active injury and suspension periods are mutually exclusive: a roster member must be healed before suspension and reinstated before injury. A single universal status enum or state machine must not erase the underlying lifecycle histories.

Recovery operations are also distinct. Healing ends an injury period, while reinstatement ends a suspension period. An injured roster member cannot be reinstated as a substitute for medical clearance.

An explicit state machine may still be appropriate for an individual dimension when its transition graph provides clear value. That decision must be made separately for each lifecycle dimension.

## Responsibility Boundaries

### Actions

An action is a typed application entry point for one explicit business operation.

Examples include employing a wrestler, retiring a tag team, or deleting a manager. Actions accept concrete domain types, enforce the operation's application-level sequence, and expose a single `handle()` method.

Actions may coordinate shared lifecycle mechanics and related-entity consequences. They must not become generic dispatchers based on model class names, method discovery, or transition strings.

### Shared lifecycle mechanics

Shared lifecycle components perform one reusable persistence responsibility, such as:

- starting or ending an employment period;
- starting or ending a retirement period;
- starting or ending a suspension period;
- starting or ending an injury period;
- closing active lifecycle records during deletion.

These components own consistent record mutation and effective-date handling. They participate in the transaction established by the coordinating action or workflow so the primary mutation and its cascades remain atomic. They do not decide every entity's eligibility or discover supported capabilities dynamically.

### Workflows

A workflow coordinates multiple independently meaningful actions across a business use case or multiple entities.

Workflows are appropriate when the operation is more than one entity action with internal implementation steps. Examples could include a future roster overhaul or event-preparation process that coordinates several existing actions.

Workflows should call actions. Actions should not depend on high-level workflows.

### Cascades

A cascade applies the related-entity consequences of an operation.

Examples include employing a tag team's members, ending a wrestler's professional relationships during retirement, or reinstating eligible team members. Cascade behavior should be expressed through typed collaborators when reuse or complexity justifies extraction.

Callables may be replaced incrementally. A cascade must not hide unsupported model operations behind `method_exists()` checks when a domain contract can express the requirement.

### Relationship cleanup

Ending an entity's own current relationships is a typed domain action, not a configurable cascade strategy. The manager, tag-team, and wrestler domains each expose an `EndCurrentRelationshipsAction` whose `handle()` method accepts the concrete entity and effective date.

These actions update only active relationship records and preserve historical rows. They do not validate lifecycle eligibility, resolve dates, own transactions, mutate lifecycle periods, or invoke high-level actions. The coordinating retire, release, or delete action owns those responsibilities.

Relationship cleanup remains distinct from a true related-entity cascade. A cascade may invoke another entity's complete typed lifecycle action when the related entity must undergo its own business transition; relationship cleanup only dates the current relationship records.

Tag-team retirement demonstrates this distinction. `RetireCurrentMembersAction` receives a concrete tag team and retirement date, identifies eligible current wrestlers and managers, and invokes each member's complete typed retirement action. The coordinating tag-team retirement action decides whether member retirement was requested and owns the surrounding transaction.

Tag-team unretirement follows the same boundary through `UnretireCurrentMembersAction`. It attempts each retired current member's complete typed unretirement independently, preserving the established rule that one member's failure does not prevent other eligible members or the team from returning. Optional immediate team employment remains an explicit decision in the coordinating tag-team action and delegates to the existing employment action.

Wrestler unretirement does not require a cascade collaborator merely to choose whether employment follows. The wrestler action owns that explicit option and calls its typed employment action directly when requested.

Employment does not implicitly end retirement. Wrestlers, managers, and referees must first pass through their explicit unretirement action before they can be employed. Individual employment predicates delegate to the same typed guards used by their actions, including current employment, future employment, and retirement checks.

Employment cascades use explicit capability and domain collaborators. Both tag teams and wrestlers delegate current-manager employment to `Managers\EmployCurrentManagersAction`, which accepts the existing `Manageable` contract. Tag teams separately delegate current-wrestler employment to `TagTeams\EmployCurrentWrestlersAction`. Each collaborator invokes complete typed employment actions only for currently unemployed related entities.

Membership persistence remains separate from employment orchestration. `ManagerAssignmentService` establishes and synchronizes manager relationships for any model implementing `Manageable`, dates assignments that have ended, and preserves their historical pivot rows. `TagTeamMembershipService` owns the corresponding tag-team wrestler relationships and delegates manager persistence to that shared service. Eligibility is validated before the Action receives its data, while wrestler and tag-team Actions coordinate typed employment cascades when an employment date is supplied.

Stable membership persistence follows the same boundary. `StableMembershipService` adds, ends, and synchronizes wrestler and tag-team membership records while preserving history. `MergeStablesAction` owns the merge workflow and composes those persistence operations inside its transaction before deleting the secondary stable.

Tag-team suspension delegates eligible current wrestler and manager transitions to `SuspendCurrentMembersAction`. The collaborator invokes each member's complete typed suspension action, while the tag-team action retains its own validation, suspension-period persistence, effective-date handling, and transaction boundary.

Tag-team reinstatement mirrors that boundary through `ReinstateCurrentMembersAction`, which invokes complete typed reinstatement actions only for suspended current wrestlers and managers. The coordinating tag-team action retains validation, suspension-period persistence, date handling, and transaction ownership.

Stable unretirement changes only the stable's retirement and activity state. It does not infer which former wrestlers or tag teams should be unretired because the application does not record whether an individual retirement was caused by the stable. Those member transitions remain explicit operations.

Stable restoration only restores the soft-deleted record and preserves its historical state. Reunion and activation remain explicit subsequent operations, so restoration does not depend on current former-member availability.

Stable retirement eligibility is isolated in `StableRetirementEligibility`. Retirement and unretirement remain paired as opposite transitions of the retirement lifecycle dimension. The collaborator owns stable state, soft-deletion, name-conflict, and optional former-member availability rules; the Actions continue to own retirement-period persistence, dates, transactions, and establishment orchestration.

Stable deletion eligibility is isolated in `StableDeletionEligibility`. Deletion requires the stable to have already been disbanded and its current memberships to have already ended, so `DeleteAction` changes only the soft-deletion state. Restoration remains paired with deletion and restores only the historical record without reuniting it.

### Transition policies

A transition policy decides whether a lifecycle transition is permitted and provides the relevant domain failure.

Policies may remain close to models while rules are straightforward. They may be extracted when rules are reused or complex enough to earn a separate domain boundary. Persistence components must not select validation methods from transition strings.

### Pipelines

A pipeline is reserved for a genuinely composable sequence of ordered stages where each stage receives and passes explicit context.

A class is not a pipeline merely because it performs validation, mutation, and follow-up work in a fixed order. Fixed orchestration belongs in an action, workflow, or focused lifecycle component.

### State machines

A state machine defines the valid states and transitions for one lifecycle dimension.

State machines should be introduced only after that dimension's states, guards, and transitions have been reviewed. Ringside will not introduce one universal roster state machine spanning employment, retirement, suspension, injury, and deletion.

## Dependency Direction

The intended dependency direction is:

```text
Controller or Livewire component
    -> typed Action
        -> transition policy
        -> shared lifecycle mechanic
        -> typed cascade collaborators

Workflow
    -> multiple typed Actions
```

Shared lifecycle mechanics must not resolve high-level actions or workflows dynamically. Cascade collaborators may call another typed action when the related entity must undergo its own complete business operation.

## Current Implementation Assessment

The existing concrete classes under `app/Actions/{Domain}` are the active application boundary and should be preserved during the initial refactor.

The removed `StatusTransitionPipeline` combined:

- dynamic transition selection;
- dynamic validation discovery;
- lifecycle-record persistence;
- transaction ownership;
- arbitrary cascade callbacks.

It behaved as a generic transition executor rather than a composable pipeline, so its responsibilities were separated by lifecycle dimension without changing behavior.

Injury persistence uses the typed `InjuryPeriodManager`, which atomically records `Injured` and `Healed` lifecycle audits only when requested by the corresponding typed Actions; release, retirement, and deletion may close an injury period without inventing a healing event. The database permits unlimited ended injury history while enforcing at most one open injury period per wrestler, manager, or referee. Suspension persistence uses the typed `SuspensionPeriodManager`, which atomically records `Suspended` and `Reinstated` lifecycle audits only when requested by the corresponding typed Actions; release, retirement, and deletion may close a suspension period without inventing a reinstatement event. The database permits unlimited ended suspension history while enforcing at most one open suspension period per wrestler, manager, referee, or tag team. Employment persistence uses the typed `EmploymentPeriodManager`, which atomically records `Employed` and `Released` lifecycle audits for wrestlers, managers, referees, and tag teams. Retirement persistence uses the typed `RetirementPeriodManager`, which atomically records `Retired` and `Unretired` lifecycle audits; the database permits unlimited ended retirement history while enforcing at most one open retirement period per wrestler, manager, referee, tag team, stable, or title. Concrete entity actions retain validation and effective-date resolution, then delegate only starting or ending the relevant lifecycle record. Tag-team actions also retain transaction ownership around their member cascades, and cascade collaborators invoke each related entity's complete typed action. The managers accept the relevant lifecycle contract and do not own validation, transaction orchestration, cascade behavior, or dynamic capability discovery. Release actions coordinate the relevant typed period managers directly while retaining their entity-specific cascades. Deletion actions use `DeletionPeriodCloser` to close active lifecycle dimensions while retaining transaction ownership and entity-specific relationship cleanup. With every transition migrated, `StatusTransitionPipeline` has been removed.

Lifecycle eligibility compares a model's typed computed status when an exact state is required. Models do not expose generic methods that accept arbitrary enum values or attribute names.

Retirement eligibility uses explicit typed collaborators rather than an operation-name string, generic `Model` contract, or dispatch-only strategy. `IndividualRetirementEligibility` accepts only wrestlers, managers, and referees, `TagTeamRetirementEligibility` owns tag-team partner-aware rules, `StableRetirementEligibility` owns stable former-member rules, and `TitleLifecycleEligibility` owns title transitions. Each keeps predicates aligned with the typed guards used by Actions and is evaluated against an owner row locked inside the mutation transaction.

Individual soft-deletion eligibility is shared by wrestlers, managers, and referees through `IndividualDeletionEligibility`. It rejects repeated deletion and restoration of an active record through typed business exceptions. Deletion and restoration Actions invoke the collaborator while the Eloquent models expose only their persisted soft-deletion state. Restoring an individual does not infer or recreate employment or other historical relationships; those transitions remain explicit operations.

Match deletion soft deletes only the `EventMatch` record through `DeletionStateManager`. Competitors, referee and title assignments, results, winners, losers, and championship references remain intact as historical records. Default match queries exclude deleted matches, while explicit historical queries may include them with `withTrashed()`.

Suspension eligibility follows the same typed boundary. `IndividualSuspensionEligibility` accepts only wrestlers, managers, and referees and keeps each boolean predicate aligned with its throwing guard. Each typed Action reloads and locks the individual inside its transaction before changing the suspension period. Tag teams retain their established lifecycle validation, while stables and titles do not support suspension.

Wrestler injury and suspension are mutually exclusive availability states. Both transition Actions acquire a lock on the wrestler and evaluate their opposing-state guard inside the same transaction that opens the new period. Concurrent injury and suspension requests therefore serialize on the wrestler instead of both validating stale state before either period is written.

Individual injury eligibility is shared only by wrestlers, managers, and referees through `IndividualInjuryEligibility`. Injury and healing predicates delegate to the same typed guards used by their Actions, including employment, retirement, future employment, suspension, and current injury checks. Each Action reloads and locks the individual inside its transaction before checking eligibility and changing the injury period.

Individual employment eligibility is shared only by wrestlers, managers, and referees through `IndividualEmploymentEligibility`. Employment and release predicates delegate to the same typed guards used by their Actions. Each Action reloads and locks the individual inside its transaction before checking eligibility and changing employment or related lifecycle periods. Tag teams retain their separate membership-aware employment and release rules.

Wrestlers, managers, and referees persist injury history through the shared `App\Models\Lifecycle\Injury` model and its polymorphic `injurable` owner. `IsInjurable` exposes relationship-derived state predicates on each owner, while typed injury and healing Actions retain validation, locking, transaction, and transition responsibilities. The database permits historical periods but enforces at most one open injury per owner.

Wrestlers, managers, referees, and tag teams persist suspension history through the shared `App\Models\Lifecycle\Suspension` model and its polymorphic `suspendable` owner. `IsSuspendable` exposes relationship-derived state predicates on each owner, while typed suspension and reinstatement Actions retain validation, locking, transaction, date, and cascade responsibilities. The database permits historical periods but enforces at most one open suspension per owner.

Wrestlers, managers, referees, tag teams, stables, and titles persist retirement history through the shared `App\Models\Lifecycle\Retirement` model and its polymorphic `retirable` owner. `IsRetirable` exposes relationship-derived state predicates on each owner, while typed retirement and unretirement Actions retain validation, locking, transaction, date, activity, and cascade responsibilities. The database permits historical periods but enforces at most one open retirement per owner.

Tag-team lifecycle validation uses the concrete `Wrestler` models returned by its current-wrestler relationship. It must not probe for speculative capabilities with `method_exists()`. Current wrestler employment remains valid when employing the team, while an injured current wrestler remains unavailable for tag-team unretirement. Any future exclusivity rule must be introduced as an explicit reviewed domain rule with real model behavior and tests.

Tag-team employment and release eligibility is isolated in `TagTeamEmploymentEligibility`. The collaborator owns the transition predicates and typed guards, including rejection of existing current or future employment, while the Actions lock the tag team, validate inside the transaction, and orchestrate lifecycle persistence and relationship cascades. Employment never implicitly ends retirement; callers must use the explicit unretirement transition first. Releasing a tag team ends its current partnerships, so a later employment requires a newly established current membership.

Tag-team suspension and reinstatement eligibility is isolated in `TagTeamSuspensionEligibility`. The Actions reload and lock the team before validation, then retain suspension-period persistence, dates, transaction ownership, and member cascades.

Tag-team retirement and unretirement eligibility is isolated in `TagTeamRetirementEligibility`. The collaborator owns employment, name-conflict, partner-count, and partner-availability rules. The Actions validate the locked team and retain retirement-period persistence, dates, transactions, optional immediate employment, and member cascades.

Tag-team deletion and restoration eligibility is isolated in `TagTeamDeletionEligibility`. The collaborator owns inactive-state, repeated-transition, and active-name-conflict rules. The Actions reload with trashed records, lock before validation, and retain transaction ownership, soft deletion, restoration, and relationship cleanup. Tag-team lifecycle eligibility no longer lives on the Eloquent model.

Stable activity eligibility is isolated in `StableActivityEligibility`. Establishment is the first activity transition and is available only to a stable with no activity history. Disbandment closes a current activity period, while reunion opens a later period for a previously active stable that satisfies the existing former-member availability rules. The collaborator keeps each boolean predicate aligned with its throwing guard and reports reunion failures through `CannotBeReunitedException`. The shared lifecycle `StartActivityPeriodAction` and `EndActivityPeriodAction` own period persistence used by establishment, disbandment, reunion, retirement, unretirement, and merging. Coordinating typed Actions retain the stable-specific validation, transition dates, membership consequences, and transaction boundaries. `LifecyclePeriodBuilder` centralizes open, ended, current, and scheduled period query semantics across activity, employment, injury, retirement, and suspension records.

Stable and title models expose activity history through the canonical `activityPeriods` relationship. Their factories use the corresponding activity-period models directly; the legacy `activations` relationship alias and duplicate `StableActivation` model are not part of the model boundary.

Title debut and reinstatement use the same shared lifecycle persistence Actions, while the typed title Actions retain title-specific eligibility and terminology. The starter explicitly supports rescheduling an existing future title period without creating a second period.

Title lifecycle eligibility is expressed through the typed `TitleLifecycleTransition` graph. `TitleLifecycleEligibility` maps each supported transition to its focused guard and domain exception, while the concrete title Actions retain transaction, locking, effective-date, persistence, and audit responsibilities. The transition graph does not execute mutations and is not shared with unrelated lifecycle dimensions.

Stable and title activity history is stored in the polymorphic `App\Models\Lifecycle\ActivityPeriod` model through the `activeable` owner. The `activity_periods` schema permits unlimited ended history while independently enforcing at most one open period for each owner. Operations involving multiple stables still acquire their owner locks in stable-ID order to reduce deadlock risk.

Period models remain the authoritative source for lifecycle state and effective date ranges. The polymorphic `LifecycleTransition` model is a separate immutable audit log: it records which named transition occurred, its lifecycle dimension and effective date, the authenticated user when available, and optional transition context. `LifecycleTransitionBuilder` owns chronological transition ordering and uses the record ID to make equal effective dates deterministic. Stable establishment/disbandment/reunion and title debut/pull/reinstatement write their activity transition record in the same transaction as the corresponding activity-period mutation. Transition history must not be queried as the current-state source.

`StableStatus` and `TitleStatus` are computed presentations of authoritative lifecycle periods and retirement records; neither model persists a status column. The models expose relationship-backed state facts and delegate classification precedence to `StableStatusResolver` and `TitleStatusResolver`, with retirement taking precedence over activity state. Typed Actions change lifecycle records only, and callers use canonical lifecycle predicates such as `isCurrentlyActive()` and `hasFutureActivity()` instead of duplicate status aliases.

Soft deletion remains authoritative through each model's `deleted_at` value. `DeletionStateManager` atomically changes that state and records explicit `Deleted` or `Restored` lifecycle audits for wrestlers, managers, referees, tag teams, stables, titles, events, and venues. Typed Actions retain validation, lifecycle-period closure, and relationship cleanup; the shared manager does not infer or restore those domain relationships.

Retirement transitions use the same audit boundary for every retirable model. `RetirementPeriodManager` atomically writes `Retired` or `Unretired` alongside the authoritative retirement-period mutation, while typed wrestler, manager, referee, tag-team, stable, and title Actions continue to own eligibility, locking, cascades, and broader transaction orchestration. `IsRetirable` exposes the shared transition history relationship to each supported owner.

The former stable- and title-specific status-change tables are migrated into the shared transition log as legacy activity changes before those duplicate tables are removed. New lifecycle dimensions should extend the shared transition enum and recorder in focused changes while retaining their existing period models as authoritative state.

Stable retirement and soft deletion eligibility are isolated in `StableRetirementEligibility` and `StableDeletionEligibility`. Deleted stables cannot enter retirement transitions, deletion requires the stable to be inactive and without current members, and restoration preserves historical state without implicitly reuniting members. Stable split and merge state and member-availability eligibility lives in `StableRestructuringEligibility`; their Actions own selection-specific invariants, membership mutations, activity closure, and transaction boundaries. `StableFormerMemberEligibility` owns the former-member rules shared by reunion and unretirement eligibility, keeping those business decisions outside the Eloquent model. `StableMembershipService::currentMembers()` maps the current wrestler and tag-team relationships into `StableMembershipData`, which owns the minimum weighted-headcount policy and value-level membership calculations. The former model validation concerns and unused `StableRetirementValidation` strategy have been removed.

The following generalized classes were confirmed to have no production, configuration, container, console, route, or test consumers and were removed rather than retained as foundations for the target architecture:

- `ActionPipeline`;
- `UnifiedEmployAction`;
- `UnifiedInjureAction`;
- `UnifiedReinstateAction`;
- `UnifiedRetireAction`;
- `UnifiedSuspendAction`;
- the generalized `MemberCollectionManager` and `StableMembershipOrchestrator` cluster.

Related-entity cascades use typed Actions and collaborators. Classes under `App\Models\Validation\Strategies` must be reviewed within their individual lifecycle dimensions and retained only when they provide an independently useful boundary.

## Migration Sequence

The migration must remain behavior-preserving and proceed in small pull requests:

1. Add characterization tests for lifecycle record mutations, effective dates, cascades, and transaction rollback. (Completed.)
2. Confirm whether the isolated generalized classes have any dynamic runtime consumers. (Completed.)
3. Remove unused generalized abstractions independently of the active transition path. (Completed.)
4. Extract one shared lifecycle dimension from `StatusTransitionPipeline` at a time. (Completed.)
5. Keep concrete entity actions as the public entry points while moving only shared mechanics behind them.
6. Replace callable cascades with typed collaborators where the existing reuse and complexity justify it. (Completed.)
7. Review the eligibility rules for each lifecycle dimension separately.
8. Introduce a state machine only for a dimension whose reviewed transition graph benefits from one.

Each extraction must preserve existing integration tests and add focused tests for the new boundary before the next dimension is changed.

## Guardrails

- Do not replace concrete action parameters with a generic Eloquent `Model`.
- Do not dispatch behavior from class basenames, relationship-name strings, or `method_exists()` when a typed contract is available.
- Do not combine orthogonal lifecycle dimensions into one stored status.
- Do not move entity-specific cascade rules into shared persistence components.
- Do not introduce repositories solely to wrap direct Eloquent lifecycle mutations.
- Do not add a pipeline, workflow, strategy, or state machine ceremonially.
- Do not change lifecycle rules as part of a structural extraction unless that rule change is reviewed explicitly.

## Related Documentation

- [Business Rules](business-rules.md)
- [Employment Status](employment-status.md)
- [Core Capabilities](core-capabilities.md)
- [Interface Architecture](interface-architecture.md)
- [Stable Membership](stable-membership.md)
