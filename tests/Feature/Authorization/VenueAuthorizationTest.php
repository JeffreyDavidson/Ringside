<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;
use App\Models\Events\Venue;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

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
    });

    describe('Livewire component authorization', function () {
        test('admin can access venues table component', function () {
            // Arrange
            $admin = $this->admin;

            // Act
            actingAs($admin);

            $component = livewire(Main::class);

            // Assert
            $component->assertOk();
        });

        test('basic user cannot access venues table component', function () {
            // Arrange
            $basicUser = $this->basicUser;

            // Act
            actingAs($basicUser);

            $component = livewire(Main::class);

            // Assert
            $component->assertForbidden();
        });

        test('guest user cannot access venues table component', function () {
            // Act
            $component = livewire(Main::class);

            // Assert
            $component->assertForbidden();
        });
    });

    describe('basic venue management actions', function () {
        test('admin can perform basic venue management actions', function () {
            $venue = Venue::factory()->create();
            $deletedVenue = Venue::factory()->trashed()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Basic CRUD actions that don't involve complex business logic
            $component->call('delete', $venue)->assertHasNoErrors();
            $component->call('restore', $deletedVenue->id)->assertHasNoErrors();
        });
    });

});
