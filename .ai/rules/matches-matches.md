---
paths:
  - 'app/{Enums/MatchType.php,Livewire/Matches/**,Actions/Matches/**,Lifecycle/MatchCompetitorRequirements.php}'
---

# Matches Matches

## Distinguish competitor entries from represented roster members
Enforce match formats with both concepts explicitly. A selected TagTeam is one competitor entry but represents all current wrestlers for named team and handicap side sizes; reject selecting one of those wrestlers directly in the same match. MatchCompetitorRequirements remains the authoritative persistence boundary.
