<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Lifecycle\StableStatusResolver;

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
