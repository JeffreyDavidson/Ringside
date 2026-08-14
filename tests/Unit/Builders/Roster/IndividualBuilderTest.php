<?php

declare(strict_types=1);

use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for IndividualBuilder abstract base class.
 *
 * UNIT TEST SCOPE:
 * - Abstract base class functionality through concrete WrestlerBuilder implementation
 * - Common roster member employment query scopes
 * - Individual roster member retirement filtering
 * - Employment status management for individual entities
 * - Abstract class architecture and contract implementation
 *
 * These tests verify that the IndividualBuilder provides consistent
 * shared functionality for all individual roster member builders (Wrestler, Manager, Referee).
 * Uses WrestlerBuilder as the concrete implementation for testing abstract functionality.
 *
 * @see IndividualBuilder
 */
describe('IndividualBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create wrestlers in all possible states for comprehensive scope testing
        // Using Wrestler model since WrestlerBuilder extends IndividualBuilder
        $this->futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
        $this->suspendedWrestler = Wrestler::factory()->suspended()->create();
        $this->retiredWrestler = Wrestler::factory()->retired()->create();
        $this->releasedWrestler = Wrestler::factory()->released()->create();
        $this->unemployedWrestler = Wrestler::factory()->unemployed()->create();
        $this->injuredWrestler = Wrestler::factory()->injured()->create();

        // Create a single employed wrestler that will be considered "available"
        // (employed, not injured, not suspended, not retired)
        $this->availableWrestler = Wrestler::factory()->employed()->create();
    });

    describe('abstract class architecture', function () {
        test('wrestler builder extends individual builder', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(IndividualBuilder::class);
        });

    });

    describe('employment status scopes', function () {
        test('employed wrestlers can be retrieved', function () {
            // Act
            $employedWrestlers = Wrestler::employed()->get();

            // Assert - Multiple wrestlers have employment (available, suspended, injured)
            // because factories create employment records for wrestlers in different states
            expect($employedWrestlers)
                ->toHaveCount(3)
                ->and($employedWrestlers->contains($this->availableWrestler))->toBeTrue()
                ->and($employedWrestlers->contains($this->suspendedWrestler))->toBeTrue()
                ->and($employedWrestlers->contains($this->injuredWrestler))->toBeTrue();
        });

        test('unemployed wrestlers can be retrieved', function () {
            // Act
            $unemployedWrestlers = Wrestler::unemployed()->get();

            // Assert
            expect($unemployedWrestlers)
                ->toHaveCount(1)
                ->and($unemployedWrestlers->contains($this->unemployedWrestler))->toBeTrue();
        });

        test('released wrestlers can be retrieved', function () {
            // Act
            $releasedWrestlers = Wrestler::released()->get();

            // Assert
            expect($releasedWrestlers)
                ->toHaveCount(1)
                ->and($releasedWrestlers->contains($this->releasedWrestler))->toBeTrue();
        });

        test('future employed wrestlers can be retrieved', function () {
            // Act
            $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

            // Assert
            expect($futureEmployedWrestlers)
                ->toHaveCount(1)
                ->and($futureEmployedWrestlers->contains($this->futureEmployedWrestler))->toBeTrue();
        });
    });

    describe('individual roster member status scopes', function () {
        test('retired wrestlers can be retrieved', function () {
            // Act
            $retiredWrestlers = Wrestler::retired()->get();

            // Assert
            expect($retiredWrestlers)
                ->toHaveCount(1)
                ->and($retiredWrestlers->contains($this->retiredWrestler))->toBeTrue();
        });
    });

    describe('query builder inheritance verification', function () {
        test('query scope methods return correct builder instance', function () {
            // Act
            $builder = Wrestler::employed();

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(IndividualBuilder::class);
        });

        test('chained scopes maintain builder type', function () {
            // Act
            $builder = Wrestler::employed()
                ->whereNotNull('name');

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(IndividualBuilder::class);
        });
    });

});
