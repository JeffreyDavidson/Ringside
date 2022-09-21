<?php

use App\Actions\Titles\ActivateAction;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->unactivated()->create();
});

test('invoke calls activate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    ActivateAction::shouldRun()->with($this->title);
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
