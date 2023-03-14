<?php

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('invoke calls employ action and redirects', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();

    actingAs(administrator())
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionDoesntHaveErrors('error');

    EmployAction::shouldRun()->with($wrestler);
});

test('a basic user cannot employ a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    actingAs(basicUser())
        ->patch(action([EmployController::class], $wrestler))
        ->assertForbidden();
});

test('a guest user cannot employ a wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    patch(action([EmployController::class], $wrestler))
        ->assertRedirect(route('login'));
});

test('invoke returns an error message when employing a non employable wrestler', function () {
    $wrestler = Wrestler::factory()->create();

    EmployAction::allowToRun()->andThrow(CannotBeEmployedException::class);

    actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');
});
