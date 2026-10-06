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
The suite runs on SQLite, PostgreSQL and MySQL in CI. Assert generated SQL through recordStatements() or normalizedSql(), which turn MySQL backtick quoting into double quotes. A test that only applies to some engines is skipped with ->skip(fn (): bool => runsOnDriver(...), <reason>), never silently. MySQL commits the test transaction on any DDL (DROP INDEX, Schema::create), so a test that changes the schema or runs a migration lives in tests/Integration/DatabaseMigrations, which rebuilds the schema with migrate:fresh around each test instead of using RefreshDatabase (keep that directory small: each test costs a full migrate:fresh).

## Never rely on row order the query did not ask for
A query without ORDER BY returns rows in whatever order the engine picks: SQLite and MySQL usually return primary key order, PostgreSQL may not. Only assert an order the code under test sets with an explicit ORDER BY (with an id tie-break when the sort column can repeat), and insert the test data out of that order so the assertion fails without it. When the order is not part of the contract, compare with toEqualCanonicalizing(). Run REVERSE_UNORDERED_SELECTS=1 composer test:application after changing list queries (see docs/workflows/ci-cd.md).

## Make guard tests fail when the guard is removed
A test for an authorization check, lock, filter or escape must fail if that one line is deleted. Pair every allowed case with a refused one that reaches the guarded line (a view-only member calling a delete action, a membership downgraded between opening a form and saving it), assert the refusal and that nothing was written, and pick data that only the guard tells apart (a search term whose wildcard would match, a stored email that a LIKE pattern would match). Assert locks with recordStatements(): which row is locked, in which order, and before which write.
