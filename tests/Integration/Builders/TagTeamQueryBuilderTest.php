<?php

declare(strict_types=1);

use App\Models\TagTeams\TagTeam;

/**
 * Unit tests for TagTeamQueryBuilder query scopes and methods.
 *
 * UNIT TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Employment status filtering scopes (bookable, futureEmployed, unemployed, released)
 * - Status-based filtering scopes (suspended, retired)
 * - Complex business logic scopes (unbookable with multiple entities)
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the TagTeamQueryBuilder correctly implements
 * all query scopes for filtering tag teams by their various statuses.
 * Note: TagTeams cannot be injured (individual people only).
 *
 * @see \App\Builders\TagTeamBuilder
 */
describe('TagTeamQueryBuilder Unit Tests', function () {
    beforeEach(function () {
        // Create tag teams in all possible states for comprehensive scope testing
        $this->futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
        $this->bookableTagTeam = TagTeam::factory()->bookable()->create();
        $this->suspendedTagTeam = TagTeam::factory()->suspended()->create();
        $this->retiredTagTeam = TagTeam::factory()->retired()->create();
        $this->releasedTagTeam = TagTeam::factory()->released()->create();
        $this->unemployedTagTeam = TagTeam::factory()->unemployed()->create();
        $this->unbookableTagTeam = TagTeam::factory()->unbookable()->create();
    });

    describe('employment status scopes', function () {
        test('bookable tag teams can be retrieved', function () {
            // Act
            $bookableTagTeams = TagTeam::bookable()->get();

            // Assert
            expect($bookableTagTeams)
                ->toHaveCount(1)
                ->collectionHas($this->bookableTagTeam);
        });

        test('future employed tag teams can be retrieved', function () {
            // Act
            $futureEmployedTagTeams = TagTeam::futureEmployed()->get();

            // Assert
            expect($futureEmployedTagTeams)
                ->toHaveCount(1)
                ->collectionHas($this->futureEmployedTagTeam);
        });

        test('unemployed tag teams can be retrieved', function () {
            // Act
            $unemployedTagTeams = TagTeam::unemployed()->get();

            // Assert - Unemployed scope includes both unemployed and unbookable (no employment history)
            expect($unemployedTagTeams)
                ->toHaveCount(2)
                ->collectionHas($this->unemployedTagTeam)
                ->collectionHas($this->unbookableTagTeam);
        });

        test('released tag teams can be retrieved', function () {
            // Act
            $releasedTagTeams = TagTeam::released()->get();

            // Assert
            expect($releasedTagTeams)
                ->toHaveCount(1)
                ->collectionHas($this->releasedTagTeam);
        });
    });

    describe('status-based scopes', function () {
        test('suspended tag teams can be retrieved', function () {
            // Act
            $suspendedTagTeams = TagTeam::suspended()->get();

            // Assert
            expect($suspendedTagTeams)
                ->toHaveCount(1)
                ->collectionHas($this->suspendedTagTeam);
        });

        test('retired tag teams can be retrieved', function () {
            // Act
            $retiredTagTeams = TagTeam::retired()->get();

            // Assert
            expect($retiredTagTeams)
                ->toHaveCount(1)
                ->collectionHas($this->retiredTagTeam);
        });
    });

    describe('complex business logic scopes', function () {
        test('unbookable tag teams can be retrieved', function () {
            // Act
            $unbookableTagTeams = TagTeam::unbookable()->get();

            // Assert - Unbookable includes any entity that cannot be booked
            // (suspended, retired, released, unemployed, and unbookable)
            expect($unbookableTagTeams->pluck('id'))->toContain($this->suspendedTagTeam->id);
            expect($unbookableTagTeams->pluck('id'))->toContain($this->retiredTagTeam->id);
            expect($unbookableTagTeams->pluck('id'))->toContain($this->releasedTagTeam->id);
            expect($unbookableTagTeams->pluck('id'))->toContain($this->unemployedTagTeam->id);
            expect($unbookableTagTeams->pluck('id'))->toContain($this->unbookableTagTeam->id);
        });
    });
});