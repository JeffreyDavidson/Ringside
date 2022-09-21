<?php

use App\Actions\Stables\UnretireAction;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Controllers\Stables\UnretireController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    UnretireAction::shouldRun()->with($this->stable);
});

test('a basic user cannot unretire a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot unretire a stable', function () {
    $this->patch(action([UnretireController::class], $this->stable))
        ->assertRedirect(route('login'));
});
