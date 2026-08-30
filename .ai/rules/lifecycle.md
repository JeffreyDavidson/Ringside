---
paths:
  - 'app/Lifecycle/**'
---

# Lifecycle

## Reuse lifecycle projection reads
Use LifecycleStateReader::readProjectedBoolean for boolean query projections before relationship fallbacks. Status readers should share this helper so projected list queries remain query-free without duplicating attribute inspection logic.
