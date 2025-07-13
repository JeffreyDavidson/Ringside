<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\HealAction;
use App\Actions\Wrestlers\InjureAction;
use App\Actions\Wrestlers\ReinstateAction;
use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Actions\Wrestlers\SuspendAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for Wrestler Business Logic.
 *
 * UNIT TEST SCOPE:
 * - Isolated business logic verification
 * - Model state changes after actions
 * - Business rule enforcement
 * - Action class behavior in isolation
 * - Domain logic correctness
 *
 * TESTS:
 * - Specific business logic outcomes
 * - Model state transitions
 * - Business rule validation
 * - Action class isolation testing
 */
describe('Wrestler Employment Business Logic', function () {

    test('employing released wrestler changes status to employed', function () {
        $wrestler = Wrestler::factory()->released()->create();

        EmployAction::run($wrestler);

        expect($wrestler->fresh()->isEmployed())->toBeTrue();
        expect($wrestler->fresh()->isReleased())->toBeFalse();
    });

    test('releasing employed wrestler changes status to released', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        ReleaseAction::run($wrestler);

        expect($wrestler->fresh()->isReleased())->toBeTrue();
        expect($wrestler->fresh()->isEmployed())->toBeFalse();
    });

    test('employ action creates employment record', function () {
        $wrestler = Wrestler::factory()->released()->create();
        $initialEmploymentCount = $wrestler->employments()->count();

        EmployAction::run($wrestler);

        expect($wrestler->fresh()->employments()->count())
            ->toBe($initialEmploymentCount + 1);
    });

    test('release action ends current employment', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        // Verify wrestler has active employment
        expect($wrestler->currentEmployment)->not->toBeNull();

        ReleaseAction::run($wrestler);

        // Verify employment is ended
        expect($wrestler->fresh()->currentEmployment)->toBeNull();
    });
});

describe('Wrestler Retirement Business Logic', function () {

    test('retiring active wrestler changes status to retired', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        RetireAction::run($wrestler);

        expect($wrestler->fresh()->isRetired())->toBeTrue();
        expect($wrestler->fresh()->isEmployed())->toBeFalse();
    });

    test('unretiring retired wrestler changes status to active', function () {
        $wrestler = Wrestler::factory()->retired()->create();

        UnretireAction::run($wrestler);

        expect($wrestler->fresh()->isRetired())->toBeFalse();
    });

    test('retire action creates retirement record', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $initialRetirementCount = $wrestler->retirements()->count();

        RetireAction::run($wrestler);

        expect($wrestler->fresh()->retirements()->count())
            ->toBe($initialRetirementCount + 1);
    });

    test('unretire action ends current retirement', function () {
        $wrestler = Wrestler::factory()->retired()->create();

        // Verify wrestler has active retirement
        expect($wrestler->currentRetirement())->not->toBeNull();

        UnretireAction::run($wrestler);

        // Verify retirement is ended
        expect($wrestler->fresh()->currentRetirement()->first())->toBeNull();
    });
});

describe('Wrestler Suspension Business Logic', function () {

    test('suspending employed wrestler changes status to suspended', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        SuspendAction::run($wrestler);

        expect($wrestler->fresh()->isSuspended())->toBeTrue();
        expect($wrestler->fresh()->isEmployed())->toBeTrue(); // Still employed but suspended
    });

    test('reinstating suspended wrestler removes suspension', function () {
        $wrestler = Wrestler::factory()->suspended()->create();

        ReinstateAction::run($wrestler);

        expect($wrestler->fresh()->isSuspended())->toBeFalse();
        expect($wrestler->fresh()->isEmployed())->toBeTrue();
    });

    test('suspend action creates suspension record', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $initialSuspensionCount = $wrestler->suspensions()->count();

        SuspendAction::run($wrestler);

        expect($wrestler->fresh()->suspensions()->count())
            ->toBe($initialSuspensionCount + 1);
    });

    test('reinstate action ends current suspension', function () {
        $wrestler = Wrestler::factory()->suspended()->create();

        // Verify wrestler has active suspension
        expect($wrestler->currentSuspension()->first())->not->toBeNull();

        ReinstateAction::run($wrestler);

        // Verify suspension is ended
        expect($wrestler->fresh()->currentSuspension()->first())->toBeNull();
    });
});

