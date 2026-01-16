# Technical Specification

> Match System
> Reference: @.agent-os/specs/2026-01-16-match-system/spec.md

---

## Entity Reference

### 1. EventMatch

**Model:** `App\Models\Matches\EventMatch`

The core entity representing a competitive contest within an event.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| event_id | int | Foreign key to event |
| match_number | int | Order in event card |
| match_type | MatchType | Enum defining match structure |
| match_stipulation_id | int\|null | Foreign key to stipulation |
| preview | string\|null | Promotional preview text |

#### Key Relationships
```
EventMatch
├── event (BelongsTo → Event)
├── matchStipulation (BelongsTo → MatchStipulation)
├── competitors (HasMany → MatchCompetitor)
├── wrestlers (MorphToMany → Wrestler)
│   └── via events_matches_competitors with side_number
├── tagTeams (MorphToMany → TagTeam)
│   └── via events_matches_competitors with side_number
├── referees (BelongsToMany → Referee)
│   └── via events_matches_referees
├── titles (BelongsToMany → Title)
│   └── via events_matches_titles
├── result (HasOne → MatchResult)
├── winners (HasManyThrough → MatchWinner via MatchResult)
└── losers (HasManyThrough → MatchLoser via MatchResult)
```

---

### 2. MatchCompetitor

**Model:** `App\Models\Matches\MatchCompetitor`

Polymorphic pivot model linking competitors (Wrestler or TagTeam) to matches with side assignment.

**Extends:** `MorphPivot`

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| match_id | int | Foreign key to EventMatch |
| competitor_type | string | Morph type (Wrestler or TagTeam class) |
| competitor_id | int | Foreign key to competitor |
| side_number | int | Side/team assignment (1, 2, 3, etc.) |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `competitor()` | MorphTo | Polymorphic relation to Wrestler or TagTeam |
| `getCompetitor()` | Wrestler\|TagTeam | Type-safe competitor accessor |

#### Side Number Pattern

The `side_number` field groups competitors into teams/sides for the match:

```
┌─────────────────────────────────────────────────────────────┐
│                    SIDE NUMBER PATTERN                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Singles Match:                                             │
│    Side 1: [Wrestler A]                                     │
│    Side 2: [Wrestler B]                                     │
│                                                             │
│  Tag Team Match:                                            │
│    Side 1: [TagTeam A] or [Wrestler A, Wrestler B]          │
│    Side 2: [TagTeam B] or [Wrestler C, Wrestler D]          │
│                                                             │
│  Triple Threat:                                             │
│    Side 1: [Wrestler A]                                     │
│    Side 2: [Wrestler B]                                     │
│    Side 3: [Wrestler C]                                     │
│                                                             │
│  Battle Royal (null sides):                                 │
│    All competitors assigned, no side grouping               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 3. MatchStipulation

**Model:** `App\Models\Matches\MatchStipulation`

Defines special rules or conditions for a match.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Display name (e.g., "Steel Cage") |
| slug | string | URL-safe identifier |
| description | string\|null | Rule description |
| is_active | bool | Whether stipulation is available for use |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isStandardMatch()` | bool | Check if slug is 'standard' |
| `requiresSpecialSetup()` | bool | Needs special equipment (cage, ladder, etc.) |
| `isHardcoreStipulation()` | bool | Involves weapons/hardcore elements |
| `hasEliminationRules()` | bool | Uses elimination format |
| `getDisplayName()` | string | Formatted name for display |

#### Common Stipulations
| Slug | Special Setup | Hardcore |
|------|---------------|----------|
| standard | No | No |
| steel_cage | Yes | No |
| ladder_match | Yes | No |
| no_dq | No | Yes |
| hardcore_match | No | Yes |
| tlc_match | Yes | Yes |
| hell_in_a_cell | Yes | No |
| falls_count_anywhere | No | Yes |

---

### 4. MatchResult

**Model:** `App\Models\Matches\MatchResult`

Records the outcome of a completed match.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| match_id | int | Foreign key to EventMatch |
| match_decision | MatchDecision | How the match ended |
| winner_type | string | Morph type for primary winner |
| winner_id | int | Foreign key to primary winner |

#### Relationships
```
MatchResult
├── match (BelongsTo → EventMatch)
├── winners (HasMany → MatchWinner)
├── losers (HasMany → MatchLoser)
└── winner (MorphTo → Wrestler|TagTeam) - primary winner
```

---

### 5. MatchWinner

**Model:** `App\Models\Matches\MatchWinner`

Individual winner record for a match, allowing multiple winners (e.g., tag team partners).

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| match_result_id | int | Foreign key to MatchResult |
| match_competitor_id | int | Foreign key to MatchCompetitor |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `matchResult()` | BelongsTo | Parent result |
| `competitor()` | BelongsTo | Link to MatchCompetitor |
| `winner()` | Wrestler\|TagTeam | Resolved winner entity |
| `getWinner()` | Wrestler\|TagTeam | Type-safe winner accessor |

