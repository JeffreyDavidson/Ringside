<?php

use App\Actions\Titles\RestoreAction;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    RestoreAction::shouldRun()->with($this->title);
});

test('a basic user cannot restore a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot restore a title', function () {
    $this->patch(action([RestoreController::class], $this->title))
        ->assertRedirect(route('login'));
});
