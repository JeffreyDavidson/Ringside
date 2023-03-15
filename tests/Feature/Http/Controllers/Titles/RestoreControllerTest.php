<?php

use App\Actions\Titles\RestoreAction;
use App\Http\Controllers\Titles\RestoreController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->title = Title::factory()->trashed()->create();
});

test('it restores a title and redirects', function () {
    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    RestoreAction::shouldRun()->with($this->title);
});

test('a basic user cannot restore a title', function () {
    actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot restore a title', function () {
    patch(action([RestoreController::class], $this->title))
        ->assertRedirect(route('login'));
});
