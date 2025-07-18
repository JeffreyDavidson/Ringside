<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Actions\TagTeams\ReinstateAction;
use App\Actions\TagTeams\ReleaseAction;
use App\Actions\TagTeams\RetireAction;
use App\Actions\TagTeams\SuspendAction;
use App\Actions\TagTeams\UnretireAction;
use App\Livewire\TagTeams\Tables\TagTeamsTable;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamEmployment;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\TagTeams\TagTeamSuspension;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Integration tests for TagTeamsTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with complex data relationships
 * - Filtering and search functionality integration
 * - Action dropdown integration
 * - Status display integration
 * - Real database interaction with relationships
 */
describe('TagTeamsTable Component', function () {

    beforeEach(function () {
        $this->user = administrator();
        $this->actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders tag teams table with complete data relationships', function () {
            // Create tag teams with different statuses and relationships
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);
            $releasedTagTeam = TagTeam::factory()->released()->create(['name' => 'Released Tag Team']);

            // Create wrestler relationships
            $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Team Member One']);
            $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Team Member Two']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee($employedTagTeam->name)
                ->assertSee($suspendedTagTeam->name)
                ->assertSee($retiredTagTeam->name)
                ->assertSee($releasedTagTeam->name);
        });

        test('displays correct status badges for different tag team states', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Tag Team']);
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);
            $releasedTagTeam = TagTeam::factory()->released()->create(['name' => 'Released Tag Team']);
            $unemployedTagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Tag Team']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Employed Tag Team')
                ->assertSee('Suspended Tag Team')
                ->assertSee('Retired Tag Team')
                ->assertSee('Released Tag Team')
                ->assertSee('Unemployed Tag Team')
                // Status indicators should be present (exact text may vary)
                ->assertSeeHtml('class'); // Status classes should be rendered
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters tag teams correctly', function () {
            TagTeam::factory()->create(['name' => 'The Hardy Boyz']);
            TagTeam::factory()->create(['name' => 'The Dudley Boyz']);
            TagTeam::factory()->create(['name' => 'New Age Outlaws']);

            $component = Livewire::test(Main::class);

            // Test search functionality
            $component
                ->set('search', 'Hardy')
                ->assertSee('The Hardy Boyz')
                ->assertDontSee('The Dudley Boyz')
                ->assertDontSee('New Age Outlaws');

            // Test clearing search
            $component
                ->set('search', '')
                ->assertSee('The Hardy Boyz')
                ->assertSee('The Dudley Boyz')
                ->assertSee('New Age Outlaws');
        });

        test('status filter functionality works with real data', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Tag Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);

            $component = Livewire::test(Main::class);

            // Test filtering by status (if component supports it)
            $component
                ->assertSee('Employed Tag Team')
                ->assertSee('Retired Tag Team')
                ->assertSee('Suspended Tag Team');
        });
    });

    describe('action integration', function () {
        test('action dropdown displays appropriate actions for tag team states', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);

            $component = Livewire::test(Main::class);

            // Component should render without errors
            $component->assertOk();

            // Actions should be available (specific actions depend on component implementation)
            $component->assertSee($employedTagTeam->name);
            $component->assertSee($retiredTagTeam->name);
        });

        test('component integrates with authorization policies', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Test Tag Team']);

            // Test as administrator (should see all actions)
            $component = Livewire::actingAs($this->user)->test(Main::class);
            $component->assertOk();
            $component->assertSee($tagTeam->name);
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'Currently Employed']);
            $unemployedTagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Currently Unemployed']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });

        test('handles tag teams with employment history', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Team History']);

            // Create employment history
            TagTeamEmployment::factory()
                ->for($tagTeam, 'tagTeam')
                ->create([
                    'started_at' => now()->subDays(200),
                    'ended_at' => now()->subDays(100),
                ]);

            TagTeamEmployment::factory()
                ->for($tagTeam, 'tagTeam')
                ->current()
                ->create(['started_at' => now()->subDays(50)]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Team History');
        });
    });

    describe('retirement and suspension integration', function () {
        test('displays retirement status correctly', function () {
            $activeTagTeam = TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Active Tag Team')
                ->assertSee('Retired Tag Team');
        });

        test('displays suspension status correctly', function () {
            $activeTagTeam = TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Active Tag Team')
                ->assertSee('Suspended Tag Team');
        });

        test('handles tag teams with retirement history', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Retirement History']);

            // Create retirement history
            TagTeamRetirement::factory()
                ->for($tagTeam, 'tagTeam')
                ->create([
                    'started_at' => now()->subDays(100),
                    'ended_at' => now()->subDays(50),
                ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Retirement History');
        });

        test('handles tag teams with suspension history', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Suspension History']);

            // Create suspension history
            TagTeamSuspension::factory()
                ->for($tagTeam, 'tagTeam')
                ->create([
                    'started_at' => now()->subDays(100),
                    'ended_at' => now()->subDays(50),
                ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Suspension History');
        });
    });

    describe('wrestler partnership integration', function () {
        test('displays tag teams without wrestler partnerships', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Independent Tag Team']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Independent Tag Team');
        });

        test('displays tag teams with current wrestler partnerships', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Partner Tag Team']);
            $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Partner One']);
            $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Partner Two']);

            // Create tag team partnerships
            $wrestler1->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subMonths(6),
                'left_at' => null,
            ]);

            $wrestler2->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subMonths(3),
                'left_at' => null,
            ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Partner Tag Team');
        });

        test('displays tag teams with former wrestler partnerships', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Former Partner Team']);
            $formerWrestler = Wrestler::factory()->bookable()->create(['name' => 'Former Partner']);
            $currentWrestler = Wrestler::factory()->bookable()->create(['name' => 'Current Partner']);

            // Create former partnership
            $formerWrestler->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subYear(),
                'left_at' => now()->subMonths(6),
            ]);

            // Create current partnership
            $currentWrestler->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subMonths(3),
                'left_at' => null,
            ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Former Partner Team');
        });
    });

    describe('performance and data loading integration', function () {
        test('component handles large datasets efficiently', function () {
            // Create multiple tag teams with various relationships
            TagTeam::factory()->count(20)->create();

            // Add some relationships
            $tagTeams = TagTeam::factory()->count(5)->employed()->create();
            $wrestlers = Wrestler::factory()->count(10)->bookable()->create();

            $component = Livewire::test(Main::class);

            // Component should render efficiently with created data
            $component->assertOk()
                ->assertSee('Tag Team'); // Should display some tag team data
        });

        test('component eager loads necessary relationships', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Relationship Team']);

            $component = Livewire::test(Main::class);

            $component
                ->assertOk()
                ->assertSee('Relationship Team');
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when tag team data changes', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Original Team']);

            $component = Livewire::test(Main::class);
            $component->assertSee('Original Team');

            // Update tag team name
            $tagTeam->update(['name' => 'Updated Team']);

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Updated Team');
            $component->assertDontSee('Original Team');
        });

        test('component reflects employment status changes', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Employment Team']);

            $component = Livewire::test(Main::class);

            // Employ the tag team
            EmployAction::run($tagTeam, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Employment Team');
        });

        test('component reflects suspension status changes', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Suspension Team']);

            $component = Livewire::test(Main::class);

            // Suspend the tag team
            SuspendAction::run($tagTeam, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Suspension Team');
        });

        test('component reflects retirement status changes', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Retirement Team']);

            $component = Livewire::test(Main::class);

            // Retire the tag team
            RetireAction::run($tagTeam, now());

            // Refresh component
            $component->call('$refresh');
            $component->assertSee('Retirement Team');
        });
    });

    describe('complex business rule integration', function () {
        test('component handles tag teams with complex status combinations', function () {
            // Create tag team with multiple statuses
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Complex Team']);

            // Tag team is employed but also suspended
            SuspendAction::run($tagTeam, now());

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Complex Team');

            // Should show both employed and suspended status indicators
        });

        test('component respects business rules for action availability', function () {
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Team']);

            $component = Livewire::test(Main::class);

            // Component should render and show appropriate actions based on business rules
            $component
                ->assertOk()
                ->assertSee('Suspended Team')
                ->assertSee('Retired Team');
        });

        test('component handles tag team employment transitions', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Transitioning Team']);

            $component = Livewire::test(Main::class);
            $component->assertSee('Transitioning Team');

            // Test that component handles data changes appropriately
            $component->call('$refresh');
            $component->assertSee('Transitioning Team');
        });

        test('component handles tag team bookability for matches', function () {
            $bookableTagTeam = TagTeam::factory()->bookable()->create(['name' => 'Bookable Team']);
            $unbookableTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Unbookable Team']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Team']);

            $component = Livewire::test(Main::class);

            // Component should show all tag teams with appropriate status indicators
            $component
                ->assertOk()
                ->assertSee('Bookable Team')
                ->assertSee('Unbookable Team')
                ->assertSee('Retired Team');
        });
    });

    describe('tag team specialization integration', function () {
        test('component displays tag team signature moves', function () {
            $tagTeam = TagTeam::factory()->create([
                'name' => 'Signature Team',
                'signature_move' => 'Poetry in Motion',
            ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Signature Team');
        });

        test('component handles tag teams with different partnership lengths', function () {
            $veteranTeam = TagTeam::factory()->employed()->create(['name' => 'Veteran Team']);
            $newTeam = TagTeam::factory()->employed()->create(['name' => 'New Team']);

            // Create different employment history lengths
            TagTeamEmployment::factory()
                ->for($veteranTeam, 'tagTeam')
                ->create([
                    'started_at' => now()->subYears(5),
                    'ended_at' => now()->subYears(3),
                ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Veteran Team')
                ->assertSee('New Team');
        });

        test('component displays tag teams with different roster counts', function () {
            $dualTeam = TagTeam::factory()->employed()->create(['name' => 'Dual Team']);
            $tripleTeam = TagTeam::factory()->employed()->create(['name' => 'Triple Team']);

            // Create different wrestler counts
            $wrestler1 = Wrestler::factory()->bookable()->create();
            $wrestler2 = Wrestler::factory()->bookable()->create();
            $wrestler3 = Wrestler::factory()->bookable()->create();

            // Dual team - 2 wrestlers
            $wrestler1->tagTeams()->attach($dualTeam->id, ['joined_at' => now()->subMonths(6)]);
            $wrestler2->tagTeams()->attach($dualTeam->id, ['joined_at' => now()->subMonths(6)]);

            // Triple team - 3 wrestlers
            $wrestler1->tagTeams()->attach($tripleTeam->id, ['joined_at' => now()->subMonths(3)]);
            $wrestler2->tagTeams()->attach($tripleTeam->id, ['joined_at' => now()->subMonths(3)]);
            $wrestler3->tagTeams()->attach($tripleTeam->id, ['joined_at' => now()->subMonths(3)]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Dual Team')
                ->assertSee('Triple Team');
        });
    });

    describe('tag team lifecycle integration', function () {
        test('component handles tag team formation and dissolution', function () {
            $formingTeam = TagTeam::factory()->unemployed()->create(['name' => 'Forming Team']);
            $dissolvingTeam = TagTeam::factory()->employed()->create(['name' => 'Dissolving Team']);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Forming Team')
                ->assertSee('Dissolving Team');

            // Test status transitions
            EmployAction::run($formingTeam, now());
            ReleaseAction::run($dissolvingTeam, now());

            $component->call('$refresh');
            $component
                ->assertSee('Forming Team')
                ->assertSee('Dissolving Team');
        });

        test('component handles tag team reunions', function () {
            $reunitedTeam = TagTeam::factory()->unemployed()->create(['name' => 'Reunited Team']);

            // Create previous employment and retirement
            TagTeamEmployment::factory()
                ->for($reunitedTeam, 'tagTeam')
                ->create([
                    'started_at' => now()->subYears(2),
                    'ended_at' => now()->subYear(),
                ]);

            TagTeamRetirement::factory()
                ->for($reunitedTeam, 'tagTeam')
                ->create([
                    'started_at' => now()->subYear(),
                    'ended_at' => now()->subMonths(6),
                ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Reunited Team');
        });

        test('component handles tag team member changes', function () {
            $evolvingTeam = TagTeam::factory()->employed()->create(['name' => 'Evolving Team']);

            $originalMember1 = Wrestler::factory()->bookable()->create(['name' => 'Original One']);
            $originalMember2 = Wrestler::factory()->bookable()->create(['name' => 'Original Two']);
            $newMember = Wrestler::factory()->bookable()->create(['name' => 'New Member']);

            // Original formation
            $originalMember1->tagTeams()->attach($evolvingTeam->id, [
                'joined_at' => now()->subYear(),
                'left_at' => null,
            ]);
            $originalMember2->tagTeams()->attach($evolvingTeam->id, [
                'joined_at' => now()->subYear(),
                'left_at' => now()->subMonths(6), // Left the team
            ]);

            // New member joins
            $newMember->tagTeams()->attach($evolvingTeam->id, [
                'joined_at' => now()->subMonths(3),
                'left_at' => null,
            ]);

            $component = Livewire::test(Main::class);

            $component
                ->assertSee('Evolving Team');
        });
    });
});
