# Error Handling

Exception standards and error handling patterns for Ringside development.

## Overview

Proper error handling ensures robust, maintainable applications with clear failure modes.

## Exception Standards

### Exception Design
- **Custom Exceptions**: Use domain-specific exceptions
- **Clear Messages**: Exception messages should be user-friendly
- **Static Factories**: Use static factory methods for consistency
- **Exception Hierarchy**: Organize exceptions by domain

```php
// ✅ CORRECT - Domain-specific exception with static factory
class CannotBeEmployedException extends Exception
{
    public static function alreadyEmployed(Wrestler $wrestler): self
    {
        return new self("Cannot employ {$wrestler->name} - already employed.");
    }

    public static function isRetired(Wrestler $wrestler): self
    {
        return new self("Cannot employ {$wrestler->name} - currently retired.");
    }
}
```

## Error Handling Patterns

### Best Practices
- **Fail Fast**: Validate inputs early and fail fast
- **Meaningful Messages**: Provide actionable error messages
- **Logging**: Log errors appropriately for debugging
- **Recovery**: Provide recovery mechanisms where possible

## Related Documentation
- [Code Style Guide](code-style.md)
- [Architecture Standards](architecture.md)
- [Testing Standards](testing.md)
