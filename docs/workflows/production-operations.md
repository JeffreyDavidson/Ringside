# Production Operations

Production runs on a Laravel Forge server with MySQL 8, behind Cloudflare. The items below are owned by the operator
(server, `.env`, Cloudflare and Forge settings), not by code. They came out of the round 3 production audit. Keep this a
checklist: record what to set, never the secret values.

## Status (last checked 2026-10-06)

Checked read-only on the server (non-secret config values, `SHOW GRANTS`, process list), through the public response
headers, and in Forge. Ticked items below were verified. Open findings:

- `APP_NAME` is still `Laravel`, and `mail.from` is `hello@example.com` / `Laravel`.
- `APP_URL` is `http://app.theringside.app`. Links generated during web requests still use HTTPS, because the proxy is
  trusted, but queued or console-generated links would not.
- The `single` log channel logs at `debug`. `LOG_LEVEL` is not set to `warning`.
- `MAIL_MAILER` resolves to `log`, so password reset links are written to the log and never emailed. Nobody can reset
  a password until a real mailer is configured.
- Forge's database backups need the Business plan, which this account does not have, so no Forge-managed backups
  exist. Confirm where the pre-release backups go and test a restore.
- No `Strict-Transport-Security` header is sent, so HSTS is off in Cloudflare.
- `X-Content-Type-Options: nosniff` is sent twice (the app and nginx).
- TLS 1.2 and 1.3 are accepted. Rejecting TLS 1.0/1.1 could not be checked from a client that no longer offers them;
  confirm the minimum version in Cloudflare.
- The server runs Node 22.23.3, not 24. It is supported by `engines`, but differs from CI.
- Uptime monitoring of `GET /up` was not found. Forge health checks and Nightwatch were not confirmed.

Fixed on 2026-10-06:
- Forge scheduled job "Laravel scheduler" (every minute).
- `SESSION_SECURE_COOKIE=true`.
- `NIGHTWATCH_INGEST_URI` changed from `127.0.0.1:2410` (another site's agent) to `127.0.0.1:2411`.
- The deploy script restarts `daemon-1095961` after each deploy.
- The app connects as a dedicated `ringside` MySQL user with `ALL PRIVILEGES ON ringside.*` only. The `forge` user stays
  for admin access. The credentials are in 1Password (Ringside vault, "Ringside Database (Production)").

## Environment (`.env` on the server)

- [ ] `APP_NAME=Ringside`. Production had `APP_NAME=Laravel`; the code default is already `Ringside`, but an explicit
      value in `.env` wins. Set `MAIL_FROM_NAME` and `MAIL_FROM_ADDRESS` to match, since Laravel's fallback sender
      name is `Example`.
- [ ] `APP_URL` uses `https://` and the public host, so links in emails (password reset) are correct.
- [x] `SESSION_SECURE_COOKIE=true` (the session cookie is only sent over HTTPS). Set on 2026-10-06 and confirmed
      with `config:show session.secure`.
- [ ] `LOG_LEVEL=warning`. The level is an operator setting; the code does not change it.
- [x] `LOG_DEPRECATIONS_CHANNEL` is `null` to discard deprecation warnings, or a channel such as `daily` while
      preparing an upgrade. `LOG_DEPRECATIONS_TRACE=true` adds stack traces. Both are read by `config/logging.php`.
- [ ] A real mailer (`MAIL_MAILER` set to SMTP or an API transport). With `MAIL_MAILER=log`, password reset links are
      written to the log file instead of being sent.
- [x] `QUEUE_CONNECTION=database` needs the `jobs`, `failed_jobs` and `job_batches` tables; the last two are created
      by the `create_failed_jobs_and_job_batches_tables` migration. Run a queue worker as soon as the app dispatches
      queued jobs (it does not yet).

## Scheduler

- [x] Forge runs the Laravel scheduler: a scheduled job (Forge > Scheduler) runs `php artisan schedule:run` every
      minute. Without it nothing in `routes/console.php` ever runs. Today that is `activitylog:clean`, scheduled daily
      with `--force` (the command asks for confirmation in production), which deletes activity log rows older than the
      package default of 365 days (`clean_after_days` in `spatie/laravel-activitylog`; the app has no published
      `config/activitylog.php`, so publish it to change the retention). Without the scheduler the `activity_log` table
      grows without bound.

## Database

- [x] The app connects with a dedicated MySQL user that only has privileges on the Ringside database (no `root`, no
      global grants). Migrations need DDL on that database only.
- [ ] Off-site backups: automated, kept somewhere other than the server, and restored at least once to prove they
      work. Take a manual backup before merging a release that contains migrations
      (see [Releases](releases.md)).

## Monitoring

- [x] `NIGHTWATCH_INGEST_URI` points at Ringside's own Nightwatch agent port, not an agent belonging to another site on
      the same server.
- [x] The Nightwatch agent is restarted on every deploy (Forge deploy script or daemon restart), so it runs the
      current release.
- [ ] Uptime monitoring checks `GET /up` (Laravel's health route) and alerts on failure.

## Cloudflare and TLS

- [ ] HSTS (`Strict-Transport-Security`) is enabled in Cloudflare (SSL/TLS > Edge Certificates). The application does
      not send it.
- [ ] Minimum TLS version 1.2 in Cloudflare.
- [ ] The application sends `Referrer-Policy`, `Permissions-Policy` and `X-Content-Type-Options` itself (see
      [Authentication and Session Security](../architecture/authentication-and-session-security.md#security-headers)),
      so the `X-Content-Type-Options` line in the Forge nginx config can be removed to avoid a duplicate header.

## Server

- [ ] Node.js 24 on the server. `package.json` accepts Node 22.13 or later so a Node 22 server builds without
      `EBADENGINE` warnings, but CI and local development use Node 24.
