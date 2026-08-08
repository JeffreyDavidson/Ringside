<?php

declare(strict_types=1);

use App\Builders\Roster\RefereeBuilder;
use App\Models\Referees\Referee;

/**
 * Unit tests for RefereeQueryBuilder query scopes and methods.
 *
 * UNIT TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Employment status filtering scopes (bookable, futureEmployed, unemployed, released)
 * - Individual roster member status scopes (suspended, retired, injured)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the RefereeQueryBuilder correctly implements
 * all query scopes for filtering referees by their various statuses.
 * Referees are individual roster members who can be injured.
 *
 * @see RefereeBuilder
 */
describe('RefereeQueryBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create referees in all possible states for comprehensive scope testing
        $this->futureEmployedReferee = Referee::factory()->withFutureEmployment()->create();
        $this->bookableReferee = Referee::factory()->bookable()->create();
        $this->suspendedReferee = Referee::factory()->suspended()->create();
        $this->retiredReferee = Referee::factory()->retired()->create();
        $this->releasedReferee = Referee::factory()->released()->create();
        $this->unemployedReferee = Referee::factory()->unemployed()->create();
        $this->injuredReferee = Referee::factory()->injured()->create();
    });

    describe('availability status scopes', function () {
        test('bookable referees can be retrieved', function () {
            // Act
            $bookableReferees = Referee::bookable()->get();

            // Assert
            expect($bookableReferees)
                ->toHaveCount(1)
                ->and($bookableReferees->contains($this->bookableReferee))->toBeTrue();
        });
    });

    describe('employment status scopes', function () {
        test('future employed referees can be retrieved', function () {
            // Act
            $futureEmployedReferees = Referee::futureEmployed()->get();

            // Assert
            expect($futureEmployedReferees)
                ->toHaveCount(1)
                ->and($futureEmployedReferees->contains($this->futureEmployedReferee))->toBeTrue();
        });

        test('unemployed referees can be retrieved', function () {
            // Act
            $unemployedReferees = Referee::unemployed()->get();

            // Assert
            expect($unemployedReferees)
                ->toHaveCount(1)
                ->and($unemployedReferees->contains($this->unemployedReferee))->toBeTrue();
        });

        test('released referees can be retrieved', function () {
            // Act
            $releasedReferees = Referee::released()->get();

            // Assert
            expect($releasedReferees)
                ->toHaveCount(1)
                ->and($releasedReferees->contains($this->releasedReferee))->toBeTrue();
        });
    });

    describe('individual roster member status scopes', function () {
        test('suspended referees can be retrieved', function () {
            // Act
            $suspendedReferees = Referee::suspended()->get();

            // Assert
            expect($suspendedReferees)
                ->toHaveCount(1)
                ->and($suspendedReferees->contains($this->suspendedReferee))->toBeTrue();
        });

        test('retired referees can be retrieved', function () {
            // Act
            $retiredReferees = Referee::retired()->get();

            // Assert
            expect($retiredReferees)
                ->toHaveCount(1)
                ->and($retiredReferees->contains($this->retiredReferee))->toBeTrue();
        });

        test('injured referees can be retrieved', function () {
            // Act
            $injuredReferees = Referee::injured()->get();

            // Assert
            expect($injuredReferees)
                ->toHaveCount(1)
                ->and($injuredReferees->contains($this->injuredReferee))->toBeTrue();
        });
    });
});
