# Stable Membership

Stable membership rules and management.

## Overview

Stable membership defines how wrestlers and tag teams can belong to stables.

## Membership Rules

### Membership Types
- **Wrestler Membership**: Individual wrestlers can belong to stables
- **Tag Team Membership**: Tag teams can belong to stables
- **Manager Exclusion**: Managers do NOT belong to stables directly (they manage individual wrestlers/tag teams)
- **Derived Manager History**: A manager is historically associated with a stable only when their wrestler or tag-team assignment overlaps that member's stable-membership period; this reporting association does not create stable membership for the manager
- **Single Current Stable**: Wrestlers and tag teams can belong to only one stable at a time
- **Database Enforcement**: Membership history tables enforce one open membership per wrestler or tag team while allowing unlimited ended memberships
- **Membership History**: Wrestlers and tag teams can belong to multiple stables across non-overlapping historical periods
- **Stable Leadership**: Stables can have designated leaders
- **Member Availability**: New memberships require employed members who are not suspended; injured wrestlers cannot join
- **Employment Timing**: A member's employment must begin on or before the stable membership period
- **Unformed Stables**: A stable may be created without members and assembled before establishment
- **Minimum Size**: An active stable requires a headcount of at least three; an individual wrestler counts as one and a tag team counts as two
- **Eligibility Boundary**: `StableMembershipData` calculates weighted headcount; `StableMembershipRequirements` owns the minimum-size decision shared by validation and lifecycle workflows
- **Split Integrity**: A split moves only available current members, and both resulting active stables must meet the minimum size
- **Split Name and Promotion**: The new stable belongs to the original stable's promotion. After locking the original stable, `StableRestructuringEligibility::ensureSplitNameAvailable()` rejects a name that another non-deleted stable of that promotion (or, for a stable without a promotion, another non-deleted stable without one) already uses, with `CannotBeSplitException::nameTaken()`. The database also enforces unique names per promotion, but on MySQL its generated-column index cannot cover stables without a promotion (a partial index does on PostgreSQL and SQLite), so this check is the only guard there besides form validation. Like the form rule, it does not lock against a concurrent split choosing the same name
- **Ended Memberships Never Precede Their Start**: Ending current relationships (wrestler, tag team, or manager cascades) closes rows that already started on the effective date, and closes rows that start later (for example a stable established with a future start date) on their own start date, so `left_at`/`fired_at` is never earlier than `joined_at`/`hired_at`. History is kept; nothing is deleted. `OpenPeriodEnder` implements this
- **Edit Form Cannot Reopen a Stable**: The stable edit form never writes a blank end date onto a closed first activity period, and never closes or moves an earlier period once later periods exist. A disbanded stable returns only through `ReuniteAction`; blanking the end date of a disbanded stable is a validation error. The debut-date rule compares against the first period, so reunited stables remain editable
- **Merge Integrity**: A merge rejects unavailable secondary members, preserves their membership history, and ends the secondary stable's activity period before soft deletion

### Membership Management
- **Add Members**: Add wrestlers or tag teams to stables
- **Remove Members**: Remove members from stables
- **Leadership**: Assign and change stable leadership
- **Membership History**: Track membership changes over time

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Employment Status](employment-status.md)
