<?php

declare(strict_types=1);

use App\Enums\Promotions\MembershipRole;
use App\Enums\Promotions\MembershipStatus;
use App\Enums\Users\UserStatus;
use App\Exceptions\Promotions\CannotRemoveLastOwnerException;
use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Matches\EventMatch;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/*
 * Opt-in PostgreSQL and MySQL concurrency checks. SQLite ignores row locks, so lock ordering can only be
 * proven against a real server with real, concurrent processes. Run with:
 *
 *   DB_CONNECTION=pgsql|mysql DB_HOST=127.0.0.1 DB_DATABASE=<scratch database> DB_USERNAME=... DB_PASSWORD=... \
 *   RUN_CONCURRENCY_TESTS=1 vendor/bin/pest --group=concurrency --no-coverage
 *
 * The scratch database is rebuilt with migrate:fresh afterwards; never point this at real data.
 */

/**
 * Run one worker per spec with a start barrier, in real PHP processes: each books a match in an event
 * or, with a reschedule_date, moves an event to that date, or, with a restore_event_id, restores that deleted event,
 * or, with a create_event_at_venue_id, creates an event at that venue, or, with a demote_user_id, demotes that owner.
 *
 * @param  array<int, array<string, int|string>>  $bookings
 * @return array<int, array<mixed>>
 */
function bookConcurrently(array $bookings): array
{
    $processes = [];
    $inputs = [];

    // The workers inherit the real environment, including the DB_* settings that selected the engine.
    foreach ($bookings as $key => $booking) {
        $inputs[$key] = new InputStream;
        $processes[$key] = new Process([PHP_BINARY, __DIR__.'/booking-worker.php', json_encode($booking, JSON_THROW_ON_ERROR)]);
        $processes[$key]->setTimeout(120);
        $processes[$key]->setInput($inputs[$key]);
        $processes[$key]->start();
    }

    // Barrier: every worker has booted and holds an open connection before any is released.
    foreach ($processes as $process) {
        while (! str_contains($process->getOutput(), "\n") && $process->isRunning()) {
            usleep(10_000);
        }
    }

    // Workers spin until the same absolute time, so their actions overlap instead of merely starting close together.
    $startAt = microtime(true) + 0.2;

    foreach ($inputs as $input) {
        $input->write("{$startAt}\n");
        $input->close();
    }

    // A process only receives its pending input while it is polled, so poll every worker until release.
    // Waiting on them one by one would deliver the start time to later workers only after earlier ones finished.
    while (microtime(true) < $startAt) {
        foreach ($processes as $process) {
            $process->isRunning();
        }

        usleep(1_000);
    }

    $results = [];

    foreach ($processes as $key => $process) {
        $process->wait();

        preg_match('/RESULT:(.*)/', $process->getOutput(), $matches);
        $result = json_decode($matches[1] ?? '', true);

        if (! is_array($result)) {
            throw new RuntimeException("Worker failed: {$process->getErrorOutput()}{$process->getOutput()}");
        }

        $results[$key] = $result;
    }

    return $results;
}

/**
 * @return array{event_id: int, first_wrestler_id: int, second_wrestler_id: int, referee_id: int}
 */
function bookingFor(Event $event, Wrestler $firstWrestler): array
{
    return [
        'event_id' => $event->id,
        'first_wrestler_id' => $firstWrestler->id,
        'second_wrestler_id' => Wrestler::factory()->bookable()->create()->id,
        'referee_id' => Referee::factory()->bookable()->create()->id,
    ];
}

/**
 * Leave the transaction RefreshDatabase wraps around a test so child processes can see the data,
 * and rebuild the scratch database afterwards.
 */
function withCommittedData(Closure $callback): void
{
    DB::commit();

    try {
        $callback();
    } finally {
        Artisan::call('migrate:fresh');
    }
}

