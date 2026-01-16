# Technical Specification

> Tag Teams System
> Reference: @.agent-os/specs/2026-01-16-tag-teams-system/spec.md

---

## Entity Reference

### 1. TagTeam

**Model:** `App\Models\TagTeams\TagTeam`

The core entity representing a paired wrestling talent unit.

#### Constants
```php
const NUMBER_OF_WRESTLERS_ON_TEAM = 2;
```

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Team name |
| signature_move | string\|null | Signature finishing move |
| status | EmploymentStatus | **Computed** from relationships |
| combinedWeight | int | **Computed** sum of current wrestlers' weights |

#### Key Relationships
```
TagTeam
├── wrestlers (BelongsToMany → Wrestler)
│   ├── currentWrestlers - left_at IS NULL
│   └── previousWrestlers - left_at IS NOT NULL
├── employments (HasMany → TagTeamEmployment)
│   ├── currentEmployment - ended_at IS NULL AND started_at <= now
│   ├── futureEmployment - ended_at IS NULL AND started_at > now
│   ├── previousEmployments - ended_at IS NOT NULL
│   └── firstEmployment - earliest by started_at
├── suspensions (HasMany → TagTeamSuspension)
│   ├── currentSuspension - ended_at IS NULL
│   └── previousSuspensions - ended_at IS NOT NULL
├── retirements (HasMany → TagTeamRetirement)
│   ├── currentRetirement - ended_at IS NULL
│   └── previousRetirements - ended_at IS NOT NULL
├── managers (BelongsToMany → Manager)
│   ├── currentManagers - fired_at IS NULL
│   └── previousManagers - fired_at IS NOT NULL
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
- `ProvidesTagTeamWrestlers` - Wrestler partnership management
- `IsEmployable` - Employment lifecycle
- `IsSuspendable` - Suspension tracking
- `IsRetirable` - Retirement tracking
- `CanBeManaged` - Manager relationships
- `CanJoinStables` - Stable membership
- `CanWinTitles` - Championship relationships
- `IsBookableCompetitor` - Match participation
- `ValidatesTagTeamLifecycle` - Business rule validation
- `SoftDeletes` - Soft deletion

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isBookable()` | bool | Can be booked for matches |
| `isUnbookable()` | bool | Cannot be booked |
| `isEmployed()` | bool | Has current employment |
| `isUnemployed()` | bool | Never employed |
| `isReleased()` | bool | Was employed, now without |
| `hasFutureEmployment()` | bool | Signed for future |
| `isSuspended()` | bool | Currently suspended |
| `isRetired()` | bool | Currently retired |
| `isChampion()` | bool | Holds any title |

---

### 2. TagTeamWrestler (Pivot)

**Model:** `App\Models\TagTeams\TagTeamWrestler`

Pivot model tracking wrestler partnerships in a tag team.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| tag_team_id | int | Foreign key to tag team |
| wrestler_id | int | Foreign key to wrestler |
| joined_at | datetime | When wrestler joined the team |
| left_at | datetime\|null | When wrestler left (null = current partner) |

#### Partnership States
| State | Condition |
|-------|-----------|
| Current Partner | `left_at IS NULL` |
| Former Partner | `left_at IS NOT NULL` |

---

### 3. TagTeamEmployment

**Model:** `App\Models\TagTeams\TagTeamEmployment`

Tracks employment periods for a tag team.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| tag_team_id | int | Foreign key to tag team |
| started_at | datetime | Employment start date |
| ended_at | datetime\|null | Employment end date (null = current) |

#### Employment States
| State | Condition |
|-------|-----------|
| Current | `started_at <= now AND ended_at IS NULL` |
| Future | `started_at > now AND ended_at IS NULL` |
| Ended | `ended_at IS NOT NULL` |

---

### 4. TagTeamSuspension

**Model:** `App\Models\TagTeams\TagTeamSuspension`

Tracks suspension periods for a tag team.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| tag_team_id | int | Foreign key to tag team |
| started_at | datetime | Suspension start date |
| ended_at | datetime\|null | Reinstatement date (null = currently suspended) |

---

### 5. TagTeamRetirement

**Model:** `App\Models\TagTeams\TagTeamRetirement`

Tracks retirement periods for a tag team.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| tag_team_id | int | Foreign key to tag team |
| started_at | datetime | Retirement start date |
| ended_at | datetime\|null | Comeback date (null = currently retired) |

---

### 6. TagTeamManager (Pivot)

**Model:** `App\Models\TagTeams\TagTeamManager`

Pivot model for tag team-manager relationships.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| tag_team_id | int | Foreign key to tag team |
| manager_id | int | Foreign key to manager |
| hired_at | datetime | Management start date |
| fired_at | datetime\|null | Management end date (null = current) |

---

## Enums

### EmploymentStatus

**Location:** `App\Enums\Shared\EmploymentStatus`

Shared enum used by both Wrestlers and Tag Teams.

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

