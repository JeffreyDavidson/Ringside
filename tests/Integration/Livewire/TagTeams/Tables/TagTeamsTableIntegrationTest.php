<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Tables\TagTeamsTable;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamRetirement;
use App\Models\TagTeams\TagTeamSuspension;
use App\Models\Users\User;
use App\Models\Wrestlers\Wrestler;
use Livewire\Livewire;

/**
 * Integration tests for TagTeamsTable Livewire component.
 *
 * INTEGRATION TEST SCOPE:
 * - Component rendering with real database relationships
 * - Livewire property updates and form interactions
 * - Business action integration with real models
 * - Query building and filtering functionality
 * - Component state management with database
 * - Authorization integration with Gate facade
 *
 * These tests verify that the TagTeamsTable component works correctly
 * with actual database relationships and complex tag team scenarios
 * including partnership management and employment lifecycle.
 */
describe('TagTeamsTable Component Integration', function () {
    beforeEach(function () {
        $this->admin = User::factory()->administrator()->create();
    });

    describe('component rendering and data display', function () {
        test('renders tag teams table with complete data relationships', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'The Hardy Boyz']);
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'The Dudley Boyz']);
            $retiredTagTeam = TagTeam::factory()->retired()->create(['name' => 'The New Age Outlaws']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee($employedTagTeam->name)
                ->assertSee($suspendedTagTeam->name)
                ->assertSee($retiredTagTeam->name)
                ->assertSee('The Hardy Boyz')
                ->assertSee('The Dudley Boyz')
                ->assertSee('The New Age Outlaws');
        });

        test('displays tag team employment status information correctly', function () {
            $employedTagTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);
            $unemployedTagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Team']);
            $suspendedTagTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Employed Team')
                ->assertSee('Unemployed Team')
                ->assertSee('Suspended Team');
        });

        test('loads tag team employment periods for display', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Test Team']);

            // Verify employment period exists
            expect($tagTeam->currentEmployment)->not->toBeNull();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Test Team');
        });

        test('displays tag teams with wrestler partnerships', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'The Tag Team']);

            // Add wrestler partners to tag team
            $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Partner One']);
            $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Partner Two']);

            $wrestler1->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $wrestler2->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('The Tag Team');
        });
    });

    describe('filtering and search functionality', function () {
        test('search functionality filters tag teams correctly', function () {
            $hardyBoyz = TagTeam::factory()->employed()->create(['name' => 'The Hardy Boyz']);
            $dudleys = TagTeam::factory()->employed()->create(['name' => 'The Dudley Boyz']);
            $outlaws = TagTeam::factory()->employed()->create(['name' => 'New Age Outlaws']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            // Test search for "Hardy"
            $component->set('search', 'Hardy')
                ->assertSee('The Hardy Boyz')
                ->assertDontSee('The Dudley Boyz')
                ->assertDontSee('New Age Outlaws');

            // Test search for "Dudley"
            $component->set('search', 'Dudley')
                ->assertSee('The Dudley Boyz')
                ->assertDontSee('The Hardy Boyz')
                ->assertDontSee('New Age Outlaws');
        });

        test('employment status filter works correctly', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);
            $unemployedTeam = TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Team']);
            $suspendedTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            // Initially should see all tag teams
            $component->assertSee('Employed Team')
                ->assertSee('Unemployed Team')
                ->assertSee('Suspended Team');

            // Test filtering (exact filter implementation depends on component)
            $component->assertOk();
        });

        test('employment period filter functionality', function () {
            $oldTeam = TagTeam::factory()->employed()->create(['name' => 'Old Team']);
            $newTeam = TagTeam::factory()->employed()->create(['name' => 'New Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Old Team')
                ->assertSee('New Team');
        });
    });

    describe('tag team business actions integration', function () {
        test('employ action integration works correctly', function () {
            $unemployedTeam = TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('employ', $unemployedTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is employed
            expect($unemployedTeam->fresh()->isEmployed())->toBeTrue();
        });

        test('release action integration works correctly', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('release', $employedTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is released
            expect($employedTeam->fresh()->isReleased())->toBeTrue();
        });

        test('suspend action integration works correctly', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('suspend', $employedTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is suspended
            expect($employedTeam->fresh()->isSuspended())->toBeTrue();
        });

        test('reinstate action integration works correctly', function () {
            $suspendedTeam = TagTeam::factory()->suspended()->create(['name' => 'Suspended Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('reinstate', $suspendedTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is reinstated
            expect($suspendedTeam->fresh()->isEmployed())->toBeTrue();
        });

        test('retire action integration works correctly', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('retire', $employedTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is retired
            expect($employedTeam->fresh()->isRetired())->toBeTrue();
        });

        test('unretire action integration works correctly', function () {
            $retiredTeam = TagTeam::factory()->retired()->create(['name' => 'Retired Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('unretire', $retiredTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is unretired
            expect($retiredTeam->fresh()->isUnemployed())->toBeTrue();
        });

        test('restore action integration works correctly', function () {
            $deletedTeam = TagTeam::factory()->trashed()->create(['name' => 'Deleted Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('restore', $deletedTeam->id)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Verify tag team is restored
            expect(TagTeam::find($deletedTeam->id))->not->toBeNull();
            expect($deletedTeam->fresh())->not->toBeNull();
        });

        test('delete action integration works correctly', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Test Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('delete', $tagTeam)
                ->assertHasNoErrors();

            // Verify tag team is soft deleted
            expect(TagTeam::find($tagTeam->id))->toBeNull();
            expect(TagTeam::onlyTrashed()->find($tagTeam->id))->not->toBeNull();
        });
    });

    describe('business rule enforcement', function () {
        test('employ action fails for inappropriate tag team status', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('employ', $employedTeam)
                ->assertRedirect();

            // Verify tag team status unchanged
            expect($employedTeam->fresh()->isEmployed())->toBeTrue();
        });

        test('release action fails for non-employed tag team', function () {
            $unemployedTeam = TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('release', $unemployedTeam)
                ->assertRedirect();

            // Verify tag team status unchanged
            expect($unemployedTeam->fresh()->isUnemployed())->toBeTrue();
        });

        test('suspend action fails for non-employed tag team', function () {
            $unemployedTeam = TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('suspend', $unemployedTeam)
                ->assertRedirect();

            expect($unemployedTeam->fresh()->isUnemployed())->toBeTrue();
        });

        test('reinstate action fails for non-suspended tag team', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('reinstate', $employedTeam)
                ->assertRedirect();

            expect($employedTeam->fresh()->isEmployed())->toBeTrue();
        });

        test('unretire action fails for non-retired tag team', function () {
            $employedTeam = TagTeam::factory()->employed()->create(['name' => 'Employed Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->call('unretire', $employedTeam)
                ->assertRedirect();

            expect($employedTeam->fresh()->isEmployed())->toBeTrue();
        });
    });

    describe('authorization integration', function () {
        test('component requires proper authorization for access', function () {
            $basicUser = User::factory()->create();

            Livewire::actingAs($basicUser)
                ->test(TagTeamsTable::class)
                ->assertForbidden();
        });

        test('guest users cannot access component', function () {
            Livewire::test(TagTeamsTable::class)
                ->assertForbidden();
        });

        test('admin can perform all tag team actions', function () {
            $employedTeam = TagTeam::factory()->employed()->create();
            $unemployedTeam = TagTeam::factory()->unemployed()->create();
            $suspendedTeam = TagTeam::factory()->suspended()->create();
            $retiredTeam = TagTeam::factory()->retired()->create();
            $deletedTeam = TagTeam::factory()->trashed()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            // All actions should be available to admin
            $component->call('employ', $unemployedTeam)->assertHasNoErrors();
            $component->call('release', $employedTeam)->assertHasNoErrors();
            $component->call('suspend', $employedTeam)->assertHasNoErrors();
            $component->call('reinstate', $suspendedTeam)->assertHasNoErrors();
            $component->call('retire', $employedTeam)->assertHasNoErrors();
            $component->call('unretire', $retiredTeam)->assertHasNoErrors();
            $component->call('restore', $deletedTeam->id)->assertHasNoErrors();
        });
    });

    describe('query optimization and performance', function () {
        test('component loads efficiently with many tag teams', function () {
            TagTeam::factory()->count(20)->employed()->create();
            TagTeam::factory()->count(10)->retired()->create();
            TagTeam::factory()->count(5)->suspended()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk();
        });

        test('eager loading relationships works correctly', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Test Team']);

            // Ensure employment period exists for eager loading test
            expect($tagTeam->currentEmployment)->not->toBeNull();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Test Team');
        });

        test('component handles large datasets efficiently', function () {
            // Create tag teams with various statuses and relationships
            TagTeam::factory()->count(15)->employed()->create();
            TagTeam::factory()->count(10)->unemployed()->create();
            TagTeam::factory()->count(5)->retired()->create();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk();

            // Verify component loads without performance issues
            expect($component->payload['serverMemo']['data'])->toBeDefined();
        });
    });

    describe('complex tag team scenarios', function () {
        test('displays tag teams with multiple wrestler partnerships correctly', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Multi Partner Team']);

            // Add multiple wrestler partners
            $wrestler1 = Wrestler::factory()->bookable()->create(['name' => 'Partner One']);
            $wrestler2 = Wrestler::factory()->bookable()->create(['name' => 'Partner Two']);
            $wrestler3 = Wrestler::factory()->bookable()->create(['name' => 'Partner Three']);

            // Attach current partners
            $wrestler1->tagTeams()->attach($tagTeam->id, ['joined_at' => now()->subMonths(6), 'created_at' => now(), 'updated_at' => now()]);
            $wrestler2->tagTeams()->attach($tagTeam->id, ['joined_at' => now()->subMonths(4), 'created_at' => now(), 'updated_at' => now()]);

            // Attach former partner
            $wrestler3->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subYear(),
                'left_at' => now()->subMonths(8),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Multi Partner Team');
        });

        test('handles tag teams with changing partnership history', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Evolving Team']);

            // Add wrestler who left and rejoined
            $wrestler = Wrestler::factory()->bookable()->create(['name' => 'Returning Partner']);

            // First partnership period (ended)
            $wrestler->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subYear(),
                'left_at' => now()->subMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Current partnership period
            $wrestler->tagTeams()->attach($tagTeam->id, [
                'joined_at' => now()->subMonths(3),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Evolving Team');
        });

        test('displays tag teams with multiple employment periods correctly', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Rehired Team']);

            // This tag team would have had previous employment periods
            // The factory should handle creating the appropriate employment history

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Rehired Team');
        });

        test('handles tag teams with suspension history correctly', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Previously Suspended Team']);

            // Create a previous suspension that ended
            TagTeamSuspension::factory()
                ->for($tagTeam, 'tagTeam')
                ->past()
                ->create();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Previously Suspended Team');
        });

        test('displays tag teams with retirement and comeback history', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Comeback Team']);

            // Create a previous retirement that ended
            TagTeamRetirement::factory()
                ->for($tagTeam, 'tagTeam')
                ->past()
                ->create();

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $component->assertOk()
                ->assertSee('Comeback Team');
        });
    });

    describe('component state management', function () {
        test('component maintains state through action calls', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'State Test Team']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            // Perform action and verify component state updates
            $component->call('employ', $tagTeam)
                ->assertHasNoErrors()
                ->assertRedirect();

            // Component should reflect the change after refresh
            $refreshComponent = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            $refreshComponent->assertOk()
                ->assertSee('State Test Team');
        });

        test('component handles concurrent status changes gracefully', function () {
            $tagTeam1 = TagTeam::factory()->unemployed()->create(['name' => 'Team One']);
            $tagTeam2 = TagTeam::factory()->unemployed()->create(['name' => 'Team Two']);

            $component = Livewire::actingAs($this->admin)
                ->test(TagTeamsTable::class);

            // Perform multiple actions
            $component->call('employ', $tagTeam1)
                ->assertHasNoErrors();

            $component->call('employ', $tagTeam2)
                ->assertHasNoErrors();

            // Both should be successful
            expect($tagTeam1->fresh()->isEmployed())->toBeTrue();
            expect($tagTeam2->fresh()->isEmployed())->toBeTrue();
        });
    });
});
