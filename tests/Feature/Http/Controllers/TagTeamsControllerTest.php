<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeams\IndexController;
use App\Http\Controllers\TagTeams\ShowController;
use App\Livewire\TagTeams\Tables\PreviousManagersTable;
use App\Livewire\TagTeams\Tables\PreviousMatchesTable;
use App\Livewire\TagTeams\Tables\PreviousStablesTable;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionshipsTable;
use App\Livewire\TagTeams\Tables\PreviousWrestlersTable;
use App\Livewire\TagTeams\Tables\TagTeamsTable;
use App\Models\TagTeams\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for TagTeams Controllers.
 *
 * @see IndexController
 * @see ShowController
 */
describe('index', function () {
    /**
     * @see IndexController::__invoke()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action(IndexController::class))
            ->assertOk()
            ->assertViewIs('tag-teams.index')
            ->assertSeeLivewire(TagTeamsTable::class);
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a basic user cannot view tag teams index page', function () {
        actingAs(basicUser())
            ->get(action(IndexController::class))
            ->assertForbidden();
    });

    /**
     * @see IndexController::__invoke()
     */
    test('a guest cannot view tag teams index page', function () {
        get(action(IndexController::class))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
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
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class)
            ->assertSeeLivewire(PreviousMatchesTable::class)
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class)
            ->assertSeeLivewire(PreviousStablesTable::class);
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
