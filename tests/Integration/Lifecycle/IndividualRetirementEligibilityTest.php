<?php

declare(strict_types=1);

use App\Exceptions\Roster\Individuals\CannotBeRetiredException;
use App\Exceptions\Roster\Individuals\CannotBeUnretiredException;
use App\Lifecycle\IndividualRetirementEligibility;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

describe('individual retirement eligibility', function () {
    test('keeps the retirement predicate aligned with its guard', function (string $factoryState, bool $canBeRetired) {
        $eligibility = new IndividualRetirementEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canRetire($wrestler))->toBe($canBeRetired);

        if ($canBeRetired) {
            expect(fn () => $eligibility->ensureCanRetire($wrestler))
                ->not->toThrow(CannotBeRetiredException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanRetire($wrestler))
            ->toThrow(CannotBeRetiredException::class);
    })->with([
        'employed' => ['employed', true],
        'suspended' => ['suspended', true],
        'injured' => ['injured', true],
        'released' => ['released', true],
        'unemployed' => ['unemployed', false],
        'future employment' => ['withFutureEmployment', false],
        'retired' => ['retired', false],
    ]);

    test('supports each individual roster model', function (string $modelClass) {
        $eligibility = new IndividualRetirementEligibility();
        $individual = $modelClass::factory()->employed()->create();

        expect($eligibility->canRetire($individual))->toBeTrue()
            ->and(fn () => $eligibility->ensureCanRetire($individual))
            ->not->toThrow(CannotBeRetiredException::class);
    })->with([
        Wrestler::class,
        Manager::class,
        Referee::class,
    ]);

    test('rejects unretiring a deleted retired individual consistently', function () {
        $eligibility = new IndividualRetirementEligibility();
        $wrestler = Wrestler::factory()->retired()->create();
        $wrestler->delete();

        expect($eligibility->canUnretire($wrestler))->toBeFalse()
            ->and(fn () => $eligibility->ensureCanUnretire($wrestler))
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::deleted($wrestler)->getMessage(),
            );
    });

    test('keeps the unretirement predicate aligned with its guard', function (string $factoryState, bool $canBeUnretired) {
        $eligibility = new IndividualRetirementEligibility();
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        expect($eligibility->canUnretire($wrestler))->toBe($canBeUnretired);

        if ($canBeUnretired) {
            expect(fn () => $eligibility->ensureCanUnretire($wrestler))
                ->not->toThrow(CannotBeUnretiredException::class);

            return;
        }

        expect(fn () => $eligibility->ensureCanUnretire($wrestler))
            ->toThrow(
                CannotBeUnretiredException::class,
                CannotBeUnretiredException::notRetired($wrestler)->getMessage(),
            );
    })->with([
        'retired' => ['retired', true],
        'not retired' => ['employed', false],
    ]);
});
