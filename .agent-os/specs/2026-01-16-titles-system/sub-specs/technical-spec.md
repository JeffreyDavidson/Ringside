# Technical Specification

> Titles System
> Reference: @.agent-os/specs/2026-01-16-titles-system/spec.md

---

## Entity Reference

### 1. Title

**Model:** `App\Models\Titles\Title`

The core entity representing a wrestling championship.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Title name |
| type | TitleType | Singles or TagTeam |
| status | TitleStatus | **Computed** from activity periods |

#### Key Relationships
```
Title
├── activityPeriods (HasMany → TitleActivityPeriod)
│   ├── currentActivityPeriod - ended_at IS NULL AND started_at <= now
│   ├── futureActivityPeriod - ended_at IS NULL AND started_at > now
│   ├── previousActivityPeriods - ended_at IS NOT NULL
│   ├── previousActivityPeriod - most recent ended
│   └── firstActivityPeriod - earliest by started_at
├── championships (HasMany → TitleChampionship)
│   └── currentChampionship - lost_at IS NULL
├── retirements (HasMany → TitleRetirement)
│   ├── currentRetirement - ended_at IS NULL
│   └── previousRetirements - ended_at IS NOT NULL
└── statusChanges (HasMany → TitleStatusChange)
    ├── debutStatusChange - first status change
    └── latestStatusChange - most recent
```

#### Interfaces
- `Debutable` - Can be debuted
- `HasActivityPeriods` - Activity period tracking
- `HasDisplayName` - Display name support
- `Retirable` - Retirement tracking

#### Traits
- `HasActivityPeriods` - Activity period relationships and status checks
- `HasChampionships` - Championship reign relationships
- `HasStatusHistory` - Status change tracking
- `HasStatusScopes` - Query scopes for activity-based filtering (on builder)
- `IsRetirable` - Retirement relationships
- `ProvidesDisplayName` - Display name (returns `name`)
- `ValidatesRetirement` - Retirement validation
- `ValidatesTitleLifecycle` - Debut/reinstate/pull validation
- `SoftDeletes` - Soft deletion

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isCurrentlyActive()` | bool | Has current activity period |
| `hasFutureActivity()` | bool | Has scheduled future debut |
| `hasActivityPeriods()` | bool | Has any activity periods |
| `isInactive()` | bool | Not currently active |
| `isUnactivated()` | bool | Never debuted |
| `isRetired()` | bool | Currently retired |
| `isSinglesTitle()` | bool | Type is Singles |
| `isTagTeamTitle()` | bool | Type is TagTeam |
| `isVacant()` | bool | No current champion |
| `canBeDebuted()` | bool | Can be debuted (new, not retired) |
| `canBeReinstated()` | bool | Can be reinstated (inactive, not retired) |
| `canBePulled()` | bool | Can be pulled (active, not retired) |
| `ensureCanBeDebuted()` | void | Throws if cannot be debuted |
| `ensureCanBeReinstated()` | void | Throws if cannot be reinstated |
| `ensureCanBePulled()` | void | Throws if cannot be pulled |

#### Status Computation

Status is a **computed attribute**, never stored:

```php
protected function status(): Attribute
{
    return Attribute::make(
        get: function (): TitleStatus {
            if ($this->isCurrentlyActive()) {
                return TitleStatus::Active;
            }
            if ($this->hasFutureActivity()) {
                return TitleStatus::PendingDebut;
            }
            if ($this->hasActivityPeriods()) {
                return TitleStatus::Inactive;
            }
            return TitleStatus::New;
        }
    );
}
```

```
┌─────────────────────────────────────────────────────────────┐
│                   TITLE STATUS LOGIC                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Has current activity period?                            │
│     YES → ACTIVE                                            │
│     NO  → Continue                                          │
│                                                             │
│  2. Has future activity period?                             │
│     YES → PENDING_DEBUT                                     │
│     NO  → Continue                                          │
│                                                             │
│  3. Has any activity periods?                               │
│     YES → INACTIVE                                          │
│     NO  → NEW                                               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 2. TitleChampionship

**Model:** `App\Models\Titles\TitleChampionship`

Tracks championship reigns with polymorphic champion (Wrestler or TagTeam).

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| title_id | int | Foreign key to title |
| champion_type | string | Polymorphic type |
| champion_id | int | Polymorphic ID |
| won_at | datetime | Date reign started |
| lost_at | datetime\|null | Date reign ended (null = current) |
| won_match_id | int\|null | Match where title was won |
| lost_match_id | int\|null | Match where title was lost |

