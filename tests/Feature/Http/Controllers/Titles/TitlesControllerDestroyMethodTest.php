<?php

use App\Actions\Titles\DeleteAction;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([TitlesController::class, 'destroy'], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->title);
});

test('a basic user cannot delete a title', function () {
    $this->actingAs(basicUser())
        ->delete(action([TitlesController::class, 'destroy'], $this->title))
        ->assertForbidden();
});

test('a guest cannot delete a title', function () {
    $this->delete(action([TitlesController::class, 'destroy'], $this->title))
        ->assertRedirect(route('login'));
});
