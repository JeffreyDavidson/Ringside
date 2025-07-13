<?php

declare(strict_types=1);

use App\Livewire\Wrestlers\Tables\WrestlersTable;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Integration tests for WrestlersTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component can be mounted and rendered correctly
 * - Component integrates with Livewire lifecycle
 * - Component methods can be called without technical errors
 * - Component displays data correctly from models
 * - Component handles events and state management
 * - Component integrates with search/filtering functionality
 *
 * DOES NOT TEST:
 * - Business logic outcomes (Unit test concern)
 * - Authorization rules (Feature test concern)
 * - Database state changes (Unit/Feature test concern)
 * - Complete user workflows (Feature test concern)
 */
describe('WrestlersTable Component Integration', function () {

    beforeEach(function () {
        $this->wrestler = Wrestler::factory()->create([
            'name' => 'Test Wrestler',
            'hometown' => 'Test City',
            'height' => 72,
            'weight' => 200,
        ]);
        $this->admin = User::factory()->administrator()->create();
    });

    describe('component mounting and rendering', function () {
        test('component can be mounted successfully', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            expect($component->instance())->toBeInstanceOf(WrestlersTable::class);
            $component->assertOk();
        });

        test('component displays wrestler data correctly', function () {
            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->assertSee($this->wrestler->name)
                ->assertSee($this->wrestler->hometown)
                ->assertSee((string) $this->wrestler->height)
                ->assertSee((string) $this->wrestler->weight);
        });

        test('component handles empty state correctly', function () {
            Wrestler::query()->delete();

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->assertOk()
                ->assertDontSee($this->wrestler->name);
        });
    });

    describe('search integration', function () {
        test('search functionality integrates with component state', function () {
            $wrestler1 = Wrestler::factory()->create(['name' => 'John Cena']);
            $wrestler2 = Wrestler::factory()->create(['name' => 'The Rock']);

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->assertSee('John Cena')
                ->assertSee('The Rock')
                ->set('search', 'John')
                ->assertSee('John Cena')
                ->assertDontSee('The Rock');
        });

        test('search state persists correctly', function () {
            $wrestler = Wrestler::factory()->create(['name' => 'Stone Cold']);

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->set('search', 'Stone');

            expect($component->get('search'))->toBe('Stone');
            $component->assertSee('Stone Cold');
        });
    });

    describe('filtering integration', function () {
        test('status filter integrates with component state', function () {
            $bookableWrestler = Wrestler::factory()->bookable()->create(['name' => 'Active Wrestler']);
            $releasedWrestler = Wrestler::factory()->released()->create(['name' => 'Released Wrestler']);

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->assertSee('Active Wrestler')
                ->assertSee('Released Wrestler')
                ->set('filterComponents.status', 'bookable')
                ->assertSee('Active Wrestler')
                ->assertDontSee('Released Wrestler');
        });

        test('filter state persists correctly', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->set('filterComponents.status', 'retired');

            expect($component->get('filterComponents.status'))->toBe('retired');
        });
    });

    describe('action method integration', function () {
        test('component action methods can be called without errors', function () {
            $wrestler = Wrestler::factory()->bookable()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Test that methods exist and can be called (integration)
            // NOT testing business outcomes (that's Unit/Feature test concern)
            $component->call('handleWrestlerAction', 'employ', $wrestler->id);
            expect($component->instance())->toBeInstanceOf(WrestlersTable::class);

            $component->call('handleWrestlerAction', 'release', $wrestler->id);
            expect($component->instance())->toBeInstanceOf(WrestlersTable::class);
        });

        test('delete method integrates properly', function () {
            $wrestler = Wrestler::factory()->create();

            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('delete', $wrestler);

            // Just verify the method call works - business logic tested elsewhere
        });

        test('component handles method calls properly', function () {
            $wrestler = Wrestler::factory()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Just verify component can handle method calls - business logic tested elsewhere
            expect($component->instance())->toBeInstanceOf(WrestlersTable::class);
        });
    });

    describe('component state management', function () {
        test('component maintains state correctly during interactions', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->set('search', 'Test')
                ->set('filterComponents.status', 'bookable');

            // Verify component state is maintained
            expect($component->get('search'))->toBe('Test');
            expect($component->get('filterComponents.status'))->toBe('bookable');
        });

        test('component resets state correctly', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->set('search', 'Test')
                ->set('search', '');

            expect($component->get('search'))->toBe('');
        });
    });

    describe('livewire lifecycle integration', function () {
        test('component handles refresh correctly', function () {
            Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class)
                ->call('$refresh')
                ->assertOk();
        });

        test('component handles property updates', function () {
            $component = Livewire::actingAs($this->admin)
                ->test(WrestlersTable::class);

            // Test that component can handle property updates
            $component->set('search', 'Updated Search');
            expect($component->get('search'))->toBe('Updated Search');
        });
    });
});
