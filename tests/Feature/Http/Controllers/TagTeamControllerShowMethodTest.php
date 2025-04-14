<?php

declare(strict_types=1);

use App\Http\Controllers\TagTeamsController;
use App\Models\TagTeam;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->tagTeam = TagTeam::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([TagTeamsController::class, 'show'], $this->tagTeam))
        ->assertViewIs('tag-teams.show')
        ->assertViewHas('tagTeam', $this->tagTeam);
});

test('a basic user can view their tag team profile', function () {
    $tagTeam = TagTeam::factory()->for($user = basicUser())->create();

    actingAs($user)
        ->get(action([TagTeamsController::class, 'show'], $tagTeam))
        ->assertOk();
});

test('a basic user cannot view another users tag team profile', function () {
    $tagTeam = TagTeam::factory()->for(User::factory())->create();

    actingAs(basicUser())
        ->get(action([TagTeamsController::class, 'show'], $tagTeam))
        ->assertForbidden();
});

test('a guest cannot view a tag team profile', function () {
    get(action([TagTeamsController::class, 'show'], $this->tagTeam))
        ->assertRedirect(route('login'));
});
