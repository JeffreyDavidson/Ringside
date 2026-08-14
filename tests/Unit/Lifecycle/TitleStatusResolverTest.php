<?php

declare(strict_types=1);

use App\Enums\Titles\TitleStatus;
use App\Lifecycle\TitleStatusResolver;

test('resolves title status from lifecycle state', function (
    bool $isCurrentlyActive,
    bool $hasFutureActivity,
    bool $hasActivityHistory,
    TitleStatus $expectedStatus,
) {
    $status = TitleStatusResolver::resolve(
        isCurrentlyActive: $isCurrentlyActive,
        hasFutureActivity: $hasFutureActivity,
        hasActivityHistory: $hasActivityHistory,
    );

    expect($status)->toBe($expectedStatus);
})->with([
    'active takes precedence over future activity' => [true, true, true, TitleStatus::Active],
    'future activity takes precedence over history' => [false, true, true, TitleStatus::PendingDebut],
    'inactive with only activity history' => [false, false, true, TitleStatus::Inactive],
    'undebuted without activity state' => [false, false, false, TitleStatus::Undebuted],
]);