---

### 6. MatchLoser

**Model:** `App\Models\Matches\MatchLoser`

Individual loser record for a match, providing symmetric querying alongside MatchWinner.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| match_result_id | int | Foreign key to MatchResult |
| match_competitor_id | int | Foreign key to MatchCompetitor |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `matchResult()` | BelongsTo | Parent result |
| `competitor()` | BelongsTo | Link to MatchCompetitor |
| `loser()` | Wrestler\|TagTeam | Resolved loser entity |
| `getLoser()` | Wrestler\|TagTeam | Type-safe loser accessor |

---

## Enums

### MatchType

**Location:** `App\Enums\MatchType`

Defines the structure and competitor requirements for a match.

```php
enum MatchType: string {
    case Singles = 'singles';
    case TagTeam = 'tag-team';
    case TripleThreat = 'triple-threat';
    case Triangle = 'triangle';
    case Fatal4Way = 'fatal-4-way';
    case SixManTagTeam = '6-man-tag-team';
    case EightManTagTeam = '8-man-tag-team';
    case TenManTagTeam = '10-man-tag-team';
    case TwoOnOneHandicap = 'two-on-one-handicap';
    case ThreeOnTwoHandicap = 'three-on-two-handicap';
    case BattleRoyal = 'battle-royal';
    case RoyalRumble = 'royal-rumble';
    case TornadoTagTeam = 'tornado-tag-team';
    case Gauntlet = 'gauntlet';
}
```

#### Match Type Properties

| Type | Sides | Min Competitors | Allows TagTeams |
|------|-------|-----------------|-----------------|
| Singles | 2 | 2 | No |
| TagTeam | 2 | 2 | Yes |
| TripleThreat | 3 | 3 | Yes |
| Triangle | 3 | 3 | Yes |
| Fatal4Way | 4 | 4 | Yes |
| SixManTagTeam | 2 | 2 | Yes |
| EightManTagTeam | 2 | 2 | Yes |
| TenManTagTeam | 2 | 2 | Yes |
| TwoOnOneHandicap | 2 | 2 | No |
| ThreeOnTwoHandicap | 2 | 2 | No |
| BattleRoyal | null | 2 | Yes |
| RoyalRumble | null | 2 | Yes |
| TornadoTagTeam | 2 | 2 | Yes |
| Gauntlet | 2 | 2 | No |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `label()` | string | Human-readable name |
| `numberOfSides()` | int\|null | Side count (null for battle royals) |
| `getAllowedCompetitorTypes()` | array | ['wrestler'] or ['wrestler', 'tag_team'] |
| `allowsWrestlers()` | bool | Always true |
| `allowsTagTeams()` | bool | Match-type dependent |
| `getMinimumCompetitors()` | int | Minimum required |
| `getMaximumCompetitors()` | int | Maximum allowed |

---

### MatchDecision

**Location:** `App\Enums\MatchDecision`

Defines how a match concluded and whether it produced winners/losers.

```php
enum MatchDecision: string {
    case Pinfall = 'pinfall';
    case Submission = 'submission';
    case Disqualification = 'disqualification';
    case Countout = 'countout';
    case Knockout = 'knockout';
    case Stipulation = 'stipulation';
    case Forfeit = 'forfeit';
    case TimeLimitDraw = 'time-limit-draw';
    case NoDecision = 'no-decision';
    case ReverseDecision = 'reverse-decision';
}
```

#### Decision Outcomes

| Decision | Has Winners | Has Losers | Notes |
|----------|-------------|------------|-------|
| Pinfall | Yes | Yes | Standard finish |
| Submission | Yes | Yes | Tap out / pass out |
| Disqualification | Yes | Yes | Winner by opponent DQ |
| Countout | Yes | Yes | Winner by opponent countout |
| Knockout | Yes | Yes | TKO / referee stoppage |
| Stipulation | Yes | Yes | Won via stipulation rules |
| Forfeit | Yes | Yes | Opponent didn't compete |
| TimeLimitDraw | **No** | **No** | Time expired, no winner |
| NoDecision | **No** | **No** | Match thrown out |
| ReverseDecision | **No** | **No** | Result overturned |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `label()` | string | Human-readable name |
| `hasWinners()` | bool | True if decision produces winners |
| `hasLosers()` | bool | True if decision produces losers |
| `hasNoOutcome()` | bool | True for draws/no contests |

---

## Database Tables

