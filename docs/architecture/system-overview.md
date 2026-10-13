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

A venue may host only one event per calendar day (in the venue's own time zone, `venues.timezone`, UTC by default and set on the venue form) across all promotions: venues are shared, so `VenueSchedulingEligibility` queries the venue's events without the promotion scope, ignores soft-deleted events and the event being edited, and reports only `Venue [name] is already booked on Oct 6, 2026 (venue time).` (the venue-local day, which can differ from the promotion's), never another promotion's event details. Event creation and updates lock the selected venue row before that check, serializing competing bookings and rolling back the complete event write when a conflict exists. An update runs the check only when it changes the venue or the calendar day (or schedules an unscheduled event, `VenueSchedulingEligibility::isBookingChanging()`), so events that already share a venue day (booked before the per-day rule) can still have their name, preview, or time on that day edited. Unscheduled events do not reserve a venue day.

Restoring a soft-deleted event applies the same venue lock and availability check before reactivating its booking, so a later event cannot be displaced or share the same venue slot. It also takes the date-slot lock and rejects the restore with a scheduling conflict when a wrestler, tag team, referee, or title booked on the event's matches is booked in another event at that date and time (see the canonical lock order in `match-system.md`).

A venue with upcoming events cannot be deleted: `Venues\DeleteAction` locks the venue row, then `VenueDeletionEligibility::ensureCanDelete()` refuses with `Venues\CannotBeDeletedException` ("Move it to another venue or delete it first") when any live event is dated on or after the start of today in the venue's time zone. Events of every promotion count (venues are shared, so the promotion scope is lifted), but the message carries only the venue name and the count, never another promotion's event names. Past, soft-deleted and undated events do not block, so history never prevents retiring a venue. The index-table Delete shows the message as the error toast.

An event may therefore sit at a deleted venue. Editing such an event and leaving the venue unchanged saves normally and keeps the venue (the form's venue rule and the venue select both admit the event's own stored venue, and the select shows its name). A deleted venue cannot be newly chosen: the form rule refuses it, and `Events\CreateAction` and `Events\UpdateAction` re-check after the venue row lock, throwing `Events\VenueDeletedException` (shown on the venue field) unless the venue is the event's existing one.

Restoring a soft-deleted venue locks the venue row and verifies that no active venue has claimed its name, preserving venue identity without bypassing active-name uniqueness.
Restoring a soft-deleted title locks the title row and verifies that no active title has claimed its name. Restoration preserves the title's historical state without bypassing active-name uniqueness.

### Saved event operations pack

The event detail page links to an authenticated pack editor. `EventPacksController` uses the event's existing promotion-scoped `view` and `update` abilities: viewers can read and print, managers can save. GET requests never create a pack. The print preview uses saved operational fields with the current complete card and roster; attendance, briefing and actual times remain paper blanks.

`event_packs` holds one record per event (unique foreign key), a version, a card fingerprint and a JSON content document. The document contains full local date/time strings with an explicit saved timezone, show notes, revision/master-copy labels, match entries keyed by match ID, and UUID segment entries anchored before the card or after a match. A promotion-timezone change does not convert saved times. The editor flags card/date/timezone changes for review. Removed-match entries survive edits and return when the match is restored; removed segment anchors must be repositioned. Soft deletion of the event retains the pack; permanent deletion cascades it.

`UpdateEventPackRequest` validates nested match ownership, active segment anchors, dates and lengths. A trailing form-completion marker prevents a server-truncated form from partially saving. `SaveEventPackAction` locks the event, its matches in ID order and the pack inside one transaction, then checks the submitted version and current card fingerprint before writing. Repeated or stale submissions retain entered values and require reload/review. Existing match fields and assignments are never written by the pack.

`EventPackCardQuery` batches current card/roster relationships, expands tag teams using current membership, deduplicates people and lists all assigned match numbers. Deleted or unavailable booked people remain visible with a warning. It does not reconstruct a historical tag-team lineup. `EventPackViewModel` merges timing rows and anchored segments for printing, and identifies retained removed entries.

The dedicated print stylesheet repeats section/event headers and allows long packs to span pages. Users select running order, roster and/or notes, then use browser Print / Save as PDF. Unsaved editor changes trigger a leave warning; Save & preview persists first, while Preview saved pack explicitly offers to discard pending edits. No PDF package, generated-file storage or public sharing is involved. The light wordmark is copied unchanged from the accepted `ringside-meta/brand/charcoal-red-v7` artwork.

### Live show operations

The event's authenticated Live show page resumes one event-owned `event_live_runs` record, stored separately from `EventPack.content`. Opening the page creates nothing. `PrepareLiveShowAction` explicitly snapshots a dated event's saved, reviewed pack, complete running order and deduplicated wrestler/referee roster; unresolved segment anchors or an empty running order prevent preparation. Future events can be prepared, but attendance marks and the first item start require the event's local day; a running show can continue across midnight. Past events cannot be newly prepared. Event soft deletion retains the run; permanent deletion cascades it.

`EventLiveRun` stores status, version, source pack version/fingerprint, prepared/completed times and a JSON document containing item and attendance state. Match IDs, segment UUIDs and typed person keys provide stable identity. The roster and item descriptions are reviewed lineup snapshots, not proof of who actually performed. Actual starts, finishes, arrivals and briefings are server-recorded UTC instants with acting-user information; duration is derived from saved starts/finishes. The run retains its saved display/correction timezone, while each item's saved timezone governs its planned/revised baseline. Missing or ambiguous planned times remain unknown; revised start takes precedence over planned start.

The route and `LiveShow` component re-resolve the promotion-scoped event and enforce its existing `view`/`update` abilities. Component event identifiers, versions and draft targets are locked. Mutation Actions lock and re-read the event before the live row, require the expected version and validate the supplied item/person key. Preparation and source review additionally lock matches in ID order and the pack before the run. `PersistLiveShowAction` increments the version and writes compact Event-subject activity records with before/after values in the same transaction. Stale submissions reload committed state and require another deliberate attempt rather than overwriting it.

Arrival is required before briefing; clearing arrival also clears briefing, with confirmation in the screen. These marks never remove unavailable-person warnings or change booking eligibility, and missing marks do not block a match start. `TransitionLiveItemAction` starts only the next pending item and allows one running match or segment at a time; finishing never starts the next item automatically. Skips, timestamp corrections and reopening require a reason. Corrections reject future times, finishes before starts and overlap with recorded neighboring items; explicit UTC offsets disambiguate local correction times. Reopening is refused while another item runs or a later item has started.

`LiveShowSourceQuery` detects card/roster changes and pack revisions. `ReviewLiveShowAction` requires an explicit preview and adoption before another item starts or the show completes; an already-running item can still finish. Adoption replaces pending items only, preserves started/finished/skipped identities and baselines, and requires skip reasons for removed pending items. Existing attendance marks and checked-in names survive; newly assigned people begin unmarked, and removed people with marks remain labelled no longer assigned. Invalid replacement sources cannot discard the saved run.

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

1. Create MatchType factory method
2. Add type resolution logic
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
