<?php

declare(strict_types=1);

use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('a stable may have multiple closed activity periods', function () {
    $stable = Stable::factory()->create();

    ActivityPeriod::factory()
        ->count(2)
        ->for($stable, 'activeable')
        ->create([
            'ended_at' => now(),
        ]);

    expect($stable->activityPeriods()->count())->toBe(2);
});

test('a stable cannot have multiple open activity periods', function () {
    $stable = Stable::factory()->active()->create();

    // The nested transaction becomes a savepoint, so PostgreSQL keeps the surrounding
    // test transaction usable after the unique violation.
    expect(fn () => DB::transaction(fn () => ActivityPeriod::factory()->for($stable, 'activeable')->create()))
        ->toThrow(QueryException::class)
        ->and($stable->activityPeriods()->whereNull('ended_at')->count())->toBe(1);
});
