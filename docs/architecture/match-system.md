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
- **EventMatchResult**: Central result record linking to match decision
- **EventMatchWinner**: Polymorphic pivot for all match winners
- **EventMatchLoser**: Polymorphic pivot for all match losers
- **MatchDecision**: Determines if winners/losers should be recorded

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Championship System](championship-system.md)
