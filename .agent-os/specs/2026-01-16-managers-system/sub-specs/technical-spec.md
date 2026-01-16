# Technical Specification

> Managers System
> Reference: @.agent-os/specs/2026-01-16-managers-system/spec.md

---

## Entity Reference

### 1. Manager

**Model:** `App\Models\Managers\Manager`

The core entity representing a talent manager. Managers represent wrestlers and tag teams but are **not bookable** for matches.

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
Manager
├── employments (HasMany → ManagerEmployment)
│   ├── currentEmployment - ended_at IS NULL AND started_at <= now
│   ├── futureEmployment - ended_at IS NULL AND started_at > now
│   ├── previousEmployments - ended_at IS NOT NULL
│   └── firstEmployment - earliest by started_at
├── injuries (HasMany → ManagerInjury)
│   ├── currentInjury - ended_at IS NULL
│   └── previousInjuries - ended_at IS NOT NULL
├── suspensions (HasMany → ManagerSuspension)
│   ├── currentSuspension - ended_at IS NULL
│   └── previousSuspensions - ended_at IS NOT NULL
├── retirements (HasMany → ManagerRetirement)
│   ├── currentRetirement - ended_at IS NULL
│   └── previousRetirements - ended_at IS NOT NULL
├── wrestlers (BelongsToMany → Wrestler)
│   ├── currentWrestlers - fired_at IS NULL
│   └── previousWrestlers - fired_at IS NOT NULL
└── tagTeams (BelongsToMany → TagTeam)
    ├── currentTagTeams - fired_at IS NULL
    └── previousTagTeams - fired_at IS NOT NULL
