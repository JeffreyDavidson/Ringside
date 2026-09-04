<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Managers\Tables\Main;
use App\Models\Roster\Managers\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('ManagersTable Component', function () {

    beforeEach(function () {
        $this->user = administrator();
        actingAs($this->user);
    });

    describe('component rendering integration', function () {
        test('renders managers table with complete data relationships', function () {
            Manager::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Aaaaaa']);
            Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Aaaaab']);
            Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Aaaaac']);
            Manager::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Aaaaad']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Aaaaaa')
                ->assertSee('Injured Aaaaab')
                ->assertSee('Retired Aaaaac')
                ->assertSee('Suspended Aaaaad');
        })->group('managers', 'tables', 'rendering', 'integration');

        test('displays correct status badges for different manager states', function () {
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Manager']);
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Manager']);
            $retiredManager = Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager']);
            $releasedManager = Manager::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Manager']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Employed Manager')
                ->assertSee('Injured Manager')
                ->assertSee('Suspended Manager')
                ->assertSee('Retired Manager')
                ->assertSee('Released Manager');
        })->group('managers', 'tables', 'status', 'badges');
    });

    describe('filtering and search integration', function () {
        test('search functionality filters managers correctly', function () {
            Manager::factory()->create(['first_name' => 'Paul', 'last_name' => 'Bearer']);
            Manager::factory()->create(['first_name' => 'Jimmy', 'last_name' => 'Hart']);
            Manager::factory()->create(['first_name' => 'Bobby', 'last_name' => 'Heenan']);

            $component = livewire(Main::class);

            $component
                ->set('search', 'Paul')
                ->assertSee('Paul Bearer')
                ->assertDontSee('Jimmy Hart')
                ->assertDontSee('Bobby Heenan');

            $component
                ->set('search', '')
                ->assertSee('Paul Bearer')
                ->assertSee('Jimmy Hart')
                ->assertSee('Bobby Heenan');
        });

        test('status filter functionality works with real data', function () {
            $managers = [
                EmploymentStatus::Employed->value => Manager::factory()->employed()->create(['first_name' => 'Employed', 'last_name' => 'Manager'])->refresh(),
                EmploymentStatus::Released->value => Manager::factory()->released()->create(['first_name' => 'Released', 'last_name' => 'Manager'])->refresh(),
                EmploymentStatus::Unemployed->value => Manager::factory()->unemployed()->create(['first_name' => 'Unemployed', 'last_name' => 'Manager'])->refresh(),
                EmploymentStatus::Retired->value => Manager::factory()->retired()->create(['first_name' => 'Retired', 'last_name' => 'Manager'])->refresh(),
            ];

            foreach ($managers as $status => $visibleManager) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleManager->full_name);

                foreach ($managers as $otherStatus => $hiddenManager) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenManager->full_name);
                    }
                }
            }
        });

        test('future employment filter returns only managers awaiting employment', function () {
            $futureManager = Manager::factory()->withFutureEmployment()
                ->create([
                    'first_name' => 'Future',
                    'last_name' => 'Manager',
                ])
                ->refresh();
            Manager::factory()->employed()->create([
                'first_name' => 'Employed',
                'last_name' => 'Manager',
            ]);

            livewire(Main::class)
                ->set('filterValues.status', 'future_employment')
                ->assertSee($futureManager->full_name)
                ->assertDontSee('Employed Manager');
        });
    });

    describe('action integration', function () {
        test('restores a deleted manager and redirects to the index', function () {
            $deletedManager = Manager::factory()->trashed()->create([
                'first_name' => 'Deleted',
                'last_name' => 'Manager',
            ]);

            livewire(Main::class)
                ->call('restore', $deletedManager->id)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('managers.index');

            expect(Manager::find($deletedManager->id))->not->toBeNull();
        });
    });

    describe('employment status integration', function () {
        test('displays current employment status correctly', function () {
            $employedManager = Manager::factory()->employed()->create(['first_name' => 'Currently', 'last_name' => 'Employed']);
            $unemployedManager = Manager::factory()->unemployed()->create(['first_name' => 'Currently', 'last_name' => 'Unemployed']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Currently Employed')
                ->assertSee('Currently Unemployed');
        });

    });

    describe('injury and suspension integration', function () {
        test('displays injury status correctly', function () {
            $healthyManager = Manager::factory()->employed()->create(['first_name' => 'Healthy', 'last_name' => 'Manager']);
            $injuredManager = Manager::factory()->injured()->create(['first_name' => 'Injured', 'last_name' => 'Manager']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Healthy Manager')
                ->assertSee('Injured Manager');
        });

        test('displays suspension status correctly', function () {
            $activeManager = Manager::factory()->employed()->create(['first_name' => 'Active', 'last_name' => 'Manager']);
            $suspendedManager = Manager::factory()->suspended()->create(['first_name' => 'Suspended', 'last_name' => 'Manager']);

            $component = livewire(Main::class);

            $component
                ->assertSee('Active Manager')
                ->assertSee('Suspended Manager');
        });

    });

    describe('data loading integration', function () {
        test('builder loads the first employment and current employment state', function () {
            $manager = Manager::factory()->employed()->create(['first_name' => 'Relationship', 'last_name' => 'Manager']);

            $loadedManager = app(Main::class)->builder()->findOrFail($manager->id);

            expect($loadedManager->relationLoaded('firstEmployment'))->toBeTrue()
                ->and($loadedManager->status)->toBe(EmploymentStatus::Employed);
        });
    });

    describe('real-time updates integration', function () {
        test('component updates when manager data changes', function () {
            $manager = Manager::factory()->create(['first_name' => 'Original', 'last_name' => 'Manager']);

            $component = livewire(Main::class);
            $component->assertSee('Original Manager');

            $manager->update(['first_name' => 'Updated', 'last_name' => 'Manager']);

            $component->call('$refresh');
            $component->assertSee('Updated Manager');
            $component->assertDontSee('Original Manager');
        });

        test('component reflects employment status changes', function () {
            $manager = Manager::factory()->unemployed()->create(['first_name' => 'Employment', 'last_name' => 'Manager']);

            $component = livewire(Main::class);
            $component->assertSee('Unemployed');

            resolve(EmployAction::class)->handle($manager, now());

            $component->call('$refresh');
            $component
                ->assertSee('Employment Manager')
                ->assertSee('Employed');
        });
    });
});
