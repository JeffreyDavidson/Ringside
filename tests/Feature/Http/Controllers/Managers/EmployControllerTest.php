<?php

use App\Actions\Managers\EmployAction;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->unemployed()->create();
});

test('invoke calls employ action and redirects', function () {
    actingAs(administrator())
        ->patch(action([EmployController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    EmployAction::shouldRun()->with($this->manager);
});

test('a basic user cannot employ a manager', function () {
    actingAs(basicUser())
        ->patch(action([EmployController::class], $this->manager))
        ->assertForbidden();
});

test('a guest user cannot employ a manager', function () {
    patch(action([EmployController::class], $this->manager))
        ->assertRedirect(route('login'));
});
