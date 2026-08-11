---
paths:
  - 'app/Exceptions/Scheduling/**'
---

# Scheduling

## Separate conflicts from entity availability
Use SchedulingConflictException when two bookings, times, or resources collide. Use EntityNotAvailableException when the selected entity's own state prevents scheduling. Keep these as typed boundaries and add factories only alongside enforced scheduling policies.
