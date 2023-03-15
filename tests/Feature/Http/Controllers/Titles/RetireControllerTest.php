<?php

use App\Actions\Titles\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Titles\RetireController;
use App\Http\Controllers\Titles\TitlesController;
use App\Models\Title;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->title = Title::factory()->active()->create();
});

test('it retires a title and redirects', function () {
    actingAs(administrator())
        ->patch(action([RetireController::class], $this->title))
        ->assertRedirect(action([TitlesController::class, 'index']));

    RetireAction::shouldRun()->with($this->title);
});

test('a basic user cannot retire a title', function () {
    actingAs(basicUser())
        ->patch(action([RetireController::class], $this->title))
        ->assertForbidden();
});

test('a guest cannot retire a title', function () {
    patch(action([RetireController::class], $this->title))
        ->assertRedirect(route('login'));
});

test('it returns an error when an exception is thrown', function () {
    $title = Title::factory()->create();

    RetireAction::allowToRun()->andThrow(CannotBeRetiredException::class);

    actingAs(administrator())
        ->from(action([TitlesController::class, 'index']))
        ->patch(action([RetireController::class], $title))
        ->assertRedirect(action([TitlesController::class, 'index']))
        ->assertSessionHas('error');
});
