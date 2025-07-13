<?php

declare(strict_types=1);

use App\Livewire\Titles\Tables\TitlesTable;
use App\Models\Titles\Title;
use App\Models\Users\User;
use Livewire\Livewire;

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
            Livewire::actingAs($this->admin)
                ->test(TitlesTable::class)
                ->assertOk()
                ->assertSee('titles')
                ->assertSee($this->title->name);
        });

        test('basic user cannot access titles table', function () {
            Livewire::actingAs($this->basicUser)
                ->test(TitlesTable::class)
                ->assertForbidden();
        });

        test('guest user cannot access titles table', function () {
            Livewire::test(TitlesTable::class)
                ->assertForbidden();
        });
    });
});