#### Key Relationships
```
TitleChampionship
├── title (BelongsTo → Title)
├── champion (MorphTo → Wrestler|TagTeam)
├── wonEventMatch (BelongsTo → EventMatch)
└── lostEventMatch (BelongsTo → EventMatch)
```

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `lengthInDays()` | int | Reign length in days |

---

### 3. TitleActivityPeriod

**Model:** `App\Models\Titles\TitleActivityPeriod`

Tracks active/inactive periods for a title.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| title_id | int | Foreign key to title |
| started_at | datetime | Period start date |
| ended_at | datetime\|null | Period end date (null = current) |

#### Table Name
`titles_activations`

#### Activity Period States
| State | Condition |
|-------|-----------|
| Current | `started_at <= now AND ended_at IS NULL` |
| Future | `started_at > now AND ended_at IS NULL` |
| Ended | `ended_at IS NOT NULL` |

---

### 4. TitleRetirement

**Model:** `App\Models\Titles\TitleRetirement`

Tracks retirement periods for a title.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| title_id | int | Foreign key to title |
| started_at | datetime | Retirement start date |
| ended_at | datetime\|null | Unretirement date (null = current) |

#### Table Name
`titles_retirements`

---

### 5. TitleStatusChange

**Model:** `App\Models\Titles\TitleStatusChange`

Tracks status changes for historical reference.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| title_id | int | Foreign key to title |
| status | ActivationStatus | Status at time of change |
| changed_at | datetime | When status changed |

#### Table Name
`titles_status_changes`

---

## Enums

### TitleStatus

**Location:** `App\Enums\Titles\TitleStatus`

Represents the computed status of a title.

```php
enum TitleStatus: string {
    case New = 'new';                      // Title created, never debuted
    case PendingDebut = 'pending_debut';   // Scheduled to debut in the future
    case Active = 'active';                // Currently active and defendable
    case Inactive = 'inactive';            // Temporarily out of circulation
}
```

#### Methods
| Method | Return | Description |
|--------|--------|-------------|
| `color()` | string | UI color classes |
| `label()` | string | Human-readable label |

#### Status Colors
| Status | Color | Usage |
|--------|-------|-------|
| New | gray | Titles never debuted |
| PendingDebut | blue | Scheduled debuts |
| Active | green | Active championships |
| Inactive | yellow | Pulled/shelved titles |

---

### TitleType

**Location:** `App\Enums\Titles\TitleType`

Determines the champion entity type.

```php
enum TitleType: string {
    case Singles = 'singles';
    case TagTeam = 'tag-team';
}
```

| Type | Champion Entity |
|------|-----------------|
| Singles | Wrestler |
| TagTeam | TagTeam |

---

## Traits

### HasActivityPeriods

**Location:** `App\Models\Concerns\HasActivityPeriods`

Provides activity period tracking for the Title model.

#### Relationships
| Method | Return | Description |
|--------|--------|-------------|
| `activityPeriods()` | HasMany | All activity periods |
| `activations()` | HasMany | Alias for activityPeriods |
| `currentActivityPeriod()` | HasOne | Current active period |
| `futureActivityPeriod()` | HasOne | Scheduled future period |
| `previousActivityPeriods()` | HasMany | All ended periods |
| `previousActivityPeriod()` | HasOne | Most recently ended |
| `firstActivityPeriod()` | HasOne | Earliest period |

#### Status Checks
| Method | Return | Description |
|--------|--------|-------------|
| `hasActivityPeriods()` | bool | Has any periods |
| `isCurrentlyActive()` | bool | Has current period |
| `hasFutureActivity()` | bool | Has future period |
| `isNotCurrentlyActive()` | bool | Not active |
| `isUnactivated()` | bool | No periods |
| `isInactive()` | bool | Not currently active |

**Used by:** Title model

---

### HasChampionships

**Location:** `App\Models\Concerns\HasChampionships`

Provides championship reign relationships for the Title model.

#### Relationships
| Method | Return | Description |
|--------|--------|-------------|
| `championships()` | HasMany | All championship reigns |
| `currentChampionship()` | HasOne | Active reign (lost_at IS NULL) |

