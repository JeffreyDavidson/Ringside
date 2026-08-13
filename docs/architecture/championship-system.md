# Championship System

Title matches and championship validation rules.

## Overview

The championship system manages title matches and ensures proper competitor validation.

## Title Type Matching

### Championship Rules
- **Singles Titles**: Can only be held by individual wrestlers
- **Tag Team Titles**: Can only be held by tag teams
- **Match Validation**: Title matches must use compatible competitor types
- **Champion Defense**: Current champions can defend against appropriate challengers

## Persistence Boundaries

`TitleType` is the canonical classification value; consumers compare the model's cast `type` attribute with its enum cases instead of relying on model predicate aliases. Wrestlers and tag teams expose championship history through Eloquent relationships defined by `CanBeChampion`. Current champion state is determined through the `currentChampionships` relationship rather than a separate model method.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Core Capabilities](core-capabilities.md)
