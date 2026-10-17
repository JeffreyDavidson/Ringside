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

## Talent attendance agreements

The event's **Talent bookings** page records individual agreements to attend, independently of match eligibility, card assignment and live-show arrival/briefing. Its route, component reads/searches and every write require the event's `update` ability. Promotion Owners/Managers and permitted platform administrators can use it; view-only Members cannot access its page or private Livewire state.

`TalentBookingRosterQuery` merges the card roster from `EventPackCardService` with saved event bookings. Each direct wrestler, current tag-team wrestler and referee has their own identity; an agreement or fee never transfers to a team or replacement member. Untracked card people are labelled Not tracked. Staff can propose existing wrestlers/referees before they appear on the card, using a bounded, promotion-scoped search which includes unavailable individuals because factual agreement and match eligibility are separate.

The `TalentBookings` component authorizes the event's `update` ability before every call. `SaveTalentBookingAction` owns Proposed, Contacted, Confirmed and Cancelled writes. It locks/reloads the event, then its booking, then promotion/venue context, checks the expected version and current agreement context, and persists the booking plus one immutable `EventTalentBookingRevision` atomically. The event lock serializes first creation; database uniqueness guarantees one booking per event/person and one revision per version. Stale saves preserve the editor's input. Unchanged saves append no revision. Cancellation requires a reason and retains terms; reopening starts at Proposed. Confirmed-to-Proposed/Contacted corrections also require a reason.

Confirmation requires a scheduled date and an explicit staff attestation that agreement happened outside Ringside. Date/time, promotion timezone, venue identity/address or event deletion/restoration changes trigger reconfirmation when compared with the stored agreement. Display-name changes and match reorder/opponent/stipulation edits do not invalidate attendance. Returning to the exact original context clears a context-difference warning, while deletion/restoration and pending term changes still require review. Card removal preserves the agreement and flags removal until the person returns to the card, staff reconfirms their additional event booking, or cancels it.

Fees are nullable exact integer cents in explicitly labelled USD; unknown differs from agreed zero. Arrival is an optional UTC instant entered in the promotion timezone, with its recorded timezone retained. Unchanged repeated-hour arrival input preserves the original instant. Travel notes and cancellation/correction reasons are private escaped text. Confirmed terms can be saved pending reconfirmation or explicitly recorded as a newly agreed change. Old terms/context remain in durable revisions, outside the pruned general activity log. Roster soft deletion and event soft deletion retain the booking/history; physical parent deletion is restricted. Revision updates/deletes are refused by the immutable-record observer.

`TalentBookingsViewModel` computes availability/context/card warnings on reads, explicit Refresh and saves. It never silently cancels or rewrites a confirmation; current unavailability remains a warning even when a factual agreement is recorded. Past-event warnings explicitly describe current roster state. Confirmation is not an exclusive schedule reservation and creates no cross-event availability policy. Existing match conflict/eligibility rules remain authoritative for the card.

Booking terms and reasons are excluded from ordinary event, event-pack/print and live-show payloads, public pages, email and general activity logging. The dedicated editor shows a paginated private history. There are no talent links, outgoing messages, payments, payroll or attendance integration.

## Selecting Competitors and Referees in the Match Form

The match form never embeds the roster. Wrestlers, tag teams and referees are chosen through a searchable select (`x-form.inputs.roster-combobox`, built on the Alpine UI combobox and `resources/js/roster-combobox.js`). The component keeps the chosen ids in the form (deferred, like `wire:model`) and calls the renderless `FormModal::searchRoster($kind, $term)` action as the user types. `BookableRosterSearch` answers it with at most 20 `{id, name}` options, ordered by name, from the booked event's promotion only (the edited match's own event, otherwise the modal's event; global scopes still apply on top), matching case-insensitively with `whereLike(..., caseSensitive: false)`. `%`, `_` and `\` are stripped from the term, so it can only ever match literally. An empty term returns the first 20 by name. Titles stay a plain select because there are few of them.

Only bookable records are offered. `IndividualBuilder::bookable()` (wrestlers and referees) and `TagTeamBuilder::bookable()` project the same rules as `RosterBookingEligibility`: currently employed, not retired, not suspended and not injured; tag teams are employed, not retired and not suspended, have at least `TagTeamMembershipRequirements::MINIMUM_CURRENT_WRESTLERS` current wrestlers, and every current wrestler is individually bookable. `BookableScopeParityTest` asserts `Model::query()->bookable()->whereKey($m)->exists()` equals `RosterBookingEligibility::allows($m)` for every roster state, so a change to either side fails the test. There are no known gaps.

