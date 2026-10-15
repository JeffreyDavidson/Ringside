# Reviewing Tests

Check every item in this file. A passing test may still provide no value. For each test, identify the defect it would catch.

Report each finding. Do not delete or rewrite a test without the user's approval. When an issue appears throughout the suite as a convention, report the pattern once rather than every affected file.

## Test Value

Apply this section to behavioral tests. An architecture test states a convention for a directory, so these items do not apply to it.

- [ ] Each test covers observable behavior or an application contract, and passes after a change to the implementation that keeps the behavior.
- [ ] Each tested declaration is exercised through behavior, and no test asserts the behavior of the framework. A test of what this project configures, such as a relation with a constraint, a cast, or a scope, belongs to this project.
- [ ] Each test protects a distinct meaningful contract or boundary. Check related lower-layer coverage before calling it redundant; similar assertions may still protect HTTP wiring. Recommend consolidations rather than removing coverage during a read-only audit.
- [ ] Every changed decision and each applicable high-value failure mode has coverage.

## Names and Structure

- [ ] New and materially rewritten behavior tests have explicit Arrange/Act/Assert comments, with blank lines between phases.
- [ ] HTTP target operations assign `$response`; requests and state-changing operations are not chained into assertions or hidden in assertion callbacks.
- [ ] Factory and double chains put each method call on its own line. Installed Pest helpers are used where available, including time travel; `test()` is not a workaround for avoiding them.
- [ ] Each file has the name `{ClassName}Test.php` and the relative path of the class under test.
- [ ] Each name states a result, the condition that causes it, and the status code for an API error.
- [ ] Each file uses one declaration style consistently, and each `describe()` group holds separate behavior.
- [ ] Each test has one scenario and target action; scenario-dependent setup/assertion branches are split. Review `if`/`elseif`, `switch`, `match`, ternaries, callbacks and helpers by purpose, not syntax alone.
- [ ] Repeated Arrange/Act/Assert phases do not combine independently testable saves, reads or transitions. Any retained sequence needs the named defect and a clear baseline/target distinction.
- [ ] Dataset rows repeat the same contract and assertion structure; meaningful labels identify failures. Status/side-effect branches are not concealed in expected-status values or callable datasets.
- [ ] Arrange has blank lines between meaningful fixture, contrast, actor/context, fake and payload groups, while preserving dependencies and insertion order.
- [ ] Setup helpers' requests and side effects are accounted for; no tests share mutable records or rely on order.

## Coverage

- [ ] Each registered controller handler and distinct HTTP contract maps to meaningful success and applicable failure cases; a matching file alone is not coverage. Record the route/handler/case map from `controllers.md`.
- [ ] HTTP tests cover authentication, authorization, role, scope, and validation when applicable.
- [ ] Controller Feature tests exercise named registered routes with relevant middleware, binding, FormRequests, policies/gates and internal Actions intact. Any scoped bypass is explained and does not count as coverage of the bypassed behavior; external doubles preserve the real internal path.
- [ ] Refusal/validation tests fail for the named reason: correct actor/target/scope, otherwise valid input and specific response/error assertions. Missing binding, validation or guest redirects do not masquerade as policy-denial tests.
- [ ] Read handlers assert actual returned records/fields and applicable exclusion, filtering, ordering, pagination and empty-state contracts with deterministic fixtures, not just a successful status.
- [ ] A request for a record of a different tenant gets a status code that does not confirm that the record exists.
- [ ] Policy/gate tests own the complete pure permission matrix; HTTP tests retain each handler's distinct ability/resource-argument, middleware, role and scope paths. No blanket single-refused-role or single-endpoint-case limit removes meaningful HTTP wiring coverage.
- [ ] Each validation rule has one test that asserts the user-visible message. When a lower layer owns a matrix, identify which HTTP wiring cases are still distinct before recommending a focused consolidation.
- [ ] Rendered user input and each dynamic part of a query have a security test.

## Data and Determinism

