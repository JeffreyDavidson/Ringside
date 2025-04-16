<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeamsController;
use App\Livewire\TagTeams\Tables\PreviousManagersTable;
use App\Livewire\TagTeams\Tables\PreviousMatchesTable;
use App\Livewire\TagTeams\Tables\PreviousStablesTable;
use App\Livewire\TagTeams\Tables\PreviousTitleChampionshipsTable;
use App\Livewire\TagTeams\Tables\PreviousWrestlersTable;
use App\Livewire\TagTeams\Tables\TagTeamsTable;
use App\Models\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('index', function () {
    test('index returns a view', function () {
        actingAs(administrator())
            ->get(action([TagTeamsController::class, 'index']))
            ->assertOk()
            ->assertViewIs('tag-teams.index')
            ->assertSeeLivewire(TagTeamsTable::class);
    });

    test('a basic user cannot view tag teams index page', function () {
        actingAs(basicUser())
            ->get(action([TagTeamsController::class, 'index']))
            ->assertForbidden();
    });

    test('a guest cannot view tag teams index page', function () {
        get(action([TagTeamsController::class, 'index']))
            ->assertRedirect(route('login'));
    });
});

describe('show', function () {
    beforeEach(function () {
        $this->tagTeam = TagTeam::factory()->create();
    });

    test('show returns a view', function () {
        actingAs(administrator())
            ->get(action([TagTeamsController::class, 'show'], $this->tagTeam))
            ->assertViewIs('tag-teams.show')
            ->assertViewHas('tagTeam', $this->tagTeam)
            ->assertSeeLivewire(PreviousTitleChampionshipsTable::class)
            ->assertSeeLivewire(PreviousMatchesTable::class)
            ->assertSeeLivewire(PreviousWrestlersTable::class)
            ->assertSeeLivewire(PreviousManagersTable::class)
            ->assertSeeLivewire(PreviousStablesTable::class);
    })->skip();

    test('a basic user can view their tag team profile', function () {
        $tagTeam = TagTeam::factory()->for($user = basicUser())->create();

        actingAs($user)
            ->get(action([TagTeamsController::class, 'show'], $tagTeam))
            ->assertOk();
    })->skip();
});
