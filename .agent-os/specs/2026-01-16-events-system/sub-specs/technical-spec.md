# Technical Specification

> Events System
> Reference: @.agent-os/specs/2026-01-16-events-system/spec.md

---

## Entity Reference

### 1. Event

**Model:** `App\Models\Events\Event`

The core entity representing a wrestling show or card.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Event name |
| date | datetime\|null | Scheduled date (null = unscheduled) |
| venue_id | int\|null | Foreign key to venue |
| preview | string\|null | Promotional preview text |
| status | EventStatus | **Computed** from date |

#### Key Relationships
```
Event
├── venue (BelongsTo → Venue)
└── matches (HasMany → EventMatch)
```

#### Traits
- `HasMatches` - Match collection relationship
- `HasFactory` - Factory support
- `SoftDeletes` - Soft deletion

#### Key Methods
| Method | Return | Description |
|--------|--------|-------------|
| `isScheduled()` | bool | Has a date assigned |
| `isUnscheduled()` | bool | No date assigned |
| `hasFutureDate()` | bool | Date is in the future |
| `hasPastDate()` | bool | Date is in the past |

#### Status Computation

Status is a **computed attribute**, never stored:

```php
protected function status(): Attribute
{
    return Attribute::make(
        get: function (): EventStatus {
            if ($this->isUnscheduled()) {
                return EventStatus::Unscheduled;
            }
            return $this->hasPastDate() ? EventStatus::Past : EventStatus::Scheduled;
        }
    );
}
```

```
┌─────────────────────────────────────────────────────────────┐
│                   EVENT STATUS LOGIC                         │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. Is date NULL?                                           │
│     YES → UNSCHEDULED                                       │
│     NO  → Continue                                          │
│                                                             │
│  2. Is date in the past?                                    │
│     YES → PAST                                              │
│     NO  → SCHEDULED                                         │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

### 2. Venue

**Model:** `App\Models\Events\Venue`

Physical location where events are held.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Venue name |
| street_address | string | Street address |
| city | string | City |
| state | string | State |
| zipcode | string | ZIP code |

#### Key Relationships
```
Venue
├── events (HasMany → Event)
├── previousEvents (HasMany → Event where date < today)
└── futureEvents (HasMany → Event where date > today)
```

#### Traits
- `HoldsEvents` - Event collection relationships
- `HasFactory` - Factory support
- `SoftDeletes` - Soft deletion

---

## Enums

### EventStatus

**Location:** `App\Enums\EventStatus`

Represents the scheduling state of an event.

```php
enum EventStatus: string {
    case Past = 'past';
    case Scheduled = 'scheduled';
    case Unscheduled = 'unscheduled';
}
```

#### Methods
| Method | Return | Description |
|--------|--------|-------------|
| `color()` | string | UI color (dark, success, danger) |
| `label()` | string | Human-readable label |

#### Status Colors
| Status | Color | Usage |
|--------|-------|-------|
| Past | dark | Historical events |
| Scheduled | success | Upcoming events |
| Unscheduled | danger | Events needing scheduling |

---

## Traits

### HasMatches

**Location:** `App\Models\Concerns\HasMatches`

Provides match relationships for the Event model.

```php
public function matches(): HasMany
{
    return $this->hasMany(EventMatch::class);
}
```

**Used by:** Event model

---

### HoldsEvents

**Location:** `App\Models\Concerns\HoldsEvents`

Provides event relationships for models that host events.

```php
public function events(): HasMany
{
    return $this->hasMany(Event::class);
}

public function previousEvents(): HasMany
{
    return $this->events()->where('date', '<', today());
}

public function futureEvents(): HasMany
{
    return $this->events()->where('date', '>', today());
}
```

**Used by:** Venue model

---

## Database Tables

### Schema: events
```sql
id          BIGINT PRIMARY KEY
name        VARCHAR(255)
date        DATETIME NULLABLE
venue_id    BIGINT NULLABLE FOREIGN KEY
preview     TEXT NULLABLE
created_at  TIMESTAMP
updated_at  TIMESTAMP
deleted_at  TIMESTAMP NULLABLE
```

### Schema: venues
```sql
id             BIGINT PRIMARY KEY
name           VARCHAR(255)
street_address VARCHAR(255)
city           VARCHAR(255)
state          VARCHAR(255)
zipcode        VARCHAR(255)
created_at     TIMESTAMP
updated_at     TIMESTAMP
deleted_at     TIMESTAMP NULLABLE
```

---

## Query Builder Methods

### EventBuilder

**Location:** `App\Builders\Events\EventBuilder`

Custom query builder for Event model.

| Method | Description |
|--------|-------------|
| `scheduled()` | Events with a date assigned |
| `unscheduled()` | Events without a date |
| `past()` | Events with date in the past (no explicit null check) |
| `withFutureDate()` | Events with date in the future (explicit null check) |
| `withPastDate()` | Events with date in the past (explicit null check) |

#### Scope Behavior Note

The `past()` scope and `withPastDate()` scope have subtle differences:

| Scope | NULL Check | Use Case |
|-------|------------|----------|
| `past()` | No | Quick filter, assumes scheduled events |
| `withPastDate()` | Yes | Defensive, explicitly excludes unscheduled |

In practice, both will exclude NULL dates (database comparison with NULL returns NULL, not true), but `withPastDate()` is more explicit and self-documenting.

#### Implementation Details

```php
// scheduled() - has any date
$this->whereNotNull('date');

