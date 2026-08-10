<?php

declare(strict_types=1);

use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Validation\Strategies\IndividualRetirementValidation;
use App\Models\Wrestlers\Wrestler;

/**
 * Integration tests for IndividualRetirementValidation strategy.
 *
 * Tests retirement validation rules with real database models and relationships.
 * Verifies that the strategy correctly identifies when individual entities can/cannot retire.
 *
 * @see IndividualRetirementValidation
 */
describe('IndividualRetirementValidation', function () {
    beforeEach(function () {
        $this->strategy = new IndividualRetirementValidation();
    });

    test('validates retirement rules correctly', function ($factoryState, $shouldPass) {
        $wrestler = Wrestler::factory()->{$factoryState}()->create();

        if ($shouldPass) {
            $this->strategy->validate($wrestler);
            expectValidEntityState($wrestler);
        } else {
            expect(fn () => $this->strategy->validate($wrestler))
                ->toThrow(CannotBeRetiredException::class);
        }
        expect(true)->toBeTrue();
    })->with([
        ['employed', true],
        ['suspended', true],
        ['injured', true],
        ['released', true],
        ['unemployed', false],
        ['withFutureEmployment', false],
        ['retired', false],
    ]);

    describe('supported individual models', function () {
        test('validates managers', function () {
            $manager = Manager::factory()->employed()->create();

            $this->strategy->validate($manager);

            expect($manager->isEmployed())->toBeTrue();
        });

        test('validates referees', function () {
            $referee = Referee::factory()->employed()->create();

            $this->strategy->validate($referee);

            expect($referee->isEmployed())->toBeTrue();
        });

        test('rejects managers with future employment', function () {
            $manager = Manager::factory()->withFutureEmployment()->create();

            expect(fn () => $this->strategy->validate($manager))
                ->toThrow(CannotBeRetiredException::class);
        });
    });
});
