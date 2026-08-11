<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableActivityPeriod;
use Illuminate\Database\QueryException;

test('a stable may have multiple closed activity periods', function () {
    $stable = Stable::factory()->create();

    StableActivityPeriod::factory()
        ->count(2)
        ->for($stable)
        ->create([
            'ended_at' => now(),
        ]);

    expect($stable->activityPeriods()->count())->toBe(2);
});

test('a stable cannot have multiple open activity periods', function () {
    $stable = Stable::factory()->active()->create();

    expect(fn () => StableActivityPeriod::factory()->for($stable)->create())
        ->toThrow(QueryException::class);

    expect($stable->activityPeriods()->whereNull('ended_at')->count())->toBe(1);
});