#### Methods
| Method | Return | Description |
|--------|--------|-------------|
| `getCurrentChampionship()` | TitleChampionship\|null | Active championship |
| `currentChampion()` | Model\|null | Current champion entity |
| `previousChampionship()` | TitleChampionship\|null | Most recent ended reign |
| `previousChampion()` | Model\|null | Previous champion entity |
| `firstChampionship()` | TitleChampionship\|null | First reign ever |
| `firstChampion()` | Model\|null | First champion ever |
| `longestChampionship()` | TitleChampionship\|null | Longest reign |
| `longestChampion()` | Model\|null | Longest reigning champion |
| `reignCount()` | int | Total number of reigns |
| `isVacant()` | bool | No current champion |

**Used by:** Title model

---

### HasStatusScopes

**Location:** `App\Builders\Concerns\HasStatusScopes`

Provides query scopes for activity period-based filtering. Used by TitleBuilder.

#### Scopes
| Method | Description |
|--------|-------------|
| `currentlyActive()` | Models with current activity period |
| `currentlyInactive()` | Models without current activity period |
| `activeDuring(Carbon $start, Carbon $end)` | Active during date range |
| `activatedAfter(Carbon $date)` | Activated after specified date |
| `activatedBefore(Carbon $date)` | Activated before specified date |
| `deactivatedAfter(Carbon $date)` | Deactivated after specified date |
| `neverActivated()` | No activity periods |
| `withMultiplePeriods(int $min = 2)` | Has multiple activity periods |

**Used by:** TitleBuilder (also used by StableBuilder)

---

### ValidatesTitleLifecycle

**Location:** `App\Models\Concerns\ValidatesTitleLifecycle`

Provides title lifecycle validation for debut, reinstate, and pull operations.

#### Validation Methods
| Method | Return | Throws | Description |
|--------|--------|--------|-------------|
| `canBeDebuted()` | bool | - | Check if can be debuted |
| `ensureCanBeDebuted()` | void | `CannotBeDebutedException` | Throws if cannot debut |
| `canBeReinstated()` | bool | - | Check if can be reinstated |
| `ensureCanBeReinstated()` | void | `CannotBeReinstatedException` | Throws if cannot reinstate |
| `canBePulled()` | bool | - | Check if can be pulled |
| `ensureCanBePulled()` | void | `CannotBePulledException` | Throws if cannot pull |

**Used by:** Title model

---

## Database Tables

### Schema: titles
```sql
id          BIGINT PRIMARY KEY
name        VARCHAR(255)
type        VARCHAR(255) -- 'singles' or 'tag-team'
created_at  TIMESTAMP
updated_at  TIMESTAMP
deleted_at  TIMESTAMP NULLABLE
```

