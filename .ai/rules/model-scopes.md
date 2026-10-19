---
paths:
  - 'app/Models/Scopes/**'
---

# Model Scopes

## Global scopes are for context boundaries only
`app/Models/Scopes` is where `php artisan make:scope` puts a global scope and where Laravel's documentation shows it. Use one only for a boundary every query must respect, which here is the promotion tenant (`PromotionContextScope`, `EventMatchPromotionContextScope`). Reusable query operations stay on typed custom builders (see builders.md); never add a global scope to hide soft-deleted, inactive or lifecycle-state rows.

## Scopes fail closed
A scope that decides visibility must return nothing, not everything, when it cannot decide. With no enforced promotion it stays unscoped only for administrators, console, queue and guest contexts, and matches no rows for an authenticated non-administrator (`PromotionContextService::failsClosed()`). Read the context through `PromotionContextService`; never from the session or the request.

## Register once and keep the key shared
`BelongsToPromotion` registers `PromotionContextScope` for every promotion-owned model with `addGlobalScope()`; a model whose ownership is reached through a relation (`EventMatch`, through its event) registers its own scope under the `PromotionContextScope` key so removing the scope removes it the same way everywhere. Scopes are `final` classes implementing `Scope`.

## Prove both sides in a test
Test a scope in `tests/Integration/Models/Scopes` with records from two promotions: the enforced promotion sees only its own, a non-administrator with no promotion sees none, and an administrator or console context sees all. A test that cannot fail when the scope is removed does not count.
