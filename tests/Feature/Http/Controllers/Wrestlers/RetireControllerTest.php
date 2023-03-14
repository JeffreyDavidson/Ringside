<?php

use App\Actions\Wrestlers\RetireAction;
use App\Exceptions\CannotBeRetiredException;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls retire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RetireController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    RetireAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot retire a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([RetireController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot retire a wrestler', function () {
    patch(action([RetireController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns error message if exception is thrown', function () {
    $wrestler = Wrestler::factory()->create();

    RetireAction::allowToRun()->andThrow(CannotBeRetiredException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([RetireController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
