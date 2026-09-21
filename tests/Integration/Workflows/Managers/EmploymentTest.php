<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Actions\Managers\ReleaseAction;
use App\Actions\Managers\SuspendAction;
use App\Actions\Managers\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\Date;

test('manager employment can cycle through release and re-employment', function () {
    // Arrange
    $manager = Manager::factory()->unemployed()->create();
    $transitionedAt = Date::now();

    // Act
    resolve(EmployAction::class)
        ->handle($manager, $transitionedAt);
    $employed = freshModel($manager);

    resolve(ReleaseAction::class)
        ->handle($employed, $transitionedAt);
    $released = freshModel($manager);
    $releasedStatus = $released->status;
    $releasedHasEmployment = $released->currentEmployment()->exists();

    resolve(EmployAction::class)
        ->handle($released, $transitionedAt);
    $reEmployed = freshModel($manager);

    // Assert
    expect($releasedStatus)->toBe(EmploymentStatus::Released)
        ->and($releasedHasEmployment)->toBeFalse()
        ->and($reEmployed->status)->toBe(EmploymentStatus::Employed)
        ->and($reEmployed->currentEmployment()->exists())->toBeTrue()
        ->and($reEmployed->employments()->count())->toBe(2);
});

test('releasing a suspended manager closes employment and suspension periods', function () {
    // Arrange
    $manager = Manager::factory()->retired()->create();
    $transitionedAt = Date::now();

    // Act
    resolve(UnretireAction::class)
        ->handle($manager, $transitionedAt);
    $employed = freshModel($manager);

    resolve(SuspendAction::class)
        ->handle($employed, $transitionedAt);
    $suspended = freshModel($manager);
    $suspendedHasEmployment = $suspended->currentEmployment()->exists();
    $suspendedHasSuspension = $suspended->currentSuspension()->exists();

    resolve(ReleaseAction::class)
        ->handle($suspended, $transitionedAt);
    $released = freshModel($manager);

    // Assert
    expect($suspendedHasEmployment)->toBeTrue()
        ->and($suspendedHasSuspension)->toBeTrue()
        ->and($released->status)->toBe(EmploymentStatus::Released)
        ->and($released->currentEmployment()->exists())->toBeFalse()
        ->and($released->currentSuspension()->exists())->toBeFalse();
});
