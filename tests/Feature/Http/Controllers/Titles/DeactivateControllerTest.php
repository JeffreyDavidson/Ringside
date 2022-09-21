<?php

use App\Actions\Titles\DeactivateAction;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->active()->create();
});

test('invoke calls deactivate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([DeactivateController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    DeactivateAction::shouldRun()->with($this->title);
});

test('a basic user cannot deactivate a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([DeactivateController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot deactivate a title', function () {
    $this->patch(action([DeactivateController::class], $this->title))
        ->assertRedirect(route('login'));
});
