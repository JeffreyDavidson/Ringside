<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\EmploymentStatusResolver;

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
