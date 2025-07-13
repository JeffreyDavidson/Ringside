<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\StablesTable;
use App\Models\Stables\Stable;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Feature tests for Stable Authorization.
 *
 * FEATURE TEST SCOPE:
 * - HTTP endpoint authorization for stable resources
 * - User role verification for stable access
 * - Livewire component authorization integration
 * - Complete authorization workflows
 *
 * These tests verify that stable authorization works correctly
 * across HTTP endpoints and Livewire components with proper
 * admin-only access patterns.
 */
describe('Stable Authorization', function () {

    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->stable = Stable::factory()->create(['name' => 'Test Stable']);
    });

    describe('HTTP endpoint authorization', function () {
        test('admin can access stables index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('stables.index'));

            // Assert
            $response->assertOk()
                ->assertViewIs('stables.index')
                ->assertSeeLivewire(StablesTable::class);
        });

        test('basic user cannot access stables index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('stables.index'));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot access stables index', function () {
            // Act
            $response = $this->get(route('stables.index'));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('admin can view stable details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('stables.show', $this->stable));

            // Assert
            $response->assertOk()
                ->assertViewIs('stables.show');
        });

        test('basic user cannot view stable details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('stables.show', $this->stable));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot view stable details', function () {
            // Act
            $response = $this->get(route('stables.show', $this->stable));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('returns 404 when stable does not exist', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('stables.show', 999999));

            // Assert
            $response->assertNotFound();
        });
    });

    describe('Livewire component authorization', function () {
        test('admin can access stables table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            // Assert
            $component->assertOk();
        });

        test('basic user cannot access stables table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->basicUser)
                ->test(StablesTable::class);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access stables table component', function () {
            // Act
            $component = Livewire::test(StablesTable::class);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('authorization consistency', function () {
        test('authorization is consistent between HTTP and Livewire', function () {
            // Test that HTTP and Livewire authorization behave the same way

            // Admin should have access to both
            $httpResponse = $this->actingAs($this->admin)
                ->get(route('stables.index'));
            $httpResponse->assertOk();

            $livewireComponent = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);
            $livewireComponent->assertOk();

            // Basic user should be forbidden from both
            $httpResponse = $this->actingAs($this->basicUser)
                ->get(route('stables.index'));
            $httpResponse->assertForbidden();

            $livewireComponent = Livewire::actingAs($this->basicUser)
                ->test(StablesTable::class);
            $livewireComponent->assertForbidden();
        });

        test('authorization works with different stable states', function () {
            $activeStable = Stable::factory()->active()->create();
            $inactiveStable = Stable::factory()->inactive()->create();
            $retiredStable = Stable::factory()->retired()->create();

            // Admin should be able to access all stable states
            foreach ([$activeStable, $inactiveStable, $retiredStable] as $stable) {
                $response = $this->actingAs($this->admin)
                    ->get(route('stables.show', $stable));
                $response->assertOk();
            }

            // Basic user should be denied access to all stable states
            foreach ([$activeStable, $inactiveStable, $retiredStable] as $stable) {
                $response = $this->actingAs($this->basicUser)
                    ->get(route('stables.show', $stable));
                $response->assertForbidden();
            }
        });
    });

    describe('route protection', function () {
        test('all stable routes require authentication', function () {
            $routes = [
                ['GET', route('stables.index')],
                ['GET', route('stables.show', $this->stable)],
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
                ->get(route('stables.show', $this->stable));
            $response->assertOk();

            // Basic user still cannot access
            $response = $this->actingAs($this->basicUser)
                ->get(route('stables.show', $this->stable));
            $response->assertForbidden();
        });
    });
});
