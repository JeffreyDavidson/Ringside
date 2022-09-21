<?php

use App\Actions\Managers\EmployAction;
use App\Http\Controllers\Managers\EmployController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->unemployed()->create();
});

test('invoke calls employ action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    EmployAction::shouldRun()->with($this->manager);
});

test('a basic user cannot employ a manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([EmployController::class], $this->manager))
        ->assertForbidden();
});

test('a guest user cannot employ a manager', function () {
    $this->patch(action([EmployController::class], $this->manager))
        ->assertRedirect(route('login'));
});
