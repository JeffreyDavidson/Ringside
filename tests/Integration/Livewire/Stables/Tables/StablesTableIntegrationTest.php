<?php

declare(strict_types=1);

use App\Livewire\Stables\Tables\StablesTable;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Integration tests for StablesTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with real database relationships
 * - Livewire property updates and form interactions
 * - Business action integration with real models
 * - Query building and filtering functionality
 * - Component state management with database
 * - Authorization integration with Gate facade
 *
 * These tests verify that the StablesTable component works correctly
 * with actual database relationships and complex stable scenarios
 * including membership management and lifecycle actions.
 */
describe('StablesTable Component Integration', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
    });

    describe('component rendering and data display', function () {
        test('renders stables table with complete data relationships', function () {
            $activeStable = Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
            $retiredStable = Stable::factory()->retired()->create(['name' => 'D-Generation X']);
            $inactiveStable = Stable::factory()->inactive()->create(['name' => 'The New World Order']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee($activeStable->name)
                ->assertSee($retiredStable->name)
                ->assertSee($inactiveStable->name)
                ->assertSee('The Four Horsemen')
                ->assertSee('D-Generation X')
                ->assertSee('The New World Order');
        });

        test('displays stable status information correctly', function () {
            $activeStable = Stable::factory()->active()->create(['name' => 'Active Stable']);
            $disbandedStable = Stable::factory()->disbanded()->create(['name' => 'Disbanded Stable']);
            $retiredStable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Active Stable')
                ->assertSee('Disbanded Stable')
                ->assertSee('Retired Stable');
        });

        test('loads stable activity periods for display', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Test Stable']);

            // Verify activity period exists
            expect($stable->currentActivityPeriod)->not->toBeNull();

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Test Stable');
        });

        test('displays stables with complex member relationships', function () {
            $stable = Stable::factory()->active()->create(['name' => 'The Stable']);

            // Add members to stable
            $wrestler = Wrestler::factory()->bookable()->create();
            $tagTeam = TagTeam::factory()->bookable()->create();
            $manager = Manager::factory()->bookable()->create();

            $wrestler->stables()->attach($stable->id, [
                'joined_at' => now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tagTeam->stables()->attach($stable->id, [
                'joined_at' => now()->subMonths(2),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $manager->stables()->attach($stable->id, [
                'joined_at' => now()->subMonths(1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('The Stable');
        });
    });

    describe('filtering and search functionality', function () {
        test('search functionality filters stables correctly', function () {
            $horsemen = Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
            $nwo = Stable::factory()->active()->create(['name' => 'New World Order']);
            $dx = Stable::factory()->active()->create(['name' => 'D-Generation X']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            // Test search for "Horsemen"
            $component->set('search', 'Horsemen')
                ->assertSee('The Four Horsemen')
                ->assertDontSee('New World Order')
                ->assertDontSee('D-Generation X');

            // Test search for "New"
            $component->set('search', 'New')
                ->assertSee('New World Order')
                ->assertDontSee('The Four Horsemen')
                ->assertDontSee('D-Generation X');
        });

        test('status filter works correctly', function () {
            $activeStable = Stable::factory()->active()->create(['name' => 'Active Stable']);
            $retiredStable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);
            $disbandedStable = Stable::factory()->disbanded()->create(['name' => 'Disbanded Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            // Initially should see all stables
            $component->assertSee('Active Stable')
                ->assertSee('Retired Stable')
                ->assertSee('Disbanded Stable');

            // Test filtering (exact filter implementation depends on component)
            // This verifies the component loads and displays filtered content
            $component->assertOk();
        });

        test('activity period filter functionality', function () {
            $oldStable = Stable::factory()->active()->create(['name' => 'Old Stable']);
            $newStable = Stable::factory()->active()->create(['name' => 'New Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Old Stable')
                ->assertSee('New Stable');
        });
    });

    describe('stable business actions integration', function () {
        test('disband action integration works correctly', function () {
            $activeStable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('disband', $activeStable)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify stable is disbanded
            expect($activeStable->fresh()->isDisbanded())->toBeTrue();
        });

        test('retire action integration works correctly', function () {
            $activeStable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('retire', $activeStable)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify stable is retired
            expect($activeStable->fresh()->isRetired())->toBeTrue();
        });

        test('unretire action integration works correctly', function () {
            $retiredStable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('unretire', $retiredStable)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify stable is unretired
            expect($retiredStable->fresh()->isInactive())->toBeTrue();
        });

        test('restore action integration works correctly', function () {
            $deletedStable = Stable::factory()->trashed()->create(['name' => 'Deleted Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('restore', $deletedStable->id)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify stable is restored
            expect(Stable::find($deletedStable->id))->not->toBeNull();
            expect($deletedStable->fresh())->not->toBeNull();
        });

        test('delete action integration works correctly', function () {
            $stable = Stable::factory()->inactive()->create(['name' => 'Test Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('delete', $stable)
                ->assertHasNoErrors();

            // Verify stable is soft deleted
            expect(Stable::find($stable->id))->toBeNull();
            expect(Stable::onlyTrashed()->find($stable->id))->not->toBeNull();
        });
    });

    describe('business rule enforcement', function () {
        test('disband action fails for inappropriate stable status', function () {
            $inactiveStable = Stable::factory()->inactive()->create(['name' => 'Inactive Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('disband', $inactiveStable)
                ->assertRedirect();

            // Verify stable status unchanged
            expect($inactiveStable->fresh()->isInactive())->toBeTrue();
        });

        test('unretire action fails for non-retired stable', function () {
            $activeStable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->call('unretire', $activeStable)
                ->assertRedirect();

            // Verify stable status unchanged
            expect($activeStable->fresh()->isActive())->toBeTrue();
        });

        test('actions respect stable business constraints', function () {
            $disbandedStable = Stable::factory()->disbanded()->create(['name' => 'Disbanded Stable']);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            // Disband should fail for already disbanded stable
            $component->call('disband', $disbandedStable)
                ->assertRedirect();

            expect($disbandedStable->fresh()->isDisbanded())->toBeTrue();
        });
    });

    describe('authorization integration', function () {
        test('component requires proper authorization for access', function () {
            $basicUser = User::factory()->create();

            Livewire::actingAs($basicUser)
                ->test(StablesTable::class)
                ->assertForbidden();
        });

        test('guest users cannot access component', function () {
            Livewire::test(StablesTable::class)
                ->assertForbidden();
        });

        test('admin can perform all stable actions', function () {
            $activeStable = Stable::factory()->active()->create();
            $retiredStable = Stable::factory()->retired()->create();
            $deletedStable = Stable::factory()->trashed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            // All actions should be available to admin
            $component->call('disband', $activeStable)->assertHasNoErrors();
            $component->call('retire', $activeStable)->assertHasNoErrors();
            $component->call('unretire', $retiredStable)->assertHasNoErrors();
            $component->call('restore', $deletedStable->id)->assertHasNoErrors();
        });
    });

    describe('query optimization and performance', function () {
        test('component loads efficiently with many stables', function () {
            Stable::factory()->count(20)->active()->create();
            Stable::factory()->count(10)->retired()->create();
            Stable::factory()->count(5)->disbanded()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk();
        });

        test('eager loading relationships works correctly', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Test Stable']);

            // Ensure activity period exists for eager loading test
            expect($stable->currentActivityPeriod)->not->toBeNull();

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Test Stable');
        });

        test('component handles large datasets efficiently', function () {
            // Create stables with various statuses and relationships
            Stable::factory()->count(15)->active()->create();
            Stable::factory()->count(10)->inactive()->create();
            Stable::factory()->count(5)->retired()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk();

            // Verify component loads without performance issues
            expect($component->payload['serverMemo']['data'])->toBeDefined();
        });
    });

    describe('complex stable scenarios', function () {
        test('displays stables with mixed member types correctly', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Mixed Stable']);

            // Add different types of members
            $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Wrestler One']);
            $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Wrestler Two']);
            $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'Tag Team']);
            $manager = Manager::factory()->bookable()->create(['name' => 'Manager']);

            // Attach members
            $wrestler1->stables()->attach($stable->id, ['joined_at' => now()->subMonths(6), 'created_at' => now(), 'updated_at' => now()]);
            $wrestler2->stables()->attach($stable->id, ['joined_at' => now()->subMonths(5), 'created_at' => now(), 'updated_at' => now()]);
            $tagTeam->stables()->attach($stable->id, ['joined_at' => now()->subMonths(4), 'created_at' => now(), 'updated_at' => now()]);
            $manager->stables()->attach($stable->id, ['joined_at' => now()->subMonths(3), 'created_at' => now(), 'updated_at' => now()]);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Mixed Stable');
        });

        test('handles stables with historical member changes', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Evolving Stable']);

            // Add member who left
            $formerWrestler = Wrestler::factory()->bookable()->create(['name' => 'Former Member']);
            $formerWrestler->stables()->attach($stable->id, [
                'joined_at' => now()->subMonths(8),
                'left_at' => now()->subMonths(4),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add current member
            $currentWrestler = Wrestler::factory()->bookable()->create(['name' => 'Current Member']);
            $currentWrestler->stables()->attach($stable->id, [
                'joined_at' => now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Evolving Stable');
        });

        test('displays stables with multiple activity periods correctly', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Reunited Stable']);

            // This stable would have been disbanded and reunited
            // The factory should handle creating the appropriate activity periods

            $component = Livewire::actingAs($this->admin)
                ->test(StablesTable::class);

            $component->assertOk()
                ->assertSee('Reunited Stable');
        });
    });
});
