---
name: testing-best-practices
description: "Laravel test design and review. Use when selecting coverage, naming or structuring tests, choosing assertions or test data, isolating dependencies, testing HTTP, FormRequest or security boundaries, improving suite performance, or reviewing test value. Use framework guidance or search-docs for Pest and PHPUnit syntax."
license: MIT
metadata:
  author: laravel
---

# Testing Best Practices

This shared skill provides reusable Laravel testing guidance. The canonical source is the shared `agent-skills` repository at `skills/testing-best-practices/`. Project-local copies are snapshots; update the shared source before refreshing those copies. Preserve the original MIT licence and attribution.

Read the target repository's instructions and scoped rules. Controller formatting, matching-test requirements, coverage targets and suite layout remain project decisions; do not impose another application's conventions.

Confirm the installed Laravel, PHP, test framework and plugin versions from the repository's manifests/lockfiles and tooling before selecting APIs. Use Boost `search-docs` when available; otherwise consult official documentation for the installed versions or inspect installed source. Missing search results are not proof that an API is unavailable.

Follow the project's Pest or PHPUnit convention. Pest examples illustrate test design, not a requirement to migrate PHPUnit suites or install plugins. Apply the same scenario, fixture and assertion principles using the existing framework. Check configured database engines, test isolation, scripts and CI gates instead of assuming SQLite, Composer script names or particular browser tooling.

## Consistency First

Read nearby tests before you choose syntax and organization.

Read explicit project rules and the user's approved conventions before copying nearby patterns. Those rules take precedence over legacy examples; repeated code alone does not override an agreed standard.

For Jeffrey's applications, new and materially rewritten behavior tests must use explicit Arrange/Act/Assert comments, a visible target action, `$response` for HTTP results, related assertions chained with `->and()`, and readable fixture/double setup. Always read `rules/assertions.md` and `rules/review.md`; controller work also requires `rules/controllers.md`, `rules/endpoint-tests.md` and `rules/isolation.md` for handler coverage, the intact HTTP path, intended failure causes, read contracts and responsibility boundaries. Middleware work also requires `rules/middleware.md` for naming, responsibilities, registration/order and behavior-plus-wiring coverage. Model work requires `rules/models.md` for naming, responsibilities, relationships, persisted casts/defaults, protected data and meaningful Integration coverage. FormRequest or request-consumer changes also require `rules/form-requests.md` for preparation, validated payloads, refusal/old-input contracts and the request-to-handler case map. Keep these requirements in the review even when tests, formatting and static analysis pass.

When refreshing project copies, copy this skill and its rule files from the shared source to every existing agent-facing copy. Preserve symlinks and project-specific rules; do not let stale snapshots or generated Pest examples silently restore an older standard. Pest syntax guides complement this skill and do not override the user's approved style. Updating guidance does not authorize refactoring application controllers/middleware/models or adding CI enforcement.

Use these rules for writing and reviewing tests. Style alone does not make an existing test defective: identify the relevant agreed convention or protected contract. Explain a proposed convention change and work only within the user's approved refactor scope; an audit does not grant permission to rewrite or delete tests.

## What to Test

Read this section before you write a test.

- Test observable behavior and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every changed decision and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to framework tests. Testing project configuration is not testing the framework. A constrained relationship, cast, scope, or validation rule belongs to this project.
- Keep coverage of distinct defects and boundaries. Similar assertions in an Action test and an HTTP test can protect different wiring. Report genuine duplication and recommend a focused consolidation; do not automatically trim or delete existing coverage.
- Write a Feature test first for HTTP application behavior. Use the configured Integration suite for model/database behavior without inventing an HTTP endpoint; preserve explicit project suite conventions. Write Unit tests only for logic that does not use the framework.
- Use browser tests for behavior that needs a real browser and cannot be adequately exercised through HTTP/component tests. Follow the configured browser suite and assert JavaScript errors with the installed tooling when supported.
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
| Controller names, resource consolidation/grouping, resource-wide review, allowed methods, HTTP ownership, empty bases and matching Feature tests | [`rules/controllers.md`](rules/controllers.md) |
| Model naming, responsibilities, relationship ownership, persisted casts/defaults, protected data and meaningful Integration coverage | [`rules/models.md`](rules/models.md) |
| Middleware purpose names, responsibilities, native reuse, registration/order, mirrored Feature ownership and behavior/wiring coverage | [`rules/middleware.md`](rules/middleware.md) |
| FormRequest preparation, validation, payloads, old input and request-to-handler coverage | [`rules/form-requests.md`](rules/form-requests.md) |
| Per-handler HTTP coverage, real execution, intended failure causes, read contracts, authorization, tenant isolation, validation, and browser tests | [`rules/endpoint-tests.md`](rules/endpoint-tests.md) |
| Factories, test data ownership, and repeated input values | [`rules/test-data.md`](rules/test-data.md) |
| Fakes, mocks, outbound HTTP, time, randomness, and databases | [`rules/isolation.md`](rules/isolation.md) |
| Escaping, injection, cross-tenant access, and privilege checks | [`rules/security.md`](rules/security.md) |
| Environment and CI settings for a slow suite | [`rules/performance.md`](rules/performance.md) |
| Reviewing a test or suite | [`rules/review.md`](rules/review.md) |
