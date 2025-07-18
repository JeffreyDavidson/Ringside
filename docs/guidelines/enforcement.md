# Enforcement

Automated checks and quality gates for Ringside development.

## Overview

Enforcement mechanisms ensure consistent code quality and adherence to standards.

## Automated Checks

### Quality Gates
- **Pre-commit Hooks**: Run quality checks before commits
- **CI/CD Pipeline**: Automated quality checks in CI
- **Code Review**: Manual code review process
- **Quality Gates**: Quality gates for deployment

## Tools Integration

### Command Reference
```bash
# Code quality checks
composer lint          # Format code
composer test:types     # Static analysis
composer test:coverage  # Test coverage
composer rector         # Code modernization
```

## Quality Metrics

### Coverage Requirements
- **Code Coverage**: 100% test coverage required
- **Type Coverage**: 100% type coverage required
- **Static Analysis**: PHPStan level 6 compliance
- **Code Style**: Laravel Pint compliance

## Related Documentation
- [Code Style Guide](code-style.md)
- [Quality Tools](quality-tools.md)
- [Testing Standards](testing.md)
