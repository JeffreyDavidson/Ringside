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

Run `composer test:push`; the native `.githooks/pre-push` hook runs the same command.

### Git Hooks

Run `npm install` once after cloning to configure Git to use `.githooks`. The
hooks run `npx lint-staged` before commits and `composer test:push` before pushes.

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
