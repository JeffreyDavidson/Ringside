# Production Operations

Production runs on a Laravel Forge server with MySQL 8, behind Cloudflare. The items below are owned by the operator
(server, `.env`, Cloudflare and Forge settings), not by code. They came out of the round 3 production audit. Keep this a
checklist: record what to set, never the secret values.

## Environment (`.env` on the server)

- [ ] `APP_NAME=Ringside`. Production had `APP_NAME=Laravel`; the code default is already `Ringside`, but an explicit
      value in `.env` wins. Set `MAIL_FROM_NAME` and `MAIL_FROM_ADDRESS` to match, since Laravel's fallback sender
      name is `Example`.
- [ ] `APP_URL` uses `https://` and the public host, so links in emails (password reset) are correct.
- [ ] `LOG_LEVEL=warning`. The level is an operator setting; the code does not change it.
- [ ] `LOG_DEPRECATIONS_CHANNEL` is `null` to discard deprecation warnings, or a channel such as `daily` while
      preparing an upgrade. `LOG_DEPRECATIONS_TRACE=true` adds stack traces. Both are read by `config/logging.php`.
- [ ] A real mailer (`MAIL_MAILER` set to SMTP or an API transport). With `MAIL_MAILER=log`, password reset links are
      written to the log file instead of being sent.
- [ ] `QUEUE_CONNECTION=database` needs the `jobs`, `failed_jobs` and `job_batches` tables; the last two are created
      by the `create_failed_jobs_and_job_batches_tables` migration. Run a queue worker as soon as the app dispatches
      queued jobs (it does not yet).

## Database

- [ ] The app connects with a dedicated MySQL user that only has privileges on the Ringside database (no `root`, no
      global grants). Migrations need DDL on that database only.
- [ ] Off-site backups: automated, kept somewhere other than the server, and restored at least once to prove they
      work. Take a manual backup before merging a release that contains migrations
      (see [Releases](releases.md)).

## Monitoring

- [ ] `NIGHTWATCH_INGEST_URI` points at Ringside's own Nightwatch agent port, not an agent belonging to another site on
      the same server.
- [ ] The Nightwatch agent is restarted on every deploy (Forge deploy script or daemon restart), so it runs the
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
