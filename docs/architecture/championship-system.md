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

## Match Outcomes

`ApplyMatchTitleOutcomesAction` is the match-side championship orchestrator composed by `RecordResultAction`. It locks every attached title and its reigns before applying a result, so winner metadata and championship changes commit or roll back together. `ChampionshipReignManager` is the single reign write boundary: it opens, closes, and reconciles persisted reigns for match outcomes, title retirement, title deletion, and champion relationship cleanup. Reporting remains in `TitleChampionshipQuery`.

Assign match competitors before attaching championship stakes. The match form applies the data-aware `CurrentChampionIsCompeting` rule to each selected title so invalid title defenses receive field-level validation before data construction. `AddTitlesToMatchAction` repeats the invariant authoritatively and rejects any non-vacant title whose current wrestler or tag-team champion is not already assigned as a competitor; vacant titles do not require a defending champion.

A champion defense leaves the current reign open. A compatible challenger winning by a title-changing finish closes the current reign with the match and event date, then creates the challenger's reign with the same match and date. A vacant title creates only the new reign. Winner-take-all matches apply that transition independently to every attached title inside the same transaction.

Correcting a result soft deletes a reign incorrectly created by that match and reopens the preceding reign before applying the corrected outcome. Corrections are rejected after a later reign has been recorded because rewriting that earlier result would invalidate dependent lineage.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Core Capabilities](core-capabilities.md)
