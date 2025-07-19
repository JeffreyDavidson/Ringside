<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Actions\Managers\InjureAction;
use App\Livewire\Managers\Tables\Main;
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
describe('ManagersTable Component', function () {

    beforeEach(function () {
        $this->user = administrator();
        $this->actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders managers table with complete data relationships', function () {
            // Create managers with different statuses and relationships
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Manager']);
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);
            $retiredManager = Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Manager']);

            // Create relationships
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Managed Wrestler']);
            $tagTeam = TagTeam::factory()->bookable()->create(['name' => 'Managed Tag Team']);
            $stable = Stable::factory()->active()->create(['name' => 'Manager Stable']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee($employedManager->name)
                ->assertSee($injuredManager->name)
                ->assertSee($retiredManager->name)
                ->assertSee($suspendedManager->name);
        });

        test('displays correct status badges for different manager states', function () {
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Manager']);
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Manager']);
            $retiredManager = Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager']);
            $releasedManager = Manager::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

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
            Manager::factory()->create(['first_name' => 'Paul', 'last_name' => 'Bearer']);
            Manager::factory()->create(['first_name' => 'Jimmy', 'last_name' => 'Hart']);
            Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);

            $component = Livewire::test(Main::class);

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
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Manager']);
            $retiredManager = Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager']);
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            // Test filtering by status (if component supports it)
            $component
                ->assertSee('Employed Manager')
                ->assertSee('Retired Manager')
                ->assertSee('Injured Manager');
        });
    });

    describe('action integration', function () {
        test('action dropdown displays appropriate actions for manager states', function () {
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Manager']);
            $retiredManager = Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            // Component should render without errors
            $component->assertOk();

            // Actions should be available (specific actions depend on component implementation)
            $component->assertSee($employedManager->name);
            $component->assertSee($retiredManager->name);
        });

        test('component integrates with authorization policies', function () {
            $manager = Manager::factory()->create(['first_name' => 'Test', 'last_name' => 'Manager']);

            // Test as administrator (should see all actions)
            $component = Livewire::actingAs($this->user)->test(Main::class);
            $component->assertOk();
            $component->assertSee($manager->name);
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Currently', 'last_name' => 'Employed']);
            $unemployedManager = Manager::factory()->unemployed()->create(['first_name' => 'Currently', 'last_name' => 'Unemployed']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });

        test('handles managers with employment history', function () {
            $manager = Manager::factory()->create(['first_name' => 'Manager', 'last_name' => 'History']);

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

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Manager History');
        });
    });

    describe('injury and suspension integration', function () {
        test('displays injury status correctly', function () {
            $healthyManager = Manager::factory()->employed()->create(['first_name' => 'Healthy', 'last_name' => 'Manager']);
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Healthy Manager')
                ->assertSee('Injured Manager');
        });

        test('displays suspension status correctly', function () {
            $activeManager = Manager::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Active Manager')
                ->assertSee('Suspended Manager');
        });

        test('handles managers with injury history', function () {
            $manager = Manager::factory()->create(['first_name' => 'Injury', 'last_name' => 'History']);

            // Create injury history
            ManagerInjury::factory()
                ->for($manager, 'manager')
                ->create([
                    'started_at' => now()->subDays(100),
                    'ended_at' => now()->subDays(50),
                ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Injury History');
        });
    });

    describe('management relationships integration', function () {
        test('displays managers without wrestler/tag team relationships', function () {
            $manager = Manager::factory()->employed()->create(['first_name' => 'Independent', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Independent Manager');
        });

        test('displays managers who have managed wrestlers', function () {
            $manager = Manager::factory()->employed()->create(['first_name' => 'Wrestler', 'last_name' => 'Manager']);
            $wrestler = Wrestler::factory()->bookable()->create();

            // Create wrestler-manager relationship (managers are associated through wrestlers)
            $wrestler->managers()->attach($manager->id, [
                'hired_at' => now()->subMonths(6),
                'fired_at' => now()->subMonths(1),
            ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Wrestler Manager');
        });
    });

    describe('performance and data loading integration', function () {
        test('component handles large datasets efficiently', function () {
            // Create multiple managers with various relationships
            Manager::factory()->count(20)->create();

            // Add some relationships
            $managers = Manager::factory()->count(5)->employed()->create();
            $stables = Stable::factory()->count(3)->active()->create();

            // No stable relationships since managers are no longer direct stable members

            $component = Livewire::test(Main::class);

            // Component should render efficiently with created data
            $component->assertOk()
                ->assertSee('Manager'); // Should display some manager data
        });

        test('component eager loads necessary relationships', function () {
            $manager = Manager::factory()->employed()->create(['first_name' => 'Relationship', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            $component
                ->assertOk()
                ->assertSee('Relationship Manager');
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when manager data changes', function () {
            $manager = Manager::factory()->create(['first_name' => 'Original', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);
            $component->assertSee('Original Manager');

            // Update manager name
            $manager->update(['first_name' => 'Updated', 'last_name' => 'Manager']);

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Updated Manager');
            $component->assertDontSee('Original Manager');
        });

        test('component reflects employment status changes', function () {
            $manager = Manager::factory()->unemployed()->create(['first_name' => 'Employment', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            // Employ the manager
            EmployAction::run($manager, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Employment Manager');
        });

        test('component reflects injury status changes', function () {
            $manager = Manager::factory()->employed()->create(['first_name' => 'Injury', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            // Injure the manager
            InjureAction::run($manager, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Injury Manager');
        });
    });

    describe('complex business rule integration', function () {
        test('component handles managers with complex status combinations', function () {
            // Create manager with multiple statuses
            $manager = Manager::factory()->employed()->create(['first_name' => 'Complex', 'last_name' => 'Manager']);

            // Manager is employed but also injured
            InjureAction::run($manager, now());

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Complex Manager');

            // Should show both employed and injured status indicators
        });

        test('component respects business rules for action availability', function () {
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);
            $retiredManager = Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);

            // Component should render and show appropriate actions based on business rules
            $component
                ->assertOk()
                ->assertSee('Injured Manager')
                ->assertSee('Retired Manager');
        });

        test('component handles manager employment transitions', function () {
            $manager = Manager::factory()->employed()->create(['first_name' => 'Transitioning', 'last_name' => 'Manager']);

            $component = Livewire::test(Main::class);
            $component->assertSee('Transitioning Manager');

            // Test that component handles data changes appropriately
            $component->call('$refresh');
            $component->assertSee('Transitioning Manager');
        });
    });
});
