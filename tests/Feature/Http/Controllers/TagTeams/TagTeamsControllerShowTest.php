<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Livewire\TagTeams\Tables\PreviousManagers;
use App\Livewire\TagTeams\Tables\PreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\Roster\TagTeams\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Controller.
 *
 * @see TagTeamsController
 */
describe('TagTeams Controller', function () {
    beforeEach(function () {
        $this->tagTeam = TagTeam::factory()->create();
    });

    /**
     * @see TagTeamsController::show()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([TagTeamsController::class, 'show'], $this->tagTeam))
            ->assertOk()
            ->assertViewIs('tag-teams.show')
            ->assertViewHas('tagTeam', $this->tagTeam)
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousManagers::class)
            ->assertSeeLivewire(PreviousStables::class);
    });

    /**
     * @see TagTeamsController::show()
     */
    test('a basic user cannot view tag team profiles', function () {
        actingAs(basicUser())
            ->get(action([TagTeamsController::class, 'show'], $this->tagTeam))
            ->assertForbidden();
    });

    /**
     * @see TagTeamsController::show()
     */
    test('a guest cannot view a tag team profile', function () {
        $tagTeam = TagTeam::factory()->create();

        get(action([TagTeamsController::class, 'show'], $tagTeam))
            ->assertRedirect(route('login'));
    });

    /**
     * @see TagTeamsController::show()
     */
    test('returns 404 when tag team does not exist', function () {
        actingAs(administrator())
            ->get(action([TagTeamsController::class, 'show'], 999999))
            ->assertNotFound();
    });
});
