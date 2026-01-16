# Technical Specification

> Referees System
> Reference: @.agent-os/specs/2026-01-16-referees-system/spec.md

---

## Entity Reference

### 1. Referee

**Model:** `App\Models\Referees\Referee`

The core entity representing a match official. Referees **officiate matches** but do not compete.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| first_name | string | First name |
| last_name | string | Last name |
| displayName | string | **Computed** "FirstName LastName" |
| status | EmploymentStatus | **Computed** from relationships |

#### Key Relationships
```
Referee
├── employments (HasMany → RefereeEmployment)
│   ├── currentEmployment - ended_at IS NULL AND started_at <= now
│   ├── futureEmployment - ended_at IS NULL AND started_at > now
│   ├── previousEmployments - ended_at IS NOT NULL
│   └── firstEmployment - earliest by started_at
├── injuries (HasMany → RefereeInjury)
│   ├── currentInjury - ended_at IS NULL
│   └── previousInjuries - ended_at IS NOT NULL
├── suspensions (HasMany → RefereeSuspension)
│   ├── currentSuspension - ended_at IS NULL
│   └── previousSuspensions - ended_at IS NOT NULL
├── retirements (HasMany → RefereeRetirement)
│   ├── currentRetirement - ended_at IS NULL
│   └── previousRetirements - ended_at IS NOT NULL
└── matches (BelongsToMany → EventMatch)
    ├── upcomingMatches - event date >= now (PENDING - see tasks.md)
    └── previousMatches - event date < now
```

#### Interfaces
- `BookableOfficial` - Can be assigned to officiate matches
- `Employable` - Employment lifecycle
- `Injurable` - Injury tracking
- `Suspendable` - Suspension tracking
- `Retirable` - Retirement tracking
- `HasDisplayName` - Display name support

#### Traits
- `HasMatches` - Base match relationship (used with trait resolution)
- `OfficiatesMatches` - Match officiating relationship
- `IsEmployable` - Employment lifecycle
- `IsInjurable` - Injury tracking
- `IsSuspendable` - Suspension tracking
- `IsRetirable` - Retirement tracking
- `ProvidesDisplayName` - Display name from first_name + last_name
- `ValidatesEmployment` - Employment validation
- `ValidatesInjury` - Injury validation
- `ValidatesSuspension` - Suspension validation
- `ValidatesRetirement` - Retirement validation
- `SoftDeletes` - Soft deletion

#### Trait Resolution
The Referee model uses both `HasMatches` and `OfficiatesMatches` traits which have overlapping methods. PHP trait conflict resolution is used:

```php
use HasMatches, OfficiatesMatches {
    OfficiatesMatches::matches insteadof HasMatches;
    OfficiatesMatches::previousMatches insteadof HasMatches;
}
```

This ensures `OfficiatesMatches` methods take precedence for the officiating relationship.

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `getDisplayName()` | string | Returns "FirstName LastName" |
| `isBookable()` | bool | Can be assigned to officiate matches |
| `isEmployed()` | bool | Has current employment |
| `isUnemployed()` | bool | Never employed |
| `isReleased()` | bool | Was employed, now without |
| `hasFutureEmployment()` | bool | Signed for future |
| `isInjured()` | bool | Currently injured |
| `isSuspended()` | bool | Currently suspended |
| `isRetired()` | bool | Currently retired |

---

### 2. RefereeEmployment

**Model:** `App\Models\Referees\RefereeEmployment`

Tracks employment periods for a referee.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| referee_id | int | Foreign key to referee |
| started_at | datetime | Employment start date |
| ended_at | datetime\|null | Employment end date (null = current) |

#### Employment States
| State | Condition |
|-------|-----------|
| Current | `started_at <= now AND ended_at IS NULL` |
| Future | `started_at > now AND ended_at IS NULL` |
| Ended | `ended_at IS NOT NULL` |

---

### 3. RefereeInjury

