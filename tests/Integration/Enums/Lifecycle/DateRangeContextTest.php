<?php

declare(strict_types=1);

use App\Enums\Lifecycle\DateRangeContext;
use App\Enums\Lifecycle\LifecycleDimension;

test('each date range context has a label', function (DateRangeContext $context, string $label): void {
    // Act
    $result = $context->label();

    // Assert
    expect($result)->toBe($label);
})->with([
    'stable activity' => [DateRangeContext::StableActivity, 'stable activity'],
    'stable establishment' => [DateRangeContext::StableEstablishment, 'stable establishment'],
    'stable membership removal' => [DateRangeContext::StableMembershipRemoval, 'stable membership removal'],
    'activity end' => [DateRangeContext::ActivityEnd, 'activity end'],
    'activity period' => [DateRangeContext::ActivityPeriod, 'activity period'],
    'deletion period' => [DateRangeContext::DeletionPeriod, 'deletion period'],
    'employment period' => [DateRangeContext::EmploymentPeriod, 'employment period'],
    'injury period' => [DateRangeContext::InjuryPeriod, 'injury period'],
    'retirement period' => [DateRangeContext::RetirementPeriod, 'retirement period'],
    'suspension period' => [DateRangeContext::SuspensionPeriod, 'suspension period'],
]);

test('each lifecycle dimension has a period context', function (LifecycleDimension $dimension, DateRangeContext $context): void {
    // Act
    $result = DateRangeContext::forPeriod($dimension);

    // Assert
    expect($result)->toBe($context);
})->with([
    'activity' => [LifecycleDimension::Activity, DateRangeContext::ActivityPeriod],
    'deletion' => [LifecycleDimension::Deletion, DateRangeContext::DeletionPeriod],
    'employment' => [LifecycleDimension::Employment, DateRangeContext::EmploymentPeriod],
    'injury' => [LifecycleDimension::Injury, DateRangeContext::InjuryPeriod],
    'retirement' => [LifecycleDimension::Retirement, DateRangeContext::RetirementPeriod],
    'suspension' => [LifecycleDimension::Suspension, DateRangeContext::SuspensionPeriod],
]);
