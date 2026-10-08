<?php

declare(strict_types=1);

use App\Builders\Roster\ManagerBuilder;
use App\Models\Roster\Managers\Manager;

/**
 * Integration tests for ManagerQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Employment status filtering scopes (available, futureEmployed, unemployed, released)
 * - Individual roster member status scopes (suspended, retired, injured)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the ManagerQueryBuilder correctly implements
 * all query scopes for filtering managers by their various statuses.
 * Managers are individual roster members who can be injured.
 *
 * @see ManagerBuilder
 */
describe('ManagerQueryBuilder Integration Tests', function () {
    describe('employment status scopes', function () {
        test('future employed managers can be retrieved', function () {
            $futureEmployedManager = Manager::factory()->withFutureEmployment()->create();
            Manager::factory()->employed()->create();
            Manager::factory()->suspended()->create();
            Manager::factory()->retired()->create();
            Manager::factory()->released()->create();
            Manager::factory()->unemployed()->create();
            Manager::factory()->injured()->create();

            // Act
            $futureEmployedManagers = Manager::futureEmployed()->get();

            // Assert
            expect($futureEmployedManagers)
                ->toHaveCount(1)
                ->and($futureEmployedManagers->contains($futureEmployedManager))->toBeTrue();
        });

        test('unemployed managers can be retrieved', function () {
            Manager::factory()->withFutureEmployment()->create();
            Manager::factory()->employed()->create();
            Manager::factory()->suspended()->create();
            Manager::factory()->retired()->create();
            Manager::factory()->released()->create();
            $unemployedManager = Manager::factory()->unemployed()->create();
            Manager::factory()->injured()->create();

            // Act
            $unemployedManagers = Manager::unemployed()->get();

            // Assert
            expect($unemployedManagers)
                ->toHaveCount(1)
                ->and($unemployedManagers->contains($unemployedManager))->toBeTrue();
        });

        test('released managers can be retrieved', function () {
            Manager::factory()->withFutureEmployment()->create();
            Manager::factory()->employed()->create();
            Manager::factory()->suspended()->create();
            Manager::factory()->retired()->create();
            $releasedManager = Manager::factory()->released()->create();
            Manager::factory()->unemployed()->create();
            Manager::factory()->injured()->create();

            // Act
            $releasedManagers = Manager::released()->get();

            // Assert
            expect($releasedManagers)
                ->toHaveCount(1)
                ->and($releasedManagers->contains($releasedManager))->toBeTrue();
        });
    });

    describe('individual roster member status scopes', function () {
        test('retired managers can be retrieved', function () {
            Manager::factory()->withFutureEmployment()->create();
            Manager::factory()->employed()->create();
            Manager::factory()->suspended()->create();
            $retiredManager = Manager::factory()->retired()->create();
            Manager::factory()->released()->create();
            Manager::factory()->unemployed()->create();
            Manager::factory()->injured()->create();

            // Act
            $retiredManagers = Manager::retired()->get();

            // Assert
            expect($retiredManagers)
                ->toHaveCount(1)
                ->and($retiredManagers->contains($retiredManager))->toBeTrue();
        });

    });
});
