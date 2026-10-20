# System Overview

## Introduction

The Match Generation System in Ringside is a comprehensive framework for creating realistic wrestling matches with proper business rule enforcement, championship integration, and flexible configuration options. The system supports everything from simple exhibition matches to complex championship tournaments.

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Match Generation System                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────┐    ┌─────────────────┐    ┌──────────────┐ │
│  │   MatchFactory  │────│  Configuration  │────│ Match Types  │ │
│  │                 │    │    Resolver     │    │   System     │ │
│  └─────────────────┘    └─────────────────┘    └──────────────┘ │
│           │                        │                     │      │
│           │                        │                     │      │
│  ┌─────────────────┐    ┌─────────────────┐    ┌──────────────┐ │
│  │   Competitor    │────│ Winner/Loser    │────│ Championship │ │
│  │   Management    │    │ Architecture    │    │ Integration  │ │
│  └─────────────────┘    └─────────────────┘    └──────────────┘ │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Core Principles

### 1. Configuration-Driven Design

The system operates through structured configuration arrays that define match parameters:

```php
$matchConfig = [
    'match_type' => 'singles',
    'competitors' => ['wrestler', 'wrestler'],
    'winner_strategy' => 'random',
    'decision_type' => 'pinfall',
    'titles' => [$championship],
    'referees' => 1
];
```

### 2. Business Rule Enforcement

All matches adhere to wrestling business rules:
- Match types define valid competitor combinations
- Championship requirements are validated
- Winner/loser relationships maintain integrity
- Referee assignments follow regulations

### 3. Extensible Architecture

The system supports growth without breaking existing functionality:
- New match types can be added seamlessly
- Custom winner strategies are supported
- Additional configuration options integrate cleanly
- Backward compatibility is maintained

## System Components

### MatchFactory (`database/factories/Matches/MatchFactory.php`)

The central orchestrator providing:
- **Simple Factory Methods**: `singles()`, `tagTeam()`, `battleRoyal()`
- **Advanced Generation**: `generateFullMatch()` with comprehensive configuration
- **Title Integration**: Automatic championship handling
- **Competitor Management**: Flexible competitor assignment

### Match Type System

Defines rules and constraints for different match formats:
- **Singles**: 1v1 wrestler competitions
- **Tag Team**: Team-based matches
- **Multi-Way**: Triple threat, fatal four way
- **Battle Royal**: Multi-competitor elimination
- **Royal Rumble**: Special timed entry format

### Winner/Loser Architecture

Modern foreign key-based system providing:
- **Data Integrity**: Database-level referential constraints
- **Performance**: Optimized queries and indexing
- **Flexibility**: Support for multiple winners/losers
- **Backward Compatibility**: Accessor methods for legacy code

### Championship Integration

Seamless title management including:
- **Automatic Champion Detection**: Identifies current titleholders
- **Title Defense Logic**: Handles retention vs. title changes
- **Multi-Title Scenarios**: Unification and multi-championship matches
- **Lineage Tracking**: Maintains championship history

## Data Flow

### Match Creation Process

```
1. Configuration Input
   ↓
2. Match Type Resolution
   ↓
3. Competitor Generation
   ↓
4. Title Integration (if applicable)
   ↓
5. Match Creation
   ↓
6. Result Generation
   ↓
7. Winner/Loser Assignment
   ↓
8. Championship Updates (if applicable)
```

### Entity Relationships

```
Event
  ↓
EventMatch ←────────────── Title (many-to-many)
  ↓
MatchCompetitor ←──── Wrestler/TagTeam (polymorphic)
  ↓
EventMatch (match_finish, winning_side_id)
    ↓
MatchSide ←──── MatchCompetitor
```

## Key Features

### 1. Comprehensive Match Types

- **Singles Match**: Traditional 1-on-1 competition
- **Tag Team Match**: Team-based wrestling
- **Triple Threat**: 3-way competition
- **Fatal Four Way**: 4-way elimination
- **Battle Royal**: Multi-competitor over-the-top-rope elimination
- **Royal Rumble**: Timed entry battle royal variant

### 2. Flexible Competitor Assignment

