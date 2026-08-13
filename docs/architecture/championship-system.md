# Championship System

Title matches and championship validation rules.

## Overview

The championship system manages title matches and ensures proper competitor validation.

`Title` owns only the championship relationships. Current, previous, first, longest, vacancy, and reign-count reporting is provided by `TitleChampionshipQuery`, keeping reporting queries and in-memory summaries outside the Eloquent model.

## Title Type Matching

### Championship Rules
- **Singles Titles**: Can only be held by individual wrestlers
- **Tag Team Titles**: Can only be held by tag teams
- **Match Validation**: Title matches must use compatible competitor types
- **Champion Defense**: Current champions can defend against appropriate challengers

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Core Capabilities](core-capabilities.md)
