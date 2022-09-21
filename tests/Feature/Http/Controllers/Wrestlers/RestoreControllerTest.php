<?php

use App\Actions\Wrestlers\RestoreAction;
use App\Http\Controllers\Wrestlers\RestoreController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    RestoreAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot restore a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot restore a wrestler', function () {
    $this->patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
