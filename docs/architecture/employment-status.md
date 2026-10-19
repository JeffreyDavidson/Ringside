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
- **Shared Persistence**: Store every employment period in `employments` through the polymorphic `employable` owner
- **Supported Owners**: Wrestlers, managers, referees, and tag teams
- **Database Enforcement**: Each employable entity may have only one open employment period while retaining unlimited ended periods
- **Employment Date Rule**: `CanChangeEmploymentDate` compares whole local days in the record's promotion time zone, like `CanChangeDebutDate`. The form holds only the day, so re-saving the day the current employment started passes whatever time of day it started; any other day outside the current employment still fails.
- **Edit Forms**: The wrestler, manager, referee and tag team `UpdateAction`s employ from the form's employment date only when the record has no employment history at all (as `Titles\UpdateAction` debuts only a title that has never been active), so renaming a released, retired or started record never re-employs it or throws; the individual forms also hide the date field once history exists (re-employment goes through the explicit employ action), and their modals show any remaining `BaseBusinessException` as a form error
- **Move or Clear a Scheduled Start**: While a record's only employment has not started yet (`soleScheduledEmployment()`), the edit form shows its start date, prefilled in the promotion's time zone. Saving another day (today or later; `CanChangeEmploymentDate` refuses an earlier one) moves the employment and its `employed` transition through `EmploymentPeriodManager::reschedule()`; a date moved to today starts it, so the record becomes employed. Clearing the field cancels the hire through `EmploymentPeriodManager::cancel()`, which deletes the not-yet-started period and its `employed` transition and leaves the record unemployed. All four `UpdateAction`s share this through `ChangeScheduledEmploymentAction`. Once the employment has started, or when it follows an earlier employment, the date stays locked as above. Moving a tag team's start does not move the employments of its members, which are their own records.

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Derived Rules](derived-rules.md)
