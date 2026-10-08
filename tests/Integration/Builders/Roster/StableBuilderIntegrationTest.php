<?php

declare(strict_types=1);

use App\Builders\Roster\StableBuilder;
use App\Models\Roster\Stables\Stable;

/**
 * Integration tests for StableQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Activity period filtering scopes (active, inactive, unactivated, withFutureActivation)
 * - Status-based filtering scopes (retired)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the StableQueryBuilder correctly implements
 * all query scopes for filtering stables by their various statuses.
 * Note: Stables use activity periods rather than employment for status tracking.
 *
 * @see StableBuilder
 */
describe('StableQueryBuilder Integration Tests', function () {
    describe('activity period scopes', function () {
        test('active stables can be retrieved', function () {
            $activeStable = Stable::factory()->active()->create();
            Stable::factory()->withFutureActivation()->create();
            Stable::factory()->inactive()->create();
            Stable::factory()->retired()->create();
            Stable::factory()->unactivated()->create();

            // Act
            $activeStables = Stable::query()->established()->get();

            // Assert
            expect($activeStables)
                ->toHaveCount(1)
                ->and($activeStables->contains($activeStable))->toBeTrue();
        });

        test('future activated stables can be retrieved', function () {
            Stable::factory()->active()->create();
            $futureActivatedStable = Stable::factory()->withFutureActivation()->create();
            Stable::factory()->inactive()->create();
            Stable::factory()->retired()->create();
            Stable::factory()->unactivated()->create();

            // Act
            $futureActivatedStables = Stable::query()->withFutureEstablishment()->get();

            // Assert
            expect($futureActivatedStables)
                ->toHaveCount(1)
                ->and($futureActivatedStables->contains($futureActivatedStable))->toBeTrue();
        });

        test('inactive stables can be retrieved', function () {
            Stable::factory()->active()->create();
            Stable::factory()->withFutureActivation()->create();
            $inactiveStable = Stable::factory()->inactive()->create();
            Stable::factory()->retired()->create();
            Stable::factory()->unactivated()->create();

            // Act
            $inactiveStables = Stable::query()->disbanded()->get();

            // Assert
            expect($inactiveStables)
                ->toHaveCount(1)
                ->and($inactiveStables->contains($inactiveStable))->toBeTrue();
        });

        test('unactivated stables can be retrieved', function () {
            Stable::factory()->active()->create();
            Stable::factory()->withFutureActivation()->create();
            Stable::factory()->inactive()->create();
            Stable::factory()->retired()->create();
            $unactivatedStable = Stable::factory()->unactivated()->create();

            // Act
            $unactivatedStables = Stable::query()->unestablished()->get();

            // Assert
            expect($unactivatedStables)
                ->toHaveCount(1)
                ->and($unactivatedStables->contains($unactivatedStable))->toBeTrue();
        });
    });

});
