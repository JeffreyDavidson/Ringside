<?php

use App\Actions\Managers\SuspendAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\SuspendController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->available()->create();
});

test('invoke calls suspend action and redirects', function () {
    actingAs(administrator())
        ->patch(action([SuspendController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    SuspendAction::shouldRun()->with($this->manager);
});

test('a basic user cannot suspend a manager', function () {
    actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot suspend a manager', function () {
    patch(action([SuspendController::class], $this->manager))
        ->assertRedirect(route('login'));
});
