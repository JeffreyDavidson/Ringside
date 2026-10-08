<?php

declare(strict_types=1);

use App\Builders\Roster\IndividualBuilder;
use App\Builders\Roster\WrestlerBuilder;
use App\Enums\Shared\EmploymentStatus;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\expectsDatabaseQueryCount;

/**
 * Integration tests for IndividualBuilder abstract base class.
 *
 * INTEGRATION TEST SCOPE:
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
describe('IndividualBuilder Integration Tests', function () {
    describe('abstract class architecture', function () {
        test('wrestler builder extends individual builder', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Arrange
            $builder = Wrestler::query();

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(IndividualBuilder::class);
        });

    });

    describe('employment status scopes', function () {
        test('employed wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            $suspendedWrestler = Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            $injuredWrestler = Wrestler::factory()->injured()->create();
            $availableWrestler = Wrestler::factory()->employed()->create();

            // Act
            $employedWrestlers = Wrestler::employed()->get();

            // Assert - Multiple wrestlers have employment (available, suspended, injured)
            // because factories create employment records for wrestlers in different states
            expect($employedWrestlers)
                ->toHaveCount(3)
                ->and($employedWrestlers->contains($availableWrestler))->toBeTrue()
                ->and($employedWrestlers->contains($suspendedWrestler))->toBeTrue()
                ->and($employedWrestlers->contains($injuredWrestler))->toBeTrue();
        });

        test('unemployed wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            $unemployedWrestler = Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Act
            $unemployedWrestlers = Wrestler::unemployed()->get();

            // Assert
            expect($unemployedWrestlers)
                ->toHaveCount(1)
                ->and($unemployedWrestlers->contains($unemployedWrestler))->toBeTrue();
        });

        test('released wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            $releasedWrestler = Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Act
            $releasedWrestlers = Wrestler::released()->get();

            // Assert
            expect($releasedWrestlers)
                ->toHaveCount(1)
                ->and($releasedWrestlers->contains($releasedWrestler))->toBeTrue();
        });

        test('future employed wrestlers can be retrieved', function () {
            $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Act
            $futureEmployedWrestlers = Wrestler::futureEmployed()->get();

            // Assert
            expect($futureEmployedWrestlers)
                ->toHaveCount(1)
                ->and($futureEmployedWrestlers->contains($futureEmployedWrestler))->toBeTrue();
        });
    });

    describe('individual roster member status scopes', function () {
        test('retired wrestlers can be retrieved', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            $retiredWrestler = Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Act
            $retiredWrestlers = Wrestler::retired()->get();

            // Assert
            expect($retiredWrestlers)
                ->toHaveCount(1)
                ->and($retiredWrestlers->contains($retiredWrestler))->toBeTrue();
        });
    });

    test('projected employment status does not query per wrestler', function () {
        $futureEmployedWrestler = Wrestler::factory()->withFutureEmployment()->create();
        $suspendedWrestler = Wrestler::factory()->suspended()->create();
        $retiredWrestler = Wrestler::factory()->retired()->create();
        $releasedWrestler = Wrestler::factory()->released()->create();
        $unemployedWrestler = Wrestler::factory()->unemployed()->create();
        $injuredWrestler = Wrestler::factory()->injured()->create();
        $availableWrestler = Wrestler::factory()->employed()->create();

        // Arrange
        expectsDatabaseQueryCount(1);

        // Act
        $query = Wrestler::query();
        $query->withEmploymentStatusState();
        $query->orderBy('id');
        $wrestlers = $query->get();
        $statuses = $wrestlers->mapWithKeys(fn (Wrestler $wrestler): array => [$wrestler->id => $wrestler->status]);

        // Assert
        expect($statuses->all())->toBe([
            $futureEmployedWrestler->id => EmploymentStatus::FutureEmployment,
            $suspendedWrestler->id => EmploymentStatus::Employed,
            $retiredWrestler->id => EmploymentStatus::Retired,
            $releasedWrestler->id => EmploymentStatus::Released,
            $unemployedWrestler->id => EmploymentStatus::Unemployed,
            $injuredWrestler->id => EmploymentStatus::Employed,
            $availableWrestler->id => EmploymentStatus::Employed,
        ]);
    });

    describe('query builder inheritance verification', function () {
        test('query scope methods return correct builder instance', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Act
            $builder = Wrestler::employed();

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(IndividualBuilder::class);
        });

        test('chained scopes maintain builder type', function () {
            Wrestler::factory()->withFutureEmployment()->create();
            Wrestler::factory()->suspended()->create();
            Wrestler::factory()->retired()->create();
            Wrestler::factory()->released()->create();
            Wrestler::factory()->unemployed()->create();
            Wrestler::factory()->injured()->create();
            Wrestler::factory()->employed()->create();

            // Act
            $builder = Wrestler::employed()
                ->whereNotNull('name');

            // Assert
            expect($builder)->toBeInstanceOf(WrestlerBuilder::class);
            expect($builder)->toBeInstanceOf(IndividualBuilder::class);
        });
    });

});
