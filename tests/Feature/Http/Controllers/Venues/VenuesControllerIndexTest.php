<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\VenuesController;
use App\Livewire\Venues\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * Feature tests for Venues Controller.
 *
 * @see VenuesController
 */
describe('Venues Controller', function () {
    /**
     * @see VenuesController::index()
     */
    test('an administrator can view the venues index page', function () {
        // Arrange
        $administrator = administrator();

        // Act
        $response = actingAs($administrator)
            ->get(route('venues.index'));

        // Assert
        $response
            ->assertSuccessful()
            ->assertViewIs('venues.index')
            ->assertSeeLivewire(Main::class);
    });

    /**
     * @see VenuesController::index()
     */
    test('a basic user cannot view the venues index page', function () {
        // Arrange
        $basicUser = basicUser();

        // Act
        $response = actingAs($basicUser)
            ->get(route('venues.index'));

        // Assert
        $response->assertForbidden();
    });

    /**
     * @see VenuesController::index()
     */
    test('a guest is redirected before reaching the venues index authorization policy', function () {
        // Act
        $response = get(route('venues.index'));

        // Assert
        $response
            ->assertRedirect(route('login'));
    });
});
