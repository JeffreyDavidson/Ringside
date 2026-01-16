# Technical Specification

> Stables System
> Reference: @.agent-os/specs/2026-01-16-stables-system/spec.md

---

## Entity Reference

### 1. Stable

**Model:** `App\Models\Stables\Stable`

The core stable/faction entity representing a wrestling group.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Stable name |
| user_id | int\|null | Owner reference |
| status | StableStatus | **Computed** from activity periods |

#### Key Relationships
```
Stable
├── wrestlers (BelongsToMany → Wrestler)
│   ├── currentWrestlers - left_at IS NULL (PRIMARY MEMBERS)
│   └── previousWrestlers - left_at IS NOT NULL
├── tagTeams (BelongsToMany → TagTeam)
│   ├── currentTagTeams - left_at IS NULL (ANNOTATION ONLY)
│   └── previousTagTeams - left_at IS NOT NULL
│   └── Note: Tag teams don't count toward membership - historical record only
├── managers (computed via wrestlers/tagTeams)
│   └── Derived from managers of current wrestlers and tag teams
│   └── NOT direct stable members - associated through managed entities
├── activityPeriods (HasMany → StableActivityPeriod)
│   ├── currentActivityPeriod - ended_at IS NULL, started_at <= now
│   └── futureActivityPeriod - ended_at IS NULL, started_at > now
├── retirements (HasMany → StableRetirement)
│   └── currentRetirement - ended_at IS NULL
└── statusChanges (HasMany → StableStatusChange)
```

#### Interfaces
- `Debutable` - Can be debuted/established with activity periods

#### Traits
- `HasMembers` - Wrestler/tag team membership methods
- `HasActivityPeriods` - Activity period lifecycle
- `HasStatusHistory` - Status change audit trail
- `IsRetirable` - Retirement handling
- `ValidatesStableLifecycle` - Business rule validation

#### Trait Resolution
The Stable model uses both `HasActivityPeriods` and `HasStatusHistory` traits which have overlapping methods. PHP trait conflict resolution is used:

```php
use HasActivityPeriods, HasStatusHistory {
    HasActivityPeriods::recordStatusChange insteadof HasStatusHistory;
}
```

This ensures `HasActivityPeriods` methods take precedence for status change recording.

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isActive()` | bool | Status is Active |
| `isDisbanded()` | bool | Status is Inactive |
| `hasFutureEstablishment()` | bool | Has scheduled future activity |
| `hasCurrentMembers()` | bool | Has any current members |
| `getCurrentMemberCount()` | int | Total current member count |
| `getCurrentMembersData()` | StableMembershipData | DTO with all current members |
| `getEmployedMembersData()` | StableMembershipData | Only employed current members |

#### Constants
```php
public const int MIN_MEMBERS_COUNT = 3;
```

---

### 2. StableWrestler

**Model:** `App\Models\Stables\StableWrestler`

Pivot model tracking wrestler membership in a stable.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| stable_id | int | Foreign key to stable |
| wrestler_id | int | Foreign key to wrestler |
| joined_at | datetime | When wrestler joined |
| left_at | datetime\|null | When wrestler left (null = current) |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isActive()` | bool | left_at IS NULL |
| `hasEnded()` | bool | left_at IS NOT NULL |

---

### 3. StableTagTeam

**Model:** `App\Models\Stables\StableTagTeam`

