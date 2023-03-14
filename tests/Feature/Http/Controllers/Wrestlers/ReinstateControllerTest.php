<?php

use App\Actions\Wrestlers\ReinstateAction;
use App\Exceptions\CannotBeReinstatedException;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->suspended()->create();
});

test('invoke call reinstate action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot reinstate a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot reinstate a wrestler', function () {
    patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    ReinstateAction::allowToRun()->andThrow(CannotBeReinstatedException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([ReinstateController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
