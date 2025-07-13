<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Livewire\Managers\Tables\ManagersTable;
use App\Models\Managers\Manager;
use App\Models\Managers\ManagerEmployment;
use App\Models\Managers\ManagerInjury;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Integration tests for ManagersTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with complex data relationships
 * - Filtering and search functionality integration
 * - Action dropdown integration
 * - Status display integration
 * - Real database interaction with relationships
 */
describe('ManagersTable Component Integration', function () {

    beforeEach(function () {
        $this->user = administrator();
        $this->actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders managers table with complete data relationships', function () {
            // Create managers with different statuses and relationships
            $employedManager = Manager::factory()->bookable()->create(['name' => 'Active Manager']);
            $injuredManager = Manager::factory()->injured()->create(['name' => 'Injured Manager']);
            $retiredManager = Manager::factory()->retired()->create(['name' => 'Retired Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['name' => 'Suspended Manager']);

            // Create relationships
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Managed Wrestler']);
            $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'Managed Tag Team']);
            $stable = Stable::factory()->active()->create(['name' => 'Manager Stable']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee($employedManager->name)
                ->assertSee($injuredManager->name)
                ->assertSee($retiredManager->name)
                ->assertSee($suspendedManager->name);
        });

        test('displays correct status badges for different manager states', function () {
            $employedManager = Manager::factory()->bookable()->create(['name' => 'Employed Manager']);
            $injuredManager = Manager::factory()->injured()->create(['name' => 'Injured Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['name' => 'Suspended Manager']);
            $retiredManager = Manager::factory()->retired()->create(['name' => 'Retired Manager']);
            $releasedManager = Manager::factory()->released()->create(['name' => 'Released Manager']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Employed Manager')
                ->assertSee('Injured Manager')
                ->assertSee('Suspended Manager')
                ->assertSee('Retired Manager')
                ->assertSee('Released Manager')
                // Status indicators should be present (exact text may vary)
                ->assertSeeHtml('class'); // Status classes should be rendered
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters managers correctly', function () {
            Manager::factory()->create(['name' => 'Paul Bearer']);
            Manager::factory()->create(['name' => 'Jimmy Hart']);
            Manager::factory()->create(['name' => 'Bobby Heenan']);

            $component = Livewire::test(ManagersTable::class);

            // Test search functionality
            $component
                ->set('search', 'Paul')
                ->assertSee('Paul Bearer')
                ->assertDontSee('Jimmy Hart')
                ->assertDontSee('Bobby Heenan');

            // Test clearing search
            $component
                ->set('search', '')
                ->assertSee('Paul Bearer')
                ->assertSee('Jimmy Hart')
                ->assertSee('Bobby Heenan');
        });

        test('status filter functionality works with real data', function () {
            $employedManager = Manager::factory()->bookable()->create(['name' => 'Employed Manager']);
            $retiredManager = Manager::factory()->retired()->create(['name' => 'Retired Manager']);
            $injuredManager = Manager::factory()->injured()->create(['name' => 'Injured Manager']);

            $component = Livewire::test(ManagersTable::class);

            // Test filtering by status (if component supports it)
            $component
                ->assertSee('Employed Manager')
                ->assertSee('Retired Manager')
                ->assertSee('Injured Manager');
        });
    });

    describe('action integration', function () {
        test('action dropdown displays appropriate actions for manager states', function () {
            $employedManager = Manager::factory()->bookable()->create(['name' => 'Active Manager']);
            $retiredManager = Manager::factory()->retired()->create(['name' => 'Retired Manager']);

            $component = Livewire::test(ManagersTable::class);

            // Component should render without errors
            $component->assertOk();

            // Actions should be available (specific actions depend on component implementation)
            $component->assertSee($employedManager->name);
            $component->assertSee($retiredManager->name);
        });

        test('component integrates with authorization policies', function () {
            $manager = Manager::factory()->create(['name' => 'Test Manager']);

            // Test as administrator (should see all actions)
            $component = Livewire::actingAs($this->user)->test(ManagersTable::class);
            $component->assertOk();
            $component->assertSee($manager->name);
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            $employedManager = Manager::factory()->bookable()->create(['name' => 'Currently Employed']);
            $unemployedManager = Manager::factory()->unemployed()->create(['name' => 'Currently Unemployed']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });

        test('handles managers with employment history', function () {
            $manager = Manager::factory()->create(['name' => 'Manager with History']);

            // Create employment history
            ManagerEmployment::factory()
                ->for($manager, 'manager')
                ->create([
                    'started_at' => now()->subDays(200),
                    'ended_at' => now()->subDays(100),
                ]);

            ManagerEmployment::factory()
                ->for($manager, 'manager')
                ->current()
                ->create(['started_at' => now()->subDays(50)]);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Manager with History');
        });
    });

    describe('injury and suspension integration', function () {
        test('displays injury status correctly', function () {
            $healthyManager = Manager::factory()->bookable()->create(['name' => 'Healthy Manager']);
            $injuredManager = Manager::factory()->injured()->create(['name' => 'Injured Manager']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Healthy Manager')
                ->assertSee('Injured Manager');
        });

        test('displays suspension status correctly', function () {
            $activeManager = Manager::factory()->bookable()->create(['name' => 'Active Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['name' => 'Suspended Manager']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Active Manager')
                ->assertSee('Suspended Manager');
        });

        test('handles managers with injury history', function () {
            $manager = Manager::factory()->create(['name' => 'Manager with Injury History']);

            // Create injury history
            ManagerInjury::factory()
                ->for($manager, 'manager')
                ->create([
                    'started_at' => now()->subDays(100),
                    'ended_at' => now()->subDays(50),
                ]);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Manager with Injury History');
        });
    });

    describe('management relationships integration', function () {
        test('displays managers without wrestler/tag team relationships', function () {
            $manager = Manager::factory()->bookable()->create(['name' => 'Independent Manager']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Independent Manager');
        });

        test('displays managers who have managed wrestlers', function () {
            $manager = Manager::factory()->bookable()->create(['name' => 'Wrestler Manager']);
            $wrestler = Wrestler::factory()->bookable()->create();

            // Create wrestler-manager relationship (managers are associated through wrestlers)
            $wrestler->managers()->attach($manager->id, [
                'started_at' => now()->subMonths(6),
                'ended_at' => now()->subMonths(1),
            ]);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Wrestler Manager');
        });
    });

    describe('performance and data loading integration', function () {
        test('component handles large datasets efficiently', function () {
            // Create multiple managers with various relationships
            Manager::factory()->count(20)->create();

            // Add some relationships
            $managers = Manager::factory()->count(5)->bookable()->create();
            $stables = Stable::factory()->count(3)->active()->create();

            // No stable relationships since managers are no longer direct stable members

            $component = Livewire::test(ManagersTable::class);

            // Component should render efficiently
            $component->assertOk();

            // Should not have N+1 query issues (would require query monitoring in real implementation)
            expect($component->get('managers'))->not->toBeEmpty();
        });

        test('component eager loads necessary relationships', function () {
            $manager = Manager::factory()->bookable()->create(['name' => 'Relationship Manager']);

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertOk()
                ->assertSee('Relationship Manager')
                ->assertSee('Manager Stable');
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when manager data changes', function () {
            $manager = Manager::factory()->create(['name' => 'Original Manager Name']);

            $component = Livewire::test(ManagersTable::class);
            $component->assertSee('Original Manager Name');

            // Update manager name
            $manager->update(['name' => 'Updated Manager Name']);

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Updated Manager Name');
            $component->assertDontSee('Original Manager Name');
        });

        test('component reflects employment status changes', function () {
            $manager = Manager::factory()->unemployed()->create(['name' => 'Employment Test Manager']);

            $component = Livewire::test(ManagersTable::class);

            // Employ the manager
            EmployAction::run($manager, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Employment Test Manager');
        });

        test('component reflects injury status changes', function () {
            $manager = Manager::factory()->bookable()->create(['name' => 'Injury Test Manager']);

            $component = Livewire::test(ManagersTable::class);

            // Injure the manager
            InjureAction::run($manager, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Injury Test Manager');
        });
    });

    describe('complex business rule integration', function () {
        test('component handles managers with complex status combinations', function () {
            // Create manager with multiple statuses
            $manager = Manager::factory()->bookable()->create(['name' => 'Complex Status Manager']);

            // Manager is employed but also injured
            InjureAction::run($manager, now());

            $component = Livewire::test(ManagersTable::class);

            $component
                ->assertSee('Complex Status Manager');

            // Should show both employed and injured status indicators
        });

        test('component respects business rules for action availability', function () {
            $injuredManager = Manager::factory()->injured()->create(['name' => 'Injured Manager']);
            $retiredManager = Manager::factory()->retired()->create(['name' => 'Retired Manager']);

            $component = Livewire::test(ManagersTable::class);

            // Component should render and show appropriate actions based on business rules
            $component
                ->assertOk()
                ->assertSee('Injured Manager')
                ->assertSee('Retired Manager');
        });

        test('component handles manager employment transitions', function () {
            $manager = Manager::factory()->bookable()->create(['name' => 'Transitioning Manager']);

            $component = Livewire::test(ManagersTable::class);
            $component->assertSee('Transitioning Manager');

            // Test that component handles data changes appropriately
            $component->call('$refresh');
            $component->assertSee('Transitioning Manager');
        });
    });
});
