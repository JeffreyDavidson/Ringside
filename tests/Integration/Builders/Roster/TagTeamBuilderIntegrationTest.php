<?php

declare(strict_types=1);

use App\Builders\Roster\TagTeamBuilder;
use App\Models\Roster\TagTeams\TagTeam;

/**
 * @return array{
 *     futureEmployedTagTeam: TagTeam,
 *     bookableTagTeam: TagTeam,
 *     suspendedTagTeam: TagTeam,
 *     retiredTagTeam: TagTeam,
 *     releasedTagTeam: TagTeam,
 *     unemployedTagTeam: TagTeam,
 *     unbookableTagTeam: TagTeam,
 *     undersizedTagTeam: TagTeam,
 * }
 */
function buildersTagTeamBuilderIntegrationFixtures(): array
{
    // Create tag teams in all possible states for comprehensive scope testing
    $futureEmployedTagTeam = TagTeam::factory()->withFutureEmployment()->create();
    $bookableTagTeam = TagTeam::factory()->bookable()->create();
    $suspendedTagTeam = TagTeam::factory()->suspended()->create();
    $retiredTagTeam = TagTeam::factory()->retired()->create();
    $releasedTagTeam = TagTeam::factory()->released()->create();
    $unemployedTagTeam = TagTeam::factory()->unemployed()->create();
    $unbookableTagTeam = TagTeam::factory()->unbookable()->create();
    $undersizedTagTeam = TagTeam::factory()->employed()->create();
    $undersizedTagTeam->currentWrestlers()->updateExistingPivot(
        $undersizedTagTeam->currentWrestlers()->firstOrFail(),
        ['left_at' => now()],
    );

    return [
        'futureEmployedTagTeam' => $futureEmployedTagTeam,
        'bookableTagTeam' => $bookableTagTeam,
        'suspendedTagTeam' => $suspendedTagTeam,
        'retiredTagTeam' => $retiredTagTeam,
        'releasedTagTeam' => $releasedTagTeam,
        'unemployedTagTeam' => $unemployedTagTeam,
        'unbookableTagTeam' => $unbookableTagTeam,
        'undersizedTagTeam' => $undersizedTagTeam,
    ];
}

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
    describe('employment status scopes', function () {
        test('future employed tag teams can be retrieved', function () {
            ['futureEmployedTagTeam' => $futureEmployedTagTeam] = buildersTagTeamBuilderIntegrationFixtures();

            // Act
            $futureEmployedTagTeams = TagTeam::futureEmployed()->get();

            // Assert
            expect($futureEmployedTagTeams)
                ->toHaveCount(1)
                ->and($futureEmployedTagTeams->contains($futureEmployedTagTeam))->toBeTrue();
        });

        test('unemployed tag teams can be retrieved', function () {
            ['unemployedTagTeam' => $unemployedTagTeam, 'unbookableTagTeam' => $unbookableTagTeam] = buildersTagTeamBuilderIntegrationFixtures();

            // Act
            $unemployedTagTeams = TagTeam::unemployed()->get();

            // Assert - Unemployed scope includes both unemployed and unbookable (no employment history)
            expect($unemployedTagTeams)
                ->toHaveCount(2)
                ->and($unemployedTagTeams->contains($unemployedTagTeam))->toBeTrue()
                ->and($unemployedTagTeams->contains($unbookableTagTeam))->toBeTrue();
        });

        test('released tag teams can be retrieved', function () {
            ['releasedTagTeam' => $releasedTagTeam] = buildersTagTeamBuilderIntegrationFixtures();

            // Act
            $releasedTagTeams = TagTeam::released()->get();

            // Assert
            expect($releasedTagTeams)
                ->toHaveCount(1)
                ->and($releasedTagTeams->contains($releasedTagTeam))->toBeTrue();
        });
    });

    describe('status-based scopes', function () {
        test('retired tag teams can be retrieved', function () {
            ['retiredTagTeam' => $retiredTagTeam] = buildersTagTeamBuilderIntegrationFixtures();

            // Act
            $retiredTagTeams = TagTeam::retired()->get();

            // Assert
            expect($retiredTagTeams)
                ->toHaveCount(1)
                ->and($retiredTagTeams->contains($retiredTagTeam))->toBeTrue();
        });
    });

});
