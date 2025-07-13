<?php

declare(strict_types=1);

use App\Livewire\Managers\Tables\ManagersTable;
use App\Models\Managers\Manager;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Feature tests for Manager Authorization.
 *
 * FEATURE TEST SCOPE:
 * - HTTP endpoint authorization for manager resources
 * - User role verification for manager access
 * - Livewire component authorization integration
 * - Complete authorization workflows
 *
 * These tests verify that manager authorization works correctly
 * across HTTP endpoints and Livewire components with proper
 * admin-only access patterns.
 */
describe('Manager Authorization', function () {

    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->manager = Manager::factory()->create(['first_name' => 'Test', 'last_name' => 'Manager']);
    });

    describe('HTTP endpoint authorization', function () {
        test('admin can access managers index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('managers.index'));

            // Assert
            $response->assertOk()
                ->assertViewIs('managers.index')
                ->assertSeeLivewire(ManagersTable::class);
        });

        test('basic user cannot access managers index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('managers.index'));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot access managers index', function () {
            // Act
            $response = $this->get(route('managers.index'));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('admin can view manager details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('managers.show', $this->manager));

            // Assert
            $response->assertOk()
                ->assertViewIs('managers.show')
                ->assertSee($this->manager->full_name);
        });

        test('basic user cannot view manager details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('managers.show', $this->manager));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot view manager details', function () {
            // Act
            $response = $this->get(route('managers.show', $this->manager));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('returns 404 when manager does not exist', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('managers.show', 999999));

            // Assert
            $response->assertNotFound();
        });
    });

    describe('Livewire component authorization', function () {
        test('admin can access managers table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->admin)
                ->test(ManagersTable::class);

            // Assert
            $component->assertOk()
                ->assertSee($this->manager->full_name);
        });

        test('basic user cannot access managers table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->basicUser)
                ->test(ManagersTable::class);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access managers table component', function () {
            // Act
            $component = Livewire::test(ManagersTable::class);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('authorization consistency', function () {
        test('authorization is consistent between HTTP and Livewire', function () {
            // Test that HTTP and Livewire authorization behave the same way

            // Admin should have access to both
            $httpResponse = $this->actingAs($this->admin)
                ->get(route('managers.index'));
            $httpResponse->assertOk();

            $livewireComponent = Livewire::actingAs($this->admin)
                ->test(ManagersTable::class);
            $livewireComponent->assertOk();

            // Basic user should be forbidden from both
            $httpResponse = $this->actingAs($this->basicUser)
                ->get(route('managers.index'));
            $httpResponse->assertForbidden();

            $livewireComponent = Livewire::actingAs($this->basicUser)
                ->test(ManagersTable::class);
            $livewireComponent->assertForbidden();
        });

        test('authorization works with different manager states', function () {
            $employedManager = Manager::factory()->employed()->create();
            $injuredManager = Manager::factory()->injured()->create();
            $retiredManager = Manager::factory()->retired()->create();
            $suspendedManager = Manager::factory()->suspended()->create();

            // Admin should be able to access all manager states
            foreach ([$employedManager, $injuredManager, $retiredManager, $suspendedManager] as $manager) {
                $response = $this->actingAs($this->admin)
                    ->get(route('managers.show', $manager));
                $response->assertOk();
            }

            // Basic user should be denied access to all manager states
            foreach ([$employedManager, $injuredManager, $retiredManager, $suspendedManager] as $manager) {
                $response = $this->actingAs($this->basicUser)
                    ->get(route('managers.show', $manager));
                $response->assertForbidden();
            }
        });
    });

    describe('route protection', function () {
        test('all manager routes require authentication', function () {
            $routes = [
                ['GET', route('managers.index')],
                ['GET', route('managers.show', $this->manager)],
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
                ->get(route('managers.show', $this->manager));
            $response->assertOk();

            // Basic user still cannot access
            $response = $this->actingAs($this->basicUser)
                ->get(route('managers.show', $this->manager));
            $response->assertForbidden();
        });
    });
});