**Model:** `App\Models\Referees\RefereeInjury`

Tracks injury periods for a referee.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| referee_id | int | Foreign key to referee |
| started_at | datetime | Injury start date |
| ended_at | datetime\|null | Recovery date (null = currently injured) |

---

### 4. RefereeSuspension

**Model:** `App\Models\Referees\RefereeSuspension`

Tracks suspension periods for a referee.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| referee_id | int | Foreign key to referee |
| started_at | datetime | Suspension start date |
| ended_at | datetime\|null | Lift date (null = currently suspended) |

---

### 5. RefereeRetirement

**Model:** `App\Models\Referees\RefereeRetirement`

Tracks retirement periods for a referee.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| referee_id | int | Foreign key to referee |
| started_at | datetime | Retirement start date |
| ended_at | datetime\|null | Comeback date (null = currently retired) |

---

## Enums

### EmploymentStatus

**Location:** `App\Enums\Shared\EmploymentStatus`

Shared enum used by all roster entities.

```php
enum EmploymentStatus: string {
    case Employed = 'employed';
    case FutureEmployment = 'future_employment';
    case Released = 'released';
    case Retired = 'retired';
    case Unemployed = 'unemployed';
}
```

#### Status Computation Priority
```
┌─────────────────────────────────────────────────────────────┐
│               EMPLOYMENT STATUS PRIORITY                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. RETIRED           (has active retirement)               │
│       ↓                                                     │
│  2. EMPLOYED          (has current employment)              │
│       ↓                                                     │
│  3. FUTURE_EMPLOYMENT (signed but not started)              │
│       ↓                                                     │
│  4. RELEASED          (was employed, now without)           │
│       ↓                                                     │
│  5. UNEMPLOYED        (never employed)                      │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## Database Tables

### Referee Tables
| Table | Description |
|-------|-------------|
| `referees` | Core referee records |
| `referees_employments` | Employment periods |
| `referees_injuries` | Injury periods |
| `referees_suspensions` | Suspension periods |
| `referees_retirements` | Retirement periods |

### Match Officiating Pivot
| Table | Description |
|-------|-------------|
| `events_matches_referees` | Referee-Match assignments |

### Schema: referees
```sql
id          BIGINT PRIMARY KEY
first_name  VARCHAR(255)
last_name   VARCHAR(255)
created_at  TIMESTAMP
updated_at  TIMESTAMP
deleted_at  TIMESTAMP NULLABLE
```

### Schema: referees_employments
```sql
id          BIGINT PRIMARY KEY
referee_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: referees_injuries
```sql
id          BIGINT PRIMARY KEY
referee_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: referees_suspensions
```sql
id          BIGINT PRIMARY KEY
referee_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: referees_retirements
```sql
id          BIGINT PRIMARY KEY
referee_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: events_matches_referees
```sql
match_id    BIGINT FOREIGN KEY
referee_id  BIGINT FOREIGN KEY
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

## Bookability Rules

### isBookable() Logic
```php
public function isBookable(): bool
{
    return ! (
        $this->isNotInEmployment() ||  // Must be employed
        $this->isSuspended() ||         // Must not be suspended
        $this->isInjured() ||           // Must not be injured
        $this->hasFutureEmployment()    // Must have started employment
    );
}
```

### Bookability Conditions
| Condition | Bookable? |
|-----------|-----------|
| Employed, healthy, not suspended | Yes |
| Employed but injured | No |
| Employed but suspended | No |
| Future employment (not started) | No |
| Released | No |
| Unemployed | No |
| Retired | No |

### Officials vs Competitors

Referees are **officials**, not competitors:

| Aspect | Referee (Official) | Wrestler (Competitor) |
|--------|-------------------|----------------------|
| Interface | `BookableOfficial` | `BookableCompetitor` |
| Trait | `OfficiatesMatches` | `IsBookableCompetitor` |
| Match Role | Officiates | Competes |
| Pivot Table | `events_matches_referees` | Polymorphic morph pivot |
| Relationship | `BelongsToMany` | `MorphToMany` |

