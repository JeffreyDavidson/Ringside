<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeClearedFromInjuryException;
use App\Exceptions\Roster\Individuals\CannotBeInjuredException;
use App\Lifecycle\Roster\Individuals\IndividualInjuryEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

describe('individual injury eligibility', function () {
    test('keeps the injury predicate aligned with its guard', function (string $factoryState, bool $canBeInjured) {
        $eligibility = new IndividualInjuryEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canInjure($wrestler))->toBe($canBeInjured);

        if ($canBeInjured) {
            expect(fn () => $eligibility->ensureCanInjure($wrestler))
                ->not->toThrow(CannotBeInjuredException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanInjure($wrestler))
            ->toThrow(CannotBeInjuredException::class);
    })->with([
        'employed' => ['employed', true],
        'unemployed' => ['unemployed', false],
        'released' => ['released', false],
        'retired' => ['retired', false],
        'future employment' => ['withFutureEmployment', false],
        'suspended' => ['suspended', false],
        'injured' => ['injured', false],
    ]);

    test('supports each individual roster model', function (string $modelClass) {
        $eligibility = new IndividualInjuryEligibility();
        $individual = $modelClass::factory()->employed()->create();

        expect($eligibility->canInjure($individual))->toBeTrue()
            ->and(fn () => $eligibility->ensureCanInjure($individual))
            ->not->toThrow(CannotBeInjuredException::class);
    })->with([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ]);

    test('keeps the injury clearance predicate aligned with its guard', function (string $factoryState, bool $canBeClearedFromInjury) {
        $eligibility = new IndividualInjuryEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canBeClearedFromInjury($wrestler))->toBe($canBeClearedFromInjury);

        if ($canBeClearedFromInjury) {
            expect(fn () => $eligibility->ensureCanBeClearedFromInjury($wrestler))
                ->not->toThrow(CannotBeClearedFromInjuryException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanBeClearedFromInjury($wrestler))
            ->toThrow(CannotBeClearedFromInjuryException::class);
    })->with([
        'injured' => ['injured', true],
        'not injured' => ['employed', false],
    ]);
});
