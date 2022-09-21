<?php

use App\Actions\Managers\ReinstateAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->suspended()->create();
});

test('invoke calls reinstate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->manager);
});

test('a basic user cannot reinstate a manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot reinstate a manager', function () {
    $this->patch(action([ReinstateController::class], $this->manager))
        ->assertRedirect(route('login'));
});
