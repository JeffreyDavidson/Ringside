---
paths:
  - 'tests/Browser/**'
---

# Browser

## Refresh browser databases without rollbacks
Use RefreshDatabase for browser tests and keep reset configuration centralized in tests/Pest.php. Browser tests must not use DatabaseMigrations because this project uses forward-only migrations without rollback support.
