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

Wrestlers expose current and historical tag team membership through the `currentTagTeam`, `previousTagTeam`, and `tagTeams` Eloquent relationships. Whether a wrestler currently belongs to a tag team is determined by querying `currentTagTeam`; the model contract does not duplicate that relationship state with a boolean predicate.

Single current/previous tag team and current Stable lookups use Laravel's native `HasOneThrough` relationships through the persisted membership models. Each Stable-member model explicitly supplies its membership table, foreign key, and pivot model to the shared concern; the concern does not infer its host type at runtime. Collection relationships remain `BelongsToMany` so callers can inspect complete history and membership pivot dates. Do not reintroduce the abandoned `ankurk91/laravel-eloquent-relationships` package.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Championship System](championship-system.md)
