# Development Commands

This document provides a comprehensive reference for all development and testing commands used in the Ringside project.

## Testing & Quality Assurance

### Primary Test Commands
- `composer test` - Run quality checks and the default Pest suites with coverage
- `composer test:coverage` - Run the default Pest suites with PCOV coverage, non-parallel, Browser suite excluded (minimum 100%)
- `composer test:application` - Run Feature, Unit, and Integration tests in parallel
- `composer test:browser` - Run Browser tests in parallel
- `composer test:tia` - Run tests selected by Test Impact Analysis
- `composer test:push` - Run quality checks, application tests, and browser tests before pushing
- `composer test:types` - Run PHPStan for the application and Pest tests (both level 9)
- `composer test:types:pest` - Run PHPStan against the Pest test suite (level 9)
- `composer test:type-coverage` - Check type coverage (min 100%)
- `composer test:lint` - Check PHP and Blade formatting with Laravel Pint
- `composer test:rector` - Test code modernization (dry-run)

### Shared Development Commands

Ringside and KneadIt share explicit command names while retaining each application's
Pint rules, Rector exclusions, PHPStan levels, and coverage requirements.

- `composer check` - Run formatting, static analysis, Rector, type coverage, application tests, frontend lint, and the production asset build; browser tests remain a separate gate
- `composer lint:dirty` - Fix formatting in changed PHP and Blade files
- `composer lint:check` - Alias for `test:lint`
- `composer rector:fix` - Apply application and Pest Rector transformations
- `composer rector:pest:fix` - Apply only Pest Rector transformations
- `composer frontend:check` - Run frontend lint and the production asset build

The shared test commands also include `test:application`, `test:browser`,
`test:types`, `test:type-coverage`, `test:rector`, and `test:push`.
Existing `test`, `test:push`, `lint`, and `rector` commands retain their behavior.
In Ringside, `lint` formats all applicable files and `rector` applies fixes;
use `lint:dirty`, `test:rector`, and `rector:fix` for explicit intent across apps.

Pint owns PHP and Blade formatting, using the Laravel preset plus Ringside's
existing additional rules. `npm run format` and `npm run format:check` cover
JavaScript only, so Blade is not passed through a second formatter configuration.
Playwright is a development dependency; browser testing requires a development
dependency install. The ESLint configuration's `@eslint/js` import is declared
directly in `package.json`.

### Code Quality Tools
- `composer lint` - Fix PHP and Blade formatting with Laravel Pint
- `composer rector` - Apply code modernization with Rector

### Test Utilities
- Use `testLivewire()` for Livewire component tests so PHPStan retains the concrete component type.
- Prefer native Pest expectations. Add a custom expectation only when it expresses reusable domain behavior that native expectations cannot represent clearly.
- Use `JMac\Testing\Double` for focused interaction tests where its explicit expectations improve clarity. Mockery remains available for existing tests and cases that need dynamic methods.

## Development Server

### Server Commands
- `composer dev` - Start all development services (server, queue, logs, vite)
- `php artisan serve` - Start Laravel development server only

## Database

Local development uses SQLite: a single file, `database/database.sqlite`, and no
database server to install. `composer setup` creates the file and runs the
migrations; on an existing checkout, run
`touch database/database.sqlite && php artisan migrate --seed`. Automated tests
also run on SQLite, in memory. The `sqlite` connection in `config/database.php`
uses WAL, a 5-second busy timeout and IMMEDIATE transactions, so the web server,
queue and scheduler can write at the same time without "database is locked"
errors.

Production runs MySQL 8. CI runs the test suite on SQLite, PostgreSQL and MySQL
(see [CI/CD](../workflows/ci-cd.md)). To develop against the production engine
instead, set `DB_CONNECTION=mysql` and the `DB_*` values in `.env` (see the
commented lines in `.env.example`).

### What SQLite won't show you

SQLite behaves like MySQL for almost everything in Ringside. These differences
can't show up locally; the CI **MySQL tests** job catches them before a pull
request can merge:

- **Row locks.** SQLite ignores `lockForUpdate()`, so locking and deadlock
  behaviour only exists on MySQL and PostgreSQL. The `concurrency` test group
  proves it there; see [Concurrency tests](../testing/postgres-concurrency-tests.md)
  to run it locally against a real MySQL or PostgreSQL database.
- **Active stable names.** SQLite and PostgreSQL enforce one active stable per
  name with a partial unique index. MySQL has no partial indexes, so it relies on
  `StableNameLock` and validation instead.
- **Text comparison.** MySQL's collation ignores case and accents, so "Foo" and
  "foo", or "Café" and "Cafe", count as the same name and surface as a
  validation error. SQLite treats them as different names.
- **Row order.** A query without `ORDER BY` can return rows in a different order
  on each engine; never rely on it.

### Database Commands
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database with test data

## Test Generation

Use Laravel's native Pest test generator. Test names mirror the applicable `app/` path.

```bash
php artisan make:test --pest --unit Models/Roster/Wrestlers/WrestlerTest
```

## Quality Assurance Protocol

### Before Committing
1. Run `composer lint` when formatting changes are needed.
2. Run the affected tests and relevant quality checks.
3. Review the diff and include only the intended changes.

### Before Pushing

Run `composer test:push` when you want the complete local verification suite.
The pre-commit hook intentionally runs only fast staged-file checks; CI remains
the required gate for pushed branches.

### Git Hooks

Run `npm install` once after cloning to configure Git to use `.githooks`. The
pre-commit hook checks staged PHP syntax and formatting without running tests or
static analysis.

### Test Running Best Practices
- Run affected tests after changes.
- Use specific test commands for targeted testing
- Always verify application and Pest PHPStan level 9 compliance
- Maintain 100% type coverage requirement

## Git Integration

### Workflow Commands
- Tests must pass before commits
- Use `composer lint` and `composer rector` for automatic fixes
- Only commit when code is properly formatted and tested

For more development workflow information, see [Git Workflow](../workflows/git-workflow.md).
