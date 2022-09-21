<?php

use App\Actions\Wrestlers\RetireAction;
use App\Http\Controllers\Wrestlers\RetireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls retire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    RetireAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot retire a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot retire a wrestler', function () {
    $this->patch(action([RetireController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
