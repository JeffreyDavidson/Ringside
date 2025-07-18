# Continuous Integration

## CI Requirements
```bash
# Required CI checks
composer test
composer test:types
composer test:type-coverage
composer test:lint
```

## Quality Gates
- **100% Test Coverage**: No code ships without tests
- **All Tests Pass**: No failing tests allowed
- **Static Analysis**: PHPStan level 6 compliance
- **Code Style**: Laravel Pint formatting compliance
