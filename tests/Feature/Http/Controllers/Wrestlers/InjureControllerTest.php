<?php

use App\Actions\Wrestlers\InjureAction;
use App\Http\Controllers\Wrestlers\InjureController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls injure action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    InjureAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot injure a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([InjureController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest user cannot injure a wrestler', function () {
    $this->patch(action([InjureController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
