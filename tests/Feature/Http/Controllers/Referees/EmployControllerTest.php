<?php

use App\Actions\Referees\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->unemployed()->create();
});

test('invoke calls employ action and redirects', function () {
    actingAs(administrator())
        ->patch(action([EmployController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    EmployAction::shouldRun()->with($this->referee);
});

test('a basic user cannot employ a referee', function () {
    actingAs(basicUser())
        ->patch(action([EmployController::class], $this->referee))
        ->assertForbidden();
});

test('a guest user cannot employ a referee', function () {
    patch(action([EmployController::class], $this->referee))
        ->assertRedirect(route('login'));
});

test('invoke returns an error message when employing a non employable referee', function () {
    $referee = Referee::factory()->create();

    EmployAction::allowToRun()->andThrow(CannotBeEmployedException::class);

    actingAs(administrator())
        ->from(action([RefereesController::class, 'index']))
        ->patch(action([EmployController::class], $referee))
        ->assertRedirect(action([RefereesController::class, 'index']))
        ->assertSessionHas('error');
});