The dropdown is a convenience, not a security boundary. Submission still validates every id: the form's `exists` rules for wrestlers, tag teams, referees and titles are scoped to the booked event's `promotion_id` (`CreateEditForm::validateForEvent()` and `MatchCompetitorRuleSet`), not to the request's promotion context, and the `IsBookable` rules apply `RosterBookingEligibility`. The assignment actions re-check availability under lock, so a forged id that was never offered is rejected exactly as before.

### Bookings stay inside the event's promotion

Platform administrators are global: without a membership they have no enforced promotion context, so promotion-scoped queries (including the `IsBookable` rules' `find`) see every promotion's records. The request context therefore cannot keep a booking inside one promotion. `AddMatchForEventAction` and `UpdateMatchAction` are the authority: `MatchConfigurationRequirements::ensureWithinEventPromotion()` rejects any referee, title, wrestler or tag team whose `promotion_id` differs from the event's with `InvalidMatchConfigurationException` ("Selected wrestlers must all belong to the event's promotion."), before anything is written. It compares the models in `EventMatchData` and runs no locking query (`UpdateMatchAction` reads the event of the match it has already locked), so the canonical lock order is unchanged. The standalone `Add*ToMatchAction::handle()` entry points repeat the check for their own records through `ensureParticipantsWithinEventPromotion()`, right after locking the match and before attaching anything (`handleWithinTransaction()` relies on its caller having checked). A foreign record on a card would also break the card for the event promotion's members, whose scoped queries cannot load it.

Retiring or releasing a booked wrestler, referee or tag team stays allowed, but the confirmation on their show page lists the upcoming events they are booked in. `UpcomingBookings` reads those matches (upcoming or unresulted, soft-deleted matches excluded, every promotion included) and also backs the delete-eligibility checks; the `Actions` Livewire components pass the event list to the `core.lifecycle_confirmations.release_booked` and `retire_booked` messages. The confirmation list also includes events booked through the relationships those actions end: a tag team's list adds the matches of its current wrestlers (retiring a team retires them) and a wrestler's adds the matches of their current tag team (leaving a wrestler ends the membership). It shows an unscheduled event as "(Unscheduled)" and lists the first five events, then "and N more".

When editing, ids already stored on the match are labelled by `FormModal::selectedRosterLabels`, one query per kind that includes soft-deleted rows and ignores bookability, so a competitor who has since retired or been deleted still shows by name. The rendered modal HTML therefore stays the same size for 5 or 5,000 wrestlers. An id with no known label shows a neutral "Selected record", never a raw id.

How the combobox behaves:

- Focus preloads the first 20 results; a click (or typing, or the arrow keys) opens the list. Opening on focus was tried and dropped: Alpine UI refocuses the input on the next frames, so a list that opens on focus can pull focus back from the next field.
- In multiple mode the search box is always empty and the chips show the selection (Alpine UI passes the whole selection to `display-value`). In single mode it shows the chosen name, and typing over that name starts a new search.
- Every keystroke clears the visible options and marks the list as searching before the debounced request goes out, and Enter is ignored while a search is in flight, so Enter can never pick an option from the previous term. A ticket discards responses for older terms, and `loading` is reset in a `finally` so a failed request cannot leave the list searching forever.
- A polite status region announces "Searching…", the result count, "No bookable matches" or "Showing the first 20 matches. Keep typing to narrow the list."; the visible list shows the same rows. Each input is described by a usage hint and, when present, by its error (`{field}-hint`, `{field}-error`).
- Escape closes an open list and keeps the modal open; with the list closed, Escape is passed on so the modal closes. Removing a chip moves focus to the next chip's remove button, or to the search box after the last one.

Tag team and multi-side layouts wrap each side in a `fieldset` whose legend is `CreateEditForm::sideLabel()` (Team A, Side 1, Competitor 1), and the comboboxes carry that name as a hidden prefix so every search box has a unique accessible name. The same labels name the sides in validation messages ("Add at least 2 wrestlers or a tag team to Team A."). Because the comboboxes always send both a `wrestlers` and a `tag_teams` list, `CreateEditForm::prepareForValidation()` drops empty lists before validation: an empty list means nothing of that kind was chosen, so a side booked with a tag team is not also asked for wrestlers.

## Date availability calendar

Wrestlers and referees can have inclusive promotion-local unavailable date ranges in `roster_unavailabilities`. Each record belongs to the person and promotion, retains an optional internal reason and its creation/cancellation actor, and is cancelled rather than deleted. Overlaps remain independent; cancelling one does not remove another restriction. Availability writes lock the owning person, reject exact repeated active submissions and record a compact activity audit in the same transaction. Owners and managers manage entries; members can view them through the existing promotion Gate. The profile component validates the dates and reason and authorizes `update` in each of `review`, `save`, `requestCancel` and `cancel` before calling the create or cancel Action; the Actions neither validate nor check permissions. `Unavailability` fills only the dates and reason; the promotion, person identity, creating actor and cancellation fields are assigned explicitly by the create and cancel Actions.

`RosterDateAvailability` owns the date restriction independently of employment, injury and suspension. `RosterBookingEligibility::allows()` and `bookable()` keep their existing lifecycle-only meaning, including for title results and historical result recording. Individual/team `bookableOn()` queries compose those lifecycle predicates with the date restriction; `BookableScopeParityTest` covers both projections. Teams derive date availability from every current wrestler, not just the minimum two. Former members are ignored.

The event's stored UTC start is converted to a calendar date in its actual promotion's IANA timezone. Date columns remain calendar strings, not 24-hour UTC intervals. A promotion timezone change keeps the event instant and entered date strings; its changed local event day recomputes restrictions. Missing event dates are explicitly not checked. This feature evaluates the event start day and does not model travel, event duration, arrival/departure windows or calendar exports.

Search, form rules and assignment Actions use the booked event's day. New date-unavailable assignments fail atomically with `EntityNotAvailableException`. Adding an entry leaves existing assignments on the card and displays warnings. A match edit may retain an already stored assignment despite its calendar restriction: `UpdateMatchAction` derives retained keys under its match lock before replacing rows; new assignments and existing lifecycle rules are still checked. No retained-key list comes from the client. The form independently checks its persisted match assignments for the same retention rule.

`MatchRosterLock` prelocks the entire requested roster before per-side assignment: referees, selected tag-team parents, then direct wrestlers and all current team members, each kind ordered by ID. This preserves event-set-before-match locking and prevents a direct-wrestler side from taking a team member before another side takes that member's team. Standalone team assignment also locks represented members after the team parents. Availability writes take only the person/entry locks and do not acquire event locks afterwards. Booking-first followed by unavailability is allowed and flags the retained booking; unavailability-first rejects the new booking. The native PostgreSQL/MySQL harness exercises direct, team and event-reschedule races.

Scheduling an undated event or rescheduling to a conflicting day is rejected by the form and locked event Action, even when no other event occupies that exact timestamp. Unchanged-date edits stay possible. Restoring a future event checks its lineup; restoring past history and recording results do not retrospectively apply calendar restrictions. Existing venue-day and exact-instant roster conflict policies retain their meanings.

Profile availability sections provide a review of affected bookings before save, plus active entries and paginated history. The promotion calendar is a searchable day agenda with person-type filters and affected event links. `EventPackCardService` and the event card add generic calendar warnings for upcoming/unresulted assignments without exporting reasons. Saved packs/live runs retain their content and actual timing/attendance; changed card fingerprints follow the existing review flow rather than auto-saving snapshots.

## Match Assignment Failures

Match configuration and participant availability are separate failure boundaries. `InvalidMatchConfigurationException` describes an incomplete or structurally invalid match, such as missing referees, missing competitors, insufficient populated sides, or an invalid side number. `EntityNotAvailableException` describes a wrestler, tag team, referee, or title whose current state prevents assignment. `SchedulingConflictException` is reserved for an actual collision between bookings, times, or resources and must not substitute for either boundary.

Match assignment actions lock the complete scheduling event set, then the match, and only then reload and lock selected wrestlers, tag teams, referees, or titles. Availability and conflict checks use those locked rows, so stale caller models cannot bypass current booking rules and concurrent assignments serialize across the same event window.

### Canonical lock order for scheduling

Every scheduling operation acquires locks in this order. `MatchSchedulingLock` owns the row-lock helpers (`lockEventSet()`, `lockMatchWithEventSet()`) and `SchedulingSlotLock` owns the date-slot lock:

-1. Only for `Events\CreateAction` and `Events\UpdateAction`: the event name lock (`RecordNameLock`, see [Core Capabilities](core-capabilities.md)), before everything below. It rejects a name another event of the promotion already uses, which no index enforces. Every Action that takes name locks takes them before any other lock of its transaction, and no booking action ever waits for one, so a name-lock waiter holds nothing and the lock cannot join a deadlock cycle with the slot, event, venue and competitor locks.
0. Only for actions that move an event to a date or bring one back at its date (`Events\UpdateAction`, `Events\RestoreAction`): the date-slot lock of the old and new date, ascending by timestamp (see below). Booking actions never take it.
1. The complete scheduling event set in one statement ordered by ascending id: the action's own event plus every other event on the same exact date and time (an unscheduled event's set is itself alone, because unscheduled events conflict only within their own card).
2. The match row (inserted last for `AddMatchForEventAction`, so nobody else can lock it first).
3. Competitors and other resources in ascending id: referees, selected tag-team parents, direct wrestlers and represented team members, then titles and their current reign. `MatchRosterLock` acquires the roster set before nested per-side assignment checks; teams must precede their member rows, as they do in lifecycle cascades. A champion's own cascade (retirement, release, or deletion) closes its current reigns in ascending reign id too, so it queues behind a multi-title result instead of inverting it (see Cascade lock order in `lifecycle-operation-boundaries.md`).

