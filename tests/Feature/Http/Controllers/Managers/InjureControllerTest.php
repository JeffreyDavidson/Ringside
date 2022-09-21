<?php

use App\Actions\Managers\InjureAction;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;

beforeEach(function () {
    $this->manager = Manager::factory()->available()->create();
});

test('invoke calls injure action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    InjureAction::shouldRun()->with($this->manager);
});

test('a basic user cannot injure a manager', function () {
    $this->actingAs(basicUser())
        ->patch(action([InjureController::class], $this->manager))
        ->assertForbidden();
});

test('a guest user cannot injure a manager', function () {
    $this->patch(action([InjureController::class], $this->manager))
        ->assertRedirect(route('login'));
});
