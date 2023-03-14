<?php

use App\Actions\Wrestlers\SuspendAction;
use App\Exceptions\CannotBeSuspendedException;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls suspend action and redirects', function () {
    actingAs(administrator())
        ->patch(action([SuspendController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    SuspendAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot suspend a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot suspend a wrestler', function () {
    patch(action([SuspendController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    SuspendAction::allowToRun()->andThrow(CannotBeSuspendedException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([SuspendController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