### Match Tables
| Table | Description |
|-------|-------------|
| `events_matches` | Core match records |
| `events_matches_competitors` | Polymorphic competitor assignments |
| `events_matches_referees` | Referee officiating assignments |
| `events_matches_titles` | Championship title associations |
| `events_matches_results` | Match outcomes |
| `events_matches_winners` | Individual winner records |
| `events_matches_losers` | Individual loser records |
| `matches_stipulations` | Match stipulation definitions |

### Schema: events_matches
```sql
id                   BIGINT PRIMARY KEY
event_id             BIGINT FOREIGN KEY
match_number         INT
match_type           VARCHAR(255)  -- MatchType enum value
match_stipulation_id BIGINT NULLABLE FOREIGN KEY
preview              TEXT NULLABLE
created_at           TIMESTAMP
updated_at           TIMESTAMP
```

### Schema: events_matches_competitors
```sql
id              BIGINT PRIMARY KEY
match_id        BIGINT FOREIGN KEY
competitor_type VARCHAR(255)  -- Morph type
competitor_id   BIGINT        -- Morph ID
side_number     INT           -- Team/side assignment
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Schema: events_matches_referees
```sql
match_id    BIGINT FOREIGN KEY
referee_id  BIGINT FOREIGN KEY
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: events_matches_titles
```sql
match_id    BIGINT FOREIGN KEY
title_id    BIGINT FOREIGN KEY
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: events_matches_results
```sql
id             BIGINT PRIMARY KEY
match_id       BIGINT FOREIGN KEY
match_decision VARCHAR(255)  -- MatchDecision enum value
winner_type    VARCHAR(255)  -- Morph type
winner_id      BIGINT        -- Morph ID
created_at     TIMESTAMP
updated_at     TIMESTAMP
```

### Schema: events_matches_winners
```sql
id                  BIGINT PRIMARY KEY
match_result_id     BIGINT FOREIGN KEY
match_competitor_id BIGINT FOREIGN KEY
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### Schema: events_matches_losers
```sql
id                  BIGINT PRIMARY KEY
match_result_id     BIGINT FOREIGN KEY
match_competitor_id BIGINT FOREIGN KEY
created_at          TIMESTAMP
updated_at          TIMESTAMP
```

### Schema: matches_stipulations
```sql
id          BIGINT PRIMARY KEY
name        VARCHAR(255)
slug        VARCHAR(255)
description TEXT NULLABLE
is_active   BOOLEAN DEFAULT TRUE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

## Custom Collection

### MatchCompetitorsCollection

**Location:** `App\Collections\MatchCompetitorsCollection`

Custom Eloquent collection for MatchCompetitor models with specialized methods.

| Method | Return | Description |
|--------|--------|-------------|
| `sortBySideNumber()` | static | Sort by side_number ascending |
| `sides()` | array | Get unique side numbers |
| `countPerSide()` | Collection | Map side → count |
| `countCompetitorsForSide(int)` | int | Count for specific side |
| `hasTagTeamsOnSide(int)` | bool | Check for tag teams on side |
| `allBookable()` | bool | All competitors bookable? |
| `groupBySide()` | Collection | Group by side_number |
| `mapToCompetitorInstances()` | Collection | Extract Wrestler/TagTeam models |
| `onlyWrestlers()` | static | Filter to wrestlers |
| `onlyTagTeams()` | static | Filter to tag teams |
| `filterBySide(int)` | static | Filter by side number |
| `pluckCompetitors()` | Collection | Get competitor models |
| `pluckWrestlers()` | Collection | Get wrestler models only |
| `pluckTagTeams()` | Collection | Get tag team models only |
| `pluckCompetitorsBySide()` | Collection | Competitors grouped by side |
| `getCompetitorsForSide(int)` | Collection | Get competitors for side |

---

## Actions

### AddMatchForEventAction

**Location:** `App\Actions\Matches\AddMatchForEventAction`

Creates a complete match for an event with all components.

```php
public function handle(Event $event, EventMatchData $eventMatchData): EventMatch
```

**Workflow:**
1. Validate event can accept matches
2. Validate match data completeness
3. Create base match record (in transaction)
4. Add referees via `AddRefereesToMatchAction`
5. Add titles via `AddTitlesToMatchAction`
6. Add competitors via `AddCompetitorsToMatchAction`
7. Return created match

---

### AddCompetitorsToMatchAction

**Location:** `App\Actions\Matches\AddCompetitorsToMatchAction`

Assigns competitors to match with side-based grouping.

```php
public function handle(EventMatch $eventMatch, Collection $competitors): void
```

**Parameters:**
- `$competitors`: Collection keyed by side number, each containing `wrestlers` and `tag_teams` arrays

**Example:**
```php
$competitors = collect([
    1 => ['wrestlers' => [$wrestler1], 'tag_teams' => []],
    2 => ['wrestlers' => [$wrestler2], 'tag_teams' => []]
]);
AddCompetitorsToMatchAction::run($match, $competitors);
```

**Validation:**
- Must have at least 2 sides with competitors

---

### AddRefereesToMatchAction

**Location:** `App\Actions\Matches\AddRefereesToMatchAction`

Assigns referees to officiate a match.

```php
public function handle(EventMatch $eventMatch, Collection $referees): void
```

**Validation:**
- Referees must be bookable (`isBookable()` returns true)
- At least one eligible referee required

---

### AddWrestlersToMatchAction

**Location:** `App\Actions\Matches\AddWrestlersToMatchAction`

Adds wrestlers to a specific side of a match.

```php
public function handle(EventMatch $eventMatch, Collection $wrestlers, int $sideNumber): void
```

---

### AddTagTeamsToMatchAction

**Location:** `App\Actions\Matches\AddTagTeamsToMatchAction`

Adds tag teams to a specific side of a match.

```php
public function handle(EventMatch $eventMatch, Collection $tagTeams, int $sideNumber): void
```

---

### AddTitlesToMatchAction

**Location:** `App\Actions\Matches\AddTitlesToMatchAction`

Associates championship titles with a match.

```php
public function handle(EventMatch $eventMatch, Collection $titles): void
```

---

## Business Rules

### Competitor Booking Rules

#### Double-Booking Restriction
Competitors (wrestlers and tag teams) can only compete in **one match per event**:

| Entity | Same-Event Booking |
|--------|-------------------|
| Wrestler | One match per event |
| Tag Team | One match per event |
| Referee | **Multiple matches per event** |

**Rationale:** Wrestlers/tag teams compete; they need rest between matches. Referees officiate multiple matches on the same show.

#### Bookability Requirements
Competitors must be bookable to be assigned to a match:
- Employed (not future employment)
- Not injured
- Not suspended
- Not retired

### Match Type Validation

#### Competitor Type Restrictions
Match types enforce which competitor types are allowed:

```php
// Singles matches only allow wrestlers
MatchType::Singles->allowsTagTeams(); // false

