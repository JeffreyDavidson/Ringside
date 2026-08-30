<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Lifecycle\Roster\Stables\StableStatusResolver;
use App\Models\Roster\Stables\Stable;

test('resolves stable status from lifecycle state', function (
    bool $isRetired,
    bool $isCurrentlyActive,
    bool $hasFutureActivity,
    bool $hasActivityHistory,
    StableStatus $expectedStatus,
) {
    $status = StableStatusResolver::resolve(
        isRetired: $isRetired,
        isCurrentlyActive: $isCurrentlyActive,
        hasFutureActivity: $hasFutureActivity,
        hasActivityHistory: $hasActivityHistory,
    );

    expect($status)->toBe($expectedStatus);
})->with([
    'retired takes precedence' => [true, true, true, true, StableStatus::Retired],
    'active takes precedence over future activity' => [false, true, true, true, StableStatus::Active],
    'future activity takes precedence over history' => [false, false, true, true, StableStatus::PendingEstablishment],
    'inactive with only activity history' => [false, false, false, true, StableStatus::Inactive],
    'unformed without lifecycle state' => [false, false, false, false, StableStatus::Unformed],
]);

test('resolves stable status from a stable model', function () {
    $stable = Stable::factory()->active()->create();

    expect(StableStatusResolver::resolveFor($stable))->toBe(StableStatus::Active);
});

test('uses projected activity state when it is available', function () {
    $stable = Stable::factory()->make([
        'status_current_retirement_exists' => 0,
        'status_current_activity_period_exists' => 1,
        'status_future_activity_period_exists' => 0,
        'status_activity_periods_exists' => 1,
    ]);

    expect(StableStatusResolver::resolveFor($stable))->toBe(StableStatus::Active);
});
