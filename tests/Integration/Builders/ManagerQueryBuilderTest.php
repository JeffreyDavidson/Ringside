<?php

declare(strict_types=1);

use App\Models\Managers\Manager;

/**
 * Unit tests for ManagerQueryBuilder query scopes and methods.
 *
 * UNIT TEST SCOPE:
 * - Builder class structure and scope functionality  
 * - Employment status filtering scopes (available, futureEmployed, unemployed, released)
 * - Individual roster member status scopes (suspended, retired, injured)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the ManagerQueryBuilder correctly implements
 * all query scopes for filtering managers by their various statuses.
 * Managers are individual roster members who can be injured.
 *
 * @see \App\Builders\Roster\ManagerBuilder
 */
describe('ManagerQueryBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create managers in all possible states for comprehensive scope testing
        $this->futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
        $this->availableManager = Manager::factory()->available()->create();
        $this->suspendedManager = Manager::factory()->suspended()->create();
        $this->retiredManager = Manager::factory()->retired()->create();
        $this->releasedManager = Manager::factory()->released()->create();
        $this->unemployedManager = Manager::factory()->unemployed()->create();
        $this->injuredManager = Manager::factory()->injured()->create();
    });

    describe('availability status scopes', function () {
        test('available managers can be retrieved', function () {
            // Act
            $availableManagers = Manager::available()->get();

            // Assert
            expect($availableManagers)
                ->toHaveCount(1)
                ->collectionHas($this->availableManager);
        });
    });

    describe('employment status scopes', function () {
        test('future employed managers can be retrieved', function () {
            // Act
            $futureEmployedManagers = Manager::futureEmployed()->get();

            // Assert
            expect($futureEmployedManagers)
                ->toHaveCount(1)
                ->collectionHas($this->futureEmployedManager);
        });

        test('unemployed managers can be retrieved', function () {
            // Act
            $unemployedManagers = Manager::unemployed()->get();

            // Assert
            expect($unemployedManagers)
                ->toHaveCount(1)
                ->collectionHas($this->unemployedManager);
        });

        test('released managers can be retrieved', function () {
            // Act
            $releasedManagers = Manager::released()->get();

            // Assert
            expect($releasedManagers)
                ->toHaveCount(1)
                ->collectionHas($this->releasedManager);
        });
    });

    describe('individual roster member status scopes', function () {
        test('suspended managers can be retrieved', function () {
            // Act
            $suspendedManagers = Manager::suspended()->get();

            // Assert
            expect($suspendedManagers)
                ->toHaveCount(1)
                ->collectionHas($this->suspendedManager);
        });

        test('retired managers can be retrieved', function () {
            // Act
            $retiredManagers = Manager::retired()->get();

            // Assert
            expect($retiredManagers)
                ->toHaveCount(1)
                ->collectionHas($this->retiredManager);
        });

        test('injured managers can be retrieved', function () {
            // Act
            $injuredManagers = Manager::injured()->get();

            // Assert
            expect($injuredManagers)
                ->toHaveCount(1)
                ->collectionHas($this->injuredManager);
        });
    });
});
