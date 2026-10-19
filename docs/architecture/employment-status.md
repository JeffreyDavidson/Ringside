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

**Rule**: Unretiring restores the state before retirement
- **Employed when retired**: A wrestler, manager or referee who was employed at the moment they retired (an employment period ended by the retirement) comes back employed, with a new employment period starting on the unretirement date.
- **Released before retiring**: One who was already released (their employment ended earlier, or through a release) comes back released, with no new employment period.
- **Re-hiring**: Putting a released person back to work stays the explicit Employ action.
- **Tag teams**: A tag team can only be retired while employed, so it always comes back employed (when it has current wrestlers).
- **Explicit callers**: `Wrestlers\UnretireAction` and `Managers\UnretireAction` take an optional `$employImmediately`; `null` (the default) applies this rule, `true` or `false` overrides it (the tag team cascade passes `false` and employs the members itself).

## Employment Management

### Employment Lifecycle
- **Employment Start**: Record employment start date
- **Employment End**: Record employment end date when applicable
- **Employment History**: Track all employment periods
- **Current Employment**: Determine current employment status
- **Shared Persistence**: Store every employment period in `employments` through the polymorphic `employable` owner
- **Supported Owners**: Wrestlers, managers, referees, and tag teams
- **Database Enforcement**: Each employable entity may have only one open employment period while retaining unlimited ended periods
- **Employment Date Rule**: `CanChangeEmploymentDate` compares whole local days in the record's promotion time zone, like `CanChangeDebutDate`. The form holds only the day, so re-saving the day the current employment started passes whatever time of day it started; any other day outside the current employment still fails.
- **Edit Forms**: The wrestler, manager, referee and tag team `UpdateAction`s employ from the form's employment date only when the record has no employment history at all (as `Titles\UpdateAction` debuts only a title that has never been active), so renaming a released, retired or future-employed record never re-employs it or throws; the individual forms also hide the date field once history exists (re-employment goes through the explicit employ action), and their modals show any remaining `BaseBusinessException` as a form error

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Derived Rules](derived-rules.md)
