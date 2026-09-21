<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;

test('it provides filter options from the enum cases', function (): void {
    expect(EmploymentStatus::filterOptions())->toBe([
        '' => 'All',
        'employed' => 'Employed',
        'future_employment' => 'Awaiting Employment',
        'released' => 'Released',
        'retired' => 'Retired',
        'unemployed' => 'Unemployed',
    ]);
});
