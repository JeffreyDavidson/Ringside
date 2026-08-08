---
paths:
  - 'routes/**'
  - routes/web.php
---

# Routes

## Route authorization
Authorize controller endpoints through route can middleware backed by application policies.

## Controller-backed web routes
Define web route handlers with controller classes; do not place endpoint behavior in route closures.

## Route-assigned middleware
Assign middleware in route files and route groups, not on controller classes.
