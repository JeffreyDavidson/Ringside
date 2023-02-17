<?php

use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('create returns a view', function () {
    actingAs(administrator())
        ->get(action([RefereesController::class, 'create']))
        ->assertSuccessful()
        ->assertViewIs('referees.create')
        ->assertViewHas('referee', new Referee());
});

test('a basic user cannot view the form for creating a referee', function () {
    actingAs(basicUser())
        ->get(action([RefereesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a referee', function () {
    get(action([RefereesController::class, 'create']))
        ->assertRedirect(route('login'));
});
