<?php

declare(strict_types=1);

use App\Builders\Roster\WrestlerBuilder;
use App\Models\Roster\Wrestlers\Wrestler;

/**
 * Integration tests for WrestlerQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder integration with Model, Database, and Factory layers
 * - Business logic validation through complete data pipeline
 * - Employment status filtering with real data (available, futureEmployed, unemployed, released)
 * - Status-based filtering with database persistence (suspended, retired, injured)
 * - Query scope accuracy with actual database results
 *
 * These tests verify that the WrestlerQueryBuilder correctly integrates
 * with the data layer and returns proper business outcomes.
 *
 * @see WrestlerBuilder
 */
describe('WrestlerQueryBuilder Integration Tests', function () {
    describe('employment status scopes', function () {
        test('future employed wrestlers can be retrieved', function () {
            $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->bookable()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();

            // Act
            $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

            // Assert
            expect($futureEmployedWrestlers)
                ->toHaveCount(1)
                ->and($futureEmployedWrestlers->contains($futureEmployedWrestler))->toBeTrue();
        });

        test('unemployed wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->bookable()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();

            // Act
            $unemployedWrestlers = Wrestler::unemployed()->get();

            // Assert
            expect($unemployedWrestlers)
                ->toHaveCount(1)
                ->and($unemployedWrestlers->contains($unemployedWrestler))->toBeTrue();
        });

        test('released wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->bookable()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            $releasedWrestler = Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();

            // Act
            $releasedWrestlers = Wrestler::released()->get();

            // Assert
            expect($releasedWrestlers)
                ->toHaveCount(1)
                ->and($releasedWrestlers->contains($releasedWrestler))->toBeTrue();
        });
    });

    describe('status-based scopes', function () {
        test('retired wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->bookable()->create();
            Wrestler::factory()->suspended()->create();
            $retiredWrestler = Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();

            // Act
            $retiredWrestlers = Wrestler::retired()->get();

            // Assert
            expect($retiredWrestlers)
                ->toHaveCount(1)
                ->and($retiredWrestlers->contains($retiredWrestler))->toBeTrue();
        });

    });
});
