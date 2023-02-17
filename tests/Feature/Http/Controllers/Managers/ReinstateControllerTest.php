<?php

use App\Actions\Managers\ReinstateAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReinstateController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->suspended()->create();
});

test('invoke calls reinstate action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->manager);
});

test('a basic user cannot reinstate a manager', function () {
    actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot reinstate a manager', function () {
    patch(action([ReinstateController::class], $this->manager))
        ->assertRedirect(route('login'));
});