### Schema: titles_championships
```sql
id              BIGINT PRIMARY KEY
title_id        BIGINT FOREIGN KEY
champion_type   VARCHAR(255) -- polymorphic type
champion_id     BIGINT       -- polymorphic ID
won_at          TIMESTAMP
lost_at         TIMESTAMP NULLABLE
won_match_id    BIGINT NULLABLE FOREIGN KEY
lost_match_id   BIGINT NULLABLE FOREIGN KEY
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### Schema: titles_activations
```sql
id          BIGINT PRIMARY KEY
title_id    BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: titles_retirements
```sql
id          BIGINT PRIMARY KEY
title_id    BIGINT FOREIGN KEY
started_at  TIMESTAMP
ended_at    TIMESTAMP NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

### Schema: titles_status_changes
```sql
id          BIGINT PRIMARY KEY
title_id    BIGINT FOREIGN KEY
status      VARCHAR(255) -- ActivationStatus enum value
changed_at  TIMESTAMP
created_at  TIMESTAMP
updated_at  TIMESTAMP
```

---

## Query Builder Methods

### TitleBuilder

**Location:** `App\Builders\Titles\TitleBuilder`

Custom query builder for Title model.

#### Activity Scopes
| Method | Description |
|--------|-------------|
| `neverDebuted()` | Titles with no activity periods |
| `active()` | Titles with current activity period |
| `inactive()` | Titles with previous but no current period |
| `withPendingDebut()` | Titles with future activity period |

#### Availability Scopes
| Method | Description |
|--------|-------------|
| `available()` | Alias for `active()` |
| `unavailable()` | Inactive, never debuted, or retired |
| `competable()` | Alias for `active()` |

#### Retirement Scopes
| Method | Description |
|--------|-------------|
| `retired()` | Currently retired |
| `unretired()` | Not currently retired |

#### Championship Scopes
| Method | Description |
|--------|-------------|
| `vacant()` | Active but no current champion |
| `defended()` | Has championship history |
| `newTitles()` | No championship history |

#### Activity Period Scopes (from HasStatusScopes)
| Method | Description |
|--------|-------------|
| `currentlyActive()` | Has current activity period |
| `currentlyInactive()` | No current activity period |
| `activeDuring(Carbon $start, Carbon $end)` | Active during date range |
| `activatedAfter(Carbon $date)` | Activated after date |
| `activatedBefore(Carbon $date)` | Activated before date |
| `deactivatedAfter(Carbon $date)` | Deactivated after date |
| `neverActivated()` | No activity periods |
| `withMultiplePeriods(int $min)` | Has multiple activity periods |

---

### TitleChampionshipBuilder

**Location:** `App\Builders\Titles\TitleChampionshipBuilder`

Custom query builder for TitleChampionship model.

| Method | Description |
|--------|-------------|
| `current()` | Active reigns (lost_at IS NULL) |
| `previous()` | Ended reigns (lost_at IS NOT NULL) |
| `latestWon()` | Ordered by won_at DESC |
| `latestLost()` | Ordered by lost_at DESC |
| `withReignLength()` | Select with computed reign length |

---

## Actions

### CreateAction

**Location:** `App\Actions\Titles\CreateAction`

Creates a new title.

```php
public function handle(TitleData $titleData): Title
```

**Workflow:**
1. Wrap in database transaction
2. Create title with name and type
3. If debut_date provided, create activity period
4. Return created title

---

### DebutAction

**Location:** `App\Actions\Titles\DebutAction`

Debuts a title (first activation for new titles).

```php
public function handle(Title $title, ?Carbon $debutDate = null, ?string $notes = null): void
```

**Workflow:**
1. Validate title can be debuted (`ensureCanBeDebuted`)
2. Create activity period with debut date
3. Title status becomes Active

**Throws:** `CannotBeDebutedException`

---

### PullAction

**Location:** `App\Actions\Titles\PullAction`

Temporarily deactivates an active title.

```php
public function handle(Title $title, ?Carbon $pullDate = null, ?string $notes = null): void
```

**Workflow:**
1. Validate title is currently active
2. End current activity period (set ended_at)
3. Title status becomes Inactive

**Throws:** `CannotBePulledException`

---

### ReinstateAction

**Location:** `App\Actions\Titles\ReinstateAction`

Reactivates an inactive title.

```php
public function handle(Title $title, ?Carbon $reinstateDate = null, ?string $notes = null): void
```

**Workflow:**
1. Validate title can be reinstated (`ensureCanBeReinstated`)
2. Create new activity period
3. Title status becomes Active

**Throws:** `CannotBeReinstatedException`

---

### RetireAction

**Location:** `App\Actions\Titles\RetireAction`

Permanently retires a title.

```php
public function handle(Title $title, ?Carbon $retirementDate = null): void
```

**Workflow:**
1. Validate title can be retired (`ensureCanBeRetired`)
2. If active, end current activity period
3. End current championship reign if any
4. Create retirement record

**Impact:**
- Ends any active championship reign
- Creates retirement record

**Throws:** `CannotBeRetiredException`

---

### UnretireAction

**Location:** `App\Actions\Titles\UnretireAction`

Brings back a retired title.

```php
public function handle(Title $title, ?Carbon $unretiredDate = null): void
```

**Workflow:**
1. Validate title can be unretired (`ensureCanBeUnretired`)
2. End current retirement (set ended_at)
3. Title is ready to be activated (requires separate reinstate)

**Throws:** `CannotBeUnretiredException`

---

### ActivateAction

**Location:** `App\Actions\Titles\ActivateAction`

Smart activation that determines appropriate action.

```php
public function handle(Title $title, ?Carbon $activationDate = null): void
```

**Workflow:**
1. If retired → unretire first
2. If has activity periods → reinstate
3. If never debuted → debut

---

### DeactivateAction

**Location:** `App\Actions\Titles\DeactivateAction`

Alias for PullAction (backward compatibility).

```php
public function handle(Title $title, ?Carbon $deactivationDate = null): void
```

---

## Business Rules

### Title Lifecycle Rules

- Titles can be created without a debut date (status: New)
- Titles can be scheduled for future debut (status: PendingDebut)
- Active titles can be pulled to Inactive
- Inactive titles can be reinstated to Active
- Titles can be retired from any state
- Retired titles can be unretired

### Championship Rules

- Singles titles can only be held by Wrestlers
- Tag Team titles can only be held by TagTeams
- Only one current championship per title
- Championship reigns tracked with win/loss matches
- Retiring a title ends any current championship

### Activity Period Rules

- Activity periods use temporal tracking (started_at/ended_at)
- Multiple periods allowed (pull/reinstate cycles)
- Current period: started_at <= now AND ended_at IS NULL
- Future period: started_at > now AND ended_at IS NULL
- Status is computed from activity periods, never stored

### Retirement Rules

- Retirement is separate from activity state
- Multiple retirements allowed (retire/unretire cycles)
- Current retirement: ended_at IS NULL
- Retiring ends current activity and championship

---

## File Locations

```
app/
├── Enums/Titles/
│   ├── TitleStatus.php
│   └── TitleType.php
├── Models/Titles/
│   ├── Title.php
│   ├── TitleChampionship.php
│   ├── TitleActivityPeriod.php
│   ├── TitleRetirement.php
│   └── TitleStatusChange.php
├── Models/Concerns/
│   ├── HasActivityPeriods.php
│   ├── HasChampionships.php
│   ├── HasStatusHistory.php
│   ├── IsRetirable.php
│   └── ValidatesTitleLifecycle.php
├── Builders/Titles/
│   ├── TitleBuilder.php
│   └── TitleChampionshipBuilder.php
├── Actions/Titles/
│   ├── CreateAction.php
│   ├── UpdateAction.php
│   ├── DeleteAction.php
│   ├── RestoreAction.php
│   ├── DebutAction.php
│   ├── PullAction.php
│   ├── ReinstateAction.php
│   ├── RetireAction.php
│   ├── UnretireAction.php
│   ├── ActivateAction.php
│   └── DeactivateAction.php
├── Data/Titles/
│   └── TitleData.php
└── Exceptions/Titles/
    ├── CannotBeDebutedException.php
    ├── CannotBePulledException.php
    ├── CannotBeReinstatedException.php
    ├── CannotBeRetiredException.php
    └── CannotBeUnretiredException.php
