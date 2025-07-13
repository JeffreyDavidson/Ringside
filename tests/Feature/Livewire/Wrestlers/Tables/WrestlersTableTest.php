<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\WrestlersTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Feature tests for WrestlersTable Livewire component workflows.
 *
 * FEATURE TEST SCOPE:
 * - Complete business workflows via Livewire components
 * - UI component and business logic integration
 * - User interaction workflows
 * - Component-level feature functionality
 * - Error handling and user feedback workflows
 *
 * NOTE: This file focuses on component-level feature testing.
 * For authorization testing, see: /tests/Feature/Authorization/WrestlerAuthorizationTest.php
 * For integration testing, see: /tests/Integration/Livewire/Wrestlers/Tables/WrestlersTableIntegrationTest.php
 * For business logic testing, see: /tests/Unit/Actions/Wrestlers/WrestlerBusinessLogicTest.php
 */
describe('WrestlersTable Component Feature Workflows', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
        $this->admin = User::factory()->administrator()->create();
    });

    describe('wrestler management workflows', function () {
        test('complete wrestler employment workflow', function () {
            $wrestler = Wrestler::factory()->released()->create();

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('handleWrestlerAction', 'employ', $wrestler->id)
                ->assertHasNoErrors()
                ->assertSessionMissing('error');

            // Verify workflow completed successfully
            expect($wrestler->fresh()->isEmployed())->toBeTrue();
        });

        test('complete wrestler release workflow', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('handleWrestlerAction', 'release', $wrestler->id)
                ->assertHasNoErrors()
                ->assertSessionMissing('error');

            expect($wrestler->fresh()->isReleased())->toBeTrue();
        });

        test('complete wrestler retirement workflow', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('handleWrestlerAction', 'retire', $wrestler->id)
                ->assertHasNoErrors();

            expect($wrestler->fresh()->isRetired())->toBeTrue();
        });

        test('complete wrestler deletion and restoration workflow', function () {
            $wrestler = Wrestler::factory()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Delete workflow
            $component->call('delete', $wrestler)
                ->assertHasNoErrors();

            // Verify wrestler is soft deleted
            $freshWrestler = Wrestler::withTrashed()->find($wrestler->id);
            expect($freshWrestler)->not->toBeNull();
            expect($freshWrestler->trashed())->toBeTrue();
            expect(Wrestler::find($wrestler->id))->toBeNull(); // Should not be found without withTrashed()
            expect(Wrestler::onlyTrashed()->find($wrestler->id))->not->toBeNull();

            // Restore workflow
            $component->call('restore', $wrestler->id)
                ->assertHasNoErrors();

            // Verify wrestler is restored
            expect(Wrestler::withTrashed()->find($wrestler->id)->deleted_at)->toBeNull();
            expect($wrestler->fresh())->not->toBeNull();
        });
    });

    describe('component feature functionality', function () {
        test('search feature workflow', function () {
            $wrestler1 = Wrestler::factory()->create(['name' => 'Stone Cold Steve Austin']);
            $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->assertSee('Stone Cold Steve Austin')
                ->assertSee('The Rock')
                ->set('search', 'Stone Cold')
                ->assertSee('Stone Cold Steve Austin')
                ->assertDontSee('The Rock')
                ->set('search', '')
                ->assertSee('Stone Cold Steve Austin')
                ->assertSee('The Rock');
        });

        test('status filtering feature workflow', function () {
            $employedWrestler = Wrestler::factory()->bookable()->create(['name' => 'Active Wrestler']);
            $releasedWrestler = Wrestler::factory()->released()->create(['name' => 'Released Wrestler']);

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->assertSee('Active Wrestler')
                ->assertSee('Released Wrestler')
                ->set('filterComponents.status', 'employed')
                ->assertSee('Active Wrestler')
                ->assertDontSee('Released Wrestler')
                ->set('filterComponents.status', 'released')
                ->assertDontSee('Active Wrestler')
                ->assertSee('Released Wrestler');
        });
    });

    describe('table feature workflows', function () {
        test('table handles wrestler actions through delegation', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Test that the action delegation works without errors
            $component->call('handleWrestlerAction', 'employ', $wrestler->id)
                ->assertHasNoErrors();

            // This tests the delegation mechanism, not the business logic
            expect($wrestler->fresh())->not->toBeNull();
        });
    });

    describe('component state management workflows', function () {
        test('component maintains state during business operations', function () {
            $wrestler = Wrestler::factory()->released()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->set('search', 'Test Search')
                ->set('filterComponents.status', 'released');

            // Perform business operation
            $component->call('handleWrestlerAction', 'employ', $wrestler->id);

            // Component state should be maintained
            expect($component->get('search'))->toBe('Test Search');
            expect($component->get('filterComponents.status'))->toBe('released');
        });

        test('component handles concurrent user interactions', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Simulate multiple rapid interactions
            $component->set('search', 'Test')
                ->call('handleWrestlerAction', 'release', $wrestler->id)
                ->set('search', 'Updated')
                ->assertHasNoErrors();

            expect($component->get('search'))->toBe('Updated');
        });
    });

    describe('user experience workflows', function () {
        test('component provides immediate feedback for successful actions', function () {
            $wrestler = Wrestler::factory()->released()->create();

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('handleWrestlerAction', 'employ', $wrestler->id)
                ->assertHasNoErrors()
                ->assertSessionMissing('error');

            // Component should update to reflect new state
            // (visual feedback tested in Browser tests)
        });

        test('component handles long-running operations gracefully', function () {
            $wrestler = Wrestler::factory()->create();

            // Test that component doesn't break with operations that might take time
            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('delete', $wrestler)
                ->call('restore', $wrestler->id)
                ->assertHasNoErrors();
        });
    });
});
