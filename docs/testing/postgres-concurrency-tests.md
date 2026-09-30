# PostgreSQL Concurrency Tests

SQLite ignores row locks (`lockForUpdate()`), so the normal suite can only assert *which* locks an Action takes and in *what order* (see `tests/Integration/Actions/Matches/SchedulingLockOrderTest.php`, which renders the lock as a visible SQL comment). Whether that order really prevents deadlocks can only be proven against PostgreSQL with real, concurrent processes.

`tests/Integration/Concurrency/BookingConcurrencyTest.php` does that for match booking. It spawns two real PHP processes (`booking-worker.php`), waits until both have booted and opened their connection, releases them together, and asserts:

- Two bookings on different events at the same date and time that share a wrestler never surface a deadlock (SQLSTATE 40P01): exactly one succeeds and the other raises `SchedulingConflictException`, and the wrestler ends up on one card only.
- Two bookings on those events without any shared resource both succeed.

The tests belong to the `postgres-concurrency` group and are skipped unless `DB_CONNECTION=pgsql` and `RUN_CONCURRENCY_TESTS=1` are both set in the real environment, so normal and coverage runs never execute them.

## Running them

Use a scratch database. The test commits its data so the child processes can see it and rebuilds the schema with `migrate:fresh` afterwards.

```bash
createdb ringside_pg_concurrency
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=ringside_pg_concurrency \
DB_USERNAME="$(whoami)" DB_PASSWORD= RUN_CONCURRENCY_TESTS=1 \
  vendor/bin/pest --group=postgres-concurrency --no-coverage
dropdb ringside_pg_concurrency
```

The CI `Postgres tests` job runs the same group as a separate step. Because the workers are released by a barrier rather than a fixed delay, a slow runner can only make the race window smaller (the loser then fails with a normal scheduling conflict); it cannot make the assertions fail. A regression to the old lock order shows up as a `deadlock detected` failure.
