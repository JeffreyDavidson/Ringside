<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\VenuesTable;
use App\Models\Shared\Venue;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Feature tests for Venue Authorization.
 *
 * FEATURE TEST SCOPE:
 * - HTTP endpoint authorization for venue resources
 * - User role verification for venue access
 * - Livewire component authorization integration
 * - Complete authorization workflows
 *
 * These tests verify that venue authorization works correctly
 * across HTTP endpoints and Livewire components with proper
 * admin-only access patterns.
 */
describe('Venue Authorization', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->venue = Venue::factory()->create(['name' => 'Test Venue']);
    });

    describe('HTTP endpoint authorization', function () {
        test('admin can access venues index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('venues.index'));

            // Assert
            $response->assertOk()
                ->assertViewIs('venues.index')
                ->assertSeeLivewire(VenuesTable::class);
        });

        test('basic user cannot access venues index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('venues.index'));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot access venues index', function () {
            // Act
            $response = $this->get(route('venues.index'));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('admin can view venue details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('venues.show', $this->venue));

            // Assert
            $response->assertOk()
                ->assertViewIs('venues.show');
        });

        test('basic user cannot view venue details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('venues.show', $this->venue));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot view venue details', function () {
            // Act
            $response = $this->get(route('venues.show', $this->venue));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('returns 404 when venue does not exist', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('venues.show', 999999));

            // Assert
            $response->assertNotFound();
        });
    });

    describe('Livewire component authorization', function () {
        test('admin can access venues table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->admin)
                ->test(VenuesTable::class);

            // Assert
            $component->assertOk();
        });

        test('basic user cannot access venues table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->basicUser)
                ->test(VenuesTable::class);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access venues table component', function () {
            // Act
            $component = Livewire::test(VenuesTable::class);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('basic venue management actions', function () {
        test('admin can perform basic venue management actions', function () {
            $venue = Venue::factory()->create();
            $deletedVenue = Venue::factory()->trashed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(VenuesTable::class);

            // Basic CRUD actions that don't involve complex business logic
            $component->call('delete', $venue)->assertHasNoErrors();
            $component->call('restore', $deletedVenue->id)->assertHasNoErrors();
        });
    });

    describe('authorization consistency', function () {
        test('authorization is consistent between HTTP and Livewire', function () {
            // Test that HTTP and Livewire authorization behave the same way

            // Admin should have access to both
            $httpResponse = $this->actingAs($this->admin)
                ->get(route('venues.index'));
            $httpResponse->assertOk();

            $livewireComponent = Livewire::actingAs($this->admin)
                ->test(VenuesTable::class);
            $livewireComponent->assertOk();

            // Basic user should be forbidden from both
            $httpResponse = $this->actingAs($this->basicUser)
                ->get(route('venues.index'));
            $httpResponse->assertForbidden();

            $livewireComponent = Livewire::actingAs($this->basicUser)
                ->test(VenuesTable::class);
            $livewireComponent->assertForbidden();
        });
    });

    describe('route protection', function () {
        test('all venue routes require authentication', function () {
            $routes = [
                ['GET', route('venues.index')],
                ['GET', route('venues.show', $this->venue)],
            ];

            foreach ($routes as [$method, $uri]) {
                $response = $this->call($method, $uri);

                expect($response->getStatusCode())
                    ->toBeIn([302, 401], "Route {$method} {$uri} should require authentication");
            }
        });

        test('authorization is enforced per request', function () {
            // Admin can access
            $response = $this->actingAs($this->admin)
                ->get(route('venues.show', $this->venue));
            $response->assertOk();

            // Basic user still cannot access
            $response = $this->actingAs($this->basicUser)
                ->get(route('venues.show', $this->venue));
            $response->assertForbidden();
        });
    });
});