### Tag Team Tables
| Table | Description |
|-------|-------------|
| `tag_teams` | Core tag team records |
| `tag_teams_wrestlers` | Wrestler partnership pivot |
| `tag_teams_employments` | Employment periods |
| `tag_teams_suspensions` | Suspension periods |
| `tag_teams_retirements` | Retirement periods |
| `tag_teams_managers` | Manager relationship pivot |

### Schema: tag_teams
```sql
id              BIGINT PRIMARY KEY
name            VARCHAR(255)
signature_move  VARCHAR(255) NULLABLE
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP NULLABLE
```

### Schema: tag_teams_wrestlers
```sql
id           BIGINT PRIMARY KEY
tag_team_id  BIGINT FOREIGN KEY
wrestler_id  BIGINT FOREIGN KEY
joined_at    TIMESTAMP
left_at      TIMESTAMP NULLABLE
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

### Schema: tag_teams_employments
```sql
id           BIGINT PRIMARY KEY
tag_team_id  BIGINT FOREIGN KEY
started_at   TIMESTAMP
ended_at     TIMESTAMP NULLABLE
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

### Schema: tag_teams_suspensions
```sql
id           BIGINT PRIMARY KEY
tag_team_id  BIGINT FOREIGN KEY
started_at   TIMESTAMP
ended_at     TIMESTAMP NULLABLE
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

### Schema: tag_teams_retirements
```sql
id           BIGINT PRIMARY KEY
tag_team_id  BIGINT FOREIGN KEY
started_at   TIMESTAMP
ended_at     TIMESTAMP NULLABLE
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

