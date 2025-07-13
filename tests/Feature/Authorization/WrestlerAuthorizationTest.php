<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\WrestlersTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Feature tests for Wrestler Authorization.
 *
 * FEATURE TEST SCOPE:
 * - HTTP endpoint authorization for wrestler resources
 * - User role verification for wrestler access
 * - Livewire component authorization integration
 * - Complete authorization workflows
 *
 * These tests verify that wrestler authorization works correctly
 * across HTTP endpoints and Livewire components with proper
 * admin-only access patterns.
 */
describe('Wrestler Authorization', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->wrestler = Wrestler::factory()->create(['name' => 'Test Wrestler']);
    });

    describe('HTTP endpoint authorization', function () {
        test('admin can access wrestlers index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('wrestlers.index'));

            // Assert
            $response->assertOk()
                ->assertViewIs('wrestlers.index')
                ->assertSeeLivewire(WrestlersTable::class);
        });

        test('basic user cannot access wrestlers index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('wrestlers.index'));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot access wrestlers index', function () {
            // Act
            $response = $this->get(route('wrestlers.index'));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('admin can view wrestler details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('wrestlers.show', $this->wrestler));

            // Assert
            $response->assertOk()
                ->assertViewIs('wrestlers.show');
        });

        test('basic user cannot view wrestler details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('wrestlers.show', $this->wrestler));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot view wrestler details', function () {
            // Act
            $response = $this->get(route('wrestlers.show', $this->wrestler));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('returns 404 when wrestler does not exist', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('wrestlers.show', 999999));

            // Assert
            $response->assertNotFound();
        });
    });

    describe('Livewire component authorization', function () {
        test('admin can access wrestlers table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Assert
            $component->assertOk();
        });

        test('basic user cannot access wrestlers table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->basicUser)
                ->test(WrestlersTable::class);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access wrestlers table component', function () {
            // Act
            $component = Livewire::test(WrestlersTable::class);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('basic wrestler management actions', function () {
        test('admin can perform basic wrestler management actions', function () {
            $wrestler = Wrestler::factory()->create();
            $deletedWrestler = Wrestler::factory()->trashed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Basic CRUD actions that don't involve complex business logic
            $component->call('delete', $wrestler)->assertHasNoErrors();
            $component->call('restore', $deletedWrestler->id)->assertHasNoErrors();
        });
    });

    describe('authorization consistency', function () {
        test('authorization is consistent between HTTP and Livewire', function () {
            // Test that HTTP and Livewire authorization behave the same way

            // Admin should have access to both
            $httpResponse = $this->actingAs($this->admin)
                ->get(route('wrestlers.index'));
            $httpResponse->assertOk();

            $livewireComponent = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);
            $livewireComponent->assertOk();

            // Basic user should be forbidden from both
            $httpResponse = $this->actingAs($this->basicUser)
                ->get(route('wrestlers.index'));
            $httpResponse->assertForbidden();

            $livewireComponent = Livewire::actingAs($this->basicUser)
                ->test(WrestlersTable::class);
            $livewireComponent->assertForbidden();
        });
    });

    describe('route protection', function () {
        test('all wrestler routes require authentication', function () {
            $routes = [
                ['GET', route('wrestlers.index')],
                ['GET', route('wrestlers.show', $this->wrestler)],
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
                ->get(route('wrestlers.show', $this->wrestler));
            $response->assertOk();

            // Basic user still cannot access
            $response = $this->actingAs($this->basicUser)
                ->get(route('wrestlers.show', $this->wrestler));
            $response->assertForbidden();
        });
    });
});
