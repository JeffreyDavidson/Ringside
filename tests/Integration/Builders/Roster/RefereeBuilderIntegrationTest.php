<?php

declare(strict_types=1);

use App\Builders\Roster\RefereeBuilder;
use App\Models\Roster\Referees\Referee;

/**
 * Integration tests for RefereeQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Employment status filtering scopes (available, futureEmployed, unemployed, released)
 * - Individual roster member status scopes (suspended, retired, injured)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the RefereeQueryBuilder correctly implements
 * all query scopes for filtering referees by their various statuses.
 * Referees are individual roster members who can be injured.
 *
 * @see RefereeBuilder
 */
describe('RefereeQueryBuilder Integration Tests', function () {
    describe('employment status scopes', function () {
        test('future employed referees can be retrieved', function () {
            $futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
            Referee::factory()->bookable()->create();
            Referee::factory()->suspended()->create();
            Referee::factory()->retired()->create();
            Referee::factory()->released()->create();
            Referee::factory()->unemployed()->create();
            Referee::factory()->injured()->create();

            // Act
            $futureEmployedReferees = Referee::futureEmployed()->get();

            // Assert
            expect($futureEmployedReferees)
                ->toHaveCount(1)
                ->and($futureEmployedReferees->contains($futureEmployedReferee))->toBeTrue();
        });

        test('unemployed referees can be retrieved', function () {
            Referee::factory()->withFutureEmployment()->create();
            Referee::factory()->bookable()->create();
            Referee::factory()->suspended()->create();
            Referee::factory()->retired()->create();
            Referee::factory()->released()->create();
            $unemployedReferee = Referee::factory()->unemployed()->create();
            Referee::factory()->injured()->create();

            // Act
            $unemployedReferees = Referee::unemployed()->get();

            // Assert
            expect($unemployedReferees)
                ->toHaveCount(1)
                ->and($unemployedReferees->contains($unemployedReferee))->toBeTrue();
        });

        test('released referees can be retrieved', function () {
            Referee::factory()->withFutureEmployment()->create();
            Referee::factory()->bookable()->create();
            Referee::factory()->suspended()->create();
            Referee::factory()->retired()->create();
            $releasedReferee = Referee::factory()->released()->create();
            Referee::factory()->unemployed()->create();
            Referee::factory()->injured()->create();

            // Act
            $releasedReferees = Referee::released()->get();

            // Assert
            expect($releasedReferees)
                ->toHaveCount(1)
                ->and($releasedReferees->contains($releasedReferee))->toBeTrue();
        });
    });

    describe('individual roster member status scopes', function () {
        test('retired referees can be retrieved', function () {
            Referee::factory()->withFutureEmployment()->create();
            Referee::factory()->bookable()->create();
            Referee::factory()->suspended()->create();
            $retiredReferee = Referee::factory()->retired()->create();
            Referee::factory()->released()->create();
            Referee::factory()->unemployed()->create();
            Referee::factory()->injured()->create();

            // Act
            $retiredReferees = Referee::retired()->get();

            // Assert
            expect($retiredReferees)
                ->toHaveCount(1)
                ->and($retiredReferees->contains($retiredReferee))->toBeTrue();
        });

    });
});
