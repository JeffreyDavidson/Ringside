<?php

use App\Actions\Wrestlers\ReleaseAction;
use App\Http\Controllers\Wrestlers\ReleaseController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls release action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    ReleaseAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot release a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReleaseController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot release a wrestler', function () {
    $this->patch(action([ReleaseController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
