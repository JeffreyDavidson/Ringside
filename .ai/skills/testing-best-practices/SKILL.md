---
name: testing-best-practices
description: "Laravel test design and review. Use when selecting coverage, naming or structuring tests, choosing assertions or test data, isolating dependencies, testing HTTP or security boundaries, improving suite performance, or reviewing test value. Use framework guidance or search-docs for Pest and PHPUnit syntax."
license: MIT
metadata:
  author: laravel
---

# Testing Best Practices

This is the project-maintained override of the Laravel testing guidance. Keep generic test design here and read the repository's scoped rules for controller formatting and matching-test requirements.

This skill provides rules for designing Laravel tests. Each rule file explains what to do and why. Use `search-docs` for Laravel and Pest API syntax.
This project uses Pest. Follow the corresponding guidance in each rule.

## Consistency First

Read nearby tests before you choose syntax and organization.

Read explicit project rules and the user's approved conventions before copying nearby patterns. Those rules take precedence over legacy examples; repeated code alone does not override an agreed standard.

Use these rules for writing and reviewing tests. Style alone does not make an existing test defective: identify the relevant agreed convention or protected contract. Explain a proposed convention change and work only within the user's approved refactor scope; an audit does not grant permission to rewrite or delete tests.

## What to Test

Read this section before you write a test.

- Test observable behavior and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every changed decision and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to framework tests. Testing project configuration is not testing the framework. A constrained relationship, cast, scope, or validation rule belongs to this project.
- Keep coverage of distinct defects and boundaries. Similar assertions in an Action test and an HTTP test can protect different wiring. Report genuine duplication and recommend a focused consolidation; do not automatically trim or delete existing coverage.
- Write a feature test first. Write a unit test only for logic that does not use the framework.
- Write a browser test only for behavior in JavaScript that a feature test cannot reach. Put a browser test in `tests/Browser`, and call `assertNoJavaScriptErrors()` in it.
- Judge an architecture test by the convention it protects, not by the rules above. An `arch()` test declares a rule for an entire directory, such as the parent class of every model, the classes that may use an enum, or the methods every factory declares. It intentionally checks declarations and fails when a new file breaks the convention.
- Use the test tools that the project installs. Add a new test dependency, plugin, or browser only after the user asks for it.

## Choose the Mode

Read the code under test, its callers, applicable scoped rules, nearby tests and setup helpers before choosing an approach. Identify the decision and defect each scenario protects, then load only the relevant references below.

- **Write:** cover the changed behavior and meaningful failure paths. Keep one target action and one coherent assertion phase per scenario; run the affected tests.
- **Audit:** remain read-only. Use `rules/review.md` to inventory tests and dataset cases, identify missing or overlapping contracts, and recommend keep/consolidate/rewrite/remove with evidence. Passing CI is validation evidence, not proof of test value.
- **Refactor:** work only within the approved files and changes. Preserve original scenarios, input combinations, fixture identity and meaningful assertions. Splitting mixed scenarios can increase the number of test declarations; compare protected contracts rather than raw counts. Run the affected tests and required project checks.

Report confirmed code defects and uncertain product decisions separately from test-style findings. A review request does not authorize fixes, test removal, new dependencies, coverage exclusions or weaker security/CI gates. Record what was inspected or executed and what remains unknown; do not describe proposed changes as implemented.

## Rule Index

Most changes need more than one rule file.

| Subject | Rule File |
| --- | --- |
| Test framework features that may already do the work | [`rules/finding-features.md`](rules/finding-features.md) |
| File layout, test names, and groups | [`rules/naming.md`](rules/naming.md) |
| Arrange-act-assert and choosing the correct assertion | [`rules/assertions.md`](rules/assertions.md) |
| Endpoint coverage, authentication, authorization, tenant isolation, validation, and browser tests | [`rules/endpoint-tests.md`](rules/endpoint-tests.md) |
| Factories, test data ownership, and repeated input values | [`rules/test-data.md`](rules/test-data.md) |
| Fakes, mocks, outbound HTTP, time, randomness, and databases | [`rules/isolation.md`](rules/isolation.md) |
| Escaping, injection, cross-tenant access, and privilege checks | [`rules/security.md`](rules/security.md) |
| Environment and CI settings for a slow suite | [`rules/performance.md`](rules/performance.md) |
| Reviewing a test or suite | [`rules/review.md`](rules/review.md) |
