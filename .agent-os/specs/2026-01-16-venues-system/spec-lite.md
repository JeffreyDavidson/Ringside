# Venues System - Quick Reference

> Location management for wrestling events

## Core Entity

| Entity | Purpose |
|--------|---------|
| Venue | Location where events are held |

## Venue Attributes

| Field | Type | Description |
|-------|------|-------------|
| name | string | Venue name |
| street_address | string | Street address |
| city | string | City |
| state | string | State |
| zipcode | string | Postal code |

## Key Relationships

```
Venue
├── events (HasMany → Event)
├── previousEvents (past dates)
└── futureEvents (future dates)
```

## Query Scopes

| Scope | Description |
|-------|-------------|
| `withEvents()` | Venues with any events |
| `withPastEvents()` | Venues with past events |
| `withFutureEvents()` | Venues with upcoming events |
| `withoutEvents()` | Venues with no event history |

## File Locations

- `app/Models/Events/Venue.php`
- `app/Builders/Events/VenueBuilder.php`
- `app/Models/Concerns/HoldsEvents.php`
