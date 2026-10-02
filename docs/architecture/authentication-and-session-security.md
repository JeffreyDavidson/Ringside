# Authentication and Session Security

## Rate limits

Guest account endpoints are throttled per client IP in `routes/auth.php` with the `throttle:` middleware. Each endpoint
has its own prefix, so exhausting one does not block the others.

| Endpoint | Limit |
| --- | --- |
| `POST login` | 5 per minute per email and IP (`LoginRequest`, unchanged) |
| `POST register` | 6 per minute per IP |
| `POST forgot-password` | 6 per minute per IP |
| `POST reset-password` | 6 per minute per IP |

Requests over the limit receive `429`. The password broker keeps its own per-user resend throttle.

## Password policy

`AppServiceProvider::boot()` sets `Password::defaults()` to a 12 character minimum in every environment. Every place
that accepts a new password uses the defaults: registration, password reset and the administrator user form.
`uncompromised()` is deliberately not enabled because it calls an external service on every validation.

## Session invalidation

`bootstrap/app.php` enables `authenticateSessions()`, which adds Laravel's `AuthenticateSession` middleware to the
`web` group. It stores a hash of the password in the session, so changing a password logs the account out of every
other session and "remember me" cookie on its next request. Sessions that predate the change store the hash on their
next request and keep working.

The middleware priority list from the promotion context work is unchanged: `AuthenticatesSessions`, then
`EnsureUserIsActive`, then `EstablishPromotionContext`, then `SubstituteBindings`. Livewire update requests go through
the `web` group, so they are covered as well.

## Existence oracle

Foreign and missing roster IDs both return `404`: `EstablishPromotionContext` runs before route model binding, and
promotion scopes fail closed. `tests/Feature/Http/Controllers/RosterRecordExistenceTest.php` covers every roster
show route for Owner and Member roles.

## Modal state

`BaseModal::destroyOnClose()` returns `true`, so wire-elements-modal removes a modal component's state when it closes
instead of letting it accumulate in the page's component list.

## Removed dependency

`stancl/tenancy` was unused and exposed `GET /tenancy/assets/{path?}`. It was removed; the route now returns `404`.

## Seeders

`DatabaseSeeder` seeds demo data and `UsersTableSeeder` creates accounts with the well-known password `password`.
Both throw a `RuntimeException` when `app()->isProduction()` so they cannot run against production. Match types and
decisions are enums, so there are no seeders for them. Run `php artisan db:seed` against a local or scratch database
only.
