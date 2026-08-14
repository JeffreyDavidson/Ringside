# Match System Architecture

Match types, competitor rules, and winner/loser system architecture.

## Overview

The match system handles complex wrestling match scenarios with flexible competitor configurations.

## Match Types and Competitor Rules

### Competitor Type Restrictions
**Rule**: Match types have specific competitor type restrictions

#### Wrestler-Only Match Types
- **Singles**: Only wrestler vs wrestler
- **Royal Rumble**: Only individual wrestlers
- **Rationale**: These match types require individual competitor mechanics

#### Mixed Competitor Match Types
- **Tag Team**: Can be wrestlers, tag teams, or mixed combinations
- **Triple Threat**: Can be wrestlers, tag teams, or mixed
- **Fatal 4-Way**: Can be wrestlers, tag teams, or mixed
- **6/8/10 Man Tag Team**: Can be wrestlers, tag teams, or mixed
- **Handicap Matches**: Can be wrestlers, tag teams, or mixed
- **Battle Royal**: Can be wrestlers, tag teams, or mixed
- **Tornado Tag Team**: Can be wrestlers, tag teams, or mixed
- **Gauntlet**: Can be wrestlers, tag teams, or mixed
- **Rationale**: These match types support flexible competitor configurations

## Match Assignment Failures

Match configuration and participant availability are separate failure boundaries. `InvalidMatchConfigurationException` describes an incomplete or structurally invalid match, such as missing referees, missing competitors, insufficient populated sides, or an invalid side number. `EntityNotAvailableException` describes a wrestler, tag team, referee, or title whose current state prevents assignment. `SchedulingConflictException` is reserved for an actual collision between bookings, times, or resources and must not substitute for either boundary.

## Event Card Scheduling

Match assignments observe these collision rules:

- A wrestler, tag team, or title may be assigned only once on an event card.
- A wrestler, tag team, or title may not be assigned to different events scheduled for the same exact date and time.
- A referee may officiate multiple matches on one event card, but may not officiate matches on different events scheduled for the same exact date and time.
- An unscheduled event still prevents duplicate assignments within its own card. Its missing date does not conflict with other unscheduled events.

Assignment actions lock the affected event rows and enforce these rules inside their database transactions. This serializes assignment commands that use the application boundary. The schema cannot enforce overlapping match windows because individual matches do not currently have their own start and end times.

Roster booking eligibility is evaluated by `RosterBookingEligibility`, not by Eloquent models or builders. The policy combines persisted employment, injury, suspension, future-employment, and tag-team membership state. A tag team must satisfy its own lifecycle state, have at least two current wrestlers, and have every current wrestler individually eligible. Models expose those relationships and state predicates; validation rules, assignment Actions, and collections invoke the policy when deciding whether a participant may be booked.

`MatchStipulation` is a persistence record containing its configured name, slug, description, active flag, and match relationship. Stipulation capabilities and match presentation must be implemented by the match domain when they are enforced; the model does not infer behavior from hard-coded slug lists.

## Winner/Loser System

### Multiple Winners and Losers
**Rule**: Matches can have multiple winners and multiple losers

#### Winner/Loser Assignment
- **Multiple Winners**: Tag team matches, handicap matches, etc. can have multiple winners
- **Multiple Losers**: Battle royals, elimination matches can have multiple losers
- **No-Outcome Matches**: Some match decisions result in no winners or losers
  - Time Limit Draw
  - No Decision
  - Reverse Decision
- **Rationale**: Wrestling matches have complex outcome scenarios

### Match Result Architecture
- **MatchResult**: Central result record linking to match decision
- **MatchWinner**: Polymorphic pivot for all match winners
- **MatchLoser**: Polymorphic pivot for all match losers
- **MatchDecision**: Determines if winners/losers should be recorded

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Championship System](championship-system.md)
