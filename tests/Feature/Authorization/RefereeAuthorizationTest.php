<?php

declare(strict_types=1);

use App\Livewire\Referees\Tables\RefereesTable;
use App\Models\Referees\Referee;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Feature tests for Referee Authorization.
 *
 * FEATURE TEST SCOPE:
 * - HTTP endpoint authorization for referee resources
 * - User role verification for referee access
 * - Livewire component authorization integration
 * - Complete authorization workflows
 *
 * These tests verify that referee authorization works correctly
 * across HTTP endpoints and Livewire components with proper
 * admin-only access patterns.
 */
describe('Referee Authorization', function () {

    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->referee = Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
    });

    describe('HTTP endpoint authorization', function () {
        test('admin can access referees index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('referees.index'));

            // Assert
            $response->assertOk()
                ->assertViewIs('referees.index')
                ->assertSeeLivewire(RefereesTable::class);
        });

        test('basic user cannot access referees index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('referees.index'));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot access referees index', function () {
            // Act
            $response = $this->get(route('referees.index'));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('admin can view referee details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('referees.show', $this->referee));

            // Assert
            $response->assertOk()
                ->assertViewIs('referees.show')
                ->assertSee($this->referee->full_name);
        });

        test('basic user cannot view referee details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('referees.show', $this->referee));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot view referee details', function () {
            // Act
            $response = $this->get(route('referees.show', $this->referee));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('returns 404 when referee does not exist', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('referees.show', 999999));

            // Assert
            $response->assertNotFound();
        });
    });

    describe('Livewire component authorization', function () {
        test('admin can access referees table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->admin)
                ->test(RefereesTable::class);

            // Assert
            $component->assertOk()
                ->assertSee($this->referee->full_name);
        });

        test('basic user cannot access referees table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->basicUser)
                ->test(RefereesTable::class);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access referees table component', function () {
            // Act
            $component = Livewire::test(RefereesTable::class);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('authorization consistency', function () {
        test('authorization is consistent between HTTP and Livewire', function () {
            // Test that HTTP and Livewire authorization behave the same way

            // Admin should have access to both
            $httpResponse = $this->actingAs($this->admin)
                ->get(route('referees.index'));
            $httpResponse->assertOk();

            $livewireComponent = Livewire::actingAs($this->admin)
                ->test(RefereesTable::class);
            $livewireComponent->assertOk();

            // Basic user should be forbidden from both
            $httpResponse = $this->actingAs($this->basicUser)
                ->get(route('referees.index'));
            $httpResponse->assertForbidden();

            $livewireComponent = Livewire::actingAs($this->basicUser)
                ->test(RefereesTable::class);
            $livewireComponent->assertForbidden();
        });

        test('authorization works with different referee states', function () {
            $employedReferee = Referee::factory()->bookable()->create();
            $injuredReferee = Referee::factory()->injured()->create();
            $retiredReferee = Referee::factory()->retired()->create();
            $suspendedReferee = Referee::factory()->suspended()->create();

            // Admin should be able to access all referee states
            foreach ([$employedReferee, $injuredReferee, $retiredReferee, $suspendedReferee] as $referee) {
                $response = $this->actingAs($this->admin)
                    ->get(route('referees.show', $referee));
                $response->assertOk();
            }

            // Basic user should be denied access to all referee states
            foreach ([$employedReferee, $injuredReferee, $retiredReferee, $suspendedReferee] as $referee) {
                $response = $this->actingAs($this->basicUser)
                    ->get(route('referees.show', $referee));
                $response->assertForbidden();
            }
        });
    });

    describe('route protection', function () {
        test('all referee routes require authentication', function () {
            $routes = [
                ['GET', route('referees.index')],
                ['GET', route('referees.show', $this->referee)],
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
                ->get(route('referees.show', $this->referee));
            $response->assertOk();

            // Basic user still cannot access
            $response = $this->actingAs($this->basicUser)
                ->get(route('referees.show', $this->referee));
            $response->assertForbidden();
        });
    });
});
