---
paths:
  - 'app/Actions/**'
---

# Actions

## Direct Eloquent access
Query Eloquent models directly. Move reusable query behavior into typed custom Eloquent Builders; do not create repositories that merely wrap Eloquent.

## Action entry points
Implement individual application operations as focused Action classes with a public handle() method.

## Inject action collaborators
Acquire Action and service collaborators through constructor injection inside application operations.

## Lock lifecycle period transitions
Acquire the owning model row lock inside a transaction before validating and mutating lifecycle periods. Start operations create a new period only when no open period exists; reject an existing open period instead of rewriting it. End operations require an open period and must reject an end date before its start.

## Lock administrators before a user row
User Actions that can remove an active administrator (Users ChangeStatusAction to a non-active status, Users UpdateAction to a non-administrator role) call EnsureAnotherActiveAdministratorAction first, which locks every active administrator in ascending id order, and only then lock the target user; ChangeStatusAction locks the user's owned promotions (ascending id) after that. Never lock a user row before the administrator set, or two administrators removing each other deadlock.

## Record lifecycle audits atomically
When a typed lifecycle Action changes a period, record its LifecycleTransition inside the same owner-locked database transaction. Use the domain-specific transition type and effective date, and include only meaningful optional context.
