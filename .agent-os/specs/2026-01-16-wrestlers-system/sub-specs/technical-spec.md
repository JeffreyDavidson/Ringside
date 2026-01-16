# Technical Specification

> Wrestlers System
> Reference: @.agent-os/specs/2026-01-16-wrestlers-system/spec.md

---

## Entity Reference

### 1. Wrestler

**Model:** `App\Models\Wrestlers\Wrestler`

The core entity representing an individual wrestling talent.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Display name |
| height | Height | Height value object (via HeightCast) |
| weight | int | Weight in pounds |
| hometown | string | Hometown |
| signature_move | string\|null | Signature finishing move |
| status | EmploymentStatus | **Computed** from relationships |

#### Key Relationships
```
Wrestler
├── employments (HasMany → WrestlerEmployment)
│   ├── currentEmployment - ended_at IS NULL AND started_at <= now
│   ├── futureEmployment - ended_at IS NULL AND started_at > now
│   ├── previousEmployments - ended_at IS NOT NULL
│   └── firstEmployment - earliest by started_at
├── injuries (HasMany → WrestlerInjury)
│   ├── currentInjury - ended_at IS NULL
│   └── previousInjuries - ended_at IS NOT NULL
├── suspensions (HasMany → WrestlerSuspension)
│   ├── currentSuspension - ended_at IS NULL
│   └── previousSuspensions - ended_at IS NOT NULL
├── retirements (HasMany → WrestlerRetirement)
│   ├── currentRetirement - ended_at IS NULL
│   └── previousRetirements - ended_at IS NOT NULL
├── managers (BelongsToMany → Manager)
│   ├── currentManagers - fired_at IS NULL
│   └── previousManagers - fired_at IS NOT NULL
├── tagTeams (BelongsToMany → TagTeam)
│   ├── currentTagTeam - left_at IS NULL (one only)
│   └── previousTagTeams - left_at IS NOT NULL
├── stables (BelongsToMany → Stable)
│   ├── currentStable - left_at IS NULL (one only)
│   └── previousStables - left_at IS NOT NULL
├── titleChampionships (MorphMany → TitleChampionship)
│   ├── currentChampionships - lost_at IS NULL
│   └── previousTitleChampionships - lost_at IS NOT NULL
└── matches (MorphToMany → EventMatch)
    └── previousMatches - event date < now
```

