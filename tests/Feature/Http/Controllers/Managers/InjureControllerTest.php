<?php

use App\Actions\Managers\InjureAction;
use App\Http\Controllers\Managers\InjureController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->available()->create();
});

test('invoke calls injure action and redirects', function () {
    actingAs(administrator())
        ->patch(action([InjureController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    InjureAction::shouldRun()->with($this->manager);
});

test('a basic user cannot injure a manager', function () {
    actingAs(basicUser())
        ->patch(action([InjureController::class], $this->manager))
        ->assertForbidden();
});

test('a guest user cannot injure a manager', function () {
    patch(action([InjureController::class], $this->manager))
        ->assertRedirect(route('login'));
});
