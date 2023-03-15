<?php

use App\Actions\Titles\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Titles\TitlesController;
use App\Http\Controllers\Titles\UnretireController;
use App\Models\Title;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->title = Title::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([UnretireController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    UnretireAction::shouldRun()->with($this->title);
});

test('a basic user cannot unretire a title', function () {
    actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot unretire a title', function () {
    patch(action([UnretireController::class], $this->title))
        ->assertRedirect(route('login'));
});

test('it returns an error when an exception is thrown', function () {
    $title = Title::factory()->create();

    UnretireAction::allowToRun()->andThrow(CannotBeUnretiredException::class);

    actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([UnretireController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']))
        ->assertSessionHas('error');
});
