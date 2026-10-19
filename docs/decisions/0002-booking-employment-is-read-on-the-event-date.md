# 0002: Booking reads employment on the event date

Status: accepted (2026-10-18)

## Context

`RosterBookingEligibility` and the `bookable()` scopes asked "is this person employed right now?". Event cards are dated, and history can be back-filled, so that answer is often the wrong one: someone hired this month could be booked on a card from before they joined, someone with a future start date could not be booked on an event after it, and released talent could not be booked on a past card from when they were employed.

## Decision

When a booking check is given the event's date, employment is read on that date. The person (and each current wrestler of a tag team) needs an employment period that covers the event's date. Retirement, suspension and injury keep their current-state meaning. Without a date (unscheduled events, title results, tournaments) the old employed-now rule applies.

Titles follow the same rule (decision of 2026-10-19, "Use the show's date"): when a title is added to a dated event's match, the title needs an activity period covering the event's date and the champion who must compete is whoever held the title on that date, not today's champion. An unscheduled event keeps the active-now and current-champion checks.

## Why

- It matches how the record of a card is read afterwards: a match belongs to the person's career on the event date.
- It lets back-filled history be entered without releasing or re-hiring people, which the roster calendar already allows for unavailability.
- Reading retirement, suspension and injury as they stand now is the smallest change. Those states have no reliable history for a past date in every case.

## Trade-off

- A booking that was valid when saved can become invalid if employment dates are edited later. Saved matches are not re-checked; the card shows the existing "No longer bookable" marker for upcoming cards.
- Moving an existing event to another date does not re-check employment; only the roster calendar is re-checked there. Adding that is a separate change.
