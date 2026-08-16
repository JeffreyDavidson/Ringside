<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeEmployedException;
use App\Exceptions\Roster\Individuals\CannotBeReleasedException;
use App\Lifecycle\IndividualEmploymentEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

describe('individual employment eligibility', function () {
    test('keeps the employment predicate aligned with its guard', function (string $factoryState, bool $canBeEmployed) {
        $eligibility = new IndividualEmploymentEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canEmploy($wrestler))->toBe($canBeEmployed);

        if ($canBeEmployed) {
            expect(fn () => $eligibility->ensureCanEmploy($wrestler))
                ->not->toThrow(CannotBeEmployedException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanEmploy($wrestler))
            ->toThrow(CannotBeEmployedException::class);
    })->with([
        'unemployed' => ['unemployed', true],
        'released' => ['released', true],
        'employed' => ['employed', false],
        'future employment' => ['withFutureEmployment', false],
        'retired' => ['retired', false],
    ]);

    test('supports each individual roster model', function (string $modelClass) {
        $eligibility = new IndividualEmploymentEligibility();
        $individual = $modelClass::factory()->unemployed()->create();

        expect($eligibility->canEmploy($individual))->toBeTrue()
            ->and(fn () => $eligibility->ensureCanEmploy($individual))
            ->not->toThrow(CannotBeEmployedException::class);
    })->with([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ]);

    test('keeps the release predicate aligned with its guard', function (string $factoryState, bool $canBeReleased) {
        $eligibility = new IndividualEmploymentEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canRelease($wrestler))->toBe($canBeReleased);

        if ($canBeReleased) {
            expect(fn () => $eligibility->ensureCanRelease($wrestler))
                ->not->toThrow(CannotBeReleasedException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanRelease($wrestler))
            ->toThrow(CannotBeReleasedException::class);
    })->with([
        'employed' => ['employed', true],
        'suspended' => ['suspended', true],
        'injured' => ['injured', true],
        'unemployed' => ['unemployed', false],
        'released' => ['released', false],
        'future employment' => ['withFutureEmployment', false],
        'retired' => ['retired', false],
    ]);
});
