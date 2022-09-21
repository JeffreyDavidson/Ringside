<?php

use App\Actions\Managers\ReleaseAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\ReleaseController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->available()->create();
});

test('invoke calls release action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    ReleaseAction::shouldRun()->with($this->manager);
});

test('a basic user cannot release a manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReleaseController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot release a manager', function () {
    $this->patch(action([ReleaseController::class], $this->manager))
        ->assertRedirect(route('login'));
});
