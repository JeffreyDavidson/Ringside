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

Employment cascades use explicit capability and domain collaborators. Both tag teams and wrestlers delegate current-manager employment to `Managers\EmployCurrentManagersAction`, which accepts the existing `Manageable` contract. Tag teams separately delegate current-wrestler employment to `TagTeams\EmployCurrentWrestlersAction`. Each collaborator invokes complete typed employment actions only for currently unemployed related entities.

Membership persistence remains separate from employment orchestration. `ManagerAssignmentService` establishes and synchronizes manager relationships for any model implementing `Manageable`, dates assignments that have ended, and preserves their historical pivot rows. `TagTeamMembershipService` owns the corresponding tag-team wrestler relationships and delegates manager persistence to that shared service. Eligibility is validated before the Action receives its data, while wrestler and tag-team Actions coordinate typed employment cascades when an employment date is supplied.

Stable membership persistence follows the same boundary. `StableMembershipService` adds, ends, and synchronizes wrestler and tag-team membership records while preserving history. `MergeStablesAction` owns the merge workflow and composes those persistence operations inside its transaction before deleting the secondary stable.

Tag-team suspension delegates eligible current wrestler and manager transitions to `SuspendCurrentMembersAction`. The collaborator invokes each member's complete typed suspension action, while the tag-team action retains its own validation, suspension-period persistence, effective-date handling, and transaction boundary.

Tag-team reinstatement mirrors that boundary through `ReinstateCurrentMembersAction`, which invokes complete typed reinstatement actions only for suspended current wrestlers and managers. The coordinating tag-team action retains validation, suspension-period persistence, date handling, and transaction ownership.

Stable unretirement changes only the stable's retirement and activity state. It does not infer which former wrestlers or tag teams should be unretired because the application does not record whether an individual retirement was caused by the stable. Those member transitions remain explicit operations.

Stable restoration only restores the soft-deleted record and preserves its historical state. Reunion and activation remain explicit subsequent operations, so restoration does not depend on current former-member availability.

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

Injury creation and explicit healing persistence use the typed `InjuryPeriodManager`. Suspension creation and explicit reinstatement persistence similarly use the typed `SuspensionPeriodManager`, employment persistence uses the typed `EmploymentPeriodManager`, and retirement persistence uses the typed `RetirementPeriodManager`. Concrete entity actions retain validation and effective-date resolution, then delegate only starting or ending the relevant lifecycle record. Tag-team actions also retain transaction ownership around their member cascades, and cascade collaborators invoke each related entity's complete typed action. The managers accept the relevant lifecycle contract and do not own validation, transaction orchestration, cascade behavior, or dynamic capability discovery. Release actions coordinate the relevant typed period managers directly while retaining their entity-specific cascades. Deletion actions use `DeletionPeriodCloser` to close active lifecycle dimensions while retaining transaction ownership and entity-specific relationship cleanup. With every transition migrated, `StatusTransitionPipeline` has been removed.

The following generalized classes were confirmed to have no production, configuration, container, console, route, or test consumers and were removed rather than retained as foundations for the target architecture:

- `ActionPipeline`;
- `UnifiedEmployAction`;
- `UnifiedInjureAction`;
- `UnifiedReinstateAction`;
- `UnifiedRetireAction`;
- `UnifiedSuspendAction`;
- the generalized `MemberCollectionManager` and `StableMembershipOrchestrator` cluster.

The remaining cascade strategy classes contain useful related-entity behavior. Their callable-based APIs are an implementation concern to improve incrementally, not a reason to duplicate their behavior across actions.

## Migration Sequence

The migration must remain behavior-preserving and proceed in small pull requests:

1. Add characterization tests for lifecycle record mutations, effective dates, cascades, and transaction rollback. (Completed.)
2. Confirm whether the isolated generalized classes have any dynamic runtime consumers. (Completed.)
3. Remove unused generalized abstractions independently of the active transition path. (Completed.)
4. Extract one shared lifecycle dimension from `StatusTransitionPipeline` at a time. (Completed.)
5. Keep concrete entity actions as the public entry points while moving only shared mechanics behind them.
6. Replace callable cascades with typed collaborators where the existing reuse and complexity justify it. (Current relationship cleanup plus tag-team retirement and unretirement completed.)
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
