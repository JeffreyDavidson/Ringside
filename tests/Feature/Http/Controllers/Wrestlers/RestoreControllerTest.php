<?php

use App\Actions\Wrestlers\RestoreAction;
use App\Http\Controllers\Wrestlers\RestoreController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    actingAs(administrator())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    RestoreAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot restore a wrestler', function () {
    actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot restore a wrestler', function () {
    patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