- [ ] Each test creates its mutable records directly or through a helper that it calls, and every created record arranges the behavior or supports an assertion.
- [ ] Shared setup follows explicit project conventions and does not conceal alternative scenarios or a large workflow.
- [ ] Each factory state and each relationship gives the meaning of the data.
- [ ] Each call to `make()` is in a test that does not need the database.
- [ ] Time, randomness, sleep, and outbound HTTP are controlled.
- [ ] Each test passes alone, and passes in the complete suite in any order.

## Assertions

- [ ] Each expected value is a known value, and the test does not calculate the value with the logic of the implementation.
- [ ] Each test of a write operation asserts the response, the state in the database, and the side effects.
- [ ] Each fake has meaningful dispatch/non-dispatch assertions and is specific where appropriate. Payload and exact-count assertions can be complementary; one assertion per fake is not a limit.
- [ ] Each `expect()->and()` chain groups related facts about one outcome and remains easy to follow. Different subjects alone are not a finding; unrelated outcomes use separate chains or scenarios.
- [ ] Native Pest/framework assertions are retained; no PHPUnit substitution, filler comments or tooling change is used merely to evade Rector expectation chaining.

## FormRequest Contracts

For request or consumer changes, read [FormRequest contracts](form-requests.md). Perform the contract/coverage scan before expensive full verification, then retain all required checks.

- [ ] Each affected request maps to its consuming named routes, HTTP verbs/handlers and concrete preparation/validation/payload/refusal cases, including expanded dataset rows and relevant unchanged consumers.
- [ ] The actual middleware/preparation/failure path establishes expected normalization and redisplayed input; model casts alone do not prove it. Applicable missing/null/empty/non-string, presence/boolean and method-specific cases preserve meaningful input distinctions.
- [ ] Validated data reaches the real handler/workflow with contractual defaults, nested key handling, identities/coercions and scope intact. No tests merely repeat rule declarations or invent requirements for an intentionally input-free request.
- [ ] Redirected form refusals assert specific errors, preserved nonsensitive old input, sensitive-input exclusions and unchanged state/effects. Explicit-null preservation distinguishes absent keys; API JSON failures do not acquire invented session contracts.
- [ ] Stored JSON comparisons ignore incidental object-key order while retaining meaningful list order, duplicates and strict scalar types, with verification limits for untested database engines recorded.

## Controller Architecture

For controller work, read `controllers.md` and review these separately from behavior coverage.

- [ ] Each concrete controller is a singularly named resource controller with only standard resource handlers, or a verb/subject action controller with only `__invoke()` for a distinct action outside those operations. Standard resource operations are not split into invokables within the same HTTP context.
- [ ] Related controllers and registered routes for the affected resource/context are inspected together; resource grouping is an optional clarity improvement unless explicit project guidance requires it.
- [ ] Controllers coordinate HTTP behavior; substantial business workflows or reusable query logic are reviewed against appropriate existing layers. Straightforward reads/writes do not trigger mandatory Actions, ViewModels, line-count limits or one-use wrappers.
- [ ] Nested controller directories contain only actual HTTP controllers and justified bases; no helpers, traits, DTOs, queries, services, interfaces or enums are misplaced there.
- [ ] No empty application base Controller is created or retained, and no inheritance requirement forces its existence.
- [ ] Every concrete controller has the matching Feature file at the complete mirrored path; abstract bases alone are exempt.
- [ ] Actual HTTP tests establish applicable response, authorization, validation, state and effect contracts; failures/replays protect unchanged state when relevant.
- [ ] Report whether architecture enforcement actually ran, is missing, or remains proposed. Naming/shape checks do not establish semantic responsibility.

## Middleware Contracts

For middleware work, read [middleware contracts](middleware.md) and review behavior and wiring separately from declarations.

