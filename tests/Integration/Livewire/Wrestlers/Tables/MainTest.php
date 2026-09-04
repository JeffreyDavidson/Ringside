<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Wrestlers\Tables\Main;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('Main Component Integration', function () {

    beforeEach(function () {
        $this->user = administrator();
        actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders the configured table header and search prompt', function () {
            livewire(Main::class)
                ->assertSee('Add Wrestler')
                ->assertSeeHtml('placeholder="Search wrestlers"');
        });

        test('renders wrestlers with different lifecycle states', function () {
            $employedWrestler = Wrestler::factory()->employed()->create(['name' => 'Active Wrestler']);
            $injuredWrestler = Wrestler::factory()->injured()->create(['name' => 'Injured Wrestler']);
            $retiredWrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
            $suspendedWrestler = Wrestler::factory()->suspended()->create(['name' => 'Suspended Wrestler']);

            $component = livewire(Main::class);

            $component
                ->assertSee($employedWrestler->name)
                ->assertSee($injuredWrestler->name)
                ->assertSee($retiredWrestler->name)
                ->assertSee($suspendedWrestler->name);
        });

        test('displays correct status badges for different wrestler states', function () {
            $employedWrestler = Wrestler::factory()->employed()->create(['name' => 'Employed Wrestler']);
            $injuredWrestler = Wrestler::factory()->injured()->create(['name' => 'Injured Wrestler']);
            $suspendedWrestler = Wrestler::factory()->suspended()->create(['name' => 'Suspended Wrestler']);
            $retiredWrestler = Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']);
            $releasedWrestler = Wrestler::factory()->released()->create(['name' => 'Released Wrestler']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Employed Wrestler')
                ->assertSee('Injured Wrestler')
                ->assertSee('Suspended Wrestler')
                ->assertSee('Retired Wrestler')
                ->assertSee('Released Wrestler');
        });
    });

    describe('filtering and search integration', function () {
        test('search functionality filters wrestlers correctly', function () {
            Wrestler::factory()->create(['name' => 'John Cena']);
            Wrestler::factory()->create(['name' => 'The Rock']);
            Wrestler::factory()->create(['name' => 'Stone Cold Steve Austin']);

            $component = livewire(Main::class);

            $component
                ->set('search', 'John')
                ->assertSee('John Cena')
                ->assertDontSee('The Rock')
                ->assertDontSee('Stone Cold Steve Austin');

            $component
                ->set('search', '')
                ->assertSee('John Cena')
                ->assertSee('The Rock')
                ->assertSee('Stone Cold Steve Austin');
        });

        test('status filter functionality works with real data', function () {
            $wrestlers = [
                EmploymentStatus::Employed->value => Wrestler::factory()->employed()->create(['name' => 'Employed Wrestler']),
                EmploymentStatus::Released->value => Wrestler::factory()->released()->create(['name' => 'Released Wrestler']),
                EmploymentStatus::Unemployed->value => Wrestler::factory()->unemployed()->create(['name' => 'Unemployed Wrestler']),
                EmploymentStatus::Retired->value => Wrestler::factory()->retired()->create(['name' => 'Retired Wrestler']),
            ];

            foreach ($wrestlers as $status => $visibleWrestler) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleWrestler->name);

                foreach ($wrestlers as $otherStatus => $hiddenWrestler) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenWrestler->name);
                    }
                }
            }
        });

        test('future employment filter returns only wrestlers awaiting employment', function () {
            $futureWrestler = Wrestler::factory()->withFutureEmployment()->create(['name' => 'Future Wrestler']);
            Wrestler::factory()->employed()->create(['name' => 'Employed Wrestler']);

            livewire(Main::class)
                ->set('filterValues.status', 'future_employment')
                ->assertSee($futureWrestler->name)
                ->assertDontSee('Employed Wrestler');
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            $employedWrestler = Wrestler::factory()->employed()->create(['name' => 'Currently Employed']);
            $unemployedWrestler = Wrestler::factory()->unemployed()->create(['name' => 'Currently Unemployed']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });

    });

    describe('injury and suspension integration', function () {
        test('displays injury status correctly', function () {
            $healthyWrestler = Wrestler::factory()->employed()->create(['name' => 'Healthy Wrestler']);
            $injuredWrestler = Wrestler::factory()->injured()->create(['name' => 'Injured Wrestler']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Healthy Wrestler')
                ->assertSee('Injured Wrestler');
        });

        test('displays suspension status correctly', function () {
            $activeWrestler = Wrestler::factory()->employed()->create(['name' => 'Active Wrestler']);
            $suspendedWrestler = Wrestler::factory()->suspended()->create(['name' => 'Suspended Wrestler']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Wrestler')
                ->assertSee('Suspended Wrestler');
        });

    });

    describe('data loading integration', function () {
        test('builder loads the first employment and current employment state', function () {
            $wrestler = Wrestler::factory()->employed()->create(['name' => 'Relationship Wrestler']);

            $loadedWrestler = app(Main::class)->builder()->findOrFail($wrestler->id);

            expect($loadedWrestler->relationLoaded('firstEmployment'))->toBeTrue()
                ->and($loadedWrestler->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when wrestler data changes', function () {
            $wrestler = Wrestler::factory()->create(['name' => 'Original Wrestler']);

            $component = livewire(Main::class);
            $component->assertSee('Original Wrestler');

            $wrestler->update(['name' => 'Updated Wrestler']);

            $component->call('$refresh');
            $component->assertSee('Updated Wrestler');
            $component->assertDontSee('Original Wrestler');
        });

        test('component reflects employment status changes', function () {
            $wrestler = Wrestler::factory()->unemployed()->create(['name' => 'Employment Wrestler']);

            $component = livewire(Main::class);
            $component->assertSee('Unemployed');

            resolve(EmployAction::class)->handle($wrestler, now());

            $component->call('$refresh');
            $component
                ->assertSee('Employment Wrestler')
                ->assertSee('Employed');
        });
    });

    describe('wrestler specialization integration', function () {
        test('component displays wrestler physical attributes', function () {
            $wrestler = Wrestler::factory()->create([
                'name' => 'Big Wrestler',
                'height' => 78,
                'weight' => 300,
                'hometown' => 'Test City, TX',
            ]);

            $component = livewire(Main::class);

            $component
                ->assertSee('Big Wrestler')
                ->assertSee('6\'6"')
                ->assertSee('300')
                ->assertSee('Test City, TX');
        });
    });
});
