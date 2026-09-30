<?php

declare(strict_types=1);

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;

/*
 * Opt-in PostgreSQL concurrency checks. SQLite ignores row locks, so lock ordering can only be
 * proven against a real server with real, concurrent processes. Run with:
 *
 *   DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_DATABASE=<scratch database> DB_USERNAME=... DB_PASSWORD=... \
 *   RUN_CONCURRENCY_TESTS=1 vendor/bin/pest --group=postgres-concurrency --no-coverage
 *
 * The scratch database is rebuilt with migrate:fresh afterwards; never point this at real data.
 */

/**
 * Book one match in each of two events with a start barrier, in two real PHP processes.
 *
 * @param  array<int, array{event_id: int, first_wrestler_id: int, second_wrestler_id: int, referee_id: int}>  $bookings
 * @return array<int, array<mixed>>
 */
function bookConcurrently(array $bookings): array
{
    $processes = [];
    $inputs = [];

    // The workers inherit the real environment, including the DB_* settings that selected PostgreSQL.
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

    foreach ($inputs as $input) {
        $input->write("go\n");
        $input->close();
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
 * PostgreSQL's own count of deadlocks it has resolved in this database. Laravel retries a deadlocked
 * booking, so the outcome alone cannot show that a deadlock never happened.
 */
function deadlocksResolved(): int
{
    $deadlocks = DB::scalar('select deadlocks from pg_stat_database where datname = current_database()');

    return is_int($deadlocks) ? $deadlocks : throw new RuntimeException('Unable to read the PostgreSQL deadlock counter.');
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

$enabled = getenv('DB_CONNECTION') === 'pgsql' && getenv('RUN_CONCURRENCY_TESTS') === '1';

test('concurrent bookings on different events at the same time never deadlock', function () {
    withCommittedData(function (): void {
        foreach (range(1, 10) as $run) {
            // Arrange
            $date = now()->addWeeks($run);
            $firstEvent = Event::factory()->create(['date' => $date]);
            $secondEvent = Event::factory()->create(['date' => $date]);
            $sharedWrestler = Wrestler::factory()->bookable()->create();

            $deadlocksBefore = deadlocksResolved();

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

            expect(deadlocksResolved())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and($exceptions->all())->toBe([SchedulingConflictException::class])
                ->and(collect($results)->where('ok', true))->toHaveCount(1)
                ->and($eventsBookingTheWrestler)->toBe(1);
        }
    });
})->skip(! $enabled, 'Set DB_CONNECTION=pgsql and RUN_CONCURRENCY_TESTS=1 to run the PostgreSQL concurrency tests.')
    ->group('postgres-concurrency');

test('concurrent bookings without a real conflict both succeed', function () {
    withCommittedData(function (): void {
        foreach (range(1, 5) as $run) {
            // Arrange
            $date = now()->addWeeks($run);
            $firstEvent = Event::factory()->create(['date' => $date]);
            $secondEvent = Event::factory()->create(['date' => $date]);

            $deadlocksBefore = deadlocksResolved();

            // Act
            $results = bookConcurrently([
                bookingFor($firstEvent, Wrestler::factory()->bookable()->create()),
                bookingFor($secondEvent, Wrestler::factory()->bookable()->create()),
            ]);

            // Assert
            expect(deadlocksResolved())->toBe($deadlocksBefore)
                ->and(collect($results)->pluck('deadlock')->contains(true))->toBeFalse()
                ->and(collect($results)->pluck('exception')->filter()->all())->toBeEmpty()
                ->and(collect($results)->where('ok', true))->toHaveCount(2);
        }
    });
})->skip(! $enabled, 'Set DB_CONNECTION=pgsql and RUN_CONCURRENCY_TESTS=1 to run the PostgreSQL concurrency tests.')
    ->group('postgres-concurrency');