Locking the own event first and the rest of its date afterwards let two bookings on different events at the same time each hold one event and wait for the other, which PostgreSQL resolves as a deadlock (SQLSTATE 40P01; MySQL reports error 1213) and the user saw as a server error even without a real conflict. Taking the whole set in one ordered statement makes the second booking queue behind the first, and it then sees the first booking's committed rows and raises a normal `SchedulingConflictException` when there is a genuine clash.

Actions that start from an existing match (`UpdateMatchAction`, `RecordResultAction`, and the standalone `Add*ToMatchAction::handle` entry points) cannot lock the match first, because another booking holds the event set and then wants that match. They read the match's event without a lock, lock that event's set, then lock the match and confirm it still belongs to that event (repeating once if it moved). The event date is verified the same way, so an event rescheduled between the read and the lock has its new set locked instead. The `handleWithinTransaction` variants run inside the caller's transaction and only re-issue the same ordered set statement.

Unique indexes back the match invariants: `events_matches_event_id_match_number_unique` on `(event_id, match_number)` includes soft-deleted matches because numbers are never reused, and `events_matches_referees_match_referee_unique` and `events_matches_titles_match_title_unique` allow each referee and title once per match. Each migration first aborts, without changing data, and lists the offending ids when existing rows would violate its index.

`AddMatchForEventAction`, `UpdateMatchAction`, and the standalone assignment actions run their outermost transaction with `attempts: 3`, matching `Events\UpdateAction`, as a backstop: Laravel re-runs a transaction that lost a deadlock only when it is the outermost one. Its concurrency error detector recognizes PostgreSQL deadlocks (40P01), MySQL deadlocks (1213, SQLSTATE 40001) and MySQL lock wait timeouts (1205), so the retry needs no engine-specific code. Their closures only write to the database, so a retry is safe.

