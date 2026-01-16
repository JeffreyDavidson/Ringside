# Technical Specification

> Venues System
> Reference: @.agent-os/specs/2026-01-16-venues-system/spec.md

---

## Entity Reference

### 1. Venue

**Model:** `App\Models\Events\Venue`

Location entity representing where wrestling events are held.

#### Attributes
| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| name | string | Venue name |
| street_address | string | Street address |
| city | string | City |
| state | string | State |
| zipcode | string | ZIP code |

> **Note:** Address format currently supports US addresses only. International address support may be added in the future.

#### Key Relationships
```
Venue
├── events (HasMany → Event)
│   └── All events at this venue
├── previousEvents (HasMany → Event)
│   └── Events where date < today
└── futureEvents (HasMany → Event)
    └── Events where date > today
```

#### Traits
- `HoldsEvents` - Event relationship methods
- `SoftDeletes` - Soft deletion support

---

## Database Tables

### Schema: venues
```sql
id              BIGINT PRIMARY KEY
name            VARCHAR(255)
street_address  VARCHAR(255)
city            VARCHAR(255)
state           VARCHAR(255)
zipcode         VARCHAR(20)
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP NULLABLE
```

---

## Query Builder Methods

### Venue Scopes

**Location:** `App\Builders\Events\VenueBuilder`

| Method | Description |
|--------|-------------|
| `withEvents()` | Venues that have hosted any events |
| `withPastEvents()` | Venues with events in the past |
| `withFutureEvents()` | Venues with upcoming events |
| `withoutEvents()` | Venues with no event history |

#### Usage Examples
```php
// Find experienced venues
Venue::query()->withPastEvents()->get();

// Find venues with confirmed bookings
Venue::query()->withFutureEvents()->get();

// Find new/untapped venues
Venue::query()->withoutEvents()->get();

// Find venues with both past and upcoming events
Venue::query()->withPastEvents()->withFutureEvents()->get();
```

---

## Business Rules

### Venue-Event Association
- Venues can host multiple events
- Events optionally reference a venue (nullable)
- Deleting a venue does not delete associated events
- Venue can be changed on an event after creation

### No Status Computation
Unlike other entities, Venues have no computed status. They are simple location records.

---

## File Locations

```
app/
├── Models/Events/
│   └── Venue.php
├── Models/Concerns/
│   └── HoldsEvents.php
└── Builders/Events/
    └── VenueBuilder.php
```

---

## Future Considerations

These features are out of scope but may be added later:

| Feature | Description |
|---------|-------------|
| Capacity | Track venue capacity for event planning |
| Availability | Calendar-based availability tracking |
| Contracts | Venue booking/contract management |
| Costs | Venue rental costs per event |
