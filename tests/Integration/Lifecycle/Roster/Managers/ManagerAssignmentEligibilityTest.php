<?php

declare(strict_types=1);

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\Roster\Managers\CannotBeAssignedException;
use App\Lifecycle\Roster\Managers\ManagerAssignmentEligibility;
use App\Models\Roster\Managers\Manager;

it('lets an employed manager be assigned', function (): void {
    // Arrange
    $manager = Manager::factory()
        ->employed()
        ->create();

    // Act
    $canBeAssigned = resolve(ManagerAssignmentEligibility::class)->canBeAssigned($manager);

    // Assert
    expect($canBeAssigned)->toBeTrue();
});

it('refuses a manager who is not employed, retired, suspended or injured', function (string $state, BusinessRuleReason $reason, string $message): void {
    // Arrange
    $manager = Manager::factory()
        ->{$state}()
        ->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);
    $eligibility = resolve(ManagerAssignmentEligibility::class);

    // Act
    $canBeAssigned = $eligibility->canBeAssigned($manager);
    $exception = null;

    try {
        $eligibility->ensureCanBeAssigned($manager);
    } catch (CannotBeAssignedException $thrown) {
        $exception = $thrown;
    }

    // Assert
    expect($canBeAssigned)->toBeFalse()
        ->and($exception?->reason())->toBe($reason)
        ->and($exception?->getMessage())->toBe($message);
})->with([
    'never employed' => ['unemployed', BusinessRuleReason::Unemployed, 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'future employment' => ['withFutureEmployment', BusinessRuleReason::Unemployed, 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'released' => ['released', BusinessRuleReason::Unemployed, 'Manager Bobby Heenan is not currently employed and cannot be assigned.'],
    'retired' => ['retired', BusinessRuleReason::Retired, 'Manager Bobby Heenan is retired and cannot be assigned.'],
    'suspended' => ['suspended', BusinessRuleReason::Suspended, 'Manager Bobby Heenan is suspended and cannot be assigned.'],
    'injured' => ['injured', BusinessRuleReason::Injured, 'Manager Bobby Heenan is injured and cannot be assigned.'],
]);
