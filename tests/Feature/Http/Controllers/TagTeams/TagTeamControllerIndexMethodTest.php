<?php

use App\Http\Controllers\TagTeams\TagTeamsController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'index']))
        ->assertOk()
        ->assertViewIs('tagteams.index')
        ->assertSeeLivewire('tag-teams.tag-teams-list');
});

test('a basic user cannot view tag teams index page', function () {
    $this->actingAs(basicUser())
        ->get(action([TagTeamsController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view tag teams index page', function () {
    $this->get(action([TagTeamsController::class, 'index']))
        ->assertRedirect(route('login'));
});
