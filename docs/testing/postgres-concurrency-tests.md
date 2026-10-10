# Concurrency Tests (PostgreSQL and MySQL)

SQLite ignores row locks (`lockForUpdate()`), so the normal suite can only assert *which* locks an Action takes and in *what order* (see `tests/Integration/Actions/Matches/SchedulingLockOrderTest.php`, which renders the lock as a visible SQL comment). Whether that order really prevents deadlocks can only be proven against a real server (PostgreSQL or MySQL) with real, concurrent processes.

`tests/Integration/Concurrency/BookingConcurrencyTest.php` does that for match booking. It spawns two real PHP processes (`booking-worker.php`), waits until both have booted and opened their connection, releases them together, and asserts:

- Two bookings on different events at the same date and time that share a wrestler never surface a deadlock (PostgreSQL SQLSTATE 40P01, MySQL error 1213): exactly one succeeds and the other raises `SchedulingConflictException`, and the wrestler ends up on one card only.
- Two bookings on those events without any shared resource both succeed.
- Two events created at the same venue on the same day at once: the venue row lock in `Events\CreateAction` admits exactly one, and the other raises `SchedulingConflictException`.
- Two owners of a promotion demoted to member at once (each the other's last fellow owner): the promotion row lock in `UpdatePromotionMemberRoleAction` serializes them, so exactly one succeeds, the other raises `CannotRemoveLastOwnerException`, and the promotion keeps one owner.
- MySQL only: two stables without a promotion split to the same new name at once. MySQL has no unique index over active stables without a promotion, so `StableNameLock` (a row of `stable_name_locks` taken first in `SplitStableAction`) is what admits exactly one; the other raises `CannotBeSplitException::nameTaken()` and one active stable with that name exists. It skips itself on PostgreSQL and SQLite, where the partial unique index would make the loser fail with a `QueryException` and the test could not tell the lock from the index.
- MySQL only, same reasoning: two stables without a promotion created with the same name at once (`Stables\CreateAction`), a create racing a split to that name, a create racing a restore of a deleted stable with that name (`Stables\RestoreAction`), and a create racing a rename to that name (`Stables\UpdateAction`). Each takes `StableNameLock` first, so exactly one live stable has the name and no deadlock occurs. Without the lock both operations succeed on MySQL and two live stables share the name.
- PostgreSQL and MySQL: two tag teams created with the same name at once (in a promotion and without one), two created with the same signature move, a tag team rename racing a create of that name (`TagTeams\CreateAction`, `UpdateAction`), and two titles created with the same name (in a promotion and without one, `Titles\CreateAction`). No engine has a unique index over these values, so `RecordNameLock` (a row of `record_name_locks` taken first in each Action) alone admits exactly one: the other raises `NameTakenException` and one record with the value exists. Without the lock both succeed and two records share the value.
- PostgreSQL and MySQL: two wrestlers created with the same name at once (in a promotion and without one), two created with the same signature move, a wrestler rename racing a create of that name (`Wrestlers\CreateAction`, `UpdateAction`), two events created with the same name (in a promotion and without one, `Events\CreateAction`), and two venues created with the same name (`Venues\CreateAction`). Same mechanism and same expectation as the tag team and title test above: `RecordNameLock` alone admits exactly one, the other raises `NameTakenException`, and one record with the value exists.

Both of the venue and owner tests fail when their row lock is removed, which a single process cannot show because SQLite ignores row locks.

The tests belong to the `concurrency` group (they also keep the older `postgres-concurrency` group name) and are skipped unless `DB_CONNECTION` is `pgsql` or `mysql` and `RUN_CONCURRENCY_TESTS=1` is set in the real environment (`concurrencyTestsEnabled()` in `tests/Helpers/TestHelpers.php`), so normal and coverage runs never execute them.

The harness is engine-aware. Production runs MySQL 8, so the CI `MySQL tests` job runs the group too, at READ COMMITTED as configured in `config/database.php`:

| Need | PostgreSQL | MySQL |
| --- | --- | --- |
| Deadlock counter (before/after each run) | `pg_stat_database.deadlocks` | `information_schema.INNODB_METRICS` `lock_deadlocks` (needs `PROCESS` and the metric enabled) |
| Sessions blocked on a lock | `pg_stat_activity` `wait_event_type = 'Lock'` | `information_schema.INNODB_TRX` `trx_state = 'LOCK WAIT'` (needs `PROCESS`) |
| Deadlock victim in a worker | SQLSTATE 40P01 | SQLSTATE 40001 with driver code 1213 (`isDeadlock()` in `worker-support.php`) |

The two `CascadeLockOrderConcurrencyTest` cases that force PostgreSQL planner plans (`enable_hashjoin`, sequential scans, a 20,000-row `generate_series` padding) prove physical-row-order locking that does not apply to InnoDB, so they are PostgreSQL-only and skip themselves elsewhere.

## Running them

Local development uses SQLite, which can't run these tests, so point them at a scratch MySQL or PostgreSQL database. MySQL is the production engine, so prefer it. The test commits its data so the child processes can see it and rebuilds the schema with `migrate:fresh` afterwards, so never point it at a database you want to keep.

For MySQL, create a scratch database whose user has the `PROCESS` privilege and `SELECT` on `performance_schema` (the CI job grants both), make sure `SET GLOBAL innodb_monitor_enable = 'lock_deadlocks'` is in effect if the metric is disabled, then run:

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=ringside_concurrency \
DB_USERNAME=root DB_PASSWORD= RUN_CONCURRENCY_TESTS=1 \
  vendor/bin/pest --group=concurrency --no-coverage
```

For PostgreSQL:

```bash
createdb ringside_pg_concurrency
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5432 DB_DATABASE=ringside_pg_concurrency \
DB_USERNAME="$(whoami)" DB_PASSWORD= RUN_CONCURRENCY_TESTS=1 \
  vendor/bin/pest --group=concurrency --no-coverage
dropdb ringside_pg_concurrency
```

The CI `Postgres tests` and `MySQL tests` jobs run the same group as a separate step. Because the workers are released by a barrier rather than a fixed delay, a slow runner can only make the race window smaller (the loser then fails with a normal scheduling conflict); it cannot make the assertions fail. A regression to the old lock order shows up as a `deadlock detected` failure.