```

#### Traits
- `IsEmployable` - Employment lifecycle
- `IsInjurable` - Injury tracking
- `IsSuspendable` - Suspension tracking
- `IsRetirable` - Retirement tracking
- `DefinesManagedAliases` - Wrestler/TagTeam management relationships
- `ProvidesDisplayName` - Display name from first_name + last_name
- `ValidatesEmployment` - Employment validation
- `ValidatesInjury` - Injury validation
- `ValidatesSuspension` - Suspension validation
- `ValidatesRetirement` - Retirement validation
- `ValidatesRestoration` - Recovery validation
- `SoftDeletes` - Soft deletion

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `getDisplayName()` | string | Returns "FirstName LastName" |
| `isEmployed()` | bool | Has current employment |
| `isUnemployed()` | bool | Never employed |
| `isReleased()` | bool | Was employed, now without |
| `hasFutureEmployment()` | bool | Signed for future |
| `isInjured()` | bool | Currently injured |
| `isSuspended()` | bool | Currently suspended |
| `isRetired()` | bool | Currently retired |
| `removeFromCurrentWrestlers()` | void | Fire from all wrestler clients |
| `removeFromCurrentTagTeams()` | void | Fire from all tag team clients |

#### NOT Implemented
- `isBookable()` - Managers are not bookable
- `BookableCompetitor` interface - Not applicable

---

### 2. ManagerEmployment

**Model:** `App\Models\Managers\ManagerEmployment`

Tracks employment periods for a manager.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| manager_id | int | Foreign key to manager |
| started_at | datetime | Employment start date |
| ended_at | datetime\|null | Employment end date (null = current) |

#### Employment States
| State | Condition |
|-------|-----------|
| Current | `started_at <= now AND ended_at IS NULL` |
| Future | `started_at > now AND ended_at IS NULL` |
| Ended | `ended_at IS NOT NULL` |

---

### 3. ManagerInjury

**Model:** `App\Models\Managers\ManagerInjury`

Tracks injury periods for a manager.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| manager_id | int | Foreign key to manager |
| started_at | datetime | Injury start date |
| ended_at | datetime\|null | Recovery date (null = currently injured) |

---

### 4. ManagerSuspension

**Model:** `App\Models\Managers\ManagerSuspension`

Tracks suspension periods for a manager.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| manager_id | int | Foreign key to manager |
| started_at | datetime | Suspension start date |
| ended_at | datetime\|null | Lift date (null = currently suspended) |

---

### 5. ManagerRetirement

**Model:** `App\Models\Managers\ManagerRetirement`

Tracks retirement periods for a manager.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| manager_id | int | Foreign key to manager |
| started_at | datetime | Retirement start date |
| ended_at | datetime\|null | Comeback date (null = currently retired) |

---

## Enums

### EmploymentStatus

**Location:** `App\Enums\Shared\EmploymentStatus`

Shared enum used by Wrestlers, Tag Teams, Managers, and Referees.

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

### Manager Tables
| Table | Description |
|-------|-------------|
| `managers` | Core manager records |
| `managers_employments` | Employment periods |
| `managers_injuries` | Injury periods |
| `managers_suspensions` | Suspension periods |
| `managers_retirements` | Retirement periods |

### Pivot Tables (defined in other systems)
| Table | Description |
|-------|-------------|
| `wrestlers_managers` | Manager-Wrestler relationships |
| `tag_teams_managers` | Manager-TagTeam relationships |

### Schema: managers
```sql
id          BIGINT PRIMARY KEY
first_name  VARCHAR(255)
last_name   VARCHAR(255)
created_at  TIMESTAMP
updated_at  TIMESTAMP
deleted_at  TIMESTAMP NULLABLE
```

### Schema: managers_employments
```sql
id          BIGINT PRIMARY KEY
manager_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: managers_injuries
```sql
id          BIGINT PRIMARY KEY
manager_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: managers_suspensions
```sql
id          BIGINT PRIMARY KEY
manager_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: managers_retirements
```sql
id          BIGINT PRIMARY KEY
manager_id  BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

## Query Builder Methods

### Employment Scopes

**Location:** `App\Builders\Roster\ManagerBuilder`

**Extends:** `IndividualRosterMemberBuilder` (shared with Wrestler, Referee)

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

### Search Scopes

| Method | Description |
|--------|-------------|
| `whereNameLike(string $search)` | Search by first/last name |

### NO Booking Scopes

Managers are **not bookable**. The following scopes do NOT exist:
- `bookable()`
- `notBookedOn(Carbon $date)`

### availableOn(Carbon $date) - API Consistency

The `availableOn(Carbon $date)` method exists but **ignores the date parameter**:

```php
// From SingleRosterMemberBuilder
public function availableOn(Carbon $date): static
{
    // Note: $date parameter is intentionally unused for managers/referees
    // as they are not booked for matches. Method signature kept for consistency.
    return $this->available();
}
```

**Purpose:** API consistency. All roster builders have the same method signature for polymorphic use, even though managers/referees don't participate in matches.

---

## Lifecycle Validation

**Traits:** `ValidatesEmployment`, `ValidatesInjury`, `ValidatesSuspension`, `ValidatesRetirement`, `ValidatesRestoration`

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

## Business Rules

### Employment Rules
- Employment uses temporal tracking (`started_at`/`ended_at`)
- Multiple employment periods allowed (rehires)
- Cannot be employed while retired
- Releasing a manager sets `ended_at` on current employment

### Availability Rules
- Injuries, suspensions, retirements all use same temporal pattern
- Active state: `ended_at IS NULL`
- Multiple periods allowed (multiple injuries, comebacks from retirement)
- Status is computed, never stored

### Client Assignment Rules

#### Relationship Direction
Managers are **hired by** wrestlers/tag teams. The pivot tables track "this wrestler/tag team has hired this manager."

> **Note:** The relationship direction differs conceptually from the Wrestlers spec which says "wrestlers hire managers." From the Manager's perspective, the relationship is viewed as "manager manages wrestlers." Both are valid views of the same pivot data.

#### Terminology
| Field | Meaning |
|-------|---------|
| `hired_at` | Date the management relationship began |
| `fired_at` | Date the management relationship ended (null = current) |

#### Employment Requirement
Both manager AND client (wrestler/tag team) must meet one of these conditions:
- Currently employed
- Has future employment scheduled

This allows managers and clients to be paired before their debut, enabling them to debut together.

#### Multiplicity
- A manager can manage multiple wrestlers simultaneously
- A manager can manage multiple tag teams simultaneously
- A wrestler can have multiple managers simultaneously
- A tag team can have multiple managers simultaneously

### Relationship Persistence Rules

**Management relationships are independent of employment status.** They persist until explicitly terminated (`fired_at` is set), regardless of either party's employment/retirement status.

| Scenario | Relationship Status |
|----------|---------------------|
| Manager released | Client relationships persist (not auto-terminated) |
| Manager retired | Client relationships persist (not auto-terminated) |
| Client (wrestler/tag team) released | Management relationship persists |
| Client (wrestler/tag team) retired | Management relationship persists |

**Rationale:** Preserves historical context. You'd want to know "Bobby Heenan was still officially managing André the Giant when André retired" even though both have different employment statuses.

**To end a relationship:** Explicitly set `fired_at` on the pivot record using the appropriate action/service.

### Independent Retirement Model

Manager retirement and client retirement are **independent**:
- Manager retires → can still have "current" clients (relationships not auto-ended)
- Client retires → can still have a "current" manager (relationship not auto-ended)
- Manager comes out of retirement → still has their clients
- Client comes out of retirement → still has their manager

The `retired()` scope shows managers who are retired. It does NOT filter based on client retirement status.

### Stable Association Rules
Managers are **indirectly** associated with stables:
- If a manager manages a wrestler who is in a stable, the manager is associated with that stable
- If a manager manages a tag team whose wrestlers are in a stable, the manager is associated with that stable
- Managers do NOT have direct stable membership
- See Stables System spec for details

### NOT Bookable
- Managers do not participate in matches
- No `isBookable()` method exists
- No booking validation required
- `available()` scope exists for assignment eligibility, not match booking

---

## File Locations

```
app/
├── Enums/Shared/
│   └── EmploymentStatus.php
├── Models/Managers/
│   ├── Manager.php
│   ├── ManagerEmployment.php
│   ├── ManagerInjury.php
│   ├── ManagerSuspension.php
│   └── ManagerRetirement.php
├── Models/Concerns/
│   ├── IsEmployable.php
│   ├── IsInjurable.php
│   ├── IsSuspendable.php
│   ├── IsRetirable.php
│   ├── DefinesManagedAliases.php
│   ├── ProvidesDisplayName.php
│   └── Validates*.php (multiple validation traits)
└── Builders/Roster/
    ├── ManagerBuilder.php
    └── IndividualRosterMemberBuilder.php
```

---

## Comparison with Other Roster Entities

| Feature | Manager | Wrestler | Tag Team | Referee |
|---------|---------|----------|----------|---------|
| Employment | Yes | Yes | Yes | Yes |
| Injuries | Yes | Yes | No | Yes |
| Suspensions | Yes | Yes | Yes | Yes |
| Retirements | Yes | Yes | Yes | Yes |
| Bookable | **No** | Yes | Yes | Yes |
| Manages Others | Yes | No | No | No |
| Can Be Managed | No | Yes | Yes | No |
| Name Fields | first/last | name | name | first/last |
| Builder Base | Individual | Individual | Standalone | Individual |