### Date-slot lock for reschedules and restores

Row locks cannot lock a row that does not exist yet. Two `Events\UpdateAction` calls that move two different events into the same *empty* date lock nothing at that date, so under READ COMMITTED neither sees the other's uncommitted move and both pass `ensureEventCanBeRescheduled()`. If the events share a wrestler, tag team, referee, or title, that resource ends up booked twice at the same instant (a write-skew race, reproduced on PostgreSQL).

`SchedulingSlotLock` (`app/Lifecycle/Events`) closes that phantom slot. `Events\UpdateAction` calls it first in its transaction, only when the date changes, for the old and the new date, before `refreshForUpdate()` or any other row lock. The rest of the flow is unchanged: own event row, venue row, then `ensureEventCanBeRescheduled()`, which row-locks the events already at the target date. Because reschedules into one slot now run one at a time, the second one starts after the first has committed and sees its move.

`Events\RestoreAction` follows the same flow for the event's existing date, because restoring puts the event's matches back on that date's schedule: slot lock of the event's date (none for an unscheduled event), own event row, venue row (`VenueSchedulingEligibility`), then `ensureEventCanBeRescheduled($event, $event->date)` before the restore, which throws `SchedulingConflictException` and leaves the event deleted when a wrestler, tag team, referee, or title of the event's matches is booked in another event at that date. Only matches that still exist are checked; a soft-deleted match stays deleted on restore and is not re-validated. While the event's venue is soft-deleted the restore is refused with `Venues\CannotBeRestoredException::venueDeleted()` ("Restore the venue first"), because a deleted venue cannot be checked and its day may have been taken while the event was deleted. Restoring a venue in turn refuses (`VenueDeletionEligibility`, `CannotBeRestoredException::venueDoubleBooked()`) when two or more of its live events fall on one calendar day in the venue's time zone, as defence in depth for data created another way. Both refusals reach the index-table Restore as the toast text. It uses `attempts: 3` like `UpdateAction` (the closure only writes to the database, so a retry is safe). It does not use `lockEventSet()`, which cannot load a soft-deleted event: a deleted event is invisible to booking actions' event sets, so nobody can hold-and-wait on its row, and the other events at the date are locked in ascending id by `ensureEventCanBeRescheduled()` exactly as for a reschedule.

