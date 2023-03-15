<?php

use App\Actions\Titles\DeleteAction;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->title = Title::factory()->create();
});

test('it deletes a title and redirects', function () {
    actingAs(administrator())
        ->delete(action([TitlesController::class, 'destroy'], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->title);
});

test('a basic user cannot delete a title', function () {
    actingAs(basicUser())
        ->delete(action([TitlesController::class, 'destroy'], $this->title))
        ->assertForbidden();
});

test('a guest cannot delete a title', function () {
    delete(action([TitlesController::class, 'destroy'], $this->title))
        ->assertRedirect(route('login'));
});
