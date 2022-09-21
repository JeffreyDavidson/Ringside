<?php

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\User;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'show'], $this->tagTeam))
        ->assertViewIs('tagteams.show')
        ->assertViewHas('tagTeam', $this->tagTeam);
});

test('a basic user can view their tag team profile', function () {
    $tagTeam = TagTeam::factory()->for($user = basicUser())->create();

    $this->actingAs($user)
        ->get(action([TagTeamsController::class, 'show'], $tagTeam))
        ->assertOk();
});

test('a basic user cannot view another users tag team profile', function () {
    $tagTeam = TagTeam::factory()->for(User::factory())->create();

    $this->actingAs(basicUser())
        ->get(action([TagTeamsController::class, 'show'], $tagTeam))
        ->assertForbidden();
});

test('a guest cannot view a tag team profile', function () {
    $this->get(action([TagTeamsController::class, 'show'], $this->tagTeam))
        ->assertRedirect(route('login'));
});