- **Lock row.** The lock is a row of the `scheduling_slot_locks` table, keyed by the exact slot's unix timestamp (`slot`, primary key), so every process locks the same row for the same instant and two different instants never share one. Locking a slot is a single upsert of that row (`upsert(['slot' => $timestamp], ['slot'], ['slot'])`), which holds an exclusive row lock until the surrounding transaction commits or rolls back, so it can never leak and is safe behind transaction pooling. The rows carry no domain data and nothing reads them; one row is kept per slot ever locked.
- **Ordering.** Slots are locked in ascending timestamp order, deduplicated, so two transactions that both need two slots always queue in the same order. This also removes the deadlock two events swapping dates used to cause. Null dates take no lock.
- **Engine behaviour.** The same query builder call runs on every supported engine. MySQL compiles it to `INSERT ... ON DUPLICATE KEY UPDATE`, which takes an exclusive record lock on an existing key (a plain `INSERT IGNORE` would take a shared lock, and two waiters upgrading shared locks deadlock). PostgreSQL compiles it to `INSERT ... ON CONFLICT ("slot") DO UPDATE`, which locks the conflicting row. On both, a transaction upserting a key that another uncommitted transaction has just inserted waits for that transaction and then locks the committed row. SQLite compiles the same `ON CONFLICT` form and serializes all writers, so the write itself is the lock. PostgreSQL used to take a transaction-scoped advisory lock and every other driver except SQLite was rejected, so rescheduling or restoring an event failed on MySQL; the table works identically on all three engines.
- **Isolation.** The design assumes READ COMMITTED, PostgreSQL's default: once the slot lock is granted, the following conflict checks see the other transaction's committed move. MySQL defaults to REPEATABLE READ, so `config/database.php` sets `isolation_level` to `READ COMMITTED` for the `mysql` connection.
- **Compatibility with the canonical order.** Slot locks are taken before any row lock. Booking actions (`AddMatchForEventAction`, `UpdateMatchAction`, the standalone adders, `RecordResultAction`) never wait for a slot lock, so the only waiters on a slot lock hold no other lock and no cycle can include it. A reschedule into a date that holds an event being booked waits on that event's row in `ensureEventCanBeRescheduled()`, the same event-set queue the bookings use, and re-evaluates against the committed matches afterwards. Booking actions therefore do not need the slot lock: they lock the events that already exist, and an event that appears at their date later only does so through a reschedule that runs behind them.
- **Not covered by the slot lock.** `Events\CreateAction` does not take it: a new event has no matches, so it cannot double-book a resource, and its only conflict rule is the venue rule, which `CreateAction` and `UpdateAction` already serialize by locking the venue row. The date is read from the caller's event model because the row cannot be locked before the slot; a concurrent change of that same event's date between loading the model and starting the transaction can therefore leave a slot unlocked; that narrow window is not covered.

Recorded outcomes are checked by `MatchOutcomeRequirements`, which explicitly composes focused winning-side, entry-order, and elimination-history requirements. Each requirement owns one cohesive rule family and raises `InvalidMatchOutcomeException`; the coordinator preserves their deterministic validation order without using a dynamic specification registry or mutation pipeline. `RecordResultAction` locks the event's scheduling set, the match, its event date, the selected winning side, and the complete competitor collection before validation. Outcome requirements and championship reconciliation consume that shared snapshot rather than querying mutable match state independently.