describe('Wrestler Injury Business Logic', function () {

    test('injuring employed wrestler changes status to injured', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        InjureAction::run($wrestler);

        expect($wrestler->fresh()->isInjured())->toBeTrue();
        expect($wrestler->fresh()->isEmployed())->toBeTrue(); // Still employed but injured
    });

    test('healing injured wrestler removes injury', function () {
        $wrestler = Wrestler::factory()->injured()->create();

        HealAction::run($wrestler);

        expect($wrestler->fresh()->isInjured())->toBeFalse();
        expect($wrestler->fresh()->isEmployed())->toBeTrue();
    });

    test('injure action creates injury record', function () {
        $wrestler = Wrestler::factory()->bookable()->create();
        $initialInjuryCount = $wrestler->injuries()->count();

        InjureAction::run($wrestler);

        expect($wrestler->fresh()->injuries()->count())
            ->toBe($initialInjuryCount + 1);
    });

    test('heal action ends current injury', function () {
        $wrestler = Wrestler::factory()->injured()->create();

        // Verify wrestler has active injury
        expect($wrestler->currentInjury()->first())->not->toBeNull();

        HealAction::run($wrestler);

        // Verify injury is ended
        expect($wrestler->fresh()->currentInjury()->first())->toBeNull();
    });
});

describe('Wrestler Status Combination Logic', function () {

    test('injured wrestler cannot be suspended', function () {
        $wrestler = Wrestler::factory()->injured()->create();

        expect($wrestler->canBeSuspended())->toBeFalse();
    });

    test('suspended wrestler cannot be injured', function () {
        $wrestler = Wrestler::factory()->suspended()->create();

        expect($wrestler->canBeInjured())->toBeFalse();
    });

    test('retired wrestler cannot be employed', function () {
        $wrestler = Wrestler::factory()->retired()->create();

        expect($wrestler->canBeEmployed())->toBeFalse();
    });

    test('released wrestler cannot be suspended or injured', function () {
        $wrestler = Wrestler::factory()->released()->create();

        expect($wrestler->canBeSuspended())->toBeFalse();
        expect($wrestler->canBeInjured())->toBeFalse();
    });

    test('bookable wrestler is employed and not injured or suspended', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        expect($wrestler->isEmployed())->toBeTrue();
        expect($wrestler->isInjured())->toBeFalse();
        expect($wrestler->isSuspended())->toBeFalse();
        expect($wrestler->isRetired())->toBeFalse();
        expect($wrestler->isBookable())->toBeTrue();
    });
});

describe('Wrestler Business Rule Validation', function () {

    test('wrestler employment status affects bookability', function () {
        $bookableWrestler = Wrestler::factory()->bookable()->create();
        $releasedWrestler = Wrestler::factory()->released()->create();
        $retiredWrestler = Wrestler::factory()->retired()->create();

        expect($bookableWrestler->isBookable())->toBeTrue();
        expect($releasedWrestler->isBookable())->toBeFalse();
        expect($retiredWrestler->isBookable())->toBeFalse();
    });

    test('injured wrestler is not bookable', function () {
        $wrestler = Wrestler::factory()->injured()->create();

        expect($wrestler->isBookable())->toBeFalse();
    });

    test('suspended wrestler is not bookable', function () {
        $wrestler = Wrestler::factory()->suspended()->create();

        expect($wrestler->isBookable())->toBeFalse();
    });

    test('wrestler can only have one active status of each type', function () {
        $wrestler = Wrestler::factory()->bookable()->create();

        // Verify wrestler starts with exactly one active employment
        expect($wrestler->employments()->whereNull('ended_at')->count())->toBe(1);

        // Attempting to employ an already employed wrestler should fail
        expect(fn () => EmployAction::run($wrestler))
            ->toThrow(CannotBeEmployedException::class);

        // Should still only have one active employment after failed attempt
        expect($wrestler->fresh()->employments()->whereNull('ended_at')->count())->toBe(1);
    });
});
