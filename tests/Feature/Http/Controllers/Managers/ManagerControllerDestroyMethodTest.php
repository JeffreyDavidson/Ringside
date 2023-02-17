<?php

use App\Actions\Managers\DeleteAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->manager = Manager::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    actingAs(administrator())
        ->delete(action([ManagersController::class, 'destroy'], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    DeleteAction::shouldRun()->with($this->manager);
});

test('a basic user cannot delete a manager', function () {
    actingAs(basicUser())
        ->delete(action([ManagersController::class, 'destroy'], $this->manager))
        ->assertForbidden();
});

test('a guest cannot delete a manager', function () {
    delete(action([ManagersController::class, 'destroy'], $this->manager))
        ->assertRedirect(route('login'));
});