```php
// Automatic generation
'competitors' => ['wrestler', 'tag_team']

// Specific models
'competitors' => [$john_cena, $rock]

// Named creation
'competitors' => ['Stone Cold', 'The Undertaker']

// Mixed approaches
'competitors' => [$existing_wrestler, 'wrestler', 'New Wrestler Name']
```

### 3. Winner Strategy Options

- **Random**: Random competitor selection (default)
- **First**: First competitor wins
- **Last**: Last competitor wins  
- **Multiple**: Multiple random winners
- **All But One**: All competitors except last win
- **Single**: Single random winner (explicit)

### 4. Decision Type Support

- **Pinfall**: Standard victory condition
- **Submission**: Tap-out victory
- **Count Out**: Victory by opponent count-out
- **Disqualification**: Victory by opponent DQ
- **Draw**: Match ends in tie
- **No Decision**: Match has no official result

### 5. Championship Integration

- **Title Defenses**: Current champions defend titles
- **Title Changes**: Automatic championship lineage updates
- **Multi-Title Matches**: Unification and winner-take-all scenarios
- **Vacant Title Tournaments**: Crowning new champions

## Usage Patterns

### Development Workflow

```php
// 1. Define match configuration
$config = [
    'match_type' => 'singles',
    'titles' => [$worldTitle],
    'winner_strategy' => 'first'
];

// 2. Generate match
$match = MatchFactory::new()
    ->generateFullMatch($config)
    ->create();

// 3. Access the winning side
$winningSide = $match->winningSide;
$champion = $match->titles->first()->currentChampion();
```

### Testing Scenarios

The system supports comprehensive testing through 22+ predefined scenarios covering:
- All match types and configurations
- Championship scenarios
- Multi-competitor situations
- Winner/loser validation
- Business rule compliance

## Performance Characteristics

### Database Efficiency

- **Optimized Queries**: Foreign key relationships enable efficient joins
- **Bulk Operations**: Competitor creation uses batch inserts
- **Proper Indexing**: Strategic indexes on relationship columns
- **Transaction Management**: Complex matches created atomically

### Memory Management

- **Lazy Loading**: Related models loaded on-demand
- **Configuration Cleanup**: Temporary state cleared after creation
- **Efficient Relationships**: Minimal memory overhead for complex matches

### Scalability

- **Event-Level Batching**: Multiple matches per event efficiently created
- **Caching Support**: Championship and match type data cacheable
- **Query Optimization**: N+1 query prevention through eager loading

## Integration Points

### Event Management

Matches integrate seamlessly with event scheduling:
- Match numbering and ordering
- Event-specific configurations
- Cross-match relationships

An event's nullable `date` remains the authoritative scheduling value. `EventStatus::fromDate()` translates that persisted value into `Unscheduled`, `Scheduled`, or `Past`; the `Event` model exposes the result through its computed `status` attribute instead of carrying separate scheduling predicates.

Events may have a date without a venue while planning or preserving historical records. A venue is optional event metadata; when one is selected for a dated event, it is reserved exclusively for that calendar day.

Event dates become immutable once the event has occurred. `EventSchedulingEligibility` owns that rule, while both Livewire validation and `Events\UpdateAction` enforce it so non-UI callers cannot bypass the invariant. Other event details may still be corrected without changing the historical date.

