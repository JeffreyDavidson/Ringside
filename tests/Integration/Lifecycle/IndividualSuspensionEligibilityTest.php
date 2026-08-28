<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeReinstatedException;
use App\Exceptions\Roster\Individuals\CannotBeSuspendedException;
use App\Lifecycle\Roster\Individuals\IndividualSuspensionEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

describe('individual suspension eligibility', function () {
    test('keeps the suspension predicate aligned with its guard', function (string $factoryState, bool $canBeSuspended) {
        $eligibility = new IndividualSuspensionEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canSuspend($wrestler))->toBe($canBeSuspended);

        if ($canBeSuspended) {
            expect(fn () => $eligibility->ensureCanSuspend($wrestler))
                ->not->toThrow(CannotBeSuspendedException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanSuspend($wrestler))
            ->toThrow(CannotBeSuspendedException::class);
    })->with([
        'employed' => ['employed', true],
        'unemployed' => ['unemployed', false],
        'released' => ['released', false],
        'retired' => ['retired', false],
        'future employment' => ['withFutureEmployment', false],
        'injured' => ['injured', false],
        'suspended' => ['suspended', false],
    ]);

    test('supports each individual roster model', function (string $modelClass) {
        $eligibility = new IndividualSuspensionEligibility();
        $individual = $modelClass::factory()->employed()->create();

        expect($eligibility->canSuspend($individual))->toBeTrue()
            ->and(fn () => $eligibility->ensureCanSuspend($individual))
            ->not->toThrow(CannotBeSuspendedException::class);
    })->with([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ]);

    test('keeps the reinstatement predicate aligned with its guard', function (string $factoryState, bool $canBeReinstated) {
        $eligibility = new IndividualSuspensionEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canReinstate($wrestler))->toBe($canBeReinstated);

        if ($canBeReinstated) {
            expect(fn () => $eligibility->ensureCanReinstate($wrestler))
                ->not->toThrow(CannotBeReinstatedException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanReinstate($wrestler))
            ->toThrow(CannotBeReinstatedException::class);
    })->with([
        'suspended' => ['suspended', true],
        'available' => ['employed', false],
    ]);
});
