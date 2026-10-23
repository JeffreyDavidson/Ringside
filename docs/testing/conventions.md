# Test conventions

Ringside's tests follow Jeffrey's shared `laravel-testing-standards` skill. This page holds what is specific to Ringside: its suites, helpers, gates and the habits its reviews check. `TestSuiteArchitectureTest` (`tests/Feature/Architecture`) enforces the suite boundaries.

## Suites
- **Feature:** HTTP endpoints and authorization, plus the architecture tests.
- **Integration:** database, framework, component and multi-Action domain behaviour. Direct Livewire component tests live here.
- **Unit:** framework- and database-free. Unit tests use Pest's default PHPUnit test case and never boot Laravel, use `RefreshDatabase`, resolve application services or persist models.
- **Browser:** real user journeys in Pest Browser (never Laravel Dusk), isolated in the Browser suite.

Place each test beside the boundary it exercises, at the path that mirrors the class. A Blade view with no class in `app/` (an anonymous component, a layout, a vendor view override) is tested in `tests/Integration/View` at its `resources/views` path: `components/sidebar` → `tests/Integration/View/Components/SidebarTest.php`, `vendor/wire-elements-modal/modal` → `tests/Integration/View/Vendor/WireElementsModal/ModalTest.php`. A view rendered by a class is tested with that class. There is no `tests/Feature/Pages`. Every Action has a matching test file.

## Database reset
- Feature, Integration and Browser tests use `RefreshDatabase`, configured once in `tests/Pest.php`. Browser tests never use `DatabaseMigrations`: migrations are forward-only and cannot roll back.
- MySQL commits the test transaction on any DDL (`DROP INDEX`, `Schema::create`), so a test that changes the schema or runs a migration lives in `tests/Integration/DatabaseMigrations`, which `Pest.php` wires to `migrate:fresh` around each test. Keep that folder small: each test costs a full `migrate:fresh`.
- `database/schema/mysql-schema.sql` is Laravel's schema dump for MySQL. `migrate:fresh` (so `RefreshDatabase` and the per-test rebuilds above) loads it first and then runs only the migrations newer than it, which cuts a MySQL rebuild from about 5.8 s to 1.1 s locally. It is safe for the dump to lag behind new migrations, because newer ones run after it; regenerate it now and then, or when it falls far behind. Production's `migrate` only loads it into an empty database, so deploys are unaffected. SQLite and PostgreSQL have no dump (they gain about 0.1 s per rebuild). All migrations are kept (no `--prune`). To regenerate: start a throwaway MySQL 8.0 server (Homebrew `mysql@8.0`, `mysqld --initialize-insecure` into a new folder under `/private/tmp`, own socket and port, never the Homebrew service), point `DB_CONNECTION=mysql` and the `DB_*` variables at it with the `mysql@8.0` `bin` folder on `PATH` (Laravel loads and dumps through the `mysql` and `mysqldump` clients), run `php artisan migrate:fresh --force` and then `php artisan schema:dump --database=mysql`, and stop the server. Check that the file has no `AUTO_INCREMENT=` counters, `DEFINER` lines or host and credentials.

## Writing tests
- Pest functional tests with `describe` blocks for related behaviour; one subject per file; explicit Arrange, Act and Assert, with each Act call on its own line.
- Doubles of application collaborators use `JMac\Testing\Double::for()` (TestDouble), never Mockery. Bind container-resolved doubles through the container and verify their expectations.
- Assert observable behaviour: rendered output, returned data, stored state, dispatched events, authorization and validation outcomes. Do not inspect source strings, imports, comments, method counts or private structure with reflection; keep reflection for architecture rules Pest's arch expectations cannot express.
- Random Faker values never choose a branch or supply a search term. Use fixed, distinctive values, and force boolean branches with `forceFakerBoolean()` (`tests/Helpers/FakerHelpers.php`).
- Integration and Feature tests run on a frozen clock by default (`tests/Pest.php`). Never evaluate `now()`, `today()` or Carbon in dataset definitions, and use `travel()` instead of `sleep()`.

## Feature tests (HTTP)
- Every concrete controller has an executable test at its full mirrored path under `tests/Feature/Http/Controllers` (`{ControllerName}Test.php`) covering every routed action; the controller architecture gate rejects missing, empty, comment-only or pending-only files. Every application middleware has one under `tests/Feature/Http/Middleware`.
- One scenario and target action per test. Split guest/member/foreign actors, success/refusal and valid/invalid input into separate tests; use named datasets only when setup, action and assertions keep the same shape. Do not hide branches behind an expected-status parameter or assertion callback.
- Keep a multi-request sequence only when the defect needs a baseline or earlier action (stale input, replay protection, a query comparison), and make that visible.
- Arrange: authentication and promotion context, minimal deterministic fixtures, specific fakes, grouped by meaning with blank lines where the subject changes. Act: assign the response to `$response`. Assert: the response first, as one chain with the variable and each assertion on its own line, then a blank line before assertions about authentication, stored state, events, jobs, mail or notifications.
- Use named factory states, `count()`, `sequence()`, `for()`, `has()` and `hasAttached()` when they make intent clearer; do not force different lifecycle states or roles into a sequence just to shorten setup.
- Successful views assert the intended view and meaningful data. Successful writes assert the redirect, flash message, stored values and side effects. Refusals assert the error, unchanged protected state and no success side effects. Fakes do not prove listener execution.
- Cover guests, no membership, insufficient role, cross-promotion binding, missing or deleted records, validation and privileged input where they apply. A role test needs an active membership so it reaches authorization; a no-membership 403 is a separate contract.

## Gates
- `composer test:coverage` runs non-parallel and requires 100% line coverage of `app/`, including Livewire and Console. No `@codeCoverageIgnore` and no tests that only execute a line; delete unreachable code instead.
- CI runs the suite on SQLite, PostgreSQL and MySQL. Assert generated SQL through `recordStatements()` or `normalizedSql()` (they turn MySQL backticks into double quotes). Skip an engine-specific test with `->skip(fn (): bool => runsOnDriver(...), <reason>)`, never silently.
- The concurrency tests in `tests/Integration/Concurrency/` run only in CI's MySQL and PostgreSQL jobs; see [postgres-concurrency-tests.md](postgres-concurrency-tests.md).

## Habits reviews check
- Only assert an order the code sets with an explicit `ORDER BY` (with an id tie-break when the sort column can repeat), and insert test data out of that order. When order is not part of the contract, use `toEqualCanonicalizing()`. Run `REVERSE_UNORDERED_SELECTS=1 composer test:application` after changing list queries (see [ci-cd.md](../workflows/ci-cd.md)).
- A test of an authorization check, lock, filter or escape must fail if that one line is deleted. Pair each allowed case with a refused one that reaches the guarded line, assert the refusal and that nothing was written, and pick data only the guard tells apart. Assert locks with `recordStatements()`: which row, in which order, before which write.
- Compare associative arrays whose key order the database may change (MySQL JSON, PostgreSQL jsonb) with `toEqual()`; keep lists and scalars on `toBe()`.
