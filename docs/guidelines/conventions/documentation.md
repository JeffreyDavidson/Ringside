# Documentation Conventions

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

### README Documentation

#### Project Documentation Structure
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
