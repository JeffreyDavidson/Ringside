<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\ShowController;
use App\Livewire\TagTeams\Tables\PreviousManagers;
use App\Livewire\TagTeams\Tables\PreviousMatches;
use App\Livewire\TagTeams\Tables\PreviousStables;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionships;
use App\Livewire\TagTeams\Tables\PreviousWrestlers;
use App\Models\TagTeams\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Show Controller.
 *
 * @see ShowController
 */
describe('TagTeams Show Controller', function () {
    beforeEach(function () {
        $this->tagTeam = TagTeam::factory()->create();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, $this->tagTeam))
            ->assertOk()
            ->assertViewIs('tag-teams.show')
            ->assertViewHas('tagTeam', $this->tagTeam)
            ->assertSeeLivewire(PreviousTitleChampionships::class)
            ->assertSeeLivewire(PreviousMatches::class)
            ->assertSeeLivewire(PreviousWrestlers::class)
            ->assertSeeLivewire(PreviousMain::class)
            ->assertSeeLivewire(PreviousMain::class);
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a basic user cannot view tag team profiles', function () {
        actingAs(basicUser())
            ->get(action(ShowController::class, $this->tagTeam))
            ->assertForbidden();
    });

    /**
     * @see ShowController::__invoke()
     */
    test('a guest cannot view a tag team profile', function () {
        $tagTeam = TagTeam::factory()->create();

        get(action(ShowController::class, $tagTeam))
            ->assertRedirect(route('login'));
    });

    /**
     * @see ShowController::__invoke()
     */
    test('returns 404 when tag team does not exist', function () {
        actingAs(administrator())
            ->get(action(ShowController::class, 999999))
            ->assertNotFound();
    });
});