- [ ] Purpose/action names omit the `Middleware` suffix; `Ensure` applies to guards rather than every responsibility.
- [ ] One coherent request/response concern stays in middleware; model permissions and business workflows retain appropriate existing owners without arbitrary IO or class-size bans.
- [ ] Framework authentication, authorization, signature, throttling and verification capabilities are reused where sufficient; custom replacements have a concrete reason.
- [ ] Aliases, global/group/route assignments, exclusions, parameters and meaningful execution-order dependencies are traced using the installed version's registration layout. Route assignments remain outside controllers.
- [ ] Every concrete application-owned middleware has executable matching full-path Feature coverage, including actual registered wiring; isolated continuation/test-route cases supplement rather than replace it.
- [ ] Assertions establish applicable allowed/refused behavior and prevented downstream effects, observed request context, or intended response changes with relevant status/body/header/cookie preservation.
- [ ] Negative cases fail for the named middleware condition with otherwise valid prerequisites, specific browser/API responses and relevant unchanged state/effects.
- [ ] The applicable stack/registration-to-case inventory identifies meaningful parameter, exclusion and order variants. AAA, `$response`, named routes, related `->and()` chains, intact target middleware and explicit execution limits are retained.

## Model Contracts

For model work, read [model contracts](models.md) and inspect the relevant schema, factories, callers and behavior.

- [ ] Singular domain class names omit the `Model` suffix, and relationship names match actual meaning/cardinality, keys and mappings.
- [ ] Appropriate persistence and single-record behavior stay in models; substantial workflows/integrations and reusable queries retain existing owners without arbitrary wrappers. Native attributes, explicit Builder/scope layouts and package contracts are preserved.
- [ ] Real records establish relationship ownership, pivot/polymorphic behavior and applicable tenant, ordering, soft-delete/restore/permanent-delete boundaries, including relevant excluded records.
- [ ] Meaningful casts/defaults are reached through actual persistence and reloading with independent expected values, raw representations where contractual, and relevant null/precision/normalization/time boundaries. Factory overrides do not masquerade as default tests.
- [ ] Privileged-field write paths and defined serialization contracts are exercised at their actual boundaries; guarded tests do not use factory/forceFill/unguarded bypasses. Contradictory field policies are flagged, and model tests do not replace HTTP authorization or outward privacy tests.
- [ ] Mirrored Integration coverage (or explicit project suite convention) uses real queries, minimal deterministic factories and visible refreshed state. No placeholder generic-ORM tests or forced HTTP endpoints are added; retain a model-contract-to-case map and report actual execution/engine limits.

## Style Completion

Review every added or materially rewritten test against this checklist before completion, even when the suite, formatter and static analysis pass. Classify each applicable check as satisfied, a specific justified exception, or a finding; do not silently treat nearby legacy style as an exception. Preserve necessary comparison/replay sequences, but make their baseline and target explicit. A passing suite is evidence of tested behavior, not evidence that the approved style was followed.

## Audit Output and Evidence

Inventory each test and named dataset case in the requested scope. For controller work, also retain the route/handler/contract-to-case map; for middleware work, retain the middleware/registration/variant-to-case map; for model work, retain the model/schema-contract-to-case map; for FormRequest work, retain the request/consumer/contract-to-case map. Include missing cases and justified not-applicable boundaries. For each finding, record:

| Item | Evidence to record |
| --- | --- |
| Case | File, test name and dataset label; identify hidden setup requests. |
| Protected contract | The concrete defect it catches and the boundary exercised. |
| Structure | Mixed scenarios, repeated AAA, Arrange grouping, or a justified sequence. |
| Overlap | The related case/lower layer and whether it protects the same boundary. |
| Recommendation | Keep, consolidate, rewrite or remove; proposed target cases and preserved assertions. |
| Validation | Actual commands, counts, head SHA/CI evidence and measured runtime, or Unknown. |

Summarize recurring patterns with precise examples; keep the full inventory available when requested. Distinguish confirmed gaps from suspected ones. A grep hit, passing test, source count or YAML validator alone does not prove test effectiveness.

For an authorized rewrite, map old scenarios and original input combinations to the proposed cases and preserve meaningful response/state/effect/privacy/performance assertions. More declarations can be correct when splitting a mixed test. Measure changed-file runtime and fixture cost if performance is part of the task; readability alone does not prove faster CI. Never weaken coverage or security gates to make a refactor pass.
