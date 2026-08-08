---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Controller structure
Use resourceful controllers for operations belonging to a single resource. Use invokable controllers for standalone actions that do not fit a resourceful controller method.

## Thin domain controllers
Keep controllers limited to response and view composition. Authorize controller endpoints through route middleware, and place mutations and business workflows in Actions or established domain collaborators.

## Form Request validation
Validate controller input with dedicated Form Request classes and use only validated input in controller operations.
