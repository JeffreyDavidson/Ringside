---
paths:
  - 'app/{Actions/Matches,Exceptions/Matches,Exceptions/Scheduling}/**'
---

# Matches Exceptions Scheduling

## Separate match configuration, availability, and scheduling failures
Use InvalidMatchConfigurationException for incomplete or structurally invalid match assignments. Use EntityNotAvailableException when a selected participant or title's own state prevents assignment. Reserve SchedulingConflictException for actual collisions between bookings, times, or resources.