## Event Card Scheduling

Match assignments observe these collision rules:

- A wrestler, tag team, or title may be assigned only once on an event card. A tournament may explicitly permit only its qualified sequential wrestler/tag-team appearances and recorded-draw rematches on that same card; title stakes remain once per card. See [Tournament Management](tournament-generation.md) for the exact linked-match exception and additional event-union lock order.
- A wrestler, tag team, or title may not be assigned to different events scheduled for the same exact date and time.
- A referee may officiate multiple matches on one event card, but may not officiate matches on different events scheduled for the same exact date and time.
- A wrestler who is a current member of a tag team cannot be booked individually while that tag team is entered on the same card or at the same exact date and time, and a tag team cannot be entered while one of its current members is booked individually. `MatchCompetitorAssignmentEligibility` checks these through the tag team's current membership; these are read-only checks and the canonical lock order is unchanged. Former members are ignored.
- An unscheduled event still prevents duplicate assignments within its own card. Its missing date does not conflict with other unscheduled events.

Assignment actions lock the affected event rows and enforce these rules inside their database transactions. This serializes assignment commands that use the application boundary. The schema cannot enforce overlapping match windows because individual matches do not currently have their own start and end times.

Roster booking eligibility is evaluated by `RosterBookingEligibility`, not by Eloquent models or builders. The policy combines persisted employment, injury, suspension, future-employment, and tag-team membership state. A tag team must satisfy its own lifecycle state, have at least two current wrestlers, and have every current wrestler individually eligible. Models expose those relationships and state predicates (`isInjured()` and `isSuspended()`, which the booking strategies also use to drive the Injured and Suspended availability badges on roster lists and profiles; `loadExists()` with the `AVAILABILITY_STATE` projections avoids per-row queries); validation rules, assignment Actions, and collections invoke the policy when deciding whether a participant may be booked. `MatchCompetitorsCollection` owns typed wrestler and tag-team partitions for loaded competitor entries so callers do not repeat polymorphic filtering.

The Livewire match form applies model-specific booking rules to every selected wrestler, tag team, and referee before constructing `EventMatchData`. Assignment Actions repeat the eligibility check as the authoritative transactional boundary so non-UI callers and state changes between validation and persistence remain protected.

Retiring, releasing or injuring someone does not remove them from matches they are already booked in. Instead the event page (`MatchesTable::projectRowState()`) marks any competitor or referee in an upcoming or unresulted match who is no longer bookable (including soft-deleted ones) with a small "No longer bookable" note so the card gets fixed. It runs one `bookable()` id query per type (wrestlers, tag teams, referees) for the page's rows and never marks members of past matches that already have a result.

Assignment Actions treat each requested collection as an atomic command. They reject the entire assignment when any selected wrestler, tag team, referee, or title is unavailable; they never silently discard unavailable selections and persist a partial request. Repeated selections of the same record are normalized before assignment.

`MatchCompetitorRequirements` authoritatively validates competitor types and composition before assignments are persisted. Match formats whose names encode a roster-member count use the current wrestlers represented by each selected tag team: standard and tornado tag matches require 2-on-2, six/eight/ten-person tag matches require 3/4/5 per side, and handicap matches require 2-on-1 or 3-on-2 in either side order. A wrestler cannot also be selected directly when represented by a selected tag team.

Competitor entries and represented roster members are separate concepts. Singles, Triple Threat, Triangle, and Fatal 4-Way matches require exactly one wrestler or tag-team entry on each side, subject to the match type's allowed competitor types. Battle Royal and Royal Rumble entrants each occupy an individual side.

`MatchStipulation` is an optional match configuration selected from active definitions when a match is created or edited. The match retains that relationship as historical configuration even if the definition is later made inactive. Stipulation capabilities and match presentation must be implemented by the match domain when they are enforced; the model does not infer behavior from hard-coded slug lists.

`AddMatchForEventAction` receives side-based `EventMatchData`, locks the owning event's scheduling set, allocates the next card position without reusing soft-deleted match numbers (deleted matches keep their numbers, so `match_number` is a SMALLINT UNSIGNED on MySQL and holds up to 65535 numbers per event; it was a TINYINT that stopped at 255), and persists the match, officials, championship stakes, sides, and competitors in one transaction. Assignment Actions retain eligibility and scheduling-conflict enforcement for their respective relationships.

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

