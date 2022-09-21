<?php

use App\Actions\Titles\UpdateAction;
use App\Data\TitleData;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Requests\Titles\UpdateRequest;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(action([TitlesController::class, 'update'], $this->title), 'PATCH', $this->data);
});

test('update calls update action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'edit'], $this->title))
        ->patch(action([TitlesController::class, 'update'], $this->title), $this->data)
        ->assertValid()
        ->assertRedirect(action([TitlesController::class, 'index']));

    UpdateAction::shouldRun()->with($this->title, TitleData::fromUpdateRequest($this->request));
});

test('a basic user cannot update a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([TitlesController::class, 'update'], $this->title), $this->data)
        ->assertForbidden();
});

test('a guest cannot update a title', function () {
    $this->patch(action([TitlesController::class, 'update'], $this->title), $this->data)
        ->assertRedirect(route('login'));
});
