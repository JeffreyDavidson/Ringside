# Code Quality Tools

Laravel Pint, PHPStan, and Rector configuration and usage for Ringside development.

## Overview

Automated code quality tools ensure consistent formatting, type safety, and modern PHP practices.

## Laravel Pint

### Configuration
- **Configuration**: Custom rules defined in `pint.json`
- **Usage**: `composer lint` to fix PHP and Blade formatting
- **CI Integration**: Automated formatting checks

## PHPStan

### Static Analysis
- **Level**: Level 9 for application and Pest tests
- **Coverage**: 100% type coverage requirement
- **Configuration**: Rules defined in `phpstan.neon` and `phpstan-pest.neon`
- **Usage**: `composer test:types` for analysis

## Rector

### Code Modernization
- **Modernization**: Automated code modernization
- **Usage**: `composer rector` to apply updates
- **Configuration**: Rules defined in `rector.php`

## Tools Integration

### Command Reference
```bash
# Code quality checks
composer lint          # Format code
composer test:types     # Static analysis
composer test:unit      # Pest suites with PCOV coverage (minimum 44%)
composer rector         # Code modernization
```

## Quality Metrics

### Coverage Requirements
- **Code Coverage**: Current automated minimum is 44%
- **Type Coverage**: 100% type coverage required
- **Static Analysis**: PHPStan level 9 for application and Pest tests
- **Code Style**: Laravel Pint compliance

## Automated Checks

### Quality Gates
- **Pre-commit Hooks**: Run quality checks before commits
- **CI/CD Pipeline**: Automated quality checks in CI
- **Code Review**: Manual code review process
- **Quality Gates**: Quality gates for deployment

## Related Documentation
- [Code Style Guide](code-style.md)
- [PHP Standards](php.md)
- [Testing Standards](testing.md)
