<?php

use App\Actions\Titles\CreateAction;
use App\Data\TitleData;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Requests\Titles\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([TitlesController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'create']))
        ->post(action([TitlesController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    CreateAction::shouldRun()->with(TitleData::fromStoreRequest($this->request));
});

test('a basic user cannot create a title', function () {
    $this->actingAs(basicUser())
        ->post(action([TitlesController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a title', function () {
    $this->post(action([TitlesController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
