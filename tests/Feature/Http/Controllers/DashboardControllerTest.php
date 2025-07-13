<?php

declare(strict_types=1);
use App\Http\Controllers\DashboardController;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for DashboardController.
 *
 * @see DashboardController
 */

/**
 * @see DashboardController::__invoke()
 */
test('administrators can view the dashboard', function () {
    actingAs(administrator())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard');
});

/**
 * @see DashboardController::__invoke()
 */
test('basic users can view the dashboard', function () {
    actingAs(basicUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard');
});

/**
 * @see DashboardController::__invoke()
 */
test('a guest cannot view the dashboard', function () {
    get(route('dashboard'))
        ->assertRedirect(route('login'));
});
