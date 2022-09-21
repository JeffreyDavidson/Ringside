<?php

use App\Actions\TagTeams\CreateAction;
use App\Data\TagTeamData;
use App\Http\Controllers\TagTeams\TagTeamsController;
use App\Http\Requests\TagTeams\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([TagTeamsController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([TagTeamsController::class, 'create']))
        ->post(action([TagTeamsController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([TagTeamsController::class, 'index']));

    CreateAction::shouldRun()->with(TagTeamData::fromStoreRequest($this->request));
});

test('a basic user cannot create a tag team', function () {
    $this->actingAs(basicUser())
        ->post(action([TagTeamsController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a tag team', function () {
    $this->post(action([TagTeamsController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
