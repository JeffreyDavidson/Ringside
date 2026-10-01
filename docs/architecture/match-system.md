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

Match assignment actions lock the complete scheduling event set, then the match, and only then reload and lock selected wrestlers, tag teams, referees, or titles. Availability and conflict checks use those locked rows, so stale caller models cannot bypass current booking rules and concurrent assignments serialize across the same event window.

### Canonical lock order for scheduling

Every scheduling operation acquires locks in this order. `MatchAssignmentConflictService` owns the row-lock helpers (`lockEventSet()`, `lockMatchWithEventSet()`) and `SchedulingSlotLockService` owns the date-slot lock:

0. Only for actions that move an event to a date or bring one back at its date (`Events\UpdateAction`, `Events\RestoreAction`): the date-slot lock of the old and new date, ascending by timestamp (see below). Booking actions never take it.
1. The complete scheduling event set in one statement ordered by ascending id: the action's own event plus every other event on the same exact date and time (an unscheduled event's set is itself alone, because unscheduled events conflict only within their own card).
2. The match row (inserted last for `AddMatchForEventAction`, so nobody else can lock it first).
3. Competitors and other resources in ascending id: referees, wrestlers, tag teams, then titles and their current reign. A champion's own cascade (retirement, release, or deletion) closes its current reigns in ascending reign id too, so it queues behind a multi-title result instead of inverting it (see Cascade lock order in `lifecycle-operation-boundaries.md`).

Locking the own event first and the rest of its date afterwards let two bookings on different events at the same time each hold one event and wait for the other, which PostgreSQL resolves as a deadlock (SQLSTATE 40P01) and the user saw as a server error even without a real conflict. Taking the whole set in one ordered statement makes the second booking queue behind the first, and it then sees the first booking's committed rows and raises a normal `SchedulingConflictException` when there is a genuine clash.

Actions that start from an existing match (`UpdateMatchAction`, `RecordResultAction`, and the standalone `Add*ToMatchAction::handle` entry points) cannot lock the match first, because another booking holds the event set and then wants that match. They read the match's event without a lock, lock that event's set, then lock the match and confirm it still belongs to that event (repeating once if it moved). The event date is verified the same way, so an event rescheduled between the read and the lock has its new set locked instead. The `handleWithinTransaction` variants run inside the caller's transaction and only re-issue the same ordered set statement.

`AddMatchForEventAction`, `UpdateMatchAction`, and the standalone assignment actions run their outermost transaction with `attempts: 3`, matching `Events\UpdateAction`, as a backstop: Laravel re-runs a transaction that lost a deadlock only when it is the outermost one. Their closures only write to the database, so a retry is safe.

### Date-slot lock for reschedules and restores

Row locks cannot lock a row that does not exist yet. Two `Events\UpdateAction` calls that move two different events into the same *empty* date lock nothing at that date, so under READ COMMITTED neither sees the other's uncommitted move and both pass `ensureEventCanBeRescheduled()`. If the events share a wrestler, tag team, referee, or title, that resource ends up booked twice at the same instant (a write-skew race, reproduced on PostgreSQL).

`SchedulingSlotLockService` (`app/Services/Matches`) closes that phantom slot. `Events\UpdateAction` calls it first in its transaction, only when the date changes, for the old and the new date, before `refreshForUpdate()` or any other row lock. The rest of the flow is unchanged: own event row, venue row, then `ensureEventCanBeRescheduled()`, which row-locks the events already at the target date. Because reschedules into one slot now run one at a time, the second one starts after the first has committed and sees its move.

`Events\RestoreAction` follows the same flow for the event's existing date, because restoring puts the event's matches back on that date's schedule: slot lock of the event's date (none for an unscheduled event), own event row, venue row (`VenueSchedulingEligibility`), then `ensureEventCanBeRescheduled($event, $event->date)` before the restore, which throws `SchedulingConflictException` and leaves the event deleted when a wrestler, tag team, referee, or title of the event's matches is booked in another event at that date. Only matches that still exist are checked; a soft-deleted match stays deleted on restore and is not re-validated. It uses `attempts: 3` like `UpdateAction` (the closure only writes to the database, so a retry is safe). It does not use `lockEventSet()`, which cannot load a soft-deleted event: a deleted event is invisible to booking actions' event sets, so nobody can hold-and-wait on its row, and the other events at the date are locked in ascending id by `ensureEventCanBeRescheduled()` exactly as for a reschedule.

