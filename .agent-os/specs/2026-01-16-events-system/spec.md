# Spec Requirements Document

> Spec: Events System Feature Documentation
> Created: 2026-01-16

## Overview

Document the Events System—the organizational container for wrestling shows. Events represent scheduled (or planned) shows that contain matches. Each event has a name, optional date, optional venue, and a collection of matches. This spec serves as the authoritative reference for event lifecycle, venue management, scheduling, and the relationship between events and matches.

## User Stories

### Event Creation

As a **wrestling promoter**, I want to create events for my promotion, so that I can plan and organize wrestling shows.

**Workflow:**
1. Create event with name and optional preview text
2. Optionally assign a venue
3. Optionally schedule with a date
4. Event starts as "unscheduled" if no date, "scheduled" if date provided

### Event Scheduling

As a **promoter**, I want to schedule events with dates, so that I can plan my promotion calendar.

**Workflow:**
1. Select unscheduled event
2. Assign date
3. Optionally assign venue
4. Event status changes to "scheduled"
5. Event becomes eligible for match booking

### Venue Management

As a **promoter**, I want to manage venues where events are held, so that I can track locations and their event history.

**Workflow:**
1. Create venue with address details
2. Assign venues to events
3. View venue's event history (past and future)
4. Track venue utilization

### Match Card Building

As a **promoter**, I want to add matches to events, so that I can build compelling show cards.

**Workflow:**
1. Select scheduled event
2. Add matches with competitors and referees
3. Assign titles to title matches
4. Build complete match card

### Event History

As a **promoter**, I want to view past events, so that I can track show history and results.

**Workflow:**
1. View list of past events
2. See match results and outcomes
3. Track venue usage history
4. Review event performance

## Spec Scope

1. **Event Entity** - Core model with name, date, venue, preview
2. **Venue Entity** - Location model with address details
3. **Event Status** - Computed status (Unscheduled, Scheduled, Past)
4. **Event-Match Relationship** - Events contain matches
5. **Event-Venue Relationship** - Events belong to venues
6. **Query Scopes** - Filtering by status, date, venue

## Out of Scope

- Match management (see Match System spec)
- Competitor booking (see Match System spec)
- Ticket sales and revenue tracking
- Live event broadcasting

## Expected Deliverable

1. Complete event entity documentation with attributes
2. Venue entity documentation
3. EventStatus enum documentation
4. Event-Match relationship documentation
5. Event-Venue relationship documentation
6. Query builder methods reference
7. Action class reference

## Key Distinctions

**Events vs Matches:**
- Events are containers; matches are the competitive content
- Events have dates and venues; matches have types and stipulations
- Events track scheduling status; matches track results

**Event Status:**
- Status is **computed**, never stored
- Based on presence/absence of date and whether date is past
- Three states: Unscheduled, Scheduled, Past

**Venues:**
- Venues exist independently of events
- One venue can host many events
- Venue history tracked through event relationships
