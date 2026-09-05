<?php

declare(strict_types=1);

use App\Actions\TagTeams\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\TagTeams\Tables\Main;
use App\Models\Roster\TagTeams\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('TagTeamsTable Component', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    describe('component rendering integration', function () {
        test('renders the configured table header and search prompt', function () {
            livewire(Main::class)
                ->assertSee('Add Tag Team')
                ->assertSeeHtml('placeholder="Search tag teams"');
        });

        test('renders tag teams with different lifecycle states', function () {
            TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);
            TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);
            TagTeam::factory()->released()->create(['name' => 'Released Tag Team']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Tag Team')
                ->assertSee('Suspended Tag Team')
                ->assertSee('Retired Tag Team')
                ->assertSee('Released Tag Team');
        });

        test('displays tag teams in each supported employment state', function () {
            TagTeam::factory()->employed()->create(['name' => 'Employed Tag Team']);
            TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);
            TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);
            TagTeam::factory()->released()->create(['name' => 'Released Tag Team']);
            TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Tag Team']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Employed Tag Team')
                ->assertSee('Suspended Tag Team')
                ->assertSee('Retired Tag Team')
                ->assertSee('Released Tag Team')
                ->assertSee('Unemployed Tag Team');
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters tag teams correctly', function () {
            TagTeam::factory()->create(['name' => 'The Hardy Boyz']);
            TagTeam::factory()->create(['name' => 'The Dudley Boyz']);
            TagTeam::factory()->create(['name' => 'New Age Outlaws']);

            $component = livewire(Main::class);

            $component
                ->set('search', 'Hardy')
                ->assertSee('The Hardy Boyz')
                ->assertDontSee('The Dudley Boyz')
                ->assertDontSee('New Age Outlaws');

            $component
                ->set('search', '')
                ->assertSee('The Hardy Boyz')
                ->assertSee('The Dudley Boyz')
                ->assertSee('New Age Outlaws');
        });

        test('status filter functionality works with real data', function () {
            $tagTeams = [
                EmploymentStatus::Employed->value => TagTeam::factory()->employed()->create(['name' => 'Employed Tag Team']),
                EmploymentStatus::Released->value => TagTeam::factory()->released()->create(['name' => 'Released Tag Team']),
                EmploymentStatus::Unemployed->value => TagTeam::factory()->unemployed()->create(['name' => 'Unemployed Tag Team']),
                EmploymentStatus::Retired->value => TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']),
            ];

            foreach ($tagTeams as $status => $visibleTagTeam) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleTagTeam->name);

                foreach ($tagTeams as $otherStatus => $hiddenTagTeam) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenTagTeam->name);
                    }
                }
            }
        });

        test('future employment filter returns only tag teams awaiting employment', function () {
            $futureTagTeam = TagTeam::factory()->withFutureEmployment()->create(['name' => 'Future Tag Team']);
            TagTeam::factory()->employed()->create(['name' => 'Employed Tag Team']);

            livewire(Main::class)
                ->set('filterValues.status', 'future_employment')
                ->assertSee($futureTagTeam->name)
                ->assertDontSee('Employed Tag Team');
        });
    });

    describe('action integration', function () {
        test('restores a deleted tag team and redirects to the index', function () {
            $deletedTagTeam = TagTeam::factory()->trashed()->create(['name' => 'Deleted Tag Team']);

            livewire(Main::class)
                ->call('restore', $deletedTagTeam->id)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('tag-teams.index');

            expect(TagTeam::find($deletedTagTeam->id))->not->toBeNull();
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            TagTeam::factory()->employed()->create(['name' => 'Currently Employed']);
            TagTeam::factory()->unemployed()->create(['name' => 'Currently Unemployed']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });
    });

    describe('retirement and suspension integration', function () {
        test('displays retirement status correctly', function () {
            TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            TagTeam::factory()->retired()->create(['name' => 'Retired Tag Team']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Tag Team')
                ->assertSee('Retired Tag Team');
        });

        test('displays suspension status correctly', function () {
            TagTeam::factory()->employed()->create(['name' => 'Active Tag Team']);
            TagTeam::factory()->suspended()->create(['name' => 'Suspended Tag Team']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Tag Team')
                ->assertSee('Suspended Tag Team');
        });
    });

    describe('data loading integration', function () {
        test('builder loads the first employment and current employment state', function () {
            $tagTeam = TagTeam::factory()->employed()->create(['name' => 'Relationship Team']);

            $loadedTagTeam = app(Main::class)->builder()->findOrFail($tagTeam->id);

            expect($loadedTagTeam->relationLoaded('firstEmployment'))->toBeTrue()
                ->and($loadedTagTeam->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when tag team data changes', function () {
            $tagTeam = TagTeam::factory()->create(['name' => 'Original Team']);

            $component = livewire(Main::class);
            $component->assertSee('Original Team');

            $tagTeam->update(['name' => 'Updated Team']);

            $component->call('$refresh');
            $component
                ->assertSee('Updated Team')
                ->assertDontSee('Original Team');
        });

        test('component reflects employment status changes', function () {
            $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Employment Team']);

            $component = livewire(Main::class);
            $component->assertSee('Unemployed');

            resolve(EmployAction::class)->handle($tagTeam, now());

            $component->call('$refresh');
            $component
                ->assertSee('Employment Team')
                ->assertSee('Employed');
        });
    });
});
