<?php

declare(strict_types=1);

use App\Exceptions\Roster\Stables\CannotBeCreatedException;
use App\Models\Promotions\Promotion;
use App\Models\Roster\Stables\Stable;

/*
 * Opt-in MySQL and PostgreSQL concurrency check for the name of a stable that belongs to a promotion. No name
 * lock guards such a stable, because the (promotion, active name) unique index does; the loser of a race for a
 * name must still see the "name is taken" business failure instead of the index's query exception.
 * It reuses bookConcurrently() and booking-worker.php from BookingConcurrencyTest.
 */
test('two stables of a promotion created with the same name at once admit only one and report the name as taken', function () {
    withCommittedData(function (): void {
        $promotion = Promotion::factory()->create();

        foreach (range(1, 5) as $run) {
            // Arrange
            $name = "Owned Concurrent Create {$run}";
            $deadlocksBefore = resolvedDeadlocks();

            // Act
            $results = collect(bookConcurrently([
                ['create_owned_stable_name' => $name, 'owned_promotion_id' => $promotion->id],
                ['create_owned_stable_name' => $name, 'owned_promotion_id' => $promotion->id],
            ]));

            // Assert
            expect(deadlocksResolvedSince($deadlocksBefore))->toBe(0)
                ->and($results->where('ok', true))->toHaveCount(1)
                ->and($results->where('ok', false)->pluck('exception')->all())->toBe([CannotBeCreatedException::class])
                ->and(Stable::query()->where('name', $name)->count())->toBe(1);
        }
    });
})->skip(fn (): bool => ! concurrencyTestsEnabled(), 'Set DB_CONNECTION=mysql or pgsql and RUN_CONCURRENCY_TESTS=1.')
    ->group('concurrency');
