<?php

use App\Actions\Titles\UnretireAction;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use App\Models\Title;

beforeEach(function () {
    $this->title = Title::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    UnretireAction::shouldRun()->with($this->title);
});

test('a basic user cannot unretire a title', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot unretire a title', function () {
    $this->patch(action([UnretireController::class], $this->title))
        ->assertRedirect(route('login'));
});
