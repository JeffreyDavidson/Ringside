<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\EmploymentStatusResolver;
use App\Models\Roster\Wrestlers\Wrestler;

test('resolves employment status from lifecycle state', function (
    bool $isRetired,
    bool $isEmployed,
    bool $hasFutureEmployment,
    bool $hasEmploymentHistory,
    EmploymentStatus $expectedStatus,
) {
    $status = EmploymentStatusResolver::resolve(
        isRetired: $isRetired,
        isEmployed: $isEmployed,
        hasFutureEmployment: $hasFutureEmployment,
        hasEmploymentHistory: $hasEmploymentHistory,
    );

    expect($status)->toBe($expectedStatus);
})->with([
    'retired takes precedence' => [true, true, true, true, EmploymentStatus::Retired],
    'employed takes precedence over scheduled employment' => [false, true, true, true, EmploymentStatus::Employed],
    'future employment takes precedence over history' => [false, false, true, true, EmploymentStatus::FutureEmployment],
    'released when only employment history exists' => [false, false, false, true, EmploymentStatus::Released],
    'unemployed without employment state' => [false, false, false, false, EmploymentStatus::Unemployed],
]);

test('resolves employment status from an employable model', function () {
    $wrestler = Wrestler::factory()->employed()->create();

    expect(EmploymentStatusResolver::resolveFor($wrestler))->toBe(EmploymentStatus::Employed);
});

test('uses projected employment state when it is available', function () {
    $wrestler = Wrestler::factory()->make([
        'status_current_retirement_exists' => 0,
        'status_current_employment_exists' => 1,
        'status_future_employment_exists' => 0,
        'status_employments_exists' => 1,
    ]);

    expect(EmploymentStatusResolver::resolveFor($wrestler))->toBe(EmploymentStatus::Employed);
});
