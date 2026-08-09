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

`StatusTransitionPipeline` currently combines:

- dynamic transition selection;
- dynamic validation discovery;
- lifecycle-record persistence;
- transaction ownership;
- arbitrary cascade callbacks.

Although active and widely used, it behaves as a generic transition executor rather than a composable pipeline. Its responsibilities should be separated by lifecycle dimension without changing behavior.

The following generalized classes were confirmed to have no production, configuration, container, console, route, or test consumers and were removed rather than retained as foundations for the target architecture:

- `ActionPipeline`;
- `UnifiedEmployAction`;
- `UnifiedInjureAction`;
- `UnifiedReinstateAction`;
- `UnifiedRetireAction`;
- `UnifiedSuspendAction`;
- the generalized `MemberCollectionManager` and `StableMembershipOrchestrator` cluster.

The existing cascade strategy classes contain useful shared behavior. Their callable-based APIs are an implementation concern to improve incrementally, not a reason to duplicate their behavior across actions.

## Migration Sequence

The migration must remain behavior-preserving and proceed in small pull requests:

1. Add characterization tests for lifecycle record mutations, effective dates, cascades, and transaction rollback. (Completed.)
2. Confirm whether the isolated generalized classes have any dynamic runtime consumers. (Completed.)
3. Remove unused generalized abstractions independently of the active transition path. (Completed.)
4. Extract one shared lifecycle dimension from `StatusTransitionPipeline` at a time.
5. Keep concrete entity actions as the public entry points while moving only shared mechanics behind them.
6. Replace callable cascades with typed collaborators where the existing reuse and complexity justify it.
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
