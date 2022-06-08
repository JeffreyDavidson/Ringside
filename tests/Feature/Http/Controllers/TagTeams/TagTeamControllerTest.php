<?php

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Models\TagTeam;
use App\Models\User;

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

test('show returns a view', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'show'], $tagTeam))
        ->assertViewIs('tagteams.show')
        ->assertViewHas('tagTeam', $tagTeam);
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
    $tagTeam = TagTeam::factory()->create();

    $this->get(action([TagTeamsController::class, 'show'], $tagTeam))
        ->assertRedirect(route('login'));
});

test('deletes a tag team and redirects', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([TagTeamsController::class, 'destroy'], $tagTeam))
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    $this->assertSoftDeleted($tagTeam);
});

test('a basic user cannot delete a tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([TagTeamsController::class, 'destroy'], $tagTeam))
        ->assertForbidden();
});

test('a guest cannot delete a tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->delete(action([TagTeamsController::class, 'destroy'], $tagTeam))
        ->assertRedirect(route('login'));
});