---

## Query Builder Methods

### Employment Scopes

**Location:** `App\Builders\Roster\RefereeBuilder`

**Extends:** `IndividualRosterMemberBuilder` (shared with Wrestler, Manager)

| Method | Description |
|--------|-------------|
| `employed()` | Has current employment |
| `unemployed()` | Never employed |
| `released()` | Was employed, now without |
| `futureEmployed()` | Has future employment scheduled |

### Availability Scopes

| Method | Description |
|--------|-------------|
| `injured()` | Currently injured |
| `suspended()` | Currently suspended |
| `retired()` | Currently retired |
| `available()` | Employed + not injured + not suspended + not retired |
| `unavailable()` | Inverse of available |

### Booking Scopes

| Method | Description |
|--------|-------------|
| `bookable()` | Alias for `available()` |

### availableOn(Carbon $date) - API Consistency

The `availableOn(Carbon $date)` method exists but **ignores the date parameter**:

```php
// From SingleRosterMemberBuilder
public function availableOn(Carbon $date): static
{
    // Date parameter unused - referees can officiate multiple matches per day
    return $this->available();
}
```

**Purpose:** API consistency across all roster builders.

**Why date is ignored:** Unlike wrestlers/tag teams who can only compete in one match per event, referees CAN officiate multiple matches on the same date/event. Therefore, date-based conflict checking is not needed at the scope level.

---

## Lifecycle Validation

**Traits:** `ValidatesEmployment`, `ValidatesInjury`, `ValidatesSuspension`, `ValidatesRetirement`

### Employment Validation
| Method | Checks |
|--------|--------|
| `canBeEmployed()` | Not already employed, not retired |
| `ensureCanBeEmployed()` | Throws `CannotBeEmployedException` |

### Release Validation
| Method | Checks |
|--------|--------|
| `canBeReleased()` | Currently employed |
| `ensureCanBeReleased()` | Throws `CannotBeReleasedException` |

### Injury Validation
| Method | Checks |
|--------|--------|
| `canBeInjured()` | Employed, not already injured |
| `ensureCanBeInjured()` | Throws `CannotBeInjuredException` |

### Recovery Validation
| Method | Checks |
|--------|--------|
| `canBeCleared()` | Currently injured |
| `ensureCanBeCleared()` | Throws `CannotBeClearedException` |

### Suspension Validation
| Method | Checks |
|--------|--------|
| `canBeSuspended()` | Employed, not already suspended |
| `ensureCanBeSuspended()` | Throws `CannotBeSuspendedException` |

### Reinstatement Validation
| Method | Checks |
|--------|--------|
| `canBeReinstated()` | Currently suspended, still employed |
| `ensureCanBeReinstated()` | Throws `CannotBeReinstatedException` |

### Retirement Validation
| Method | Checks |
|--------|--------|
| `canBeRetired()` | Not already retired, currently employed |
| `ensureCanBeRetired()` | Throws `CannotBeRetiredException` |

### Unretirement Validation
| Method | Checks |
|--------|--------|
| `canBeUnretired()` | Currently retired |
| `ensureCanBeUnretired()` | Throws `CannotBeUnretiredException` |

---

## Match Officiating

### OfficiatesMatches Trait

**Location:** `App\Models\Concerns\OfficiatesMatches`

```php
public function matches(): BelongsToMany
{
    return $this->belongsToMany(EventMatch::class, 'events_matches_referees');
}

public function previousMatches(): BelongsToMany
{
    return $this->matches()->whereHas('event', function ($query) {
        $query->where('date', '<', now());
    });
}
```

| Relationship | Description |
|--------------|-------------|
| `matches()` | All match assignments |
| `previousMatches()` | Completed matches (event date < now) |

> **Note:** `upcomingMatches()` is a pending enhancement. See tasks.md for implementation details.

