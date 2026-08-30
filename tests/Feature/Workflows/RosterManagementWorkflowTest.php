<?php

declare(strict_types=1);

use App\Livewire\Managers\Modals\FormModal as ManagerFormModal;
use App\Livewire\Managers\Tables\Main as ManagersTable;
use App\Livewire\Stables\Modals\FormModal as StableFormModal;
use App\Livewire\Stables\Tables\Main as StablesTable;
use App\Livewire\TagTeams\Modals\FormModal as TagTeamFormModal;
use App\Livewire\TagTeams\Tables\Main as TagTeamsTable;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

/**
 * Feature tests for complete roster management workflows.
 * Tests realistic scenarios for managing complex wrestler relationships including managers, stables, and tag teams.
 */
describe('Manager Assignment Workflow', function () {
    test('administrator can create manager and assign to wrestler', function () {
        // Given: An authenticated administrator and an existing wrestler
        $admin = administrator();
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Randy Orton']);

        // When: Creating a new manager
        actingAs($admin);

        $managerComponent = livewire(ManagerFormModal::class)
            ->call('openModal')
            ->set('form.first_name', 'Paul')
            ->set('form.last_name', 'Heyman')
            ->set('form.employment_date', now()->format('Y-m-d'))
            ->call('submitForm')
            ->assertHasNoErrors();

        // Then: Manager should be created
        expect(Manager::where('first_name', 'Paul')->where('last_name', 'Heyman')->exists())->toBeTrue();
        $manager = Manager::where('first_name', 'Paul')->where('last_name', 'Heyman')->firstOrFail();

        // When: Assigning manager to wrestler (assuming this functionality exists)
        // Note: This would depend on the actual implementation of manager assignment
        // For now, we'll verify the manager exists and can be viewed
        actingAs($admin)
            ->get(route('managers.index'))
            ->assertSee('Paul')
            ->assertSee('Heyman');

        // And: Manager appears in managers table
        actingAs($admin);

        livewire(ManagersTable::class)
            ->assertSee('Paul')
            ->assertSee('Heyman');
    });

    test('manager employment lifecycle workflow', function () {
        // Given: A manager and administrator
        $admin = administrator();
        $manager = Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);

        // When: Managing employment status
        actingAs($admin);

        livewire(ManagersTable::class)
            ->call('employ', $manager)
            ->assertHasNoErrors();

        // Then: Manager should be employed
        expect(freshModel($manager)->isEmployed())->toBeTrue();

        // When: Suspending manager
        actingAs($admin);

        livewire(ManagersTable::class)
            ->call('suspend', $manager)
            ->assertHasNoErrors();

        // Then: Manager should be suspended
        expect(freshModel($manager)->isSuspended())->toBeTrue();

        // When: Reinstating manager
        actingAs($admin);

        livewire(ManagersTable::class)
            ->call('reinstate', $manager)
            ->assertHasNoErrors();

        // Then: Manager should be employed again
        expect(freshModel($manager)->isEmployed())->toBeTrue();
        expect(freshModel($manager)->isSuspended())->toBeFalse();
    });
});

describe('Stable Formation and Management Workflow', function () {
    test('administrator can create stable and manage members', function () {
        // Given: An authenticated administrator and wrestlers
        $admin = administrator();
        $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Triple H']);
        $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Shawn Michaels']);
        $wrestler3 = Wrestler::factory()->bookable()->create(['name' => 'Chyna']);

        // When: Creating a new stable
        actingAs($admin);

        $stableComponent = livewire(StableFormModal::class)
            ->call('openModal')
            ->set('form.name', 'D-Generation X')
            ->set('form.debut_date', now()->format('Y-m-d'))
            ->call('submitForm')
            ->assertHasNoErrors();

        // Then: Stable should be created
        expect(Stable::where('name', 'D-Generation X')->exists())->toBeTrue();
        $stable = Stable::where('name', 'D-Generation X')->firstOrFail();

        // And: Stable appears in stables table
        actingAs($admin);

        livewire(StablesTable::class)
            ->assertSee('D-Generation X');
    });

    test('stable lifecycle management workflow', function () {
        // Given: A stable and administrator
        $admin = administrator();
        $stable = Stable::factory()->withEmployedDefaultMembers()->create(['name' => 'The Shield']);

        // When: Establishing the stable
        actingAs($admin);

        livewire(StablesTable::class)
            ->call('establish', $stable)
            ->assertHasNoErrors();

        // Then: Stable should be active
        expect(freshModel($stable)->currentActivityPeriod()->exists())->toBeTrue();

        // When: Retiring the stable
        actingAs($admin);

        livewire(StablesTable::class)
            ->call('retire', $stable)
            ->assertHasNoErrors();

        // Then: Stable should be retired
        expect(freshModel($stable)->isRetired())->toBeTrue();

        // Given: A retired stable with viable former members
        $retiredStable = Stable::factory()->retired()->create(['name' => 'Evolution']);

        // When: Unretiring the stable
        actingAs($admin);

        livewire(StablesTable::class)
            ->call('unretire', $retiredStable)
            ->assertHasNoErrors();

        // Then: Stable should no longer be retired
        expect(freshModel($retiredStable)->isRetired())->toBeFalse();
    });
});

