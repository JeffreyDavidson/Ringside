<?php

use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    UnretireAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot unretire a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot unretire a wrestler', function () {
    patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    UnretireAction::allowToRun()->andThrow(CannotBeUnretiredException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([UnretireController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
