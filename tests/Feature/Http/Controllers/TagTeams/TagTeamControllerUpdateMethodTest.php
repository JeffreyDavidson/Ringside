<?php

use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\UpdateRequest;
use App\Models\TagTeam;
use App\Models\Wrestler;

test('edit returns a view', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->actingAs(administrator())
        ->get(action([TagTeamsController::class, 'edit'], $tagTeam))
        ->assertStatus(200)
        ->assertViewIs('tagteams.edit')
        ->assertViewHas('tagTeam', $tagTeam);
});

test('a basic user cannot view the form for editing a tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([TagTeamsController::class, 'edit'], $tagTeam))
        ->assertForbidden();
});

test('a guest cannot view the form for editing a tag team', function () {
    $tagTeam = TagTeam::factory()->create();

    $this->get(action([TagTeamsController::class, 'edit'], $tagTeam))
        ->assertRedirect(route('login'));
});

test('updates a tag team and redirects', function () {
    $tagTeam = TagTeam::factory()->create([
        'name' => 'Old Tag Team Name',
    ]);

    $data = UpdateRequest::factory()->create([
        'name' => 'New Tag Team Name',
        'start_date' => null,
    ]);

    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'edit'], $tagTeam))
        ->patch(action([TagTeamsController::class, 'update'], $tagTeam), $data)
        ->assertValid()
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->name->toBe('New Tag Team Name')
        ->employments->toBeEmpty();
});

test('wrestlers of tag team are synced when tag team is updated', function () {
    $tagTeam = TagTeam::factory()->bookable()->create();
    $formerTagTeamPartners = $tagTeam->currentWrestlers;
    $newTagTeamPartners = Wrestler::factory()->count(2)->create();

    $data = UpdateRequest::factory()->create([
        'wrestlers' => $newTagTeamPartners->modelKeys(),
    ]);

    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'edit'], $tagTeam))
        ->patch(action([TagTeamsController::class, 'update'], $tagTeam), $data)
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    expect($tagTeam->fresh())
        ->wrestlers->toHaveCount(4)
        ->currentWrestlers
            ->toHaveCount(2)
            ->toContain($newTagTeamPartners->first())
            ->toContain($newTagTeamPartners->last())
            ->not->toContain($formerTagTeamPartners->first())
            ->not->toContain($formerTagTeamPartners->last());
})->skip(true, 'Need to figure out how to check collection items');

test('a basic user cannot update a tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->actingAs(basicUser())
        ->patch(action([TagTeamsController::class, 'update'], $tagTeam), $data)
        ->assertForbidden();
});

test('a guest cannot update a tag team', function () {
    $tagTeam = TagTeam::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->patch(action([TagTeamsController::class, 'update'], $tagTeam), $data)
        ->assertRedirect(route('login'));
});
