<?php

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Http\Controllers\Wrestlers\EmployController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
});

test('invoke calls employ action and redirects', function () {
    // Only using unemployed state so I can test that a controller can employ a wrestler.
    $wrestler = Wrestler::factory()->unemployed()->create();

    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionDoesntHaveErrors('error');

    EmployAction::shouldRun()->with($wrestler);
});

test('invoke returns an error message when employing a non employable wrestler', function () {
    // Only using bookable state so I can test controller when action throws exception.
    $wrestler = Wrestler::factory()->bookable()->create();

    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'index']))
        ->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']))
        ->assertSessionHas('error');

    EmployAction::shouldRun()->with($wrestler)->andThrows(CannotBeEmployedException::class);
});

test('a basic user cannot employ a wrestler', function () {
    // Only using unemployed state so I can test that a controller can employ a wrestler.
    $wrestler = Wrestler::factory()->unemployed()->create();

    $this->actingAs(basicUser())
        ->patch(action([EmployController::class], $wrestler))
        ->assertForbidden();
});

test('a guest user cannot employ a wrestler', function () {
    // Only using unemployed state so I can test that a controller can employ a wrestler.
    $wrestler = Wrestler::factory()->unemployed()->create();

    $this->patch(action([EmployController::class], $wrestler))
        ->assertRedirect(route('login'));
});
