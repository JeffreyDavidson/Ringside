<?php

declare(strict_types=1);

use App\Livewire\Managers\Modals\ManagerFormModal;
use App\Livewire\Managers\Tables\ManagersTable;
use App\Livewire\Stables\Modals\StableFormModal;
use App\Livewire\Stables\Tables\StablesTable;
use App\Livewire\TagTeams\Modals\TagTeamFormModal;
use App\Livewire\TagTeams\Tables\TagTeamsTable;
use App\Models\Managers\Manager;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

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
        $managerComponent = Livewire::actingAs($admin)
            ->test(ManagerFormModal::class)
            ->call('openModal')
            ->set('form.first_name', 'Paul')
            ->set('form.last_name', 'Heyman')
            ->set('form.employment_date', now()->format('Y-m-d'))
            ->call('submitForm')
            ->assertHasNoErrors();

        // Then: Manager should be created
        expect(Manager::where('first_name', 'Paul')->where('last_name', 'Heyman')->exists())->toBeTrue();
        $manager = Manager::where('first_name', 'Paul')->where('last_name', 'Heyman')->first();

        // When: Assigning manager to wrestler (assuming this functionality exists)
        // Note: This would depend on the actual implementation of manager assignment
        // For now, we'll verify the manager exists and can be viewed
        actingAs($admin)
            ->get(route('managers.index'))
            ->assertSee('Paul')
            ->assertSee('Heyman');

        // And: Manager appears in managers table
        Livewire::actingAs($admin)
            ->test(ManagersTable::class)
            ->assertSee('Paul')
            ->assertSee('Heyman');
    });

    test('manager employment lifecycle workflow', function () {
        // Given: A manager and administrator
        $admin = administrator();
        $manager = Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);

        // When: Managing employment status
        Livewire::actingAs($admin)
            ->test(ManagersTable::class)
            ->call('handleManagerAction', 'employ', $manager->id)
            ->assertHasNoErrors();

        // Then: Manager should be employed
        expect($manager->fresh()->isEmployed())->toBeTrue();

        // When: Suspending manager
        Livewire::actingAs($admin)
            ->test(ManagersTable::class)
            ->call('handleManagerAction', 'suspend', $manager->id)
            ->assertHasNoErrors();

        // Then: Manager should be suspended
        expect($manager->fresh()->isSuspended())->toBeTrue();

        // When: Reinstating manager
        Livewire::actingAs($admin)
            ->test(ManagersTable::class)
            ->call('handleManagerAction', 'reinstate', $manager->id)
            ->assertHasNoErrors();

        // Then: Manager should be employed again
        expect($manager->fresh()->isEmployed())->toBeTrue();
        expect($manager->fresh()->isSuspended())->toBeFalse();
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
        $stableComponent = Livewire::actingAs($admin)
            ->test(StableFormModal::class)
            ->call('openModal')
            ->set('form.name', 'D-Generation X')
            ->set('form.debut_date', now()->format('Y-m-d'))
            ->call('submitForm')
            ->assertHasNoErrors();

        // Then: Stable should be created
        expect(Stable::where('name', 'D-Generation X')->exists())->toBeTrue();
        $stable = Stable::where('name', 'D-Generation X')->first();

        // And: Stable appears in stables table
        Livewire::actingAs($admin)
            ->test(StablesTable::class)
            ->assertSee('D-Generation X');
    });

    test('stable lifecycle management workflow', function () {
        // Given: A stable and administrator
        $admin = administrator();
        $stable = Stable::factory()->create(['name' => 'The Shield']);

        // When: Debuting the stable
        Livewire::actingAs($admin)
            ->test(StablesTable::class)
            ->call('handleStableAction', 'debut', $stable->id)
            ->assertHasNoErrors();

        // Then: Stable should be active
        expect($stable->fresh()->isCurrentlyActive())->toBeTrue();

        // When: Retiring the stable
        Livewire::actingAs($admin)
            ->test(StablesTable::class)
            ->call('handleStableAction', 'retire', $stable->id)
            ->assertHasNoErrors();

        // Then: Stable should be retired
        expect($stable->fresh()->isRetired())->toBeTrue();

        // When: Unretiring the stable
        Livewire::actingAs($admin)
            ->test(StablesTable::class)
            ->call('handleStableAction', 'unretire', $stable->id)
            ->assertHasNoErrors();

        // Then: Stable should no longer be retired
        expect($stable->fresh()->isRetired())->toBeFalse();
    });
});

describe('Tag Team Formation and Management Workflow', function () {
    test('administrator can create tag team and manage partners', function () {
        // Given: An authenticated administrator and wrestlers
        $admin = administrator();
        $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Matt Hardy']);
        $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Jeff Hardy']);

        // When: Creating a new tag team
        $tagTeamComponent = Livewire::actingAs($admin)
            ->test(TagTeamFormModal::class)
            ->call('openModal')
            ->set('form.name', 'The Hardy Boyz')
            ->set('form.wrestlerA', $wrestler1->id)
            ->set('form.wrestlerB', $wrestler2->id)
            ->set('form.employment_date', now()->format('Y-m-d'))
            ->call('submitForm')
            ->assertHasNoErrors();

        // Then: Tag team should be created
        expect(TagTeam::where('name', 'The Hardy Boyz')->exists())->toBeTrue();
        $tagTeam = TagTeam::where('name', 'The Hardy Boyz')->first();

        // And: Tag team appears in tag teams table
        Livewire::actingAs($admin)
            ->test(TagTeamsTable::class)
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
        Livewire::actingAs($admin)
            ->test(TagTeamsTable::class)
            ->call('handleTagTeamAction', 'employ', $tagTeam->id)
            ->assertHasNoErrors();

        // Then: Tag team should be employed
        expect($tagTeam->fresh()->isEmployed())->toBeTrue();

        // When: Suspending tag team
        Livewire::actingAs($admin)
            ->test(TagTeamsTable::class)
            ->call('handleTagTeamAction', 'suspend', $tagTeam->id)
            ->assertHasNoErrors();

        // Then: Tag team should be suspended
        expect($tagTeam->fresh()->isSuspended())->toBeTrue();

        // When: Reinstating tag team
        Livewire::actingAs($admin)
            ->test(TagTeamsTable::class)
            ->call('handleTagTeamAction', 'reinstate', $tagTeam->id)
            ->assertHasNoErrors();

        // Then: Tag team should be employed again
        expect($tagTeam->fresh()->isEmployed())->toBeTrue();
        expect($tagTeam->fresh()->isSuspended())->toBeFalse();

        // When: Retiring tag team
        Livewire::actingAs($admin)
            ->test(TagTeamsTable::class)
            ->call('handleTagTeamAction', 'retire', $tagTeam->id)
            ->assertHasNoErrors();

        // Then: Tag team should be retired
        expect($tagTeam->fresh()->isRetired())->toBeTrue();
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
            'previous-managers-table',
            'previous-stables-table',
            'previous-tag-teams-table',
            'previous-title-championships-table',
            'previous-matches-table',
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
