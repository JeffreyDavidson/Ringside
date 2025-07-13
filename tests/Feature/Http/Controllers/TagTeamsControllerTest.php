<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeamsController;
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
 * Feature tests for TagTeamsController.
 *
 * @see TagTeamsController
 */
describe('index', function () {
    /**
     * @see TagTeamsController::index()
     */
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([TagTeamsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('tag-teams.index')
            ->assertSeeLivewire(TagTeamsTable::class);
    });

    /**
     * @see TagTeamsController::index()
     */
    test('a basic user cannot view tag teams index page', function () {
        actingAs(basicUser())
            ->get(action([TagTeamsController::class, 'index']))
            ->assertForbidden();
    });

    /**
     * @see TagTeamsController::index()
     */
    test('a guest cannot view tag teams index page', function () {
        get(action([TagTeamsController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
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
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class)
            ->assertSeeLivewire(PreviousMatchesTable::class)
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class)
            ->assertSeeLivewire(PreviousStablesTable::class);
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
