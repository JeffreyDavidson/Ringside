<?php

declare(strict_types=1);

use App\Lifecycle\LifecycleStateReader;
use App\Models\Roster\Stables\Stable;

test('reads a projected lifecycle boolean when the attribute is present', function () {
    $stable = Stable::factory()->make([
        'status_current_activity_period_exists' => 1,
    ]);

    expect(LifecycleStateReader::readProjectedBoolean(
        $stable,
        'status_current_activity_period_exists',
        fn (): bool => false,
    ))->toBeTrue();
});

test('falls back to the relationship check when a projection is absent', function () {
    $stable = Stable::factory()->make();

    expect(LifecycleStateReader::readProjectedBoolean(
        $stable,
        'status_current_activity_period_exists',
        fn (): bool => true,
    ))->toBeTrue();
});
