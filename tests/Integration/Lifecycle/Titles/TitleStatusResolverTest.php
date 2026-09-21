<?php

declare(strict_types=1);

use App\Enums\Titles\TitleStatus;
use App\Lifecycle\Titles\TitleStatusResolver;
use App\Models\Titles\Title;

test('resolves title status from lifecycle state', function (
    bool $isRetired,
    bool $isCurrentlyActive,
    bool $hasFutureActivity,
    bool $hasActivityHistory,
    TitleStatus $expectedStatus,
) {
    $status = TitleStatusResolver::resolve(
        isRetired: $isRetired,
        isCurrentlyActive: $isCurrentlyActive,
        hasFutureActivity: $hasFutureActivity,
        hasActivityHistory: $hasActivityHistory,
    );

    expect($status)->toBe($expectedStatus);
})->with([
    'retired takes precedence over other lifecycle state' => [true, true, true, true, TitleStatus::Retired],
    'active takes precedence over future activity' => [false, true, true, true, TitleStatus::Active],
    'future activity takes precedence over history' => [false, false, true, true, TitleStatus::PendingDebut],
    'inactive with only activity history' => [false, false, false, true, TitleStatus::Inactive],
    'undebuted without activity state' => [false, false, false, false, TitleStatus::Undebuted],
]);

test('resolves title status from a title model', function () {
    $title = Title::factory()->active()->create();

    expect(TitleStatusResolver::resolveFor($title))->toBe(TitleStatus::Active);
});

test('uses projected activity state when it is available', function () {
    $title = Title::factory()->make([
        'status_current_retirement_exists' => 0,
        'status_current_activity_period_exists' => 1,
        'status_future_activity_period_exists' => 0,
        'status_activity_periods_exists' => 1,
    ]);

    expect(TitleStatusResolver::resolveFor($title))->toBe(TitleStatus::Active);
});
