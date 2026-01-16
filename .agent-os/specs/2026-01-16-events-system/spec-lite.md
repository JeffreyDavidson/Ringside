# Events System - Quick Reference

> Organizational container for wrestling shows with venue management

## Core Entities

| Entity | Purpose |
|--------|---------|
| Event | Wrestling show/card container |
| Venue | Physical location for events |

## Event Attributes

| Field | Type | Description |
|-------|------|-------------|
| name | string | Event name |
| date | datetime\|null | Scheduled date (null = unscheduled) |
| venue_id | int\|null | Optional venue association |
| preview | string\|null | Promotional preview text |
| status | EventStatus | **Computed** from date |

## Venue Attributes

| Field | Type | Description |
|-------|------|-------------|
| name | string | Venue name |
| street_address | string | Street address |
| city | string | City |
| state | string | State |
| zipcode | string | ZIP code |

## Event Status (Computed)

| Status | Condition |
|--------|-----------|
| Unscheduled | date IS NULL |
| Scheduled | date IS NOT NULL AND date >= today |
| Past | date IS NOT NULL AND date < today |

## Key Relationships

```
Event
├── venue (BelongsTo → Venue)
└── matches (HasMany → EventMatch)

Venue
├── events (HasMany → Event)
├── previousEvents - date < today
└── futureEvents - date > today
```

## Query Scopes

**Event:**
- `scheduled()` - Has a date
- `unscheduled()` - No date
- `past()` - Date in the past
- `withFutureDate()` - Scheduled with future date
- `withPastDate()` - Scheduled with past date

**Venue:**
- `withEvents()` - Has hosted events
- `withPastEvents()` - Has past events
- `withFutureEvents()` - Has future events scheduled
- `withoutEvents()` - No events yet

## File Locations

- `app/Models/Events/Event.php`
- `app/Models/Events/Venue.php`
- `app/Enums/EventStatus.php`
- `app/Builders/Events/EventBuilder.php`
- `app/Builders/Events/VenueBuilder.php`
