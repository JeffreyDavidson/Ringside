<?php

use App\Actions\Wrestlers\ReleaseAction;
use App\Exceptions\CannotBeReleasedException;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls release action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReleaseController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    ReleaseAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot release a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([ReleaseController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot release a wrestler', function () {
    patch(action([ReleaseController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    ReleaseAction::allowToRun()->andThrow(CannotBeReleasedException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([ReleaseController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
