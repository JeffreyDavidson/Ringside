---
paths:
  - 'tests/**'
---

# Tests

## Use Pest tests
Write tests with Pest's functional test, describe, and expectation APIs rather than PHPUnit test classes.

## Use TestDouble for application collaborators
Use JMac\Testing\Double::for() for doubles of application collaborators. Bind container-resolved doubles through the application container and explicitly verify their expectations.

## Organize tests by behavior and AAA phases
Keep each test file focused on one subject, group related behavior with describe blocks, and make Arrange, Act, and Assert phases explicit. Keep each Act call on its own line.

## Keep test suites aligned to application boundaries
Feature tests cover HTTP endpoints and authorization; Integration tests cover database, framework, component, and multi-action domain behavior; Unit tests remain framework- and database-free; Browser tests cover real user journeys in Pest Browser. Place each test beside the application boundary it exercises.

## Assert observable behavior
Test rendered output, returned data, persisted state, dispatched events, authorization, and validation outcomes. Do not inspect source strings, imports, comments, method counts, or private implementation structure with reflection; reserve reflection for architecture constraints that cannot be expressed through Pest architecture expectations.

## Enforce 100% coverage without ignores
composer test:coverage runs non-parallel (parallel runs lose attribution and are not deterministic) and requires 100% line coverage of app/, including Livewire and Console. Do not add @codeCoverageIgnore or tests that only execute a line; delete unreachable code instead of testing it.

## Keep tests independent of random Faker values
Do not let random Faker output choose a branch or supply a search term. Use fixed, distinctive values (for example known names for search tests) and force boolean branches with the forceFakerBoolean() helper.

## Keep expected dates on the frozen clock
Tests must not compute expected dates or times outside the frozen clock: freeze time in the test and never evaluate now()/today()/Carbon in dataset definitions, so a run crossing midnight UTC cannot flip results. Integration and Feature tests are frozen by default in tests/Pest.php; use travel() to move the clock instead of sleep().

## Run on every database engine
The suite runs on SQLite, PostgreSQL and MySQL in CI. Assert generated SQL through recordStatements() or normalizedSql(), which turn MySQL backtick quoting into double quotes. A test that only applies to some engines is skipped with ->skip(fn (): bool => runsOnDriver(...), <reason>), never silently. MySQL commits the test transaction on any DDL (DROP INDEX, Schema::create), so a test that changes the schema inside RefreshDatabase is skipped there with MYSQL_IMPLICIT_COMMIT or runs outside the test transaction and rebuilds the schema afterwards.