```

---

## Competitor-Side Relationships

### CanWinTitles Trait

**Location:** `App\Models\Concerns\CanWinTitles`

**Used by:** `Wrestler`, `TagTeam`

Provides championship relationships from the competitor perspective.

#### Methods
| Method | Return | Description |
|--------|--------|-------------|
| `titleChampionships()` | MorphMany | All championship reigns |
| `currentChampionships()` | MorphMany | Active reigns (lost_at IS NULL) |
| `currentChampionship()` | MorphOne | Most recent active reign |
| `previousTitleChampionships()` | MorphMany | Past reigns (lost_at IS NOT NULL) |
| `isChampion()` | bool | Currently holds any title |

#### Usage Examples
```php
// Get all titles ever held
$wrestler->titleChampionships;

// Get current titles
$wrestler->currentChampionships;

// Check if currently a champion
if ($wrestler->isChampion()) {
    // ...
}

// Tag team championships
$tagTeam->titleChampionships;
```

---

## Related Systems

| System | Relationship |
|--------|--------------|
| Match System | Title matches, championship changes |
| Wrestlers System | Singles title champions |
| Tag Teams System | Tag team title champions |
| Events System | Title matches at events |

---

## Lifecycle State Diagram

```
                    ┌─────────────┐
                    │    NEW      │
                    │ (no debut)  │
                    └──────┬──────┘
                           │ debut
                           ▼
    ┌──────────────────────────────────────────┐
    │                                          │
    │  ┌─────────────┐      ┌─────────────┐   │
    │  │   ACTIVE    │◄────►│  INACTIVE   │   │
    │  │ (current    │ pull │ (previous   │   │
    │  │  period)    │      │  periods)   │   │
    │  └──────┬──────┘reinst└──────┬──────┘   │
    │         │                    │          │
    └─────────┼────────────────────┼──────────┘
              │                    │
              │ retire             │ retire
              ▼                    ▼
         ┌────────────────────────────┐
         │         RETIRED            │
         │  (retirement record)       │
         └────────────────────────────┘
                    ▲
                    │ unretire
                    │ (goes to Inactive, needs reinstate)
```
