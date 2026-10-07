# Production Operations

Production runs on a Laravel Forge server with MySQL 8, behind Cloudflare. The items below are owned by the operator
(server, `.env`, Cloudflare and Forge settings), not by code. They came out of the round 3 production audit. Keep this a
checklist: record what to set, never the secret values.

## Status (last checked 2026-10-07)

Checked read-only on the server (non-secret config values, `SHOW GRANTS`, process list), through the public response
headers, and in Forge. Ticked items below were verified.

Still open:

- Rejecting TLS 1.0/1.1 can't be tested from a client that no longer offers them. The owner set the minimum to TLS 1.2
  in Cloudflare.
- The server runs Node 22.23.3, not 24. That's supported by `engines`, but differs from CI.
- Uptime monitoring of `GET /up` isn't set up yet (planned: UptimeRobot).

Fixed on 2026-10-06 and 2026-10-07:

- Scheduler: Forge scheduled job "Laravel scheduler" (every minute).
- `SESSION_SECURE_COOKIE=true`.
- Nightwatch:
  - `NIGHTWATCH_INGEST_URI` changed from `127.0.0.1:2410` (another site's agent) to `127.0.0.1:2411`;
  - the deploy script restarts `daemon-1095961` after each deploy.
- Database user: the app connects as a dedicated `ringside` MySQL user with `ALL PRIVILEGES ON ringside.*` only. The
  `forge` user stays for admin access. The credentials are in 1Password (Ringside vault, "Ringside Database
  (Production)").
- `APP_NAME=Ringside`, `APP_URL=https://app.theringside.app` and `LOG_LEVEL=warning`.
- Mail goes through Resend:
  - `MAIL_MAILER=resend` with `RESEND_API_KEY` (read since v0.7.11), from `Ringside <notifications@theringside.app>`;
  - the `theringside.app` sending domain is verified;
  - a test email was delivered.
- Off-site backups:
  - `/home/forge/backups/.control/ringside-offsite/backup.py` encrypts the newest hourly dump and uploads it to
    Backblaze B2 (`jdavidson-ringside-production-backups`) every night at 07:30 UTC (Forge job 2154750);
  - a restore test (download, decrypt, compare with the dump) matched;
  - the dead `mission-control/scripts/sync-backups.sh` cron line was removed.
- Headers:
  - the nginx `X-Content-Type-Options` line was removed, so `nosniff` is sent once;
  - the application sends `Strict-Transport-Security`, because Cloudflare's HSTS setting did not produce the header.

## Environment (`.env` on the server)

- [x] `APP_NAME=Ringside`. Production had `APP_NAME=Laravel`; the code default is already `Ringside`, but an explicit
      value in `.env` wins. Set `MAIL_FROM_NAME` and `MAIL_FROM_ADDRESS` to match, since Laravel's fallback sender
      name is `Example`.
- [x] `APP_URL` uses `https://` and the public host, so links in emails (password reset) are correct.
- [x] `SESSION_SECURE_COOKIE=true` (the session cookie is only sent over HTTPS). Set on 2026-10-06 and confirmed
      with `config:show session.secure`.
- [x] `LOG_LEVEL=warning`. The level is an operator setting; the code does not change it.
- [x] `LOG_DEPRECATIONS_CHANNEL` is `null` to discard deprecation warnings, or a channel such as `daily` while
      preparing an upgrade. `LOG_DEPRECATIONS_TRACE=true` adds stack traces. Both are read by `config/logging.php`.
- [x] A real mailer. Production uses Resend: `MAIL_MAILER=resend`, `RESEND_API_KEY` (an API key with sending access
      for a domain verified in Resend, with its DNS records in Cloudflare), `MAIL_FROM_ADDRESS` on that domain and
      `MAIL_FROM_NAME=Ringside`. The `resend/resend-php` package provides the transport. With `MAIL_MAILER=log`,
      password reset links are written to the log file instead of being sent.
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
- [x] Off-site backups: automated, kept somewhere other than the server, and restored at least once to prove they
      work. Take a manual backup before merging a release that contains migrations
      (see [Releases](releases.md)).

## Monitoring

- [x] `NIGHTWATCH_INGEST_URI` points at Ringside's own Nightwatch agent port, not an agent belonging to another site on
      the same server.
- [x] The Nightwatch agent is restarted on every deploy (Forge deploy script or daemon restart), so it runs the
      current release.
- [ ] Uptime monitoring checks `GET /up` (Laravel's health route) and alerts on failure.

## Cloudflare and TLS

- [x] HSTS (`Strict-Transport-Security: max-age=15552000`) is sent by the application on HTTPS requests (see
      [Authentication and Session Security](../architecture/authentication-and-session-security.md#security-headers)).
- [x] Minimum TLS version 1.2 in Cloudflare.
- [x] The application sends `Referrer-Policy`, `Permissions-Policy` and `X-Content-Type-Options` itself (see
      [Authentication and Session Security](../architecture/authentication-and-session-security.md#security-headers)),
      so the `X-Content-Type-Options` line in the Forge nginx config can be removed to avoid a duplicate header.

## Server

- [ ] Node.js 24 on the server. `package.json` accepts Node 22.13 or later so a Node 22 server builds without
      `EBADENGINE` warnings, but CI and local development use Node 24.
