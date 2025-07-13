<?php

declare(strict_types=1);

use App\Models\Wrestlers\Wrestler;

/**
 * Integration tests for WrestlerQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder integration with Model, Database, and Factory layers
 * - Business logic validation through complete data pipeline
 * - Employment status filtering with real data (bookable, futureEmployed, unemployed, released)
 * - Status-based filtering with database persistence (suspended, retired, injured)
 * - Query scope accuracy with actual database results
 *
 * These tests verify that the WrestlerQueryBuilder correctly integrates
 * with the data layer and returns proper business outcomes.
 *
 * @see \App\Builders\Roster\WrestlerBuilder
 */
describe('WrestlerQueryBuilder Integration Tests', function () {
    beforeEach(function () {
        // Create wrestlers in all possible states for comprehensive scope testing
        $this->futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
        $this->bookableWrestler = Wrestler::factory()->bookable()->create();
        $this->suspendedWrestler = Wrestler::factory()->suspended()->create();
        $this->retiredWrestler = Wrestler::factory()->retired()->create();
        $this->releasedWrestler = Wrestler::factory()->released()->create();
        $this->unemployedWrestler = Wrestler::factory()->unemployed()->create();
        $this->injuredWrestler = Wrestler::factory()->injured()->create();
    });

    describe('employment status scopes', function () {
        test('bookable wrestlers can be retrieved', function () {
            // Act
            $bookableWrestlers = Wrestler::bookable()->get();

            // Assert
            expect($bookableWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->bookableWrestler);
        });

        test('future employed wrestlers can be retrieved', function () {
            // Act
            $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

            // Assert
            expect($futureEmployedWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->futureEmployedWrestler);
        });

        test('unemployed wrestlers can be retrieved', function () {
            // Act
            $unemployedWrestlers = Wrestler::unemployed()->get();

            // Assert
            expect($unemployedWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->unemployedWrestler);
        });

        test('released wrestlers can be retrieved', function () {
            // Act
            $releasedWrestlers = Wrestler::released()->get();

            // Assert
            expect($releasedWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->releasedWrestler);
        });
    });

    describe('status-based scopes', function () {
        test('suspended wrestlers can be retrieved', function () {
            // Act
            $suspendedWrestlers = Wrestler::suspended()->get();

            // Assert
            expect($suspendedWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->suspendedWrestler);
        });

        test('retired wrestlers can be retrieved', function () {
            // Act
            $retiredWrestlers = Wrestler::retired()->get();

            // Assert
            expect($retiredWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->retiredWrestler);
        });

        test('injured wrestlers can be retrieved', function () {
            // Act
            $injuredWrestlers = Wrestler::injured()->get();

            // Assert
            expect($injuredWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->injuredWrestler);
        });
    });
});