Pinfall, submission, knockout, stipulation, and forfeit finishes allow titles to change hands. Disqualification, countout, time-limit draw, and no-decision finishes retain the existing champion. A title-changing winner must contain exactly one competitor compatible with each attached title type, and a new reign uses the event date. Recording a corrected result reconciles title lineage in the same transaction; it may void a reign created by that match and restore its predecessor, but it cannot rewrite a reign that later championship history already depends on. A result can only be recorded once its event has taken place (a future or unscheduled event is rejected), and a title change re-validates that the title is still active and not deleted and that the winner is still eligible and not deleted. A back-filled win by a winner who has since been released or retired is recorded but its reign ends on that release or retirement date (see [Championship System](championship-system.md)). Title-affecting results must also be recorded in event date order: a result that would create or change a reign earlier than the title's latest recorded reign is rejected with a domain exception and nothing is persisted (see [Championship System](championship-system.md)).

## Related Documentation
- [Business Rules](business-rules.md)
- [Core Capabilities](core-capabilities.md)
- [Championship System](championship-system.md)

## Booking history and continuity

The booking modal's explicit **Check history** action is read-only and reauthorizes the current create/update ability and actual event before reading. It submits the current deferred roster selection; it does not query history while searching the roster. The client discards its snapshot as soon as the competitors or format change and ignores responses for older selections. A check never relaxes booking, scheduling, title-defender or availability rules and writes no domain records.

`BookingHistoryViewModel` builds the modal payload from single-query reads (`BookingHistorySubjectsQuery`, `BookingHistoryRecentMatchesQuery`, `BookingHistoryLatestRecordedDatesQuery`, `BookingHistoryDirectRecordQuery`, `BookingHistoryNoticePairsQuery`, `BookingHistoryNoticeSourcesQuery`) and does the label and date formatting; the reusable source-row and selected-competitor filters live on `EventMatchBuilder`. It reads the existing EventMatch, MatchSide and MatchCompetitor records. Its reference is the earlier of the target event timestamp and one captured server timestamp, or now for an undated target. Earlier history excludes the reference instant, the edited match, deleted matches and deleted parent events. Every source query explicitly filters the event's promotion, including for global administrators. An unowned event/profile yields unavailable history rather than an empty record. Dates are displayed in the promotion time zone. The reads only read and return identifiers: the match form validates the chosen competitors first (`BookingHistorySelection`), and `BookingHistoryLinks` adds the page links before the history reaches the browser.

The snapshot includes five recent bookings **per selected identity**, latest recorded dates, and direct head-to-head records over all available qualifying history. A past booking without a usable result remains visible and cannot establish an appearance, draw or loss. Current corrected outcomes are authoritative; this is event-date reporting rather than an audit reconstruction. No counters or result copies are persisted. Retained deleted competitors keep their names and a Deleted marker without linking to inaccessible profiles; missing or foreign references are redacted.

Direct head-to-head records require exactly two populated sides and one same-type competitor entry on each side: Singles for wrestlers, or ordinary equal-size tag formats for named teams. Multi-way, partner combinations, handicaps, gauntlets, battle royals and rumbles do not become one-on-one records. Team outcomes remain with the named team across membership changes; members do not inherit those outcomes. A team id and a wrestler id are separate identities even when their numeric values match.

Repeat notices compare typed identities on opposing sides in the preceding 90 **local calendar days**, lower bound inclusive and reference exclusive. They distinguish recorded meetings from unresulted/incomplete bookings, show the latest source match, and paginate matching pairs five at a time. Previous opposition between today's selected partners is not a repeat opposing pairing. Individual-entrant form lists are normalized to individual sides. Notices are advisory and do not infer storyline intent.

EventMatchBuilder owns reusable promotion/time, opposing-side and direct-bout constraints plus the completed-result projection. Counts are portable SQL aggregates; previews eager-load with per-parent limits, and notice sources load in a single bounded batch. Query counts do not grow with history rows or individual opposing pairs. No membership expansion, schema change, cross-request statistics cache or new dependency is involved.

Wrestler and tag-team PreviousMatches tables use FiltersMatchHistory for a typed opponent URL filter and links from actual opponents. Forged, foreign and malformed filters fail closed; clearing the filter returns to the subject's full promotion-scoped history. These history tables stack labelled fields on phones while retaining their desktop table. Streaks and title-defence totals are intentionally not calculated.
