<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\TagTeamsTable;
use App\Models\TagTeams\TagTeam;
use App\Models\Users\User;
use Livewire\Livewire;

/**
 * Feature tests for TagTeam authorization workflows.
 *
 * FEATURE TEST SCOPE:
 * - End-to-end authorization workflows through HTTP and Livewire
 * - Multi-user role authorization scenarios
 * - Business action authorization with real policy enforcement
 * - Component-level authorization integration
 * - HTTP response validation for unauthorized access
 *
 * These tests verify that tag team authorization works correctly
 * across the entire application stack, from HTTP requests through
 * Livewire components to business action execution.
 */
describe('TagTeam Authorization Feature Tests', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
    });

    describe('admin user authorization', function () {
        test('admin can access tag teams table component', function () {
            Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class)
                ->assertOk();
        });

        test('admin can perform basic tag team management actions', function () {
            $tagTeam = TagTeam::factory()->create();
            $deletedTeam = TagTeam::factory()->trashed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            // Basic CRUD actions that don't involve complex business logic
            $component->call('delete', $tagTeam)->assertHasNoErrors();
            $component->call('restore', $deletedTeam->id)->assertHasNoErrors();
        });

    });

    describe('basic user authorization', function () {
        test('basic user cannot access tag teams table component', function () {
            Livewire::actingAs($this->basicUser)
                ->test(TagTeamsTable::class)
                ->assertForbidden();
        });

    });

    describe('guest user authorization', function () {
        test('guest user cannot access tag teams table component', function () {
            Livewire::test(TagTeamsTable::class)
                ->assertForbidden();
        });

    });
});
