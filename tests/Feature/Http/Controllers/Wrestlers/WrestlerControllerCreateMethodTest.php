<?php

declare(strict_types=1);

use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('create returns a view', function () {
    actingAs(administrator())
        ->get(action([WrestlersController::class, 'create']))
        ->assertOk()
        ->assertViewIs('wrestlers.create')
        ->assertViewHas('wrestler', new Wrestler);
});

test('a basic user cannot view the form for creating a wrestler', function () {
    actingAs(basicUser())
        ->get(action([WrestlersController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a wrestler', function () {
get(action([WrestlersController::class, 'create']))
->assertRedirect(route('login'));
    });