#### Traits
- `IsEmployable` - Employment lifecycle
- `IsInjurable` - Injury tracking
- `IsSuspendable` - Suspension tracking
- `IsRetirable` - Retirement tracking
- `CanBeManaged` - Manager relationships
- `CanJoinTagTeams` - Tag team membership
- `CanJoinStables` - Stable membership
- `CanWinTitles` - Championship relationships
- `IsBookableCompetitor` - Match participation
- `SoftDeletes` - Soft deletion

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isBookable()` | bool | Can be booked for matches |
| `isEmployed()` | bool | Has current employment |
| `isUnemployed()` | bool | Never employed |
| `isReleased()` | bool | Was employed, now without |
| `hasFutureEmployment()` | bool | Signed for future |
| `isInjured()` | bool | Currently injured |
| `isSuspended()` | bool | Currently suspended |
| `isRetired()` | bool | Currently retired |
| `isChampion()` | bool | Holds any title |

---

### 2. WrestlerEmployment

**Model:** `App\Models\Wrestlers\WrestlerEmployment`

Tracks employment periods for a wrestler.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| wrestler_id | int | Foreign key to wrestler |
| started_at | datetime | Employment start date |
| ended_at | datetime\|null | Employment end date (null = current) |

#### Employment States
| State | Condition |
|-------|-----------|
| Current | `started_at <= now AND ended_at IS NULL` |
| Future | `started_at > now AND ended_at IS NULL` |
| Ended | `ended_at IS NOT NULL` |

---

### 3. WrestlerInjury

**Model:** `App\Models\Wrestlers\WrestlerInjury`

Tracks injury periods for a wrestler.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| wrestler_id | int | Foreign key to wrestler |
| started_at | datetime | Injury start date |
| ended_at | datetime\|null | Recovery date (null = currently injured) |

---

### 4. WrestlerSuspension

**Model:** `App\Models\Wrestlers\WrestlerSuspension`

Tracks suspension periods for a wrestler.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| wrestler_id | int | Foreign key to wrestler |
| started_at | datetime | Suspension start date |
| ended_at | datetime\|null | Lift date (null = currently suspended) |

---

### 5. WrestlerRetirement

**Model:** `App\Models\Wrestlers\WrestlerRetirement`

Tracks retirement periods for a wrestler.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| wrestler_id | int | Foreign key to wrestler |
| started_at | datetime | Retirement start date |
| ended_at | datetime\|null | Comeback date (null = currently retired) |

---

### 6. WrestlerManager (Pivot)

**Model:** `App\Models\Wrestlers\WrestlerManager`

Pivot model for wrestler-manager relationships.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| wrestler_id | int | Foreign key to wrestler |
| manager_id | int | Foreign key to manager |
| hired_at | datetime | Management start date |
| fired_at | datetime\|null | Management end date (null = current) |

---

## Enums

### EmploymentStatus
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

### Wrestler Tables
| Table | Description |
|-------|-------------|
| `wrestlers` | Core wrestler records |
| `wrestlers_employments` | Employment periods |
| `wrestlers_injuries` | Injury periods |
| `wrestlers_suspensions` | Suspension periods |
| `wrestlers_retirements` | Retirement periods |
| `wrestlers_managers` | Manager relationship pivot |

### Schema: wrestlers
```sql
id              BIGINT PRIMARY KEY
name            VARCHAR(255)
height          INT
weight          INT
hometown        VARCHAR(255)
signature_move  VARCHAR(255) NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP NULLABLE
```

### Schema: wrestlers_employments
```sql
id          BIGINT PRIMARY KEY
wrestler_id BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: wrestlers_injuries
```sql
id          BIGINT PRIMARY KEY
wrestler_id BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: wrestlers_suspensions
```sql
id          BIGINT PRIMARY KEY
wrestler_id BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: wrestlers_retirements
```sql
id          BIGINT PRIMARY KEY
wrestler_id BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
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

---

## Query Builder Methods

### Employment Scopes

**Location:** `App\Builders\Roster\WrestlerBuilder`

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

### Booking Scopes

| Method | Description |
|--------|-------------|
| `available()` | Employed + not injured + not suspended + not retired |
| `unavailable()` | Inverse of available |
| `bookable()` | Alias for available |
| `availableOn(Carbon $date)` | Available AND not booked on date |
| `notBookedOn(Carbon $date)` | Not assigned to match on date |

---

## Business Rules

### Employment Rules
- Employment uses temporal tracking (`started_at`/`ended_at`)
- Multiple employment periods allowed (rehires)
- Future employment blocks bookability until start date
- Releasing a wrestler sets `ended_at` on current employment

### Availability Rules
- Injuries, suspensions, retirements all use same temporal pattern
- Active state: `ended_at IS NULL`
- Multiple periods allowed (multiple injuries, comebacks from retirement)
- Status is computed, never stored

### Relationship Rules
- Wrestler can have multiple managers simultaneously
- Wrestler can be in ONE tag team at a time
- Wrestler can be in ONE stable at a time
- Wrestler can hold multiple titles simultaneously

### Manager Relationship Rules

#### Relationship Direction
Wrestlers **hire** managers to represent them. The `WrestlerManager` pivot represents "this wrestler has hired this manager."

#### Terminology
| Field | Meaning |
|-------|---------|
| `hired_at` | Date wrestler hired the manager |
| `fired_at` | Date wrestler fired the manager (null = current) |

#### Employment Requirement
Both wrestler AND manager must meet one of these conditions:
- Currently employed (`started_at <= now AND ended_at IS NULL`)
- Has future employment scheduled (`started_at > now AND ended_at IS NULL`)

This allows wrestlers and managers to be paired before their debut, enabling them to debut together.

#### Multiplicity
- A wrestler can have multiple managers simultaneously
- A manager can manage multiple wrestlers simultaneously

#### Stable Association
Managers are **indirectly** associated with stables through the wrestlers they manage. If a manager manages a wrestler who is in a stable, the manager is considered "part of" that stable. See Stables System spec for details.

### Championship Rules
- Singles titles only (tag team titles go to TagTeam entity)
- Championship tracked via polymorphic MorphMany
- Current championship: `lost_at IS NULL`

---

## File Locations

```
app/
├── Enums/Shared/
│   └── EmploymentStatus.php
├── Models/Wrestlers/
│   ├── Wrestler.php
│   ├── WrestlerEmployment.php
│   ├── WrestlerInjury.php
│   ├── WrestlerSuspension.php
│   ├── WrestlerRetirement.php
│   └── WrestlerManager.php
├── Models/Concerns/
│   ├── IsEmployable.php
│   ├── IsInjurable.php
│   ├── IsSuspendable.php
│   ├── IsRetirable.php
│   ├── CanBeManaged.php
│   ├── CanJoinTagTeams.php
│   ├── CanJoinStables.php
│   ├── CanWinTitles.php
│   └── IsBookableCompetitor.php
└── Builders/Roster/
    ├── WrestlerBuilder.php
    └── IndividualRosterMemberBuilder.php
```

---

## Builder Hierarchy

### IndividualRosterMemberBuilder

**Location:** `App\Builders\Roster\IndividualRosterMemberBuilder`

Shared query builder for individual roster members (single-person entities).

#### Covered Entities
| Entity | Builder |
|--------|---------|
| Wrestler | `WrestlerBuilder` extends `IndividualRosterMemberBuilder` |
| Referee | `RefereeBuilder` extends `IndividualRosterMemberBuilder` |
| Manager | `ManagerBuilder` extends `IndividualRosterMemberBuilder` |

#### Purpose
Provides common query scopes shared across all individual roster members:
- Employment scopes (`employed()`, `unemployed()`, `released()`, `futureEmployed()`)
- Availability scopes where applicable

This separates individual roster members from group entities (TagTeam) which have different query requirements.

### WrestlerBuilder

**Location:** `App\Builders\Roster\WrestlerBuilder`

Wrestler-specific query builder extending `IndividualRosterMemberBuilder`.

Adds wrestler-specific scopes:
- Availability scopes (`injured()`, `suspended()`, `retired()`)
- Booking scopes (`available()`, `bookable()`, `availableOn()`)
- Match-related scopes (`notBookedOn()`)
