# Derived Business Rules

Rules that are derived from core capabilities and business logic.

## Overview

Derived rules are logical consequences of core business rules and system constraints.

## Employment Status Rules

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

## Injury Status Rules

### Injury Validation
**Rule**: Injured entities cannot be booked in matches
- **Validation**: Check injury status before booking
- **Exception**: Throw appropriate booking exception
- **Rationale**: Injured entities cannot compete

## Suspension Status Rules

### Suspension Validation
**Rule**: Suspended entities cannot be booked in matches
- **Validation**: Check suspension status before booking
- **Exception**: Throw appropriate booking exception
- **Rationale**: Suspended entities cannot compete

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Employment Status](employment-status.md)