### Schema: tag_teams_managers
```sql
id           BIGINT PRIMARY KEY
tag_team_id  BIGINT FOREIGN KEY
manager_id   BIGINT FOREIGN KEY
hired_at     TIMESTAMP
fired_at     TIMESTAMP NULLABLE
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

---

## Bookability Rules

### Current Implementation

**Model `isBookable()` method:**
```php
public function isBookable(): bool
{
    return $this->currentWrestlers->every(fn (Wrestler $wrestler) => $wrestler->isBookable());
}
```
Checks if all current wrestlers are bookable. Does **NOT** check team-level status.

**Builder `available()` scope:**
```php
public function available(): static
{
    return $this->whereEmployed()
                ->whereNotSuspended()
                ->whereNotRetired();
}
```
Checks team-level availability only.

**Builder `readyForBooking()` / `bookable()` scope:**
```php
public function readyForBooking(): static
{
    return $this->available()->withMinimumWrestlers();
}
```
Checks team availability + minimum wrestler count. Does **NOT** check individual wrestler bookability.

**Builder `withAvailableWrestlers()` scope:**
```php
public function withAvailableWrestlers(): static
{
    // Checks wrestlers are employed + not injured + not suspended + not retired
}
```

### Complete Bookability Check
For a tag team to be truly bookable for a match, ALL must be true:
1. Team is available (employed, not suspended, not retired)
2. Has minimum 2 current wrestlers
3. All current wrestlers are bookable

> **Note:** See tasks.md for consistency improvement task.

### Bookability Conditions
| Condition | Bookable? |
|-----------|-----------|
| Employed, not suspended, 2 bookable wrestlers | Yes |
| Employed but suspended | No |
| Employed but 1 wrestler injured | No |
| Employed but only 1 wrestler | No |
| Future employment (not started) | No |
| Released | No |
| Unemployed | No |
| Retired (team-level) | No |

### Key Difference from Wrestlers
Tag teams do **NOT** have injuries. A tag team's bookability depends on their individual wrestlers' availability. If one wrestler is injured, suspended, or unavailable, the tag team becomes unbookable.

---

## Query Builder Methods

### Employment Scopes

**Location:** `App\Builders\Roster\TagTeamBuilder`

| Method | Description |
|--------|-------------|
| `employed()` | Has current employment |
| `unemployed()` | Never employed |
| `released()` | Was employed, now without |
| `futureEmployed()` | Has future employment scheduled |

### Availability Scopes

| Method | Description |
|--------|-------------|
| `suspended()` | Currently suspended |
| `retired()` | Currently retired (team-level) |
| `available()` | Employed + not suspended + not retired |
| `unavailable()` | Unemployed OR suspended OR retired |

### Wrestler Scopes

| Method | Description |
|--------|-------------|
| `withAvailableWrestlers()` | All current wrestlers are available |
| `withMinimumWrestlers(int $count = 2)` | Has at least N current wrestlers |

### Booking Scopes

| Method | Description |
|--------|-------------|
| `readyForBooking()` | available() + withMinimumWrestlers() |
| `bookable()` | Alias for readyForBooking() |
| `unbookable()` | Alias for unavailable() |
| `availableOn(Carbon $date)` | Bookable AND not booked on date |
| `notBookedOn(Carbon $date)` | Not assigned to match on date |

---

## Lifecycle Validation

**Trait:** `App\Models\Concerns\ValidatesTagTeamLifecycle`

### Employment Validation
| Method | Checks |
|--------|--------|
| `canBeEmployed()` | Not employed, not retired, has current partners, no employment conflicts |
| `ensureCanBeEmployed()` | Throws `CannotBeEmployedException` |

### Release Validation
| Method | Checks |
|--------|--------|
| `canBeReleased()` | Currently employed |
| `ensureCanBeReleased()` | Throws `CannotBeReleasedException` |

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
| `canBeRetired()` | Not retired, currently employed |
| `ensureCanBeRetired()` | Throws `CannotBeRetiredException` |

### Unretirement Validation
| Method | Checks |
|--------|--------|
| `canBeUnretired()` | Currently retired, no name conflicts, has available partners, min 2 wrestlers |
| `ensureCanBeUnretired()` | Throws `CannotBeUnretiredException` |

### Deletion Validation
| Method | Checks |
|--------|--------|
| `canBeDeleted()` | Not employed, not suspended |
| `ensureCanBeDeleted()` | Throws `CannotBeDeletedException` |

### Restoration Validation
| Method | Checks |
|--------|--------|
| `canBeRestored()` | Is soft deleted, no name conflicts with active teams |
| `ensureCanBeRestored()` | Throws `CannotBeRestoredException` |

---

## Business Rules

### Partnership Rules
- Tag team requires minimum 2 wrestlers (`NUMBER_OF_WRESTLERS_ON_TEAM = 2`)
- Wrestlers join with `joined_at` timestamp
- Wrestlers leave with `left_at` timestamp
- A wrestler can only be in ONE tag team at a time (enforced on Wrestler)
- Partner changes are tracked historically

### Employment Rules
- Tag team employment is **independent** from individual wrestler employment
- Tag team must have current partners (wrestlers who have joined and not left) to be employed
- Employment uses temporal tracking (`started_at`/`ended_at`)
- Multiple employment periods allowed (re-hiring)
- Future employment blocks bookability until start date

### Availability Rules
- Suspensions and retirements use same temporal pattern
- Active state: `ended_at IS NULL`
- Multiple periods allowed (multiple suspensions, comebacks from retirement)
- Status is computed, never stored
- **No injuries** - tag teams don't get injured, wrestlers do

### Independent Retirement Model
Tag team retirement and individual wrestler retirement are **independent**:
- Tag team retires → team can't compete as a unit, but wrestlers could continue singles careers
- Wrestler retires → affects tag team bookability (wrestler not available), but team isn't "retired"
- If both wrestlers want to reunite after individual retirements → they must explicitly unretire the tag team

The `retired()` scope shows teams retired at the team level, not teams with retired wrestlers.

### Manager Relationship Rules

#### Relationship Direction
Tag teams **hire** managers. The `TagTeamManager` pivot represents "this tag team has hired this manager."

#### Employment Requirement
Both tag team AND manager must meet one of these conditions:
- Currently employed
- Has future employment scheduled

This allows tag teams and managers to be paired before debut.

#### Multiplicity
- A tag team can have multiple managers simultaneously
- A manager can manage multiple tag teams simultaneously

#### Stable Association
Managers are **indirectly** associated with stables through tag teams they manage. See Stables System spec for details.

### Stable Membership Rules
- Tag team can be in ONE stable at a time
- Track join/leave dates via `joined_at`/`left_at`
- Tag teams are historical annotation only—the **wrestlers** in the tag team are what count toward stable membership minimum
- See Stables System spec for member counting details

### Championship Rules
- Tag team titles only (singles titles go to Wrestler entity)
- Championship tracked via polymorphic MorphMany
- Current championship: `lost_at IS NULL`

---

## File Locations

```
app/
├── Enums/Shared/
│   └── EmploymentStatus.php
├── Models/TagTeams/
│   ├── TagTeam.php
│   ├── TagTeamWrestler.php
│   ├── TagTeamEmployment.php
│   ├── TagTeamSuspension.php
│   ├── TagTeamRetirement.php
│   └── TagTeamManager.php
├── Models/Concerns/
│   ├── ProvidesTagTeamWrestlers.php
│   ├── IsEmployable.php
│   ├── IsSuspendable.php
│   ├── IsRetirable.php
│   ├── CanBeManaged.php
│   ├── CanJoinStables.php
│   ├── CanWinTitles.php
│   ├── IsBookableCompetitor.php
│   └── ValidatesTagTeamLifecycle.php
├── Models/Contracts/
│   ├── HasTagTeamWrestlers.php
│   ├── CanBeATagTeamMember.php
│   └── TagTeamMember.php
└── Builders/Roster/
    └── TagTeamBuilder.php
```

### Builder Note
`TagTeamBuilder` is a standalone builder (does not extend a shared base like `IndividualRosterMemberBuilder`). Tag teams have unique query requirements (wrestler partnerships, combined availability checks) that differ from individual roster members.

---

## Comparison with Wrestlers System

| Feature | Wrestler | Tag Team |
|---------|----------|----------|
| Injuries | Yes (`IsInjurable`) | No |
| Suspensions | Yes | Yes |
| Retirements | Yes | Yes |
| Employment | Individual | Team-level |
| Bookability Check | Own availability | Team + all wrestlers' availability |
| Championships | Singles titles | Tag team titles |
| Manager Relationship | Direct | Direct |
| Stable Membership | Counted toward minimum | Historical annotation only |
