<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\ReleaseAction;
use App\Actions\Referees\SuspendAction;
use App\Actions\Referees\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Date;

test('referee employment can cycle through release and re-employment', function () {
    // Arrange
    $referee = Referee::factory()->unemployed()->create();
    $transitionedAt = Date::now();

    // Act
    resolve(EmployAction::class)
        ->handle($referee, $transitionedAt);
    $employed = freshModel($referee);

    resolve(ReleaseAction::class)
        ->handle($employed, $transitionedAt);
    $released = freshModel($referee);
    $releasedStatus = $released->status;
    $releasedIsBookable = resolve(RosterBookingEligibility::class)->allows($released);

    resolve(EmployAction::class)
        ->handle($released, $transitionedAt);
    $reEmployed = freshModel($referee);

    // Assert
    expect($releasedIsBookable)->toBeFalse()
        ->and($releasedStatus)->toBe(EmploymentStatus::Released)
        ->and(resolve(RosterBookingEligibility::class)->allows($reEmployed))->toBeTrue()
        ->and($reEmployed->status)->toBe(EmploymentStatus::Employed)
        ->and($reEmployed->employments()->count())->toBe(2);
});

test('releasing a suspended referee closes employment and suspension periods', function () {
    // Arrange
    $referee = Referee::factory()->retired()->create();
    $transitionedAt = Date::now();

    // Act
    resolve(UnretireAction::class)
        ->handle($referee, $transitionedAt);
    $employed = freshModel($referee);

    resolve(SuspendAction::class)
        ->handle($employed, $transitionedAt);
    $suspended = freshModel($referee);
    $suspendedHasEmployment = $suspended->currentEmployment()->exists();
    $suspendedHasSuspension = $suspended->currentSuspension()->exists();

    resolve(ReleaseAction::class)
        ->handle($suspended, $transitionedAt);
    $released = freshModel($referee);

    // Assert
    expect($suspendedHasEmployment)->toBeTrue()
        ->and($suspendedHasSuspension)->toBeTrue()
        ->and($released->status)->toBe(EmploymentStatus::Released)
        ->and($released->currentEmployment()->exists())->toBeFalse()
        ->and($released->currentSuspension()->exists())->toBeFalse()
        ->and(resolve(RosterBookingEligibility::class)->allows($released))->toBeFalse();
});
