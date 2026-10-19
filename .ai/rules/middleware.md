---
paths:
  - 'app/Http/Middleware/**'
---

# Middleware

## Name middleware by what it does, without a suffix
Name a class for its action or condition (`EnsureUserIsActive`, `EstablishPromotionContext`, `SendSecurityHeaders`), never with a `Middleware` suffix, and give it one request or response concern. Model-specific permissions belong in policies and the PromotionGate, not in middleware; a request guard may refuse the request itself (a 401 for a missing user, a redirect for an inactive one).

## Register in bootstrap/app.php and keep the order explicit
Group, alias and priority-list registration lives in `bootstrap/app.php`; apply per-route middleware in the route files, never in controllers. A middleware that depends on running before or after another (`EnsureUserIsActive` before `EstablishPromotionContext` before `SubstituteBindings`) states that order through the priority list, not by relying on registration order. A middleware that sets request-scoped context also needs `Livewire::addPersistentMiddleware()` (see app.md).

## Response middleware leaves what a route already chose
A middleware that adds response headers or cookies only fills what the response does not already have (`SendSecurityHeaders` skips any header a route set, and sends Strict-Transport-Security only on secure requests), so a route can pick its own policy and local HTTP development is never pinned to HTTPS.

## Give every application middleware a Feature test at its mirrored path
Every concrete class in `app/Http/Middleware` needs an executable test at the same relative path under `tests/Feature/Http/Middleware`; `MiddlewareArchitectureTest` enforces only that the mirrored file exists with an executable, non-skipped Pest test, and rejects empty, skipped and todo placeholders. By convention (not enforced) the test runs the real registered middleware, not a stand-in route, and covers both the passing and the refused or unchanged case.

Tests of Laravel's own middleware as the application configures it (`AuthenticateSession`, trusted proxies) have no class in `app/Http/Middleware`, so they live in `tests/Feature/Http` beside `ErrorPagesTest`, not in the mirrored `Middleware` folder.
