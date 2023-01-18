<?php

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->unactivated()->create();
});

test('it activates a title and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([ActivateController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    ActivateAction::shouldRun()->with($this->title);
});

test('it throws an exception when activating an active title', function () {
    $title = Title::factory()->active()->create();

    $this->actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([ActivateController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']))
        ->assertSessionHas('error');

    ActivateAction::shouldRun()->with($title)->andThrows(CannotBeActivatedException::class);
});

test('a basic user cannot activate a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([ActivateController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot activate a title', function () {
    $this->patch(action([ActivateController::class], $this->title))
        ->assertRedirect(route('login'));
});
