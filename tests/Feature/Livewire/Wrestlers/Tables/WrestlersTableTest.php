<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Wrestlers\Tables\Main;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * Feature tests for Main Livewire component workflows.
 *
 * FEATURE TEST SCOPE:
 * - Complete business workflows via Livewire components
 * - UI component and business logic integration
 * - User interaction workflows
 * - Component-level feature functionality
 * - Error handling and user feedback workflows
 *
 * NOTE: This file focuses on component-level feature testing.
 * For integration testing, see: /tests/Integration/Livewire/Wrestlers/Tables/WrestlersTableIntegrationTest.php
 * For business logic testing, see: /tests/Unit/Actions/Wrestlers/WrestlerBusinessLogicTest.php
 */
describe('Main Component Feature Workflows', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create();
        $this->admin = administrator();
    });

    describe('wrestler management workflows', function () {
        test('complete wrestler employment workflow', function () {
            $wrestler = Wrestler::factory()->released()->create();

            actingAs($this->admin);

            livewire(Main::class)
                ->call('handleWrestlerAction', 'employ', $wrestler->id)
                ->assertHasNoErrors()
                ->assertSessionMissing('error');

            // Verify workflow completed successfully
            expect(freshModel($wrestler)->currentEmployment()->exists())->toBeTrue();
        });

        test('complete wrestler release workflow', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            actingAs($this->admin);

            livewire(Main::class)
                ->call('handleWrestlerAction', 'release', $wrestler->id)
                ->assertHasNoErrors()
                ->assertSessionMissing('error');

            expect(freshModel($wrestler)->status)->toBe(EmploymentStatus::Released);
        });

        test('complete wrestler retirement workflow', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            actingAs($this->admin);

            livewire(Main::class)
                ->call('handleWrestlerAction', 'retire', $wrestler->id)
                ->assertHasNoErrors();

            expect(freshModel($wrestler)->currentRetirement()->exists())->toBeTrue();
        });

        test('complete wrestler deletion and restoration workflow', function () {
            $wrestler = Wrestler::factory()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

            // Delete workflow
            $component->call('delete', $wrestler)
                ->assertHasNoErrors();

            // Verify wrestler is soft deleted
            $freshWrestler = Wrestler::withTrashed()->findOrFail($wrestler->id);
            expect($freshWrestler)->not->toBeNull();
            expect($freshWrestler->trashed())->toBeTrue();
            expect(Wrestler::find($wrestler->id))->toBeNull(); // Should not be found without withTrashed()
            expect(Wrestler::onlyTrashed()->find($wrestler->id))->not->toBeNull();

            // Restore workflow
            $component->call('restore', $wrestler->id)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('wrestlers.index');

            // Verify wrestler is restored
            expect(Wrestler::withTrashed()->findOrFail($wrestler->id)->deleted_at)->toBeNull();
            expect($wrestler->fresh())->not->toBeNull();
        });
    });

    describe('component feature functionality', function () {
        test('search feature workflow', function () {
            $wrestler1 = Wrestler::factory()->create(['name' => 'Stone Cold Steve Austin']);
            $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);

            actingAs($this->admin);

            livewire(Main::class)
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

            actingAs($this->admin);

            livewire(Main::class)
                ->assertSee('Active Wrestler')
                ->assertSee('Released Wrestler')
                ->set('filterValues.status', 'employed')
                ->assertSee('Active Wrestler')
                ->assertDontSee('Released Wrestler')
                ->set('filterValues.status', 'released')
                ->assertDontSee('Active Wrestler')
                ->assertSee('Released Wrestler');
        });
    });

    describe('table feature workflows', function () {
        test('table handles wrestler actions through delegation', function () {
            $wrestler = Wrestler::factory()->unemployed()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

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

            actingAs($this->admin);

            $component = livewire(Main::class)
                ->set('search', 'Test Search')
                ->set('filterValues.status', 'released');

            // Perform business operation
            $component->call('handleWrestlerAction', 'employ', $wrestler->id);

            // Component state should be maintained
            expect($component->get('search'))->toBe('Test Search');
            expect($component->get('filterValues.status'))->toBe('released');
        });

        test('component handles concurrent user interactions', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            actingAs($this->admin);

            $component = livewire(Main::class);

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

            actingAs($this->admin);

            livewire(Main::class)
                ->call('handleWrestlerAction', 'employ', $wrestler->id)
                ->assertHasNoErrors()
                ->assertSessionMissing('error');

            // Component should update to reflect new state
            // (visual feedback tested in Browser tests)
        });

        test('component handles long-running operations gracefully', function () {
            $wrestler = Wrestler::factory()->create();

            // Test that component doesn't break with operations that might take time
            actingAs($this->admin);

            livewire(Main::class)
                ->call('delete', $wrestler)
                ->call('restore', $wrestler->id)
                ->assertHasNoErrors();
        });
    });
});
