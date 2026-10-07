# Authentication and Session Security

## Rate limits

Guest account endpoints are throttled per client IP in `routes/auth.php` with the `throttle:` middleware. Each endpoint
has its own prefix, so exhausting one does not block the others.

| Endpoint | Limit |
| --- | --- |
| `POST login` | 20 per minute per IP (`throttle:`), and 5 failed attempts per minute per email and IP (`LoginRequest`) |
| `POST register` | 6 per minute per IP |
| `POST forgot-password` | 6 per minute per IP |
| `POST reset-password` | 6 per minute per IP |

Requests over the limit receive `429`. The password broker keeps its own per-user resend throttle. The per-IP login
limit counts every attempt and stops one address from trying many different emails, which the per-email limit alone
does not.

Registration does not reveal whether an email is already registered: a taken email creates nothing and redirects to
the login page with the same `auth-forms.account_pending` status as a new account, like the password reset response.
Other validation errors are still shown. A duplicate that only the database unique index catches (a concurrent
registration, or an email the MySQL collation treats as equal) gets the same response and creates no user.

Submitting the password reset form answers every failed reset, whether the email is unknown, the token is invalid or
expired, or the broker throttled it, with the same `passwords.token` error on the email field and the email kept as
old input, so the form cannot be used to discover which emails are registered.

## Reverse proxy and client IP

Production is served through Cloudflare, so the TCP peer of every request is a Cloudflare edge address. The rate limits
above and the authentication log key on `request()->ip()`, which is only the real visitor when the proxy is trusted.

- `config/trustedproxy.php` lists the trusted proxies. The default is Cloudflare's published IPv4 and IPv6 ranges; set
  `TRUSTED_PROXIES` to override it with a comma-separated list or `*`. Use `*` only when the origin accepts traffic from
  the proxy alone, otherwise a visitor could spoof the client address.
- `bootstrap/app.php` trusts only `X-Forwarded-For` and `X-Forwarded-Proto`. `X-Forwarded-Host`, `X-Forwarded-Port`
  and `X-Forwarded-Prefix` are deliberately not trusted: Cloudflare forwards client-supplied headers, so trusting them
  would let a visitor choose the host or port used to build links such as password reset URLs (for example
  `https://host:8443/...`). The port comes from the `Host` header, defaulting to 443 for HTTPS.
- Forwarded headers from any peer outside the trusted list are ignored.
- Refresh the default ranges from https://www.cloudflare.com/ips-v4 and https://www.cloudflare.com/ips-v6 when
  Cloudflare announces a change. `tests/Feature/Http/Middleware/TrustedProxiesTest.php` covers the behaviour.

## Security headers

`App\Http\Middleware\SendSecurityHeaders` is prepended to the `web` group, so every page, redirect and Livewire update
sends:

- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy` disabling camera, microphone, geolocation, payment, USB, display capture, MIDI, autoplay and the
  motion sensors
- `X-Content-Type-Options: nosniff`
- `Strict-Transport-Security: max-age=15552000` (six months, this host only, no `includeSubDomains` or `preload`), sent
  only on HTTPS requests. Production is HTTPS through Cloudflare and the trusted proxy, so local `http://` development
  is never pinned to HTTPS. Cloudflare's own HSTS setting did not produce the header, so the application sends it.

A header the response already carries is left as it is, so a route can choose its own value and nothing is sent twice
from the application. The Forge nginx config adds `X-Frame-Options` and `X-XSS-Protection`; its `X-Content-Type-Options` line was removed on
2026-10-07 so `nosniff` is sent once.

Not set in code on purpose:

- `Content-Security-Policy`: Livewire and Vite need nonces or hashes, which is a separate project.

`tests/Feature/Http/Middleware/SendSecurityHeadersTest.php` covers the behaviour.

## Password policy

`AppServiceProvider::boot()` sets `Password::defaults()` to a 12 character minimum in every environment. Every place
that accepts a new password uses the defaults: registration, password reset and the administrator user form.
`uncompromised()` is deliberately not enabled because it calls an external service on every validation.

## Session invalidation

`bootstrap/app.php` enables `authenticateSessions()`, which adds Laravel's `AuthenticateSession` middleware to the
`web` group. It stores a hash of the password in the session, so changing a password logs the account out of every
other session and "remember me" cookie on its next request. Sessions that predate the change store the hash on their
next request and keep working.

The middleware writes the hash of the guard's user at the end of each request. When administrators change their own
password in the user form, `Users\Modals\FormModal::updateForm()` puts the updated user on the guard, so the session
that made the change stores the new hash and stays signed in while every other session still ends. A "remember me"
cookie issued before the change still carries the old hash, so once this session expires the administrator signs in
again with the new password.
`tests/Feature/Http/Middleware/AuthenticateSessionTest.php` drives the real Livewire update request through the
middleware.

The middleware priority list from the promotion context work is unchanged: `AuthenticatesSessions`, then
`EnsureUserIsActive`, then `EstablishPromotionContext`, then `SubstituteBindings`. Livewire update requests go through
the `web` group, so they are covered as well.

## Existence oracle

Foreign and missing roster IDs both return `404`: `EstablishPromotionContext` runs before route model binding, and
promotion scopes fail closed. `tests/Feature/Http/Controllers/RosterRecordExistenceTest.php` covers every roster
show route for Owner and Member roles.

## Error pages

`resources/views/errors/{403,404,419,500}.blade.php` render through `x-errors.page`, which uses the guest (`auth`)
layout because an error can occur before the session, the user or the promotion context exists. It reads
`auth()->hasUser()`, which only reports a user that was already resolved, so an error page never queries the database
(a 500 may come from the database itself). A signed-in user gets a link back to the dashboard and a Log out button;
anyone else gets a Sign in link. The 500 page shows a generic message, never the exception.

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
