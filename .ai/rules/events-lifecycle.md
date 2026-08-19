---
paths:
  - 'app/{Actions/Venues,Exceptions/Events,Lifecycle}/**'
---

# Events Lifecycle

## Validate venue restoration name conflicts
Venue restoration must lock the soft-deleted venue inside its transaction and reject active venue name conflicts before restoring. Preserve historical event relationships without bypassing active venue identity uniqueness.
