# Employment Status

Employment status management and validation rules.

## Overview

Employment status tracks the working relationship between entities and the promotion.

## Employment Rules

### Employment Validation
**Rule**: Cannot employ already employed entities
- **Validation**: Check current employment status before employment
- **Exception**: Throw `CannotBeEmployedException` for invalid employment
- **Rationale**: Prevents duplicate employment records

### Retirement Status Rules
**Rule**: Cannot employ retired entities
- **Validation**: Check retirement status before employment
- **Exception**: Throw `CannotBeEmployedException` for retired entities
- **Rationale**: Retired entities cannot be employed

## Employment Management

### Employment Lifecycle
- **Employment Start**: Record employment start date
- **Employment End**: Record employment end date when applicable
- **Employment History**: Track all employment periods
- **Current Employment**: Determine current employment status

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Derived Rules](derived-rules.md)
