<?php

use App\Actions\Managers\RetireAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\RetireController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->available()->create();
});

test('invoke calls retire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RetireController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    RetireAction::shouldRun()->with($this->manager);
});

test('a basic user cannot retire a manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([RetireController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot retire a manager', function () {
    $this->patch(action([RetireController::class], $this->manager))
        ->assertRedirect(route('login'));
});
