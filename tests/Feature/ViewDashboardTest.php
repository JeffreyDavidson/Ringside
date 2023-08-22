<?php

declare(strict_types=1);

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('administrators can view the dashboard', function () {
    actingAs(administrator())
        ->get(route('dashboard'))
        ->assertViewIs('dashboard');
});

test('basic users can view the dashboard', function () {
    actingAs(basicUser())
        ->get(route('dashboard'))
        ->assertViewIs('dashboard');
});

test('a guest cannot view the dashboard', function () {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});