// unscheduled() - no date
$this->whereNull('date');

// past() - date before today
$this->where('date', '<', now()->toDateString());

// withFutureDate() - date today or later
$this->whereNotNull('date')->where('date', '>=', now()->toDateString());

// withPastDate() - date before today (with null check)
$this->whereNotNull('date')->where('date', '<', now()->toDateString());
```

---

### VenueBuilder

**Location:** `App\Builders\Events\VenueBuilder`

Custom query builder for Venue model.

| Method | Description |
|--------|-------------|
| `withEvents()` | Venues that have hosted any events |
| `withPastEvents()` | Venues with past event history |
| `withFutureEvents()` | Venues with upcoming events |
| `withoutEvents()` | Venues with no event history |

---

## Actions

### CreateAction

**Location:** `App\Actions\Events\CreateAction`

Creates a new event.

```php
public function handle(EventData $eventData): Event
```

**Workflow:**
1. Wrap in database transaction
2. Create event with name, date, venue_id, preview
3. Return created event

---

### UpdateAction

**Location:** `App\Actions\Events\UpdateAction`

Updates an existing event.

```php
public function handle(Event $event, EventData $eventData): Event
```

**Workflow:**
1. Wrap in database transaction
2. Update event attributes
3. Return updated event

---

### DeleteAction

**Location:** `App\Actions\Events\DeleteAction`

Soft-deletes an event.

```php
public function handle(Event $event, ?Carbon $deletionDate = null): void
```

**Impact:**
- Soft deletes the event record
- Preserves match history for reporting
- Does not affect venue availability
- Allows future restoration

---

### RestoreAction

**Location:** `App\Actions\Events\RestoreAction`

Restores a soft-deleted event.

```php
public function handle(Event $event): Event
```

---

## Venue Actions

### Venues\CreateAction

**Location:** `App\Actions\Venues\CreateAction`

Creates a new venue.

```php
public function handle(VenueData $venueData): Venue
```

**Workflow:**
1. Create venue with name, street_address, city, state, zipcode
2. Return created venue

---

### Venues\UpdateAction

**Location:** `App\Actions\Venues\UpdateAction`

Updates an existing venue.

```php
public function handle(Venue $venue, VenueData $venueData): Venue
```

---

### Venues\DeleteAction

**Location:** `App\Actions\Venues\DeleteAction`

Soft-deletes a venue.

```php
public function handle(Venue $venue): void
```

**Impact:**
- Soft deletes the venue record
- Does not affect associated events (venue_id preserved)
- Allows future restoration

---

### Venues\RestoreAction

**Location:** `App\Actions\Venues\RestoreAction`

Restores a soft-deleted venue.

```php
public function handle(Venue $venue): Venue
```

---

## Business Rules

### Event Scheduling Rules

- Events can be created without a date (unscheduled)
- Events can be scheduled by assigning a date
- Status is computed automatically based on date
- Past events cannot be modified to future dates (recommended validation)

### Venue Assignment Rules

- Events can exist without a venue
- One venue can host many events
- Venue assignment does not affect event status
- Deleting a venue does not cascade to events (venue_id becomes null via soft delete)

### Match Booking Rules

- Matches can only be added to events (see Match System spec)
- Events provide the date context for match booking
- Competitor double-booking is checked against the event date

### Event Deletion Rules

- Events use soft deletion
- Deleted events preserve match history
- Events can be restored after deletion
- Venue relationships are preserved in history

---

## File Locations

```
app/
├── Enums/
│   └── EventStatus.php
├── Models/Events/
│   ├── Event.php
│   └── Venue.php
├── Models/Concerns/
│   ├── HasMatches.php              # Used by Event model
│   └── HoldsEvents.php             # Used by Venue model
├── Builders/Events/
│   ├── EventBuilder.php
│   └── VenueBuilder.php
├── Actions/Events/
│   ├── CreateAction.php
│   ├── UpdateAction.php
│   ├── DeleteAction.php
│   └── RestoreAction.php
├── Actions/Venues/
│   ├── CreateAction.php
│   ├── UpdateAction.php
│   ├── DeleteAction.php
│   └── RestoreAction.php
└── Data/Events/
    ├── EventData.php
    └── VenueData.php
```

---

## Related Systems

| System | Relationship |
|--------|--------------|
| Match System | Events contain matches |
| Wrestlers | Compete in matches within events |
| Tag Teams | Compete in matches within events |
| Referees | Officiate matches within events |
| Titles | Championships at stake in event matches |

---

## Terminology Note

### Past vs Completed

The Events System uses `Past` for the EventStatus enum value. Other systems (Wrestlers, Referees) have tasks to rename `previousMatches` to `completedMatches` for consistency. Consider whether EventStatus should also be renamed:

| Current | Potential |
|---------|-----------|
| `EventStatus::Past` | `EventStatus::Completed` |

This is documented as a consideration, not a required change. The current naming is clear and functional.
