<?php

declare(strict_types=1);

use App\Builders\Concerns\HasAvailabilityScopes;
use App\Builders\Concerns\HasRetirementScopes;
use App\Builders\Contracts\HasAvailability;
use App\Builders\Contracts\HasEmployment;
use App\Builders\Contracts\HasRetirement;
use App\Builders\Contracts\HasSuspension;
use App\Builders\Roster\SingleRosterMemberBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Models\Wrestlers\Wrestler;

/**
 * Unit tests for SingleRosterMemberBuilder abstract base class.
 *
 * UNIT TEST SCOPE:
 * - Abstract base class functionality through concrete WrestlerBuilder implementation
 * - Common roster member query scopes (available, unavailable, employed, unemployed)
 * - Individual roster member status filtering (injured, suspended, retired)
 * - Employment status management for individual entities
 * - Abstract class architecture and contract implementation
 *
 * These tests verify that the SingleRosterMemberBuilder provides consistent
 * shared functionality for all individual roster member builders (Wrestler, Manager, Referee).
 * Uses WrestlerBuilder as the concrete implementation for testing abstract functionality.
 *
 * @see \App\Builders\Roster\SingleRosterMemberBuilder
 */
describe('SingleRosterMemberBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create wrestlers in all possible states for comprehensive scope testing
        // Using Wrestler model since WrestlerBuilder extends SingleRosterMemberBuilder
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
        test('wrestler builder extends single roster member builder', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(SingleRosterMemberBuilder::class);
        });

        test('implements all required contracts', function () {
            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(HasAvailability::class);
            expect($builder)->toBeInstanceOf(HasEmployment::class);
            expect($builder)->toBeInstanceOf(HasRetirement::class);
            expect($builder)->toBeInstanceOf(HasSuspension::class);
        });

        test('uses required traits', function () {
            // Act & Assert
            expect(SingleRosterMemberBuilder::class)->usesTrait(HasAvailabilityScopes::class);
            expect(SingleRosterMemberBuilder::class)->usesTrait(HasRetirementScopes::class);
        });
    });

    describe('availability status scopes', function () {
        test('available wrestlers can be retrieved', function () {
            // Act
            $availableWrestlers = Wrestler::available()->get();

            // Assert - Available means employed, not injured, not suspended, not retired
            expect($availableWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->availableWrestler);
        });

        test('unavailable wrestlers can be retrieved', function () {
            // Act
            $unavailableWrestlers = Wrestler::unavailable()->get();

            // Assert - Unavailable includes injured, suspended, retired, and unemployed
            expect($unavailableWrestlers->pluck('id'))->toContain($this->injuredWrestler->id);
            expect($unavailableWrestlers->pluck('id'))->toContain($this->suspendedWrestler->id);
            expect($unavailableWrestlers->pluck('id'))->toContain($this->retiredWrestler->id);
            expect($unavailableWrestlers->pluck('id'))->toContain($this->unemployedWrestler->id);
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
                ->collectionHas($this->availableWrestler)
                ->collectionHas($this->suspendedWrestler)
                ->collectionHas($this->injuredWrestler);
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

        test('future employed wrestlers can be retrieved', function () {
            // Act
            $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

            // Assert
            expect($futureEmployedWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->futureEmployedWrestler);
        });
    });

    describe('individual roster member status scopes', function () {
        test('injured wrestlers can be retrieved', function () {
            // Act
            $injuredWrestlers = Wrestler::injured()->get();

            // Assert
            expect($injuredWrestlers)
                ->toHaveCount(1)
                ->collectionHas($this->injuredWrestler);
        });

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
    });

    describe('date-based availability scopes', function () {
        test('base class availableOn method signature exists', function () {
            // Arrange
            $testDate = now()->addWeek();
            $builder = Wrestler::query();

            // Act & Assert - Verify the method exists on the base class
            expect(method_exists($builder, 'availableOn'))->toBeTrue();
            
            // Note: WrestlerBuilder overrides this method with match-booking logic,
            // so we test method existence rather than full functionality to avoid
            // database table dependencies in unit tests.
        });
    });

    describe('query builder inheritance verification', function () {
        test('query scope methods return correct builder instance', function () {
            // Act
            $builder = Wrestler::available();

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(SingleRosterMemberBuilder::class);
        });

        test('chained scopes maintain builder type', function () {
            // Act
            $builder = Wrestler::available()
                ->employed()
                ->whereNotNull('name');

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(SingleRosterMemberBuilder::class);
        });
    });

    describe('shared functionality consistency', function () {
        test('availability scope combines all required conditions', function () {
            // Arrange
            $builder = Wrestler::available();

            // Act
            $sql = $builder->toSql();
            $bindings = $builder->getBindings();

            // Assert - Verify that available() combines employment, injury, suspension, and retirement checks
            expect($sql)->toContain('where exists');
            expect($sql)->toContain('and not exists');
            expect($bindings)->toBeArray();
        });

        test('unavailable scope uses proper OR logic for exclusions', function () {
            // Arrange
            $builder = Wrestler::unavailable();

            // Act
            $sql = $builder->toSql();

            // Assert - Verify OR logic for multiple unavailability conditions
            expect($sql)->toContain('where (');
            expect($sql)->toContain('or ');
        });
    });
});