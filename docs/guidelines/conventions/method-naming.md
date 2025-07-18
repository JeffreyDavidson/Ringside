# Method Naming Conventions

Method naming patterns and conventions for Ringside development.

## Overview

Clear method naming ensures readable, maintainable code.

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
- [Naming Conventions](naming.md)
- [Structural Patterns](structure.md)
- [Architecture Standards](../architecture.md)
