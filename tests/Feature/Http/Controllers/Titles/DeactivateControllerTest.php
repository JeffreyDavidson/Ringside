<?php

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Http\Controllers\Titles\DeactivateController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->title = Title::factory()->active()->create();
});

test('it deactivates a title and redirects', function () {
    actingAs(administrator())
        ->patch(action([DeactivateController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    DeactivateAction::shouldRun()->with($this->title);
});

test('a basic user cannot deactivate a title', function () {
    actingAs(basicUser())
        ->patch(action([DeactivateController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot deactivate a title', function () {
    patch(action([DeactivateController::class], $this->title))
        ->assertRedirect(route('login'));
});

test('it returns an error when an exception is thrown', function () {
    $title = Title::factory()->create();

    DeactivateAction::allowToRun()->andThrow(CannotBeDeactivatedException::class);

    actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([DeactivateController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']))
        ->assertSessionHas('error');
});
