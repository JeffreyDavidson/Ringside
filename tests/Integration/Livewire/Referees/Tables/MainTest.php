<?php

declare(strict_types=1);

use App\Actions\Referees\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Referees\Tables\Main;
use App\Models\Roster\Referees\Referee;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('RefereesTable Component', function () {
    beforeEach(function () {
        actingAs(administrator());
    });

    describe('component rendering integration', function () {
        test('renders the configured table header and search prompt', function () {
            livewire(Main::class)
                ->assertSee('Add Referee')
                ->assertSeeHtml('placeholder="Search referees"');
        });

        test('renders referees with different lifecycle states', function () {
            Referee::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Referee']);
            Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);
            Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
            Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Referee')
                ->assertSee('Injured Referee')
                ->assertSee('Retired Referee')
                ->assertSee('Suspended Referee');
        });

        test('displays referees in each supported employment state', function () {
            Referee::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Referee']);
            Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);
            Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);
            Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee']);
            Referee::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Employed Referee')
                ->assertSee('Injured Referee')
                ->assertSee('Suspended Referee')
                ->assertSee('Retired Referee')
                ->assertSee('Released Referee');
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters referees correctly', function () {
            Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
            Referee::factory()->create(['first_name' => 'Dave', 'last_name' => 'Hebner']);
            Referee::factory()->create(['first_name' => 'Mike', 'last_name' => 'Chioda']);
            Referee::factory()->create(['first_name' => 'Nick', 'last_name' => 'Hebnerson']);

            $component = livewire(Main::class);

            $component
                ->set('search', 'Hebner')
                ->assertSee('Earl Hebner')
                ->assertSee('Dave Hebner')
                ->assertDontSee('Mike Chioda')
                ->assertDontSee('Nick Hebnerson');

            $component
                ->set('search', '')
                ->assertSee('Earl Hebner')
                ->assertSee('Dave Hebner')
                ->assertSee('Mike Chioda')
                ->assertSee('Nick Hebnerson');
        });

        test('status filter functionality works with real data', function () {
            $referees = [
                EmploymentStatus::Employed->value => Referee::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Referee'])->refresh(),
                EmploymentStatus::Released->value => Referee::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Referee'])->refresh(),
                EmploymentStatus::Unemployed->value => Referee::factory()->unemployed()->create(['first_name' => 'Unemployed', 'last_name' => 'Referee'])->refresh(),
                EmploymentStatus::Retired->value => Referee::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Referee'])->refresh(),
            ];

            foreach ($referees as $status => $visibleReferee) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleReferee->full_name);

                foreach ($referees as $otherStatus => $hiddenReferee) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenReferee->full_name);
                    }
                }
            }
        });

        test('future employment filter returns only referees awaiting employment', function () {
            $futureReferee = Referee::factory()->withFutureEmployment()
                ->create([
                    'first_name' => 'Future',
                    'last_name' => 'Referee',
                ])
                ->refresh();
            Referee::factory()->employed()->create([
                'first_name' => 'Employed',
                'last_name' => 'Referee',
            ]);

            livewire(Main::class)
                ->set('filterValues.status', 'future_employment')
                ->assertSee($futureReferee->full_name)
                ->assertDontSee('Employed Referee');
        });
    });

    describe('action integration', function () {
        test('restores a deleted referee and redirects to the index', function () {
            $deletedReferee = Referee::factory()->trashed()->create([
                'first_name' => 'Deleted',
                'last_name' => 'Referee',
            ]);

            livewire(Main::class)
                ->call('restore', $deletedReferee->id)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('referees.index');

            expect(Referee::find($deletedReferee->id))->not->toBeNull();
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            Referee::factory()->employed()->create(['first_name' => 'Currently', 'last_name' => 'Employed']);
            Referee::factory()->unemployed()->create(['first_name' => 'Currently', 'last_name' => 'Unemployed']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });
    });

    describe('injury and suspension integration', function () {
        test('displays injury status correctly', function () {
            Referee::factory()->employed()->create(['first_name' => 'Healthy', 'last_name' => 'Referee']);
            Referee::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Healthy Referee')
                ->assertSee('Injured Referee');
        });

        test('displays suspension status correctly', function () {
            Referee::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Referee']);
            Referee::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Referee']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Referee')
                ->assertSee('Suspended Referee');
        });
    });

    describe('data loading integration', function () {
        test('builder loads the first employment and current employment state', function () {
            $referee = Referee::factory()->employed()->create(['first_name' => 'Relationship', 'last_name' => 'Referee']);

            $loadedReferee = app(Main::class)->builder()->findOrFail($referee->id);

            expect($loadedReferee->relationLoaded('firstEmployment'))->toBeTrue()
                ->and($loadedReferee->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when referee data changes', function () {
            $referee = Referee::factory()->create(['first_name' => 'Original', 'last_name' => 'Referee']);

            $component = livewire(Main::class);
            $component->assertSee('Original Referee');

            $referee->update(['first_name' => 'Updated']);

            $component->call('$refresh');
            $component
                ->assertSee('Updated Referee')
                ->assertDontSee('Original Referee');
        });

        test('component reflects employment status changes', function () {
            $referee = Referee::factory()->unemployed()->create(['first_name' => 'Employment', 'last_name' => 'Referee']);

            $component = livewire(Main::class);
            $component->assertSee('Unemployed');

            resolve(EmployAction::class)->handle($referee, now());

            $component->call('$refresh');
            $component
                ->assertSee('Employment Referee')
                ->assertSee('Employed');
        });
    });
});
