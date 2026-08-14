# Championship System

Title matches and championship validation rules.

## Overview

The championship system manages title matches and ensures proper competitor validation.

`Title` owns only the championship relationships. Current, previous, first, longest, vacancy, reign-count, and reign-length reporting is provided by `TitleChampionshipQuery`, keeping reporting queries and in-memory summaries outside the Eloquent model.

Models expose their explicit persisted naming fields: `name` for wrestlers and titles, and the database-generated `full_name` for managers and referees. They do not infer or append a generic `display_name` attribute through a shared model contract.

## Title Type Matching

### Championship Rules
- **Singles Titles**: Can only be held by individual wrestlers
- **Tag Team Titles**: Can only be held by tag teams
- **Match Validation**: Title matches must use compatible competitor types
- **Champion Defense**: Current champions can defend against appropriate challengers

## Persistence Boundaries

`TitleType` is the canonical classification value; consumers compare the model's cast `type` attribute with its enum cases instead of relying on model predicate aliases. Wrestlers and tag teams implement `CanBeChampion` and share their polymorphic championship-history relationships through `HasChampionshipReigns`. Because either champion type may hold multiple titles simultaneously, `currentChampionships` is the authoritative current-state relationship; champion models do not expose a singular `currentChampionship` relationship. A `Title` owns its `championships` and singular `currentChampionship` relationships directly because each title has at most one current reign.

`Title::status` is computed from activity-period relationships and is not a stored or cast database attribute. Only the persisted title `type` value is enum-cast.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Core Capabilities](core-capabilities.md)
