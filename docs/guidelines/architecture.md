# Architecture Standards

Class design, method design, and architectural patterns for Ringside development.

## Overview

Architectural standards ensure maintainable, scalable, and well-structured code.

## Class Design

### Design Principles
- **Single Responsibility**: Each class should have one clear purpose
- **Interface Segregation**: Use focused interfaces instead of large ones
- **Dependency Injection**: Use constructor injection for dependencies
- **Immutability**: Prefer immutable objects where possible

```php
// ✅ CORRECT - Single responsibility with interface
class WrestlerEmploymentAction implements EmploymentActionInterface
{
    public function __construct(
        private WrestlerRepository $repository,
        private EmploymentValidator $validator
    ) {}

    public function handle(Wrestler $wrestler, Carbon $date): WrestlerEmployment
    {
        $this->validator->validate($wrestler, $date);
        return $this->repository->createEmployment($wrestler, $date);
    }
}
```

## Method Design

### Method Guidelines
- **Small Methods**: Keep methods focused and concise
- **Clear Names**: Method names should describe their purpose
- **Parameter Limits**: Maximum 3-4 parameters per method
- **Return Types**: Always specify return types

```php
// ✅ CORRECT - Focused method with clear purpose
public function isBookableOn(Carbon $date): bool
{
    return $this->isEmployed()
        && !$this->isInjured()
        && !$this->isSuspended()
        && $this->isAvailableOn($date);
}

// ❌ INCORRECT - Too many responsibilities
public function processWrestlerStatusAndBooking($wrestler, $date, $match, $title)
{
    // Complex logic handling multiple concerns
}
```

## Related Documentation
- [Code Style Guide](code-style.md)
- [PHP Standards](php.md)
- [Error Handling](error-handling.md)