Pivot model tracking tag team membership in a stable.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| stable_id | int | Foreign key to stable |
| tag_team_id | int | Foreign key to tag team |
| joined_at | datetime | When tag team joined |
| left_at | datetime\|null | When tag team left (null = current) |

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isActive()` | bool | left_at IS NULL |
| `hasEnded()` | bool | left_at IS NOT NULL |

---

### 4. StableActivityPeriod

**Model:** `App\Models\Stables\StableActivityPeriod`

Tracks when a stable is established (active).

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| stable_id | int | Foreign key to stable |
| started_at | datetime | Establishment date |
| ended_at | datetime\|null | Disbandment date |

---

### 5. StableRetirement

**Model:** `App\Models\Stables\StableRetirement`

Tracks permanent stable retirements.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| stable_id | int | Foreign key to stable |
| started_at | datetime | Retirement start date |
| ended_at | datetime\|null | Unretirement date (rare) |

---

## Enums

### StableStatus
```php
enum StableStatus: string {
    case Unformed = 'unformed';
    case PendingEstablishment = 'pending_establishment';
    case Active = 'active';
    case Inactive = 'inactive';
    case Retired = 'retired';
}
```

#### Status Computation
```
┌─────────────────────────────────────────────────────────────┐
│                   STABLE STATUS PRIORITY                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. RETIRED              (has active retirement)            │
│       ↓                                                     │
│  2. ACTIVE               (has current activity period)      │
│       ↓                                                     │
│  3. PENDING_ESTABLISHMENT (future activity scheduled)       │
│       ↓                                                     │
│  4. INACTIVE             (was active, now disbanded)        │
│       ↓                                                     │
│  5. UNFORMED             (never established)                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### StableMemberType
```php
enum StableMemberType: string {
    case WRESTLER = 'wrestlers';
    case TAG_TEAM = 'tagTeams';
}
```

> **Note:** No MANAGER case - managers are associated indirectly through wrestlers/tag teams.

| Method | Description |
|--------|-------------|
| `fromModel(Model $model)` | Auto-detect type from model class |
| `getRelationshipName()` | Get relationship property name |
| `getCurrentRelationshipName()` | Get current members method |

### StableMembershipAction
```php
enum StableMembershipAction: string {
    case ADD = 'add';
    case REMOVE = 'remove';
}
```

---

## Database Tables

### Stable Tables
| Table | Description |
|-------|-------------|
| `stables` | Core stable records |
| `stables_wrestlers` | Wrestler membership pivot |
| `stables_tag_teams` | Tag team membership pivot |
| `stables_activations` | Activity periods |
| `stables_retirements` | Retirement records |
| `stables_status_changes` | Audit trail |

> **Note:** No `stables_managers` table - managers are associated indirectly through wrestlers/tag teams they manage.

### Schema: stables
```sql
id          BIGINT PRIMARY KEY
name        VARCHAR(255)
user_id     BIGINT NULLABLE FOREIGN KEY
created_at  TIMESTAMP
updated_at  TIMESTAMP
deleted_at  TIMESTAMP NULLABLE
```

### Schema: stables_wrestlers
```sql
id          BIGINT PRIMARY KEY
stable_id   BIGINT FOREIGN KEY
wrestler_id BIGINT FOREIGN KEY
joined_at   TIMESTAMP
left_at     TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: stables_tag_teams
```sql
id          BIGINT PRIMARY KEY
stable_id   BIGINT FOREIGN KEY
tag_team_id BIGINT FOREIGN KEY
joined_at   TIMESTAMP
left_at     TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

## Member Counting

### Formula
```
Total Members = Wrestlers
```

**Wrestlers are the primary members.** Tag teams are tracked separately for historical context but do NOT count toward membership.

### Membership Model
```
NWO Example:
├── Wrestlers (counts toward minimum = 3)
│   ├── Hulk Hogan
│   ├── Kevin Nash
│   └── Scott Hall
└── Tag Teams (historical annotation, doesn't count)
    └── The Outsiders (Nash + Hall)
```

### SQL Implementation
```sql
SELECT COUNT(*) FROM stables_wrestlers
WHERE stable_id = stables.id AND left_at IS NULL
```

### Examples
| Wrestlers | Tag Teams | Member Count | Valid? |
|-----------|-----------|--------------|--------|
| 3 | 0 | 3 | Yes |
| 3 | 1 | 3 | Yes (tag team is annotation) |
| 2 | 1 | 2 | No (only wrestlers count) |
| 2 | 0 | 2 | No |

### Why Tag Teams Don't Count
- Avoids double-counting (Nash is 1 member, not 1 + part of tag team)
- Tag teams are groupings OF wrestlers who are already members
- Tag team entry answers: "Was The Outsiders part of NWO?" → Yes
- But the count is based on unique wrestlers only

---

## Query Builder Methods

### Status Scopes

**Location:** `App\Builders\Roster\StableBuilder`

