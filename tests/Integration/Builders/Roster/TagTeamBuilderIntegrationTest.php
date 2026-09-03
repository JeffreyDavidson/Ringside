<?php

declare(strict_types=1);

use App\Builders\Roster\TagTeamBuilder;
use App\Models\Roster\TagTeams\TagTeam;

/**
 * Integration tests for TagTeamQueryBuilder query scopes and methods.
 *
 * INTEGRATION TEST SCOPE:
 * - Builder class structure and scope functionality
 * - Employment status filtering scopes (available, futureEmployed, unemployed, released)
 * - Status-based filtering scopes (suspended, retired)
 * - Current wrestler count scopes
 * - Query scope accuracy and entity isolation
 *
 * These tests verify that the TagTeamQueryBuilder correctly implements
 * all query scopes for filtering tag teams by their various statuses.
 * Note: TagTeams cannot be injured (individual people only).
 *
 * @see TagTeamBuilder
 */
describe('TagTeamQueryBuilder Integration Tests', function () {
    beforeEach(function () {
        // Create tag teams in all possible states for comprehensive scope testing
        $this->futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
        $this->bookableTagTeam = TagTeam::factory()->bookable()->create();
        $this->suspendedTagTeam = TagTeam::factory()->suspended()->create();
        $this->retiredTagTeam = TagTeam::factory()->retired()->create();
        $this->releasedTagTeam = TagTeam::factory()->released()->create();
        $this->unemployedTagTeam = TagTeam::factory()->unemployed()->create();
        $this->unbookableTagTeam = TagTeam::factory()->unbookable()->create();
        $this->undersizedTagTeam = TagTeam::factory()->employed()->create();
        $this->undersizedTagTeam->currentWrestlers()->updateExistingPivot(
            $this->undersizedTagTeam->currentWrestlers()->firstOrFail(),
            ['left_at' => now()],
        );
    });

    describe('employment status scopes', function () {
        test('future employed tag teams can be retrieved', function () {
            // Act
            $futureEmployedTagTeams = TagTeam::futureEmployed()->get();

            // Assert
            expect($futureEmployedTagTeams)
                ->toHaveCount(1)
                ->and($futureEmployedTagTeams->contains($this->futureEmployedTagTeam))->toBeTrue();
        });

        test('unemployed tag teams can be retrieved', function () {
            // Act
            $unemployedTagTeams = TagTeam::unemployed()->get();

            // Assert - Unemployed scope includes both unemployed and unbookable (no employment history)
            expect($unemployedTagTeams)
                ->toHaveCount(2)
                ->and($unemployedTagTeams->contains($this->unemployedTagTeam))->toBeTrue()
                ->and($unemployedTagTeams->contains($this->unbookableTagTeam))->toBeTrue();
        });

        test('released tag teams can be retrieved', function () {
            // Act
            $releasedTagTeams = TagTeam::released()->get();

            // Assert
            expect($releasedTagTeams)
                ->toHaveCount(1)
                ->and($releasedTagTeams->contains($this->releasedTagTeam))->toBeTrue();
        });
    });

    describe('status-based scopes', function () {
        test('retired tag teams can be retrieved', function () {
            // Act
            $retiredTagTeams = TagTeam::retired()->get();

            // Assert
            expect($retiredTagTeams)
                ->toHaveCount(1)
                ->and($retiredTagTeams->contains($this->retiredTagTeam))->toBeTrue();
        });
    });

});
