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

- [ ] Each file has the name `{ClassName}Test.php` and the relative path of the class under test.
- [ ] Each name states a result, the condition that causes it, and the status code for an API error.
- [ ] Each file uses one declaration style consistently, and each `describe()` group holds separate behavior.
- [ ] Each test has one scenario and target action; scenario-dependent setup/assertion branches are split. Review `if`/`elseif`, `switch`, `match`, ternaries, callbacks and helpers by purpose, not syntax alone.
- [ ] Repeated Arrange/Act/Assert phases do not combine independently testable saves, reads or transitions. Any retained sequence needs the named defect and a clear baseline/target distinction.
- [ ] Dataset rows repeat the same contract and assertion structure; meaningful labels identify failures. Status/side-effect branches are not concealed in expected-status values or callable datasets.
- [ ] Arrange has blank lines between meaningful fixture, contrast, actor/context, fake and payload groups, while preserving dependencies and insertion order.
- [ ] Setup helpers' requests and side effects are accounted for; no tests share mutable records or rely on order.

## Coverage

- [ ] HTTP tests cover authentication, authorization, role, scope, and validation when applicable.
- [ ] A request for a record of a different tenant gets a status code that does not confirm that the record exists.
- [ ] The complete permission matrix belongs in policy tests, not controller tests.
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
- [ ] Each `expect()` chain stays on one subject.

## Audit Output and Evidence

Inventory each test and named dataset case in the requested scope. For each finding, record:

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