test('concurrent bookings on different events at the same time never deadlock', function () {
    withCommittedData(function (): void {
        foreach (range(1, 10) as $run) {
            // Arrange
            $date = now()->addWeeks($run);
            $firstEvent = Event::factory()->create(['date' => $date]);
            $secondEvent = Event::factory()->create(['date' => $date]);
            $sharedWrestler = Wrestler::factory()->bookable()->create();

            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = bookConcurrently([
                bookingFor($firstEvent, $sharedWrestler),
                bookingFor($secondEvent, $sharedWrestler),
            ]);

            // Assert
            $exceptions = collect($results)->pluck('exception')->filter()->values();
            $eventsBookingTheWrestler = DB::table('events_matches_competitors')
                ->join('events_matches', 'events_matches.id', '=', 'events_matches_competitors.match_id')
                ->where('events_matches_competitors.competitor_id', $sharedWrestler->id)
                ->distinct()
                ->count('events_matches.event_id');

            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and($exceptions->all())->toBe([SchedulingConflictException::class])
                ->and(collect($results)->where('ok', true))->toHaveCount(1)
                ->and($eventsBookingTheWrestler)->toBe(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

test('concurrent bookings without a real conflict both succeed', function () {
    withCommittedData(function (): void {
        foreach (range(1, 5) as $run) {
            // Arrange
            $date = now()->addWeeks($run);
            $firstEvent = Event::factory()->create(['date' => $date]);
            $secondEvent = Event::factory()->create(['date' => $date]);

            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = bookConcurrently([
                bookingFor($firstEvent, Wrestler::factory()->bookable()->create()),
                bookingFor($secondEvent, Wrestler::factory()->bookable()->create()),
            ]);

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and(collect($results)->pluck('exception')->filter()->all())->toBeEmpty()
                ->and(collect($results)->where('ok', true))->toHaveCount(2);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

/**
 * Move two events, each already booking the given wrestlers, to the same date at once.
 *
 * @param  array{0: Wrestler, 1: Wrestler}  $wrestlers  The wrestler booked on the first and the second event
 * @return array{0: array<int, array<mixed>>, 1: Event, 2: Event, 3: Carbon}
 */
function rescheduleTwoEventsIntoTheSameEmptyDate(int $run, array $wrestlers): array
{
    $firstEvent = Event::factory()->create(['date' => now()->addWeeks($run)]);
    $secondEvent = Event::factory()->create(['date' => now()->addWeeks($run)->addDay()]);
    EventMatch::factory()->forEvent($firstEvent)->withCompetitors([$wrestlers[0]])->create();
    EventMatch::factory()->forEvent($secondEvent)->withCompetitors([$wrestlers[1]])->create();
    $target = now()->addWeeks($run)->addDays(2);

    $results = bookConcurrently([
        ['event_id' => $firstEvent->id, 'reschedule_date' => $target->toDateTimeString()],
        ['event_id' => $secondEvent->id, 'reschedule_date' => $target->toDateTimeString()],
    ]);

    return [$results, $firstEvent, $secondEvent, $target];
}

test('concurrent reschedules of events sharing a wrestler into the same empty date admit only one', function () {
    withCommittedData(function (): void {
        foreach (range(1, 10) as $run) {
            // Arrange
            $sharedWrestler = Wrestler::factory()->bookable()->create();
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            [$results, , , $target] = rescheduleTwoEventsIntoTheSameEmptyDate($run, [$sharedWrestler, $sharedWrestler]);

            // Assert
            $eventsBookingTheWrestlerAtTheTarget = DB::table('events_matches_competitors')
                ->join('events_matches', 'events_matches.id', '=', 'events_matches_competitors.match_id')
                ->join('events', 'events.id', '=', 'events_matches.event_id')
                ->where('events_matches_competitors.competitor_id', $sharedWrestler->id)
                ->where('events.date', $target->toDateTimeString())
                ->distinct()
                ->count('events.id');

            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and(collect($results)->pluck('exception')->filter()->values()->all())->toBe([SchedulingConflictException::class])
                ->and(collect($results)->where('ok', true))->toHaveCount(1)
                ->and($eventsBookingTheWrestlerAtTheTarget)->toBe(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

test('concurrent reschedules into the same empty date without a real conflict both succeed', function () {
    withCommittedData(function (): void {
        foreach (range(1, 5) as $run) {
            // Arrange
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            [$results, $firstEvent, $secondEvent, $target] = rescheduleTwoEventsIntoTheSameEmptyDate($run, [
                Wrestler::factory()->bookable()->create(),
                Wrestler::factory()->bookable()->create(),
            ]);

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and(collect($results)->pluck('exception')->filter()->all())->toBeEmpty()
                ->and(collect($results)->where('ok', true))->toHaveCount(2)
                ->and($firstEvent->refresh()->date?->toDateTimeString())->toBe($target->toDateTimeString())
                ->and($secondEvent->refresh()->date?->toDateTimeString())->toBe($target->toDateTimeString());
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

test('two events swapping dates at once never deadlock', function () {
    withCommittedData(function (): void {
        foreach (range(1, 5) as $run) {
            // Arrange
            $firstDate = now()->addWeeks($run);
            $secondDate = $firstDate->copy()->addDay();
            $firstEvent = Event::factory()->create(['date' => $firstDate]);
            $secondEvent = Event::factory()->create(['date' => $secondDate]);
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = bookConcurrently([
                ['event_id' => $firstEvent->id, 'reschedule_date' => $secondDate->toDateTimeString()],
                ['event_id' => $secondEvent->id, 'reschedule_date' => $firstDate->toDateTimeString()],
            ]);

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('exception')->filter()->all())->toBeEmpty()
                ->and(collect($results)->where('ok', true))->toHaveCount(2)
                ->and($firstEvent->refresh()->date?->toDateTimeString())->toBe($secondDate->toDateTimeString())
                ->and($secondEvent->refresh()->date?->toDateTimeString())->toBe($firstDate->toDateTimeString());
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

test('restoring an event while its wrestler is booked in another event at the same time admits only one', function () {
    withCommittedData(function (): void {
        // The restore is held back by a different delay each run so that, across the runs, it lands before, inside
        // and after the booking's own conflict check; only an unguarded restore can slip in behind that check.
        foreach ([30, 35, 40, 45, 50, 55, 60, 70] as $run => $restoreDelayMs) {
            // Arrange
            $date = now()->addWeeks($run + 1);
            $deletedEvent = Event::factory()->create(['date' => $date]);
            $otherEvent = Event::factory()->create(['date' => $date]);
            $sharedWrestler = Wrestler::factory()->bookable()->create();
            EventMatch::factory()->forEvent($deletedEvent)->withCompetitors([$sharedWrestler])->create();
            $deletedEvent->delete();
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = bookConcurrently([
                ['restore_event_id' => $deletedEvent->id, 'start_delay_ms' => $restoreDelayMs],
                bookingFor($otherEvent, $sharedWrestler),
            ]);

            // Assert
            $liveEventsBookingTheWrestler = DB::table('events_matches_competitors')
                ->join('events_matches', 'events_matches.id', '=', 'events_matches_competitors.match_id')
                ->join('events', 'events.id', '=', 'events_matches.event_id')
                ->where('events_matches_competitors.competitor_id', $sharedWrestler->id)
                ->whereNull('events.deleted_at')
                ->where('events.date', $date->toDateTimeString())
                ->distinct()
                ->count('events.id');

            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and(collect($results)->pluck('exception')->filter()->values()->all())->toBe([SchedulingConflictException::class])
                ->and(collect($results)->where('ok', true))->toHaveCount(1)
                ->and($liveEventsBookingTheWrestler)->toBe(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

test('concurrent events at the same venue on the same day admit only one', function () {
    withCommittedData(function (): void {
        foreach (range(1, 10) as $run) {
            // Arrange
            $venue = Venue::factory()->create();
            $date = now()->addWeeks($run)->setTime(19, 0);
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = bookConcurrently([
                ['create_event_at_venue_id' => $venue->id, 'date' => $date->toDateTimeString()],
                ['create_event_at_venue_id' => $venue->id, 'date' => $date->copy()->addHour()->toDateTimeString()],
            ]);

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('exception')->filter()->values()->all())->toBe([SchedulingConflictException::class])
                ->and(collect($results)->where('ok', true))->toHaveCount(1)
                ->and(Event::query()->whereBelongsTo($venue)->count())->toBe(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');

test('two owners demoting each other at once always leave one owner', function () {
    withCommittedData(function (): void {
        foreach (range(1, 10) as $run) {
            // Arrange
            $promotion = Promotion::factory()->create();
            $owners = User::factory()->count(2)->create(['status' => UserStatus::Active]);
            $owners->each(fn (User $owner) => $promotion->users()->attach($owner, [
                'role' => MembershipRole::Owner,
                'status' => MembershipStatus::Active,
            ]));
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = bookConcurrently($owners->map(fn (User $owner): array => [
                'promotion_id' => $promotion->id,
                'demote_user_id' => $owner->id,
            ])->all());

            // Assert
            expect(resolvedDeadlocks())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('exception')->filter()->values()->all())->toBe([CannotRemoveLastOwnerException::class])
                ->and(collect($results)->where('ok', true))->toHaveCount(1)
                ->and($promotion->memberships()->withRole(MembershipRole::Owner)->count())->toBe(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), CONCURRENCY_TESTS_SKIPPED)
    ->group('concurrency', 'postgres-concurrency');
