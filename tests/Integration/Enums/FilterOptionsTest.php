<?php

declare(strict_types=1);

use App\Enums\EventStatus;
use App\Enums\Shared\EmploymentStatus;
use App\Enums\Titles\TitleStatus;
use App\Enums\Users\UserStatus;

test('event statuses expose filter options in case order', function (): void {
    expect(EventStatus::filterOptions())->toBe([
        '' => __('core.all'),
        'past' => 'Past',
        'scheduled' => 'Scheduled',
        'unscheduled' => 'Unscheduled',
    ]);
});

test('employment statuses expose filter options in case order', function (): void {
    expect(EmploymentStatus::filterOptions())->toBe([
        '' => __('core.all'),
        'employed' => 'Employed',
        'future_employment' => 'Awaiting Employment',
        'released' => 'Released',
        'retired' => 'Retired',
        'unemployed' => 'Unemployed',
    ]);
});

test('title statuses expose filter options in case order', function (): void {
    expect(TitleStatus::filterOptions())->toBe([
        '' => __('core.all'),
        'undebuted' => 'Not Yet Debuted',
        'pending_debut' => 'Schedule to Debut',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'retired' => 'Retired',
    ]);
});

test('user statuses expose filter options in case order', function (): void {
    expect(UserStatus::filterOptions())->toBe([
        '' => __('core.all'),
        'unverified' => 'Unverified',
        'active' => 'Active',
        'inactive' => 'Inactive',
    ]);
});
