# Development Commands

This document provides a comprehensive reference for all development and testing commands used in the Ringside project.

## Testing & Quality Assurance

### Primary Test Commands
- `composer test` - Run quality checks and the default Pest suites with coverage
- `composer test:unit` - Run the default Pest suites with PCOV coverage (minimum 44%); despite its name, this is not limited to Unit tests
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
- `composer test:coverage` - Alias for the existing `test:unit` coverage command, with its current suite scope and 44% minimum
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

Local development uses PostgreSQL so application behavior matches the intended
shared relational database. Automated tests continue to use SQLite in memory
for fast isolated runs.

On macOS with Homebrew, install and start PostgreSQL, create the application
database, and set `DB_USERNAME` in `.env` to the local PostgreSQL role:

```bash
brew install postgresql@17
brew services start postgresql@17
createdb ringside
php artisan migrate
```

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
