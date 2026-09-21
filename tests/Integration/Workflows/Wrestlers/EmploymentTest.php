<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ClearFromInjuryAction;
use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\Individuals\IndividualEmploymentEligibility;
use App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Lifecycle\Roster\RosterBookingEligibility;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

test('wrestler career transitions retain complete lifecycle history', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();

    // Act
    resolve(EmployAction::class)
        ->handle($wrestler, Date::now()->subMonths(12));
    resolve(SuspendAction::class)
        ->handle(freshModel($wrestler), Date::now()->subMonths(10));
    resolve(ReinstateAction::class)
        ->handle(freshModel($wrestler), Date::now()->subMonths(8));
    resolve(InjureAction::class)
        ->handle(freshModel($wrestler), Date::now()->subMonths(6));
    resolve(ClearFromInjuryAction::class)
        ->handle(freshModel($wrestler), Date::now()->subMonths(4));
    resolve(RetireAction::class)
        ->handle(freshModel($wrestler), Date::now()->subMonths(2));
    resolve(UnretireAction::class)
        ->handle(freshModel($wrestler), Date::now());
    $activeWrestler = freshModel($wrestler);

    // Assert
    expect($activeWrestler->status)->toBe(EmploymentStatus::Employed)
        ->and($activeWrestler->currentEmployment()->exists())->toBeTrue()
        ->and($activeWrestler->currentSuspension()->exists())->toBeFalse()
        ->and($activeWrestler->currentInjury()->exists())->toBeFalse()
        ->and($activeWrestler->currentRetirement()->exists())->toBeFalse()
        ->and($activeWrestler->employments()->count())->toBe(2)
        ->and($activeWrestler->suspensions()->count())->toBe(1)
        ->and($activeWrestler->injuries()->count())->toBe(1)
        ->and($activeWrestler->retirements()->count())->toBe(1);
});

test('availability transitions update wrestler booking eligibility', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();
    $bookingEligibility = resolve(RosterBookingEligibility::class);
    $transitionedAt = Date::now();

    // Act and Assert
    expect($bookingEligibility->allows($wrestler))->toBeFalse();

    resolve(EmployAction::class)
        ->handle($wrestler, $transitionedAt);
    expect($bookingEligibility->allows(freshModel($wrestler)))->toBeTrue();

    resolve(InjureAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    expect($bookingEligibility->allows(freshModel($wrestler)))->toBeFalse();

    resolve(ClearFromInjuryAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    expect($bookingEligibility->allows(freshModel($wrestler)))->toBeTrue();

    resolve(SuspendAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    expect($bookingEligibility->allows(freshModel($wrestler)))->toBeFalse();

    resolve(ReinstateAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    expect($bookingEligibility->allows(freshModel($wrestler)))->toBeTrue();

    resolve(ReleaseAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    expect($bookingEligibility->allows(freshModel($wrestler)))->toBeFalse();
});

test('injury and suspension remain mutually exclusive wrestler states', function () {
    // Arrange
    $wrestler = Wrestler::factory()->bookable()->create();
    $transitionedAt = Date::now();

    // Act
    resolve(InjureAction::class)
        ->handle($wrestler, $transitionedAt);
    $injured = freshModel($wrestler);
    $injuredCanBeSuspended = resolve(IndividualSuspensionEligibility::class)->canSuspend($injured);
    $injuredHasInjury = $injured->currentInjury()->exists();
    $injuredHasSuspension = $injured->currentSuspension()->exists();

    resolve(ClearFromInjuryAction::class)
        ->handle($injured, $transitionedAt);
    resolve(SuspendAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    $suspended = freshModel($wrestler);

    // Assert
    expect($injuredCanBeSuspended)->toBeFalse()
        ->and($injuredHasInjury)->toBeTrue()
        ->and($injuredHasSuspension)->toBeFalse()
        ->and(resolve(IndividualInjuryEligibility::class)->canInjure($suspended))->toBeFalse()
        ->and($suspended->currentInjury()->exists())->toBeFalse()
        ->and($suspended->currentSuspension()->exists())->toBeTrue();
});

test('released wrestlers can be re-employed but retired wrestlers cannot', function () {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create();
    $transitionedAt = Date::now();

    // Act
    resolve(EmployAction::class)
        ->handle($wrestler, $transitionedAt);
    resolve(ReleaseAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    $released = freshModel($wrestler);
    $releasedStatus = $released->status;

    resolve(EmployAction::class)
        ->handle($released, $transitionedAt);
    resolve(RetireAction::class)
        ->handle(freshModel($wrestler), $transitionedAt);
    $retired = freshModel($wrestler);

    // Assert
    expect($releasedStatus)->toBe(EmploymentStatus::Released)
        ->and($retired->status)->toBe(EmploymentStatus::Retired)
        ->and(resolve(IndividualEmploymentEligibility::class)->canEmploy($retired))->toBeFalse()
        ->and($retired->employments()->count())->toBe(2);
});
