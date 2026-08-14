# Core Business Capabilities

Core business capabilities that govern the wrestling promotion management system.

## Overview

Core capabilities define what each entity type can do within the wrestling promotion system.

## Injury Capability

### Injury Rules
**Rule**: Only individual people can be injured
- **Eligible**: Wrestlers, Referees, Managers
- **Not Eligible**: TagTeams, Stables, Titles
- **Rationale**: Injuries affect individual people, not groups or objects

## Suspension Capability

### Suspension Rules
**Rule**: Only entities that can perform actions can be suspended
- **Eligible**: Wrestlers, Referees, Managers, TagTeams
- **Not Eligible**: Stables, Titles
- **Rationale**: Suspension prevents participation in activities

## Retirement Capability

### Retirement Rules
**Rule**: All active entities can be retired
- **Eligible**: Wrestlers, Managers, Referees, TagTeams, Titles, Stables
- **Rationale**: Any entity can cease active participation

## Employment Capability

### Employment Rules
**Rule**: Only entities that can work can be employed
- **Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Not Eligible**: Titles, Stables
- **Rationale**: Employment represents a working relationship

## Pull Capability

### Pull Rules
**Rule**: Only titles can be pulled from circulation
- **Eligible**: Titles only
- **Not Eligible**: All other entities
- **Rationale**: Pulling is a title-specific business action

## Debut Capability

### Debut Rules
**Rule**: Only titles and stables can be debuted
- **Eligible**: Titles, Stables
- **Not Eligible**: Wrestlers, Managers, Referees, TagTeams
- **Rationale**: Debuts represent the first time a title is contested or a stable is formed

## Booking Capability

### Booking Rules
**Rule**: Only entities that can compete in matches can be booked
- **Eligible**: Wrestlers, TagTeams
- **Not Eligible**: Managers, Referees, Titles, Stables
- **Rationale**: Booking is for match competition, not management or officiating

## Tag Team Membership Capability

Wrestlers explicitly define current and historical tag team membership through the `currentTagTeam`, `previousTagTeam`, and `tagTeams` Eloquent relationships. Because Wrestler is the only tag team member type, these persistence mappings belong directly on that model instead of behind a generic contract or concern. Whether a wrestler currently belongs to a tag team is determined by querying `currentTagTeam`; eligibility to join remains outside the model in validation rules and lifecycle collaborators.

Single current/previous tag team and current Stable lookups use Laravel's native `HasOneThrough` relationships through the persisted membership models. `HasStableMemberships` owns only the current and historical Stable relationship definitions; Stable-joining eligibility remains in validation rules and lifecycle collaborators. Each Stable-member model explicitly supplies its membership table, foreign key, and pivot model to the shared concern; the concern does not infer its host type at runtime. Collection relationships remain `BelongsToMany` so callers can inspect complete history and membership pivot dates. Do not reintroduce the abandoned `ankurk91/laravel-eloquent-relationships` package.

Wrestlers and Tag Teams share current and historical manager relationships through `CanBeManaged`. Each model explicitly supplies its manager-assignment table and pivot model; the concern defines only the shared Eloquent relationships and does not infer classes from namespaces or expose mutable test configuration.

## User and Roster Separation

Application users authenticate and operate the promotion management system; they do not own wrestler or other roster records. User and roster models therefore have no direct Eloquent relationship or foreign key.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Championship System](championship-system.md)