### Match Assignment Action

**Location:** `App\Actions\Matches\AddRefereesToMatchAction`

```php
public function handle(EventMatch $match, Collection $referees): void
```

**Workflow:**
1. Filter referees to ensure only eligible officials
2. Validate referees are bookable (`isBookable()` returns true)
3. Check for conflicts (double-booking)
4. Execute in database transaction
5. Attach eligible referees to match via pivot table

---

## Business Rules

### Employment Rules
- Employment uses temporal tracking (`started_at`/`ended_at`)
- Multiple employment periods allowed (rehires)
- Cannot be employed while retired
- Releasing a referee sets `ended_at` on current employment

### Availability Rules
- Injuries, suspensions, retirements all use same temporal pattern
- Active state: `ended_at IS NULL`
- Multiple periods allowed (multiple injuries, comebacks from retirement)
- Status is computed, never stored

### Independent Retirement Model

Referee retirement is independent and self-contained:
- Referee retires → cannot officiate matches until unretired
- Referee unretires → can be assigned to matches again
- No cascading effects (referees have no clients, stables, or managed relationships)

The `retired()` scope shows referees who are retired at the individual level.

### Officiating Rules
- Only bookable referees can be assigned to matches
- Multiple referees can officiate a single match
- A referee CAN officiate multiple matches on the same event/date (no double-booking restriction)
- Match officiating history tracked via pivot table

### No Double-Booking Restriction

Unlike wrestlers/tag teams, referees have **no double-booking restriction**:

| Entity | Same-Day Booking |
|--------|------------------|
| Wrestler | One match per event |
| Tag Team | One match per event |
| Referee | **Multiple matches per event** |

**Rationale:** In real wrestling, the same referee often officiates multiple matches on a single show.

### NOT Applicable to Referees
- **Cannot be managed** - No manager relationship
- **Cannot join stables** - No stable membership
- **Cannot compete** - Officials only, not competitors
- **Cannot win titles** - No championship relationships

---

## File Locations

```
app/
├── Enums/Shared/
│   └── EmploymentStatus.php
├── Models/Referees/
│   ├── Referee.php
│   ├── RefereeEmployment.php
│   ├── RefereeInjury.php
│   ├── RefereeSuspension.php
│   └── RefereeRetirement.php
├── Models/Concerns/
│   ├── OfficiatesMatches.php
│   ├── IsEmployable.php
│   ├── IsInjurable.php
│   ├── IsSuspendable.php
│   ├── IsRetirable.php
│   ├── ProvidesDisplayName.php
│   └── Validates*.php (multiple validation traits)
├── Models/Contracts/
│   ├── BookableOfficial.php
│   ├── Bookable.php
│   ├── Employable.php
│   ├── Injurable.php
│   ├── Suspendable.php
│   └── Retirable.php
├── Actions/Matches/
│   └── AddRefereesToMatchAction.php
└── Builders/Roster/
    ├── RefereeBuilder.php
    └── IndividualRosterMemberBuilder.php
```

---

## Comparison with Other Roster Entities

| Feature | Referee | Wrestler | Tag Team | Manager |
|---------|---------|----------|----------|---------|
| Employment | Yes | Yes | Yes | Yes |
| Injuries | Yes | Yes | No | Yes |
| Suspensions | Yes | Yes | Yes | Yes |
| Retirements | Yes | Yes | Yes | Yes |
| Bookable | Yes (official) | Yes (competitor) | Yes (competitor) | **No** |
| Match Role | Officiates | Competes | Competes | N/A |
| Can Be Managed | **No** | Yes | Yes | No |
| Can Manage | No | No | No | Yes |
| Stable Membership | **No** | Yes | Yes | Indirect |
| Championships | **No** | Singles | Tag Team | No |
| Name Fields | first/last | name | name | first/last |
| Builder Base | Individual | Individual | Standalone | Individual |
