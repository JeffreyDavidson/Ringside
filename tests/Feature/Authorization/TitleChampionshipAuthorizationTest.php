<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\PreviousTitleChampionshipsTable;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Feature tests for Title Championship Authorization.
 *
 * FEATURE TEST SCOPE:
 * - HTTP endpoint authorization for title championship resources
 * - User role verification for championship access
 * - Livewire component authorization integration
 * - Complete authorization workflows
 *
 * These tests verify that title championship authorization works correctly
 * across HTTP endpoints and Livewire components with proper
 * admin-only access patterns.
 */
describe('Title Championship Authorization', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->title = Title::factory()->create(['name' => 'Test Championship']);
    });

    describe('HTTP endpoint authorization', function () {
        test('admin can access titles index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('titles.index'));

            // Assert
            $response->assertOk()
                ->assertViewIs('titles.index');
        });

        test('basic user cannot access titles index', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('titles.index'));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot access titles index', function () {
            // Act
            $response = $this->get(route('titles.index'));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('admin can view title details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('titles.show', $this->title));

            // Assert
            $response->assertOk()
                ->assertViewIs('titles.show');
        });

        test('basic user cannot view title details', function () {
            // Arrange & Act
            $response = $this->actingAs($this->basicUser)
                ->get(route('titles.show', $this->title));

            // Assert
            $response->assertForbidden();
        });

        test('guest cannot view title details', function () {
            // Act
            $response = $this->get(route('titles.show', $this->title));

            // Assert
            $response->assertRedirect(route('login'));
        });

        test('returns 404 when title does not exist', function () {
            // Arrange & Act
            $response = $this->actingAs($this->admin)
                ->get(route('titles.show', 999999));

            // Assert
            $response->assertNotFound();
        });
    });

    describe('Livewire component authorization', function () {
        test('admin can access title championships table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->admin)
                ->test(PreviousTitleChampionshipsTable::class, ['titleId' => $this->title->id]);

            // Assert
            $component->assertOk();
        });

        test('basic user cannot access title championships table component', function () {
            // Arrange & Act
            $component = Livewire::actingAs($this->basicUser)
                ->test(PreviousTitleChampionshipsTable::class, ['titleId' => $this->title->id]);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access title championships table component', function () {
            // Act
            $component = Livewire::test(PreviousTitleChampionshipsTable::class, ['titleId' => $this->title->id]);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('authorization consistency', function () {
        test('authorization is consistent between HTTP and Livewire', function () {
            // Test that HTTP and Livewire authorization behave the same way

            // Admin should have access to both
            $httpResponse = $this->actingAs($this->admin)
                ->get(route('titles.show', $this->title));
            $httpResponse->assertOk();

            $livewireComponent = Livewire::actingAs($this->admin)
                ->test(PreviousTitleChampionshipsTable::class, ['titleId' => $this->title->id]);
            $livewireComponent->assertOk();

            // Basic user should be forbidden from both
            $httpResponse = $this->actingAs($this->basicUser)
                ->get(route('titles.show', $this->title));
            $httpResponse->assertForbidden();

            $livewireComponent = Livewire::actingAs($this->basicUser)
                ->test(PreviousTitleChampionshipsTable::class, ['titleId' => $this->title->id]);
            $livewireComponent->assertForbidden();
        });
    });

    describe('route protection', function () {
        test('all title routes require authentication', function () {
            $routes = [
                ['GET', route('titles.index')],
                ['GET', route('titles.show', $this->title)],
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
                ->get(route('titles.show', $this->title));
            $response->assertOk();

            // Basic user still cannot access
            $response = $this->actingAs($this->basicUser)
                ->get(route('titles.show', $this->title));
            $response->assertForbidden();
        });
    });
});
