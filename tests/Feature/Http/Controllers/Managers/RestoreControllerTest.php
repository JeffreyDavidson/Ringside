<?php

use App\Actions\Managers\RestoreAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\RestoreController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    RestoreAction::shouldRun()->with($this->manager);
});

test('a basic user cannot restore a manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot restore a manager', function () {
    $this->patch(action([RestoreController::class], $this->manager))
        ->assertRedirect(route('login'));
});