- **Key derivation.** The lock is `pg_advisory_xact_lock(int, int)`: the first integer is the fixed namespace `0x534C4F54` ("SLOT"), which keeps these locks apart from any other advisory lock, and the second is `crc32` of the exact slot's unix timestamp mapped to a signed 32-bit integer. The derivation is deterministic, so every process derives the same key for the same instant. Two different instants may share a key; that only serializes them needlessly and can never break correctness.
- **Ordering.** Slots are locked in ascending timestamp order, deduplicated, so two transactions that both need two slots always queue in the same order. This also removes the deadlock two events swapping dates used to cause. Null dates take no lock.
- **Driver behaviour**, following how the migrations treat drivers: `pgsql` takes the advisory lock, which is transaction scoped (released automatically at commit or rollback, so it is safe under PgBouncer transaction pooling and can never leak); `sqlite` takes nothing because SQLite serializes writers; any other driver throws a `LogicException` rather than silently running unprotected.
- **Compatibility with the canonical order.** Slot locks are advisory and are taken before any row lock. Booking actions (`AddMatchForEventAction`, `UpdateMatchAction`, the standalone adders, `RecordResultAction`) never wait for a slot lock, so the only waiters on a slot lock hold no other lock and no cycle can include it. A reschedule into a date that holds an event being booked waits on that event's row in `ensureEventCanBeRescheduled()`, the same event-set queue the bookings use, and re-evaluates against the committed matches afterwards. Booking actions therefore do not need the slot lock: they lock the events that already exist, and an event that appears at their date later only does so through a reschedule that runs behind them.
- **Not covered by the slot lock.** `Events\CreateAction` does not take it: a new event has no matches, so it cannot double-book a resource, and its only conflict rule is the venue rule, which `CreateAction` and `UpdateAction` already serialize by locking the venue row. The date is read from the caller's event model because the row cannot be locked before the slot; a concurrent change of that same event's date between loading the model and starting the transaction can therefore leave a slot unlocked; that narrow window is not covered.

Recorded outcomes are checked by `MatchOutcomeRequirements`, which explicitly composes focused winning-side, entry-order, and elimination-history requirements. Each requirement owns one cohesive rule family and raises `InvalidMatchOutcomeException`; the coordinator preserves their deterministic validation order without using a dynamic specification registry or mutation pipeline. `RecordResultAction` locks the event's scheduling set, the match, its event date, the selected winning side, and the complete competitor collection before validation. Outcome requirements and championship reconciliation consume that shared snapshot rather than querying mutable match state independently.

## Event Card Scheduling

Match assignments observe these collision rules:

- A wrestler, tag team, or title may be assigned only once on an event card.
- A wrestler, tag team, or title may not be assigned to different events scheduled for the same exact date and time.
- A referee may officiate multiple matches on one event card, but may not officiate matches on different events scheduled for the same exact date and time.
- An unscheduled event still prevents duplicate assignments within its own card. Its missing date does not conflict with other unscheduled events.

Assignment actions lock the affected event rows and enforce these rules inside their database transactions. This serializes assignment commands that use the application boundary. The schema cannot enforce overlapping match windows because individual matches do not currently have their own start and end times.

Roster booking eligibility is evaluated by `RosterBookingEligibility`, not by Eloquent models or builders. The policy combines persisted employment, injury, suspension, future-employment, and tag-team membership state. A tag team must satisfy its own lifecycle state, have at least two current wrestlers, and have every current wrestler individually eligible. Models expose those relationships and state predicates (`isInjured()` and `isSuspended()`, which the booking strategies also use to drive the Injured and Suspended availability badges on roster lists and profiles; `withAvailabilityState()` projects them to avoid per-row queries); validation rules, assignment Actions, and collections invoke the policy when deciding whether a participant may be booked. `MatchCompetitorsCollection` owns typed wrestler and tag-team partitions for loaded competitor entries so callers do not repeat polymorphic filtering.

The Livewire match form applies model-specific booking rules to every selected wrestler, tag team, and referee before constructing `EventMatchData`. Assignment Actions repeat the eligibility check as the authoritative transactional boundary so non-UI callers and state changes between validation and persistence remain protected.

Assignment Actions treat each requested collection as an atomic command. They reject the entire assignment when any selected wrestler, tag team, referee, or title is unavailable; they never silently discard unavailable selections and persist a partial request. Repeated selections of the same record are normalized before assignment.

`MatchCompetitorRequirements` authoritatively validates competitor types and composition before assignments are persisted. Match formats whose names encode a roster-member count use the current wrestlers represented by each selected tag team: standard and tornado tag matches require 2-on-2, six/eight/ten-person tag matches require 3/4/5 per side, and handicap matches require 2-on-1 or 3-on-2 in either side order. A wrestler cannot also be selected directly when represented by a selected tag team.

Competitor entries and represented roster members are separate concepts. Singles, Triple Threat, Triangle, and Fatal 4-Way matches require exactly one wrestler or tag-team entry on each side, subject to the match type's allowed competitor types. Battle Royal and Royal Rumble entrants each occupy an individual side.

`MatchStipulation` is an optional match configuration selected from active definitions when a match is created or edited. The match retains that relationship as historical configuration even if the definition is later made inactive. Stipulation capabilities and match presentation must be implemented by the match domain when they are enforced; the model does not infer behavior from hard-coded slug lists.

`AddMatchForEventAction` receives side-based `EventMatchData`, locks the owning event's scheduling set, allocates the next card position without reusing soft-deleted match numbers, and persists the match, officials, championship stakes, sides, and competitors in one transaction. Assignment Actions retain eligibility and scheduling-conflict enforcement for their respective relationships.

`UpdateMatchAction` receives the same typed data, locks the event set and then the match, and replaces its configuration and assignments in one transaction. Any unavailable replacement rolls the entire edit back to the previous configuration. Once a result has been recorded, the match configuration is immutable so its sides and competitors continue to describe that result.

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
