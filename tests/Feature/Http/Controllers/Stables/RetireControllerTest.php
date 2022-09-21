<?php

use App\Actions\Stables\RetireAction;
use App\Http\Controllers\Stables\RetireController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->active()->create();
});

test('invoke calls retire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    RetireAction::shouldRun()->with($this->stable);
});

test('a basic user cannot retire a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot retire a stable', function () {
    $this->patch(action([RetireController::class], $this->stable))
        ->assertRedirect(route('login'));
});
