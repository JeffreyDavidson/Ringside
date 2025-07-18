# Test Framework & Tools

## Framework Setup
- **Pest PHP**: Primary testing framework with Laravel plugin
- **Coverage Requirement**: 100% test coverage maintained
- **Parallel Execution**: Tests run in parallel for performance
- **Database**: SQLite in-memory database for fast test execution

## Test Commands
```bash
# Run all tests
composer test

# Run specific test types
composer test:unit
./vendor/bin/pest tests/Unit/
./vendor/bin/pest tests/Integration/
./vendor/bin/pest tests/Feature/

# Run with coverage
./vendor/bin/pest --coverage

# Run in parallel
./vendor/bin/pest --parallel
```
