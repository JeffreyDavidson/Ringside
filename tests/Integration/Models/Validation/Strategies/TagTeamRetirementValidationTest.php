<?php

declare(strict_types=1);

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Validation\Strategies\TagTeamRetirementValidation;
use App\Models\TagTeams\TagTeam;

/**
 * Integration tests for TagTeamRetirementValidation strategy.
 *
 * Tests retirement validation rules for tag teams with real database models.
 * Tag teams have more complex validation requirements due to wrestler dependencies.
 *
 * @see TagTeamRetirementValidation
 */
describe('TagTeamRetirementValidation', function () {
    beforeEach(function () {
        $this->strategy = new TagTeamRetirementValidation();
    });

    test('validates tag team retirement rules correctly', function ($factoryState, $shouldPass) {
        $tagTeam = TagTeam::factory()->{$factoryState}()->create();
        
        if ($shouldPass) {
            $this->strategy->validate($tagTeam);
            expectValidEntityState($tagTeam);
        } else {
            expect(fn() => $this->strategy->validate($tagTeam))
                ->toThrow(CannotBeRetiredException::class);
        }
    })->with([
        // Can retire: employed tag teams in various states
        ['employed', true],
        ['released', true],
        
        // Cannot retire: invalid states
        ['suspended', false],
        ['unemployed', false],
        ['withFutureEmployment', false], 
        ['retired', false],
    ]);

    test('validates wrestler dependency rules', function () {
        // Tag teams with active wrestlers might have additional constraints
        $tagTeam = TagTeam::factory()->employed()->create();
        
        // Create associated wrestlers
        $wrestler1 = createBookableWrestler();
        $wrestler2 = createBookableWrestler();
        
        $tagTeam->wrestlers()->attach([$wrestler1->id, $wrestler2->id], [
            'joined_at' => now()->subMonths(6)
        ]);

        // Tag team retirement validation should consider wrestler states
        $this->strategy->validate($tagTeam->fresh());
    });
});