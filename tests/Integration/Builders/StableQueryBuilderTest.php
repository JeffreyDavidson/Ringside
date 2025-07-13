<?php

declare(strict_types=1);

use App\Models\Stables\Stable;

/**
 * Unit tests for StableQueryBuilder query scopes and methods.
 *
 * UNIT TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Activity period filtering scopes (active, inactive, unactivated, withFutureActivation)
 * - Status-based filtering scopes (retired)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the StableQueryBuilder correctly implements
 * all query scopes for filtering stables by their various statuses.
 * Note: Stables use activity periods rather than employment for status tracking.
 *
 * @see \App\Builders\Stables\StableBuilder
 */
describe('StableQueryBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create stables in all possible states for comprehensive scope testing
        $this->activeStable = Stable::factory()->active()->create();
        $this->futureActivatedStable = Stable::factory()->withFutureActivation()->create();
        $this->inactiveStable = Stable::factory()->inactive()->create();
        $this->retiredStable = Stable::factory()->retired()->create();
        $this->unactivatedStable = Stable::factory()->unactivated()->create();
    });

    describe('activity period scopes', function () {
        test('active stables can be retrieved', function () {
            // Act
            $activeStables = Stable::active()->get();

            // Assert
            expect($activeStables)
                ->toHaveCount(1)
                ->collectionHas($this->activeStable);
        });

        test('future activated stables can be retrieved', function () {
            // Act
            $futureActivatedStables = Stable::withFutureActivation()->get();

            // Assert
            expect($futureActivatedStables)
                ->toHaveCount(1)
                ->collectionHas($this->futureActivatedStable);
        });

        test('inactive stables can be retrieved', function () {
            // Act
            $inactiveStables = Stable::inactive()->get();

            // Assert
            expect($inactiveStables)
                ->toHaveCount(1)
                ->collectionHas($this->inactiveStable);
        });

        test('unactivated stables can be retrieved', function () {
            // Act
            $unactivatedStables = Stable::unactivated()->get();

            // Assert
            expect($unactivatedStables)
                ->toHaveCount(1)
                ->collectionHas($this->unactivatedStable);
        });
    });

    describe('status-based scopes', function () {
        test('retired stables can be retrieved', function () {
            // Act
            $retiredStables = Stable::retired()->get();

            // Assert
            expect($retiredStables)
                ->toHaveCount(1)
                ->collectionHas($this->retiredStable);
        });
    });
});
