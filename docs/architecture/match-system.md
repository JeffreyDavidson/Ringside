# Match System Architecture

Match types, competitor rules, and winner/loser system architecture.

## Overview

The match system handles complex wrestling match scenarios with flexible competitor configurations.

## Match Types and Competitor Rules

### Competitor Type Restrictions
**Rule**: Match types have specific competitor type restrictions

#### Wrestler-Only Match Types
- **Singles**: Only wrestler vs wrestler
- **Battle Royal**: Individual wrestlers, with at least three entrants and no configured maximum
- **Royal Rumble**: Only individual wrestlers
- **Rationale**: These match types require individual competitor mechanics

#### Mixed Competitor Match Types
- **Tag Team**: Can be wrestlers, tag teams, or mixed combinations
- **Triple Threat**: Can be wrestlers, tag teams, or mixed
- **Fatal 4-Way**: Can be wrestlers, tag teams, or mixed
- **6/8/10 Man Tag Team**: Can be wrestlers, tag teams, or mixed
- **Handicap Matches**: Can be wrestlers, tag teams, or mixed
- **Tornado Tag Team**: Can be wrestlers, tag teams, or mixed
- **Gauntlet**: Can be wrestlers, tag teams, or mixed
- **Rationale**: These match types support flexible competitor configurations

## Match Assignment Failures

Match configuration and participant availability are separate failure boundaries. `InvalidMatchConfigurationException` describes an incomplete or structurally invalid match, such as missing referees, missing competitors, insufficient populated sides, or an invalid side number. `EntityNotAvailableException` describes a wrestler, tag team, referee, or title whose current state prevents assignment. `SchedulingConflictException` is reserved for an actual collision between bookings, times, or resources and must not substitute for either boundary.

Recorded outcomes are checked by `MatchOutcomeRequirements`, which explicitly composes focused winning-side, entry-order, and elimination-history requirements. Each requirement owns one cohesive rule family and raises `InvalidMatchOutcomeException`; the coordinator preserves their deterministic validation order without using a dynamic specification registry or mutation pipeline. `RecordResultAction` locks the match, its event date, the selected winning side, and the complete competitor collection before validation. Outcome requirements and championship reconciliation consume that shared snapshot rather than querying mutable match state independently.

## Event Card Scheduling

Match assignments observe these collision rules:

- A wrestler, tag team, or title may be assigned only once on an event card.
- A wrestler, tag team, or title may not be assigned to different events scheduled for the same exact date and time.
- A referee may officiate multiple matches on one event card, but may not officiate matches on different events scheduled for the same exact date and time.
- An unscheduled event still prevents duplicate assignments within its own card. Its missing date does not conflict with other unscheduled events.

Assignment actions lock the affected event rows and enforce these rules inside their database transactions. This serializes assignment commands that use the application boundary. The schema cannot enforce overlapping match windows because individual matches do not currently have their own start and end times.

Roster booking eligibility is evaluated by `RosterBookingEligibility`, not by Eloquent models or builders. The policy combines persisted employment, injury, suspension, future-employment, and tag-team membership state. A tag team must satisfy its own lifecycle state, have at least two current wrestlers, and have every current wrestler individually eligible. Models expose those relationships and state predicates; validation rules, assignment Actions, and collections invoke the policy when deciding whether a participant may be booked.

The Livewire match form applies model-specific booking rules to every selected wrestler, tag team, and referee before constructing `EventMatchData`. Assignment Actions repeat the eligibility check as the authoritative transactional boundary so non-UI callers and state changes between validation and persistence remain protected.

Assignment Actions treat each requested collection as an atomic command. They reject the entire assignment when any selected wrestler, tag team, referee, or title is unavailable; they never silently discard unavailable selections and persist a partial request. Repeated selections of the same record are normalized before assignment.

`MatchCompetitorRequirements` authoritatively validates competitor types and composition before assignments are persisted. Match formats whose names encode a roster-member count use the current wrestlers represented by each selected tag team: standard and tornado tag matches require 2-on-2, six/eight/ten-person tag matches require 3/4/5 per side, and handicap matches require 2-on-1 or 3-on-2 in either side order. A wrestler cannot also be selected directly when represented by a selected tag team.

Competitor entries and represented roster members are separate concepts. Singles, Triple Threat, Triangle, and Fatal 4-Way matches require exactly one wrestler or tag-team entry on each side, subject to the match type's allowed competitor types. Battle Royal and Royal Rumble entrants each occupy an individual side.

`MatchStipulation` is an optional match configuration selected from active definitions when a match is created or edited. The match retains that relationship as historical configuration even if the definition is later made inactive. Stipulation capabilities and match presentation must be implemented by the match domain when they are enforced; the model does not infer behavior from hard-coded slug lists.

`AddMatchForEventAction` receives side-based `EventMatchData`, locks the owning event, allocates the next card position without reusing soft-deleted match numbers, and persists the match, officials, championship stakes, sides, and competitors in one transaction. Assignment Actions retain eligibility and scheduling-conflict enforcement for their respective relationships.

`UpdateMatchAction` receives the same typed data, locks the match, and replaces its configuration and assignments in one transaction. Any unavailable replacement rolls the entire edit back to the previous configuration. Once a result has been recorded, the match configuration is immutable so its sides and competitors continue to describe that result.

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
- **EventMatch**: Stores the current match finish and optional winning side
- **MatchSide**: Groups competitors who compete together
- **MatchCompetitor**: Polymorphic competitor entry belonging to a match side
- **MatchFinish**: Determines whether a winning side must be recorded

### Entrant and Elimination Metadata

Royal Rumble competitors record a unique `entry_order` within the match. Battle Royal competitors may leave entry order unset because they begin simultaneously. Eliminated competitors record a unique `elimination_order`; the winner remains without one. `eliminated_by_match_competitor_id` optionally identifies the competitor responsible for the elimination and remains nullable for joint, external, or indeterminate eliminations.

`MatchResultForm` owns Livewire result input, validates that the selected side and every elimination entry belong to the loaded match, and converts the validated state into `MatchResultData`. `RecordResultAction` records the finish, winning side, elimination history, and attached championship outcomes as one atomic outcome. A decisive Battle Royal or Royal Rumble result accounts for every losing competitor exactly once, never eliminates a winner, and preserves chronological elimination order. No-outcome finishes may retain a valid partial elimination history.

Pinfall, submission, knockout, stipulation, and forfeit finishes allow titles to change hands. Disqualification, countout, time-limit draw, and no-decision finishes retain the existing champion. A title-changing winner must contain exactly one competitor compatible with each attached title type, and a new reign uses the event date. Recording a corrected result reconciles title lineage in the same transaction; it may void a reign created by that match and restore its predecessor, but it cannot rewrite a reign that later championship history already depends on.

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Championship System](championship-system.md)
