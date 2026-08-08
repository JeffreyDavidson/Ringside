<?php

declare(strict_types=1);

use App\Builders\Roster\WrestlerBuilder;
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
 * @see WrestlerBuilder
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
                ->and($bookableWrestlers->contains($this->bookableWrestler))->toBeTrue();
        });

        test('future employed wrestlers can be retrieved', function () {
            // Act
            $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

            // Assert
            expect($futureEmployedWrestlers)
                ->toHaveCount(1)
                ->and($futureEmployedWrestlers->contains($this->futureEmployedWrestler))->toBeTrue();
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
    });

    describe('status-based scopes', function () {
        test('suspended wrestlers can be retrieved', function () {
            // Act
            $suspendedWrestlers = Wrestler::suspended()->get();

            // Assert
            expect($suspendedWrestlers)
                ->toHaveCount(1)
                ->and($suspendedWrestlers->contains($this->suspendedWrestler))->toBeTrue();
        });

        test('retired wrestlers can be retrieved', function () {
            // Act
            $retiredWrestlers = Wrestler::retired()->get();

            // Assert
            expect($retiredWrestlers)
                ->toHaveCount(1)
                ->and($retiredWrestlers->contains($this->retiredWrestler))->toBeTrue();
        });

        test('injured wrestlers can be retrieved', function () {
            // Act
            $injuredWrestlers = Wrestler::injured()->get();

            // Assert
            expect($injuredWrestlers)
                ->toHaveCount(1)
                ->and($injuredWrestlers->contains($this->injuredWrestler))->toBeTrue();
        });
    });
});