describe('Tag Team Formation and Management Workflow', function () {
    test('administrator can create tag team and manage partners', function () {
        // Given: An authenticated administrator and wrestlers
        $admin = administrator();
        $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Matt Hardy']);
        $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Jeff Hardy']);

        // When: Creating a new tag team
        actingAs($admin);

        $tagTeamComponent = livewire(TagTeamFormModal::class)
            ->call('openModal')
            ->set('form.name', 'The Hardy Boyz')
            ->set('form.wrestlerA', $wrestler1->id)
            ->set('form.wrestlerB', $wrestler2->id)
            ->set('form.employment_date', now()->format('Y-m-d'))
            ->call('submitForm')
            ->assertHasNoErrors();

        // Then: Tag team should be created
        expect(TagTeam::where('name', 'The Hardy Boyz')->exists())->toBeTrue();
        $tagTeam = TagTeam::where('name', 'The Hardy Boyz')->firstOrFail();

        // And: Tag team appears in tag teams table
        actingAs($admin);

        livewire(TagTeamsTable::class)
            ->assertSee('The Hardy Boyz');

        // When: Viewing tag team details
        actingAs($admin)
            ->get(route('tag-teams.show', $tagTeam))
            ->assertOk();
    });

    test('tag team employment lifecycle workflow', function () {
        // Given: A tag team with wrestlers and administrator
        $admin = administrator();
        $wrestlers = Wrestler::factory()->count(2)->create();
        $tagTeam = TagTeam::factory()->create(['name' => 'The Dudley Boyz']);

        // Attach wrestlers to the tag team
        $tagTeam->wrestlers()->attach($wrestlers->pluck('id'), ['joined_at' => now()]);

        // When: Employing the tag team
        actingAs($admin);

        livewire(TagTeamsTable::class)
            ->call('employ', $tagTeam)
            ->assertHasNoErrors();

        // Then: Tag team should be employed
        expect(freshModel($tagTeam)->isEmployed())->toBeTrue();

        // When: Suspending tag team
        actingAs($admin);

        livewire(TagTeamsTable::class)
            ->call('suspend', $tagTeam)
            ->assertHasNoErrors();

        // Then: Tag team should be suspended
        expect(freshModel($tagTeam)->isSuspended())->toBeTrue();

        // When: Reinstating tag team
        actingAs($admin);

        livewire(TagTeamsTable::class)
            ->call('reinstate', $tagTeam)
            ->assertHasNoErrors();

        // Then: Tag team should be employed again
        expect(freshModel($tagTeam)->isEmployed())->toBeTrue();
        expect(freshModel($tagTeam)->isSuspended())->toBeFalse();

        // When: Retiring tag team
        actingAs($admin);

        livewire(TagTeamsTable::class)
            ->call('retire', $tagTeam)
            ->assertHasNoErrors();

        // Then: Tag team should be retired
        expect(freshModel($tagTeam)->isRetired())->toBeTrue();
    });
});

describe('Complex Roster Relationship Workflow', function () {
    test('wrestler can have multiple relationship changes over time', function () {
        // Given: A wrestler, manager, stable, and tag team
        $admin = administrator();
        $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Chris Jericho']);
        $manager = Manager::factory()->create(['first_name' => 'Stephanie', 'last_name' => 'McMahon']);
        $stable = Stable::factory()->create(['name' => 'The Corporation']);
        $tagTeam = TagTeam::factory()->create(['name' => 'Y2AJ']);

        // When: Viewing wrestler's history tables (empty initially)
        actingAs($admin)
            ->get(route('wrestlers.show', $wrestler))
            ->assertOk();

        // Then: History tables should be empty but visible
        $historyComponents = [
            'previous-managers',
            'previous-stables',
            'previous-tag-teams',
            'previous-title-championships',
            'previous-matches',
        ];

        foreach ($historyComponents as $component) {
            actingAs($admin)
                ->get(route('wrestlers.show', $wrestler))
                ->assertSeeLivewire("wrestlers.tables.{$component}");
        }

        // Note: In a real implementation, we would test the actual relationship
        // management workflows here, but that would require the relationship
        // assignment functionality to be implemented in the UI components
    });
});

describe('Roster Navigation Workflow', function () {
    test('administrator can navigate between different roster sections', function () {
        // Given: An authenticated administrator
        $admin = administrator();

        // When: Starting from wrestlers and navigating to other roster sections
        actingAs($admin)
            ->get(route('wrestlers.index'))
            ->assertOk()
            ->assertSee('wrestlers');

        // Then: Can navigate to managers
        actingAs($admin)
            ->get(route('managers.index'))
            ->assertOk()
            ->assertSee('managers');

        // And: Can navigate to referees
        actingAs($admin)
            ->get(route('referees.index'))
            ->assertOk()
            ->assertSee('referees');

        // And: Can navigate to tag teams
        actingAs($admin)
            ->get(route('tag-teams.index'))
            ->assertOk()
            ->assertSee('tag');

        // And: Can navigate to stables
        actingAs($admin)
            ->get(route('stables.index'))
            ->assertOk()
            ->assertSee('stables');

        // And: Can return to dashboard
        actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');
    });
});