| Method | Description |
|--------|-------------|
| `unestablished()` | Never had activity periods |
| `established()` | Has current activity period |
| `disbanded()` | Was active, no current activity |
| `withFutureEstablishment()` | Future activity scheduled |

### Member Scopes

| Method | Description |
|--------|-------------|
| `withMinimumMembers()` | Has 3+ members |
| `belowMinimumMembers()` | Has < 3 members |
| `withMemberCount(int $min, ?int $max)` | Custom member range |
| `withAvailableMembers()` | All members employed & healthy |
| `activelyManaged()` | Has available manager(s) |

### Availability Scopes

| Method | Description |
|--------|-------------|
| `available()` | Established + minimum members + not retired |
| `unavailable()` | Unestablished OR disbanded OR retired |
| `availableForReunion()` | Disbanded but not retired |

### Status Aliases
| Alias | Maps To |
|-------|---------|
| `active()` | `established()` |
| `inactive()` | `disbanded()` |
| `unactivated()` | `unestablished()` |
| `withFutureActivation()` | `withFutureEstablishment()` |

---

## Status Transitions

```
┌───────────────────────────────────────────────────────────────────────┐
│                      STABLE STATUS TRANSITIONS                         │
├───────────────────────────────────────────────────────────────────────┤
│                                                                       │
│   UNFORMED ───→ PENDING_ESTABLISHMENT ───→ ACTIVE ←───→ INACTIVE     │
│       │                                      │             │          │
│       └──────────────→ ACTIVE ───────────────┴─────────────┘          │
│                          │                                            │
│                          ▼                                            │
│                      RETIRED ←─────────────────────────────┘          │
│                          │                                            │
│                          ▼                                            │
│                   (unretire → ACTIVE)  [rare]                         │
│                                                                       │
└───────────────────────────────────────────────────────────────────────┘
```

| From | To | Action | Method |
|------|----|--------|--------|
| Unformed | PendingEstablishment | Schedule future establishment | `scheduleActivation()` |
| Unformed | Active | Establish immediately | `activate()` |
| PendingEstablishment | Active | Establishment date arrives | Automatic |
| Active | Inactive | Disband stable | `deactivate()` |
| Inactive | Active | Reunite stable | `activate()` |
| Active | Retired | Retire stable | `retire()` |
| Inactive | Retired | Retire stable | `retire()` |
| Retired | Active | Unretire (rare) | `unretire()` |

**Blocked Transitions:**
- Unformed → Inactive (must establish first)
- Unformed → Retired (must have been active)
- PendingEstablishment → Retired (must establish first)

---

## Business Rules

### Membership Rules
- **Wrestlers are the primary members** - only wrestlers count toward minimum
- Members tracked with join/leave dates (`joined_at`/`left_at`)
- Members can rejoin after leaving (multiple memberships)
- **Tag teams are historical annotation** - tracked but don't count toward membership
- Managers are NOT direct members - associated through wrestlers/tag teams they manage

### Establishment Rules
- Minimum 3 members required to establish
- Members don't need to be employed to count
- Stable can exist (Unformed) without minimum members

### Disbandment vs Retirement

| State | Meaning | Members | Can Reunite? |
|-------|---------|---------|--------------|
| **Disbanded** | Split up, on hiatus | Remain as current members | Yes |
| **Retired** | Permanently ended | Remain as current members | Rarely (unretire) |

When a stable disbands or retires:
- Members are **NOT** automatically removed
- Members remain as "current members" of the disbanded/retired stable
- This preserves the roster for potential reunion
- Members must be explicitly removed if they leave the faction

### Availability Rules
A stable is **available** when:
- Currently established (Active status)
- Has minimum members (3+)
- Not retired

---

## File Locations

```
app/
├── Enums/Stables/
│   ├── StableStatus.php
│   ├── StableMemberType.php
│   └── StableMembershipAction.php
├── Models/Stables/
│   ├── Stable.php
│   ├── StableWrestler.php
│   ├── StableTagTeam.php
│   ├── StableActivityPeriod.php
│   ├── StableRetirement.php
│   └── StableStatusChange.php
├── Models/Concerns/
│   ├── HasMembers.php
│   ├── HasActivityPeriods.php
│   └── IsRetirable.php
└── Builders/Roster/
    └── StableBuilder.php
```
