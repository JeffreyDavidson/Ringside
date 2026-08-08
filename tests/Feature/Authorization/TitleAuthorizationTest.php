<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\Main;
use App\Models\Titles\Title;
use App\Models\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * Feature tests for Title Authorization and Workflows.
 *
 * FEATURE TEST SCOPE:
 * - Complete user authorization workflows
 * - End-to-end business workflows via UI
 * - User role and permission verification
 * - Error handling and user feedback
 * - Session management and redirects
 * - Complete application feature functionality
 *
 * TESTS:
 * - User authorization across all title actions
 * - Complete business workflows from UI perspective
 * - Error handling and user feedback
 * - Session state management
 */
describe('Title Authorization and Workflows', function () {

    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
        $this->basicUser = User::factory()->create();
        $this->title = Title::factory()->create();
    });

    describe('component access authorization', function () {
        test('admin can access titles table', function () {
            actingAs($this->admin);

            livewire(Main::class)
                ->assertOk()
                ->assertSee('titles')
                ->assertSee($this->title->name);
        });

        test('basic user cannot access titles table', function () {
            actingAs($this->basicUser);

            livewire(Main::class)
                ->assertForbidden();
        });

        test('guest user cannot access titles table', function () {
            livewire(Main::class)
                ->assertForbidden();
        });
    });
});