A venue may host only one event per calendar day (in the venue's own time zone, `venues.timezone`, set on the venue form, which starts a new venue in the current promotion's time zone, or the application time zone when none is selected, and keeps an existing venue's stored zone; the column default stays UTC) across all promotions: venues are shared, so `VenueSchedulingEligibility` queries the venue's events without the promotion scope, ignores soft-deleted events and the event being edited, and reports only `Venue [name] is already booked on Oct 6, 2026 (venue time).` (the venue-local day, which can differ from the promotion's), never another promotion's event details. Event creation and updates lock the selected venue row before that check, serializing competing bookings and rolling back the complete event write when a conflict exists. An update runs the check only when it changes the venue or the calendar day (or schedules an unscheduled event, `VenueSchedulingEligibility::isBookingChanging()`), so events that already share a venue day (booked before the per-day rule) can still have their name, preview, or time on that day edited. Unscheduled events do not reserve a venue day.

Restoring a soft-deleted event applies the same venue lock and availability check before reactivating its booking, so a later event cannot be displaced or share the same venue slot. It also takes the date-slot lock and rejects the restore with a scheduling conflict when a wrestler, tag team, referee, or title booked on the event's matches is booked in another event at that date and time (see the canonical lock order in `match-system.md`).

A venue with upcoming events cannot be deleted: `Venues\DeleteAction` locks the venue row, then `VenueDeletionEligibility::ensureCanDelete()` refuses with `Venues\CannotBeDeletedException` ("Move it to another venue or delete it first") when any live event is dated on or after the start of today in the venue's time zone. Events of every promotion count (venues are shared, so the promotion scope is lifted), but the message carries only the venue name and the count, never another promotion's event names. Past, soft-deleted and undated events do not block, so history never prevents retiring a venue. The index-table Delete shows the message as the error toast.

An event may therefore sit at a deleted venue. Editing such an event and leaving the venue unchanged saves normally and keeps the venue (the form's venue rule and the venue select both admit the event's own stored venue, and the select shows its name). A deleted venue cannot be newly chosen: the form rule refuses it, and `Events\CreateAction` and `Events\UpdateAction` re-check after the venue row lock, throwing `Events\VenueDeletedException` (shown on the venue field) unless the venue is the event's existing one.

A deleted venue's name stays visible on the event wherever the venue is shown: the events table, event pack, production sheet, live show and public event page read it including deleted venues: the events table eager-loads with `withTrashed()`, and the pack, production sheet, live show, public page and publication form request read it through `EventVenueQuery` (the `Event::venue()` relation itself still excludes deleted venues, so nothing can newly select one). A deleted venue is plain text, never a link, for everyone (its page would 404, since route binding excludes trashed venues); the events table also links a live venue only when the viewer may `view` it (`VenuePolicy`, administrators only), so promotion members see the name without a link that would answer 403. The publication form checks doors times in a deleted venue's time zone, the same one the public page uses. The public page keeps publishing a deleted venue's name, address and time zone exactly as before the deletion (a venue can only be deleted once its events are past), so a page released earlier stays up and its schedule fingerprint does not change. The pack and public schedule fingerprints include the venue name, so deleting a venue does not mark packs or published pages as changed.

Changing a venue's time zone moves its local calendar days, so `Venues\UpdateAction` locks the venue row and, only when the zone actually changes, has `VenueDeletionEligibility::ensureTimezoneKeepsEventsApart()` (the same shared-day check venue restore uses) look at the venue's live events of every promotion in the new zone. If two would fall on one day it throws `Venues\CannotBeUpdatedException` and writes nothing.

Restoring a soft-deleted venue locks the venue row and verifies that no active venue has claimed its name, preserving venue identity without bypassing active-name uniqueness.
Restoring a soft-deleted title locks the title row and verifies that no active title has claimed its name. Restoration preserves the title's historical state without bypassing active-name uniqueness.

### Event operational records and mass assignment

The event-owned operational models (`EventBudget`, `EventBudgetLine`, `EventBudgetMovement`, `EventBudgetRevision`, `EventLiveRun`, `EventPack`, `EventPublication`, `EventSponsorCommitment`, `EventSponsorCommitmentRevision`, `EventTalentBooking`, `EventTalentBookingRevision`) follow the app-wide mass-assignment rule. Only fields that come from validated user input are `#[Fillable]`; their Actions write those through `fill()`: line details, payment details, pack content, the publication draft, sponsor promise details and talent terms. Ownership keys (`event_id`, `promotion_id`, parent ids), versions, request keys and hashes, statuses, actor columns, void and archive state, fingerprints, released snapshots and timestamps are assigned explicitly by the Action. The budget, live run and the three revision (audit) models have no user-input fields, so their fillable list is empty and a mass-assignment attempt throws. `EventBudget`, `EventLiveRun`, `EventPack`, `EventPublication` and `EventSponsorCommitment` use typed Eloquent builders that share `forEventId()`.

The storyline, tournament and availability models follow the same rule: `StorylineStep`, `StorylineParticipant`, `StorylineDevelopment`, `Tournament`, `TournamentNode` (walkover reason only) and `Unavailability` fill only validated user-input fields, while ownership keys, source and parent links, status, versions, submission and request ids, snapshots and actor columns stay explicit in their Actions. `StorylineRevision`, `TournamentEntry` and `TournamentMatchLink` have no user-input fields, so their fillable lists are empty and mass assignment throws. `TournamentMatchLink` has a typed builder with `forMatchId()`.

### Saved event operations pack

The shared Tailwind v4 color/font/shape tokens live in `resources/css/tokens.css` with semantic names documented in `DESIGN.md`. Both `app.css` and `marketing.css` import them. The app/auth JS entry points load app.css; app.js also loads event-pack.css, which consumes the app's shared variables. Public event pages load app.css directly. The separate event-pack-print.js entry point loads event-pack-print.css, importing the Tailwind theme and shared tokens without app layout rules or Preflight. The shared theme defines the distinct table-status and footer colors alongside the brand roles; app.css adds only the app font and its two custom text sizes. Native spacing utilities and opacity modifiers avoid redundant theme variants. Print ink and true-black overlays remain distinct.


The event detail page links to an authenticated pack editor. `EventPackController` uses the event's existing promotion-scoped `view` and `update` abilities: viewers can read and print, managers can save. GET requests never create a pack. The print preview uses saved operational fields with the current complete card and roster; attendance, briefing and actual times remain paper blanks.

`event_packs` holds one record per event (unique foreign key), a version, a card fingerprint and a JSON content document. The document contains full local date/time strings with an explicit saved timezone, show notes, revision/master-copy labels, match entries keyed by match ID, and UUID segment entries anchored before the card or after a match. A promotion-timezone change does not convert saved times. The editor flags card/date/timezone changes for review. Removed-match entries survive edits and return when the match is restored; removed segment anchors must be repositioned. Soft deletion of the event retains the pack; permanent deletion cascades it.

`UpdateEventPackRequest` validates nested match ownership, active segment anchors, dates and lengths. A trailing form-completion marker prevents a server-truncated form from partially saving. `SaveEventPackAction` locks the event card through `EventCardLock` (the event, its matches in ID order, the pack and the live run) inside one transaction, then checks the submitted version and current card fingerprint before writing. Repeated or stale submissions retain entered values and require reload/review. Existing match fields and assignments are never written by the pack.

`EventPackCardService` builds the card from one read of the event's matches (`EventPackCardMatchesQuery`, which loads competitors, current tag-team members and referees with them) and one bookability read per person type (`BookableIdsQuery`). It expands tag teams using current membership, deduplicates people, lists all assigned match numbers and derives the calendar warnings from those same matches and a single `UnavailabilityOnDateQuery` read, through `EventRosterService` and `EventRosterAvailabilityService`; it does not read the matches a second time. Deleted or unavailable booked people remain visible with a warning. It does not reconstruct a historical tag-team lineup. `EventPackSourceService` reads the saved pack (`EventPackQuery`) and merges timing rows and anchored segments for printing, and identifies retained removed entries. Actions, Lifecycle classes and ViewModels all read the card and pack through these Services, so a stored fingerprint and the page the owner sees always come from the same computation.

The dedicated print stylesheet repeats section/event headers and allows long packs to span pages. Users select running order, roster and/or notes, then use browser Print / Save as PDF. Unsaved editor changes trigger a leave warning; Save & preview persists first, while Preview saved pack explicitly offers to discard pending edits. No PDF package, generated-file storage or public sharing is involved. The light wordmark is copied unchanged from the accepted `ringside-meta/brand/charcoal-red-v7` artwork.

### Event budget and settlement

The authenticated event Budget & settlement page is available to active promotion Owners/Managers and permitted platform administrators through `viewBudget`/`updateBudget`; Members have no financial page, link, history or payload. The existing promotion gate still refuses an administrator operating in an enforced foreign promotion context. The `Budget` component authorizes `viewBudget`/`updateBudget` before each read and write and validates every field (the line and movement forms and the correction or archive reason) before calling an Action; the Actions neither validate nor check permissions, and take `BudgetLineData`/`BudgetMovementData`. Every read and Action resolves the owning event again. Budget child identifiers are resolved through that event’s budget, never by an unscoped child lookup.

One `event_budgets` row per event stores USD, the saved timezone and a version. Opening the page creates nothing. Its first intentional line save creates the budget. `event_budget_lines` holds manual income/expense estimates and nullable confirmed obligations in exact integer cents. Unknown is different from a known zero. Confirmed means the amount expected/owed, independently of cash. `event_budget_movements` records positive payments/receipts and source-linked real refunds with effective calendar dates; future dates, excess refunds and foreign sources are refused. Actual overpayments require explicit acknowledgment and show a refund balance. Line count (500) and movement count per line (1,000), input bounds and signed 64-bit sums bound the arithmetic without floating-point conversion.

`MutateBudgetAction` locks/reloads the event, then locks its budget. Owning Actions lock the affected line and movements in stable ID order, enforce domain rules, and persist state plus an `event_budget_revisions` record atomically. A required UUID identifies the form attempt within its budget: an exact authorized retry returns its committed outcome before the stale-version check; changed details under the same UUID or a fresh attempt with an old version are refused. First creation is serialized by the owning-event lock and backed by the unique event key. Revisions retain the normalized request hash, version, target, actor label/id, recorded UTC time, reason and before/after values. They are separate from the automatically pruned activity log, and private financial fields do not use generic model activity logging.

A changed confirmed amount needs a reason; reducing it below cash already recorded also requires acknowledgment. A recording correction voids the original while preserving its amount/date/actor/reason, optionally creating its replacement in the same transaction. An original with active dependent refunds cannot be voided alone. Real refunds and recording corrections are distinct. Plan-only lines may be archived only without movements or a nonzero confirmed obligation; archiving cannot hide recorded money.

`BudgetSettlementService` and `EventBudgetViewModel` separately expose planned/confirmed income and expense subtotals, missing amounts, net cash, and four balances: to pay, to collect, refund to send and refund to collect. Unknown obligations never have an invented balance. A derived “All recorded lines balanced” status requires at least one active line, all confirmed amounts known, and every line balanced; it does not assert that all real-world costs were entered. Reconciliation remains editable and audited, with no finalization/reopening state.

The class-based Livewire screen uses the existing Ringside shell, type and colors, summary-first phone rows and retained drafts. It blocks operations while offline/after an uncertain request and requires a successful explicit refresh before retry. Refresh retains the attempt UUID and draft; staff review current saved values before retrying. History is scoped and paginated, with readable field changes. No payment transfer, external integration, message, public sharing or offline queue is involved.

Card, venue, date, attendance or live-show completion changes do not rewrite financial facts. Event soft deletion blocks financial writes and retains the budget; restoration recovers the same records. Foreign keys restrict permanent deletion of an event/budget with retained financial records. No budget purge/retention job exists. Budget values never enter event packs, printable packs, live-show content or ordinary event view data. Talent fees are manual event expense labels in this version; no agreement record, roster import or fee synchronization is assumed.

### Private sponsor commitments

Owners and Managers use the event Sponsors checklist and the reusable sponsor directory. `manageSponsors` is a private promotion ability: Members cannot read sponsor records, promises, evidence or revisions, including through conventional model `view` checks. Administrators retain the existing enforced-promotion boundary. Routes and every component operation that calls a sponsor Action (`SponsorCommitments`, the sponsor `Directory`) authorize `manageSponsors` before the call, and the Actions do not check permissions; GET requests create nothing.

Three promotion-owned tables separate the reusable contact directory (`sponsors`), each event's current promise (`event_sponsor_commitments`) and its immutable revisions (`event_sponsor_commitment_revisions`). A promise records the sponsor name as selected, type, description, responsibility label, optional explicit deadline and saved timezone, private notes/reference, manual Pending/Completed/Cancelled state, completion evidence and optional HTTP(S) link. Each occurrence has its own identity; completing one never changes another event or a repeated announcement. Completion requires evidence; cancellation, reopening and corrections to terminal records require reasons. Revisions retain the full prior/current snapshots, version, actor name, operation and reason in the same transaction. Sponsor data is not copied to the Member-readable activity log.

Deadlines and actual completion times are UTC instants interpreted in the displayed saved timezone. Invalid or repeated local times need correction or an explicit offset; unchanged minute-level form fields retain the original seconds. Overdue and Completed late are computed from persisted instants. Missing deadlines remain unknown. Event name/date/venue/timezone/archive changes flag the saved context for deliberate review without moving a deadline or clearing evidence. Existing promises retain their sponsor-name snapshot until explicitly corrected. Archiving a sponsor blocks new selections but allows correction of existing promises. Soft-deleted events retain private history and allow corrections, but reject new promises and pack publication. Foreign keys restrict permanent deletion of sponsors/events/commitments with retained history.

Private instruction drafts are separate from promise descriptions and evidence. `SaveSponsorInstructionAction` requires a current preview, promise/pack versions and card fingerprint before saving only reviewed operational label, wording and responsibility to either a UUID running-order segment or a manual sponsor setup task. One promise has at most one stable link; repeated occurrences use separate rows. Retraction requires a reason. A changed/missing target, changed draft or removed match anchor flags review. Cancelled promises with saved instructions stay visible in the default attention list until reviewed/retracted. Ordinary pack saves preserve the optional `sponsor_tasks` array; its safe fields render for Members in the pack and selectable printed setup checklist. Paper marks require deliberate later transcription with evidence.

Promise writes lock event → sponsor → commitment. Instruction adoption locks the event card through `EventCardLock` (event → matches in ID order → pack → live run), then the commitment, then uses the existing pack save. Directory creation locks its promotion to serialize submission keys; editing/archiving an existing sponsor locks only that sponsor. Taking the promotion lock before an archive can cycle against the foreign-key lock held during an event promise create, so existing-directory edits do not acquire it. Version checks and unique submission keys refuse stale/repeated writes; all history and pack-link changes roll back together.

Pack adoption does not update a prepared live run. The existing live source preview/adoption workflow governs pending announcements; started/finished/skipped items cannot be rewritten or retracted by sponsor adoption. Setup tasks remain outside the live order. Live completion and private sponsor completion never update each other, and private corrections leave a completed run unchanged. The checklist and directory preserve entered drafts on validation/conflict failures, block offline/failed-request writes and require reload after a connection failure. There is no offline queue, public sponsor output, message sending, file upload, social publication or payment state.

### Private production crew and cues

Production contacts live in `crew_contacts`, with required promotion ownership, a per-promotion create UUID, optimistic version and archive timestamp. They are separate from login accounts and the wrestling roster. Names can repeat; creating a contact never invites anyone or grants access. The crew `Directory` component validates the details (and the create UUID) and authorizes `create`, `update` or `archive` before calling an Action; `SaveCrewContactAction` takes a `CrewContactData`, serializes retried creates on the promotion and locks existing contacts for edits, and `ArchiveCrewContactAction` retains them without deletion. Neither Action validates or checks permissions. Administrators must select a promotion before creating contacts. Directory searches are bounded and match wildcard characters literally through `CrewContactBuilder`.

`SaveEventProductionAction` saves an optional production section in the existing event pack. Assignment UUIDs identify a contact's role/station, saved name, full call time/timezone/optional UTC offset and setup/show/strike duties. A contact can have several roles. Ordered cue UUIDs attach to match IDs or segment UUIDs and retain their subject, trigger, manual instruction, owner snapshot, pronunciation and title/filename/HTTPS reference. References are never read from disk, fetched, uploaded, hosted or played. Call times that fall in a missing or repeated local hour need a valid disambiguation; changing event dates/timezones never shifts saved calls automatically.

Production saves lock the event, matches in ID order, pack, then selected contact rows in ID order. Version/card review guards prevent stale replacement. New selections cannot use archived or foreign-promotion contacts. Removed assignments and cues for removed matches/segments are retained; deliberately reattaching a retained cue is explicit. Ordinary pack saves preserve production even when the form omits it. The private Livewire editor saves separately, using bounded JSON updates rather than a large POST form, and updates the ordinary form's version only after a successful save against that same baseline.

`viewProduction`/`updateProduction` and crew-specific Gate checks restrict every new surface to Owners/Managers, with the existing administrator context restrictions. Member pack/live ViewModels remove production content, crew roster entries and cues before rendering; the private editor never mounts for a Member. Existing free-text pack notes remain under their established permissions and are labelled as shared. Production notes and directory contact details are excluded from print. Private cue text and crew names are excluded from generic live audit properties.

Live preparation snapshots operational crew assignments and per-item cues, with one `crew:<contact-id>` attendance entry per person, distinct from wrestler/referee identities. Arrival/briefing reuse existing guarded live actions; they neither certify duties nor prevent a start. Source adoption updates pending instructions only, preserves started/finished/skipped cue/owner snapshots, retains checked-in removed crew and leaves new crew unmarked. Completed runs stay immutable when directory or latest-pack data changes.

`EventProductionViewModel` and the authenticated phone/print endpoint use the saved plan before preparation and default to the adopted live snapshot afterwards. Source/version/timezone labels and explicit plan/live choices prevent silent mixing. Person/role filters retain the running order and show unassigned/removed-owner cues. Now/Next expose expandable saved cues; talent and crew attendance have distinct counts. The existing browser Print/Save as PDF pipeline produces optional call/cue sheets with repeated headings and paper boxes; no offline queue, generated-file hosting, sending, public sharing, financial module or external integration is involved.

### Live show operations

The event's authenticated Live show page resumes one event-owned `event_live_runs` record, stored separately from `EventPack.content`. Opening the page creates nothing. `PrepareLiveShowAction` explicitly snapshots a dated event's saved, reviewed pack, complete running order and deduplicated wrestler/referee roster; unresolved segment anchors or an empty running order prevent preparation. Future events can be prepared, but attendance marks and the first item start require the event's local day; a running show can continue across midnight. Past events cannot be newly prepared. Event soft deletion retains the run; permanent deletion cascades it.

`EventLiveRun` stores status, version, source pack version/fingerprint, prepared/completed times and a JSON document containing item and attendance state. Match IDs, segment UUIDs and typed person keys provide stable identity. The roster and item descriptions are reviewed lineup snapshots, not proof of who actually performed. Actual starts, finishes, arrivals and briefings are server-recorded UTC instants with acting-user information; duration is derived from saved starts/finishes. The run retains its saved display/correction timezone, while each item's saved timezone governs its planned/revised baseline. Missing or ambiguous planned times remain unknown; revised start takes precedence over planned start.

The route and `LiveShow` component re-resolve the promotion-scoped event and enforce its existing `view`/`update` abilities. Component event identifiers, versions and draft targets are locked. The component authorizes `update` before each Action call; the Actions do not check permissions. Mutation Actions lock and re-read the event before the live row, require the expected version and validate the supplied item/person key. Preparation and source review additionally lock matches in ID order and the pack before the run. `PersistLiveShowAction` increments the version and writes compact Event-subject activity records with before/after values in the same transaction. Stale submissions reload committed state and require another deliberate attempt rather than overwriting it.

Arrival is required before briefing; clearing arrival also clears briefing, with confirmation in the screen. These marks never remove unavailable-person warnings or change booking eligibility, and missing marks do not block a match start. `TransitionLiveItemAction` starts only the next pending item and allows one running match or segment at a time; finishing never starts the next item automatically. Skips, timestamp corrections and reopening require a reason. Corrections reject future times, finishes before starts and overlap with recorded neighboring items; explicit UTC offsets disambiguate local correction times. Reopening is refused while another item runs or a later item has started.

`LiveShowSourceService` reads the pack, card, roster and production for a run, and `LiveShowSourceRequirements` (Lifecycle) refuses a source that needs preparing and detects card/roster changes and pack revisions. `ReviewLiveShowAction` requires an explicit preview and adoption before another item starts or the show completes; an already-running item can still finish. Adoption replaces pending items only, preserves started/finished/skipped identities and baselines, and requires skip reasons for removed pending items. Existing attendance marks and checked-in names survive; newly assigned people begin unmarked, and removed people with marks remain labelled no longer assigned. Invalid replacement sources cannot discard the saved run.

`LiveShowViewModel` supplies Now/Next and saved timing comparisons. The screen keeps those actions ahead of roster/order detail on phones, links directly to both sections, and labels a correction with its item while scrolling and focusing the form. Visible unfinished screens poll every five seconds; polling preserves open correction drafts. `live-show.js` derives advisory elapsed time from the saved start and server clock and shows offline/request-failure states. Mutations are blocked while offline; after a failed request, a successful refresh is required before another write. The last confirmed record stays visible. Operation is online only: the printable event pack is the manual fallback, with no offline queue or paper-record synchronization. `CompleteLiveShowAction` requires every item finished or skipped and source review resolved, then makes the run read-only. Live operations do not write match outcomes, championship history or saved pack fields. The screen uses the existing Ringside charcoal/red identity and typography.

### Roster Management

Dynamic competitor assignment from available talent:
- Active wrestler pools
- Tag team compositions
- Availability checking

### Championship System

Tight integration with title management:
- Automatic champion identification
- Championship lineage maintenance
- Title requirement validation

### Statistics and Reporting

Match data supports comprehensive analytics:
- Win/loss records
- Championship reign tracking
- Match type performance
- Decision type analysis

## Extensibility

### Adding New Match Types

1. Add the case to the `MatchType` enum
2. Add the spelling to `MatchFactory::resolveMatchType()`
3. Define business rules
4. Update documentation

### Custom Winner Strategies

1. Extend `resolveWinnersAndLosers()` method
2. Implement strategy logic
3. Add configuration validation
4. Create test scenarios

### Additional Configuration Options

1. Extend configuration parsing
2. Add resolution logic
3. Update factory methods
4. Maintain backward compatibility

## Quality Assurance

### Testing Coverage

- **Unit Tests**: Individual component validation
- **Integration Tests**: Cross-component interaction
- **Scenario Tests**: Real-world usage patterns
- **Performance Tests**: Scalability validation

### Code Quality

- **Type Safety**: PHP 8+ union types and strict typing
- **Documentation**: Comprehensive PHPDoc coverage
- **Standards**: PSR compliance and consistent formatting
- **Error Handling**: Descriptive exceptions and validation

The Match Generation System provides a robust, flexible, and maintainable foundation for wrestling match creation, supporting both simple scenarios and complex championship tournaments while maintaining data integrity and performance.

### Reviewed public event pages

`EventPublication` belongs to one event and promotion. Owners and Managers prepare a private draft and explicitly release its card or selected results; existing administrator access is retained. Draft saves do not change released snapshots. `PublicEventSourceService` (over `PublicEventMatchesQuery` and `PublicEventPeopleQuery`) projects only public display fields, `PublicEventCardService` turns that projection into the released card and the fingerprints stored with a snapshot, `PublishedEventChangesService` compares a snapshot with the current event, `PublishedEventQuery` finds the publication for a public link, and `PublicEventData` defines the fan payload. Internal previews, crew, finances, booking notes and live-show state never enter it. Publication actions do not record match results or alter championship reigns.

Fan pages use a generated UUID and a readable slug frozen at first release: `/shows/{publicId}/{slug}`, with a deliberately separate `/results` URL. Drafts, unknown identities, deleted events and revoked publications return 404. Results and authenticated previews are `noindex`; responses use `no-store`. Unpublishing the card revokes results and poster delivery too. Event deletion revokes publication in its existing transaction; restoring an event does not republish it.

The release, unpublish and draft-save routes authorize `publishPublication`, `unpublishPublication` and `preparePublication`; the actions then acquire the existing event/match lock order and compare the saved version and source fingerprint. Released snapshots remain unchanged by later source edits. Date, venue or timezone drift suppresses travel details, tickets, card, results and poster with an updating notice until reviewed. A changed or removed selected outcome suppresses public results until reviewed. Source comparison never adds unreviewed content to a snapshot. A cancelled, postponed or rescheduled public notice is communication only and does not change internal booking state.

One optional reviewed poster is stored on the private local disk. Static JPEG, PNG and WebP uploads are limited to 5 MiB, 4096 pixels per side and 8 megapixels, decoded with GD, oriented using EXIF and re-encoded as metadata-free PNG within 2048 pixels per side. Originals are not retained or served. An unpublished or replaced asset cannot be accessed through a released page. Failed draft saves remove only the derivative created for that request. A publication-specific public URL serves the approved image; the private preview requires the same promotion authorization as the editor.