// Tag team matches allow both
MatchType::TagTeam->allowsTagTeams(); // true
MatchType::TagTeam->allowsWrestlers(); // true
```

#### Side Requirements
Match types define expected number of sides:

| Type | Expected Sides |
|------|----------------|
| Singles, TagTeam | 2 |
| TripleThreat | 3 |
| Fatal4Way | 4 |
| BattleRoyal | null (no sides) |

### Result Recording Rules

#### Decision Outcomes
- Decisions with outcomes (Pinfall, etc.) require winners and losers
- Decisions without outcomes (TimeLimitDraw, etc.) have no winners/losers

#### Winner/Loser Recording
- Winners are recorded in `events_matches_winners`
- Losers are recorded in `events_matches_losers`
- Both reference `match_competitor_id` to link back to the original assignment

### Title Match Rules

- Title matches associate championships via `events_matches_titles`
- Competitors in title matches should be eligible challengers
- Title changes are processed based on match result
- Active titles only can be put on the line

---

## File Locations

```
app/
├── Enums/
│   ├── MatchType.php
│   └── MatchDecision.php
├── Models/Matches/
│   ├── EventMatch.php
│   ├── MatchCompetitor.php
│   ├── MatchStipulation.php
│   ├── MatchResult.php
│   ├── MatchWinner.php
│   └── MatchLoser.php
├── Models/Concerns/
│   └── HasMatches.php              # Used by Event model (event has many matches)
├── Collections/
│   └── MatchCompetitorsCollection.php
├── Actions/Matches/
│   ├── AddMatchForEventAction.php
│   ├── AddCompetitorsToMatchAction.php
│   ├── AddRefereesToMatchAction.php
│   ├── AddWrestlersToMatchAction.php
│   ├── AddTagTeamsToMatchAction.php
│   └── AddTitlesToMatchAction.php
└── Data/Matches/
    └── EventMatchData.php
```

---

## Related Systems

| System | Relationship |
|--------|--------------|
| Events | Matches belong to events |
| Wrestlers | Compete as individual competitors |
| Tag Teams | Compete as team competitors |
| Referees | Officiate matches |
| Titles | Championships at stake |

---

## Comparison: Competitors vs Officials

| Aspect | Competitors | Officials |
|--------|-------------|-----------|
| Entities | Wrestler, TagTeam | Referee |
| Interface | BookableCompetitor | BookableOfficial |
| Relationship | MorphToMany (polymorphic) | BelongsToMany |
| Pivot Table | events_matches_competitors | events_matches_referees |
| Side Assignment | Yes (side_number) | No |
| Double-Booking | One match per event | Multiple matches per event |
| Role | Compete in match | Officiate match |
