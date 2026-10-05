# Championship System

Title matches and championship validation rules.

## Overview

The championship system manages title matches and ensures proper competitor validation.

`Title` owns only the championship relationships. Current, previous, first, longest, vacancy, reign-count, and reign-length reporting is provided by `TitleChampionshipQuery`, keeping reporting queries and in-memory summaries outside the Eloquent model.

The previous-championship history tables show, for each ended reign, the reign it followed: `TitleChampionshipBuilder::withPreviousChampionshipId()` selects the latest non-deleted reign of the same title won before it. Its subquery aliases the table as `previous_championships`, and Eloquent qualifies the soft-delete constraint with that alias, so a deleted reign (for example one removed by a result correction) is never reported as the previous champion.

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

`ApplyMatchTitleOutcomesAction` is the match-side championship orchestrator composed by `RecordResultAction`. The parent action locks the match, its event date, and its complete competitor collection, then passes that shared snapshot into championship reconciliation. The title action locks every attached title and its reigns before applying the result, so winner metadata and championship changes commit or roll back together. `ChampionshipReignManager` is the single reign write boundary: it opens, closes, and reconciles persisted reigns for match outcomes, title retirement, title deletion, and champion relationship cleanup. Reporting remains in `TitleChampionshipQuery`.

Assign match competitors before attaching championship stakes. The match form applies the data-aware `CurrentChampionIsCompeting` rule to each selected title so invalid title defenses receive field-level validation before data construction. `AddTitlesToMatchAction` repeats the invariant authoritatively and rejects any non-vacant title whose current wrestler or tag-team champion is not already assigned as a competitor; vacant titles do not require a defending champion.

The database backs the one-current-reign rule: the partial unique index `titles_championships_one_open_reign_unique` on `titles_championships (title_id) WHERE lost_at IS NULL AND deleted_at IS NULL` (a stored generated `open_reign_title_id` column plus unique index on MySQL/MariaDB). Soft-deleted open reigns do not count, which is why reconciliation soft deletes before it reopens or creates a reign.

A champion defense leaves the current reign open. A compatible challenger winning by a title-changing finish closes the current reign with the match and event date, then creates the challenger's reign with the same match and date. A vacant title creates only the new reign. Winner-take-all matches apply that transition independently to every attached title inside the same transaction.

Correcting a result soft deletes a reign incorrectly created by that match and reopens the preceding reign before applying the corrected outcome. Corrections are rejected after a later reign has been recorded because rewriting that earlier result would invalidate dependent lineage.

A correction reopens the preceding reign only when it is still valid: the title must still be active and the preceding champion must still exist and be employed (not retired, released, or soft deleted). Otherwise the preceding reign stays closed by this match, the title is left vacant (or crowns the corrected winner), and nothing is reopened for a champion who can no longer hold the title.

Title results are recorded in date order. `ChampionshipReignManager::ensureMatchCanBeReconciled()` runs for every attached title before any write, and rejects the result with `InvalidMatchOutcomeException::titleResultOutOfDateOrder()` when it would create or change a reign (a title-changing winner, or a correction of the reign that match already created) while the title already has another non-deleted reign won strictly after the event date. The whole `RecordResultAction` transaction rolls back, so the match result and every other attached title stay unchanged. Reigns won at the same instant are allowed, results that leave the champion unchanged (draws, disqualifications, no-decisions with no reign at that match) are unaffected, and an undated event still fails with the undated-title-match exception. The existing lineage guard still fires first when the match's own reign has already been closed by a later reign.

The date-order check also rejects a result that would create a reign when the event date falls inside another reign's closed interval (`won_at <= date < lost_at`), for example a back-dated result inside a reign that was vacated later by a retirement, release, or title retirement. A reign ending exactly at the event date does not block it. A correction that leaves the champion unchanged (the reign created by this match already belongs to the desired winner, such as correcting pinfall to submission) is a no-op and is never rejected for date order, even after a later vacancy and new reign.

### Result preconditions

`RecordResultAction` rejects every result, title or not, unless the event has already happened (`date <= now`; a future or unscheduled event fails with `InvalidMatchOutcomeException::eventNotHeld()`), so a reign can never be dated in the future. Inside the same locked transaction, a result that would put a different champion on a title re-checks current state before writing: the title must not be soft deleted (`titleDeleted()`) and the winner must still exist (`winnerDeleted()`). When the event is on the current local day or later (the day is read in the promotion's time zone via `Promotion::toLocalTime()`), the title must also still be active (`titleNotActive()`, so a pulled or retired title never gains a reign) and the winner must pass `RosterBookingEligibility` (`winnerNotEligible()`; retired, released, unemployed, injured, and suspended winners are rejected). An event from an earlier local day is a back-fill: booking already validated eligibility, so a winner who was injured, or a title that was pulled, after the event can still be recorded. Results that do not change the champion (draws, disqualifications, a champion's own defence, corrections to the same winner) are not blocked by these checks, and a deleted title is skipped for them: `ApplyMatchTitleOutcomesAction` throws `titleDeleted()` only when `ChampionshipReignManager::changesChampion()` says the result would change that title's champion, so re-recording a deleted title's match with a different title-changing finish for the same winner still succeeds and leaves its reigns untouched.

### Reign dates

A reign never ends before it began: `endCurrentReign()` and `endCurrentReignsForChampion()` clamp `lost_at` to `max(effective date, won_at)`. Reign length reporting (`TitleChampionshipQuery::reignLengthInDays()`) is clamped at zero days. Tag-team retirement ends the team's current reigns in the same locked transaction, like release and deletion do; wrestler retirement already did so.

An event whose matches have created or closed a non-deleted reign cannot be moved or unscheduled (`CannotBeRescheduledException::hasTitleReigns()`, enforced in `EventSchedulingEligibility::ensureDateCanChange()` for both the Livewire rule and `Events\UpdateAction`), because reigns store the event date.

### Title type

A title's type (singles or tag team) is locked once it has any championship reign or is booked in a non-deleted match (`TitleTypeEligibility`, enforced by `Titles\UpdateAction` with `CannotChangeTypeException`). The title form modal renders the type select disabled with an explanatory note in that case.

## Related Documentation
- [Business Rules](business-rules.md)
- [Match System](match-system.md)
- [Core Capabilities](core-capabilities.md)
