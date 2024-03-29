<?php

declare(strict_types=1);

use App\Actions\Managers\UnretireAction;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Managers\UnretireController;
use App\Models\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    actingAs(administrator())
        ->patch(action([UnretireController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    UnretireAction::shouldRun()->with($this->manager);
});

test('a basic user cannot unretire a manager', function () {
    actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot unretire a manager', function () {
    patch(action([UnretireController::class], $this->manager))
        ->assertRedirect(route('login'));
});
