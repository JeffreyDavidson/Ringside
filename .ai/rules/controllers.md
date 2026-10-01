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

## Do not reveal account existence
Authentication recovery endpoints, such as forgot-password, must return the same response whether or not the email belongs to an account or the broker throttled the request. Never surface broker status or per-account errors to the client.
