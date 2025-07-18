# Documentation Standards

PHPDoc requirements and documentation conventions for Ringside development.

## Overview

Comprehensive documentation standards ensure code clarity, maintainability, and developer productivity.

## PHPDoc Requirements

### Class Documentation
- **Class Documentation**: All classes must have comprehensive PHPDoc
- **Method Documentation**: All public methods must be documented
- **Property Documentation**: Use `@property` tags for dynamic properties
- **Generic Types**: Use proper generic type annotations

```php
/**
 * Repository for managing wrestler employment operations.
 *
 * @template T of Wrestler
 */
class WrestlerRepository
{
    /**
     * Create new employment record for wrestler.
     *
     * @param Wrestler $wrestler The wrestler to employ
     * @param Carbon $startDate Employment start date
     * @return WrestlerEmployment Created employment record
     * @throws CannotBeEmployedException If wrestler cannot be employed
     */
    public function createEmployment(Wrestler $wrestler, Carbon $startDate): WrestlerEmployment
    {
        // Implementation
    }
}
```

## Comment Standards

### Comment Guidelines
- **Avoid Obvious Comments**: Don't comment what the code clearly shows
- **Explain Why**: Comments should explain reasoning, not what
- **Complex Logic**: Comment complex business logic and algorithms
- **TODO Comments**: Use TODO for future improvements

```php
// ✅ CORRECT - Explains business reasoning
// Released entities CAN be retired per business workflow
if ($wrestler->isReleased()) {
    return true;
}

// ❌ INCORRECT - States the obvious
// Set the name to the provided name
$wrestler->name = $name;
```

## Code Documentation

### PHPDoc Standards
```php
/**
 * Action to employ a wrestler with validation and business rules.
 *
 * This action handles the complete employment process including:
 * - Validation of current employment status
 * - Retirement status checking
 * - Employment record creation
 * - Status transition management
 *
 * @see WrestlerRepository For employment data persistence
 * @see CannotBeEmployedException For employment validation errors
 */
class EmployWrestlerAction
{
    /**
     * Execute the employment action.
     *
     * @param Wrestler $wrestler The wrestler to employ
     * @param Carbon|null $startDate Employment start date (defaults to now)
     * @return Wrestler The wrestler with updated employment status
     * @throws CannotBeEmployedException If wrestler cannot be employed
     */
    public function handle(Wrestler $wrestler, ?Carbon $startDate = null): Wrestler
    {
        // Implementation
    }
}
```

### Documentation Organization
- **Class Purpose**: Explain what the class does and why
- **Method Purpose**: Describe method behavior and return values
- **Parameter Documentation**: Document all parameters
- **Exception Documentation**: Document thrown exceptions
- **Cross-References**: Link to related classes and methods

## README Documentation

### Project Documentation Structure
```markdown
# Component Name

Brief description of the component and its purpose.

## Usage

Basic usage examples with code samples.

## Configuration

Configuration options and setup instructions.

## Examples

Detailed examples showing common usage patterns.

## Testing

Information about testing the component.

## Contributing

Guidelines for contributing to the component.
```

## Related Documentation
- [Code Style Guide](code-style.md)
- [PHP Standards](php.md)
- [Testing Standards](testing.md)
