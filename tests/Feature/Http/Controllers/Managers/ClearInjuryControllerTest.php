<?php

use App\Actions\Managers\ClearInjuryAction;
use App\Http\Controllers\Managers\ClearInjuryController;
use App\Http\Controllers\Managers\ManagersController;
use App\Models\Manager;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->manager = Manager::factory()->injured()->create();
});

test('invoke calls clear injury action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->manager))
        ->assertRedirect(action([ManagersController::class, 'index']));

    ClearInjuryAction::shouldRun()->with($this->manager);
});

test('a basic user cannot mark an injured manager as cleared', function () {
    actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->manager))
        ->assertForbidden();
});

test('a guest cannot mark an injured manager as cleared', function () {
    patch(action([ClearInjuryController::class], $this->manager))
        ->assertRedirect(route('login'));
});
