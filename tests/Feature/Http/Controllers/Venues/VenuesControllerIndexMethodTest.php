<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\VenuesController;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('index returns a view', function () {
    actingAs(administrator())
        ->get(action([VenuesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('venues.index')
        ->assertSeeLivewire('venues.venues-list');
});

test('a basic user cannot view venues index page', function () {
    actingAs(basicUser())
        ->get(action([VenuesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view venues index page', function () {
get(action([VenuesController::class, 'index']))
->assertRedirect(route('login'));
    });
