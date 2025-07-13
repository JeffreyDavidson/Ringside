# Development Commands

This document provides a comprehensive reference for all development and testing commands used in the Ringside project.

## Testing & Quality Assurance

### Primary Test Commands
- `composer test` - Run all tests and quality checks
- `composer test:unit` - Run PHPUnit/Pest tests with coverage
- `composer test:types` - Run PHPStan static analysis (level 6)
- `composer test:type-coverage` - Check type coverage (min 100%)
- `composer test:lint` - Test code formatting (Laravel Pint)
- `composer test:rector` - Test code modernization (dry-run)

### Code Quality Tools
- `composer lint` - Fix code formatting with Laravel Pint
- `composer rector` - Apply code modernization with Rector

## Development Server

### Server Commands
- `composer dev` - Start all development services (server, queue, logs, vite)
- `php artisan serve` - Start Laravel development server only

## Database

### Database Commands
- `php artisan migrate` - Run database migrations
- `php artisan db:seed` - Seed database with test data

## Test Generation

### Ringside Test Generator
- `php artisan ringside:make:test --unit --model="ModelName"` - Generate standardized model unit tests

### Command Examples

```bash
# Generate test for Wrestler model (auto-detected in Wrestlers/ directory)
php artisan ringside:make:test --unit --model="Wrestler"
# Creates: tests/Unit/Models/WrestlerTest.php

# Generate test for User model with directory specification
php artisan make:model-test User --directory=Users
# Creates: tests/Unit/Models/UserTest.php
# Resolves: App\Models\Users\User

# Generate test for nested model
php artisan ringside:make:test --unit --model="TitleChampionship"  
# Creates: tests/Unit/Models/TitleChampionshipTest.php

# Generate test with full namespace
php artisan ringside:make:test --unit --model="App\Models\Shared\Venue"
# Creates: tests/Unit/Models/VenueTest.php
```

### Enhanced Command Integration

**Multiple Discovery Paths**: The Ringside test generator provides several ways to access standardized test generation:

```bash
# Option 1: Direct alias command (Laravel-style)
php artisan make:model-test Product

# Option 2: Directory-specific model resolution
php artisan make:model-test User --directory=Users

# Option 3: Full Ringside command
php artisan ringside:make:test --unit --model="Product"

# Option 4: Full command with directory
php artisan ringside:make:test --unit --model="User" --directory="Users"

# Option 5: Interactive mode
php artisan ringside:make:test
# Prompts for test type, model selection, and optional directory

# Option 6: Enhanced Laravel integration
php artisan make:test ProductTest --unit
# Detects model-like names and offers Ringside alternative
```

## Quality Assurance Protocol

### Before Committing
1. Run `composer test` to ensure all tests pass
2. Run `composer lint` to fix formatting issues
3. Run `composer rector` for code modernization
4. Verify type coverage with `composer test:type-coverage`

### Test Running Best Practices
- **IMPORTANT**: Do not run tests automatically. The user will run tests manually when needed.
- Use specific test commands for targeted testing
- Always verify PHPStan level 6 compliance
- Maintain 100% type coverage requirement

## Git Integration

### Workflow Commands
- Tests must pass before commits
- Use `composer lint` and `composer rector` for automatic fixes
- Only commit when code is properly formatted and tested

For more development workflow information, see [Development Workflow](workflow.md).