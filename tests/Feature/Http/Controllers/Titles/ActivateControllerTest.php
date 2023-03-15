<?php

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Http\Controllers\Titles\ActivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->title = Title::factory()->unactivated()->create();
});

test('it activates a title and redirects', function () {
    actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([ActivateController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    ActivateAction::shouldRun()->with($this->title);
});

test('a basic user cannot activate a title', function () {
    actingAs(basicUser())
        ->patch(action([ActivateController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot activate a title', function () {
    patch(action([ActivateController::class], $this->title))
        ->assertRedirect(route('login'));
});

test('it returns an error when an exception is thrown', function () {
    $title = Title::factory()->create();

    ActivateAction::allowToRun()->andThrow(CannotBeActivatedException::class);

    actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([ActivateController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']))
        ->assertSessionHas('error');
});
