<?php

use App\Actions\Wrestlers\DeleteAction;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    actingAs(administrator())
        ->delete(action([WrestlersController::class, 'destroy'], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    DeleteAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot delete a wrestler', function () {
    actingAs(basicUser())
        ->delete(action([WrestlersController::class, 'destroy'], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot delete a wrestler', function () {
    delete(action([WrestlersController::class, 'destroy'], $this->wrestler))
        ->assertRedirect(route('login'));
});
