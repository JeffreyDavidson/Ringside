<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Livewire\Stables\Tables\Main;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('StablesTable Component', function () {
    beforeEach(function () {
        $this->administrator = administrator();
        actingAs($this->administrator);
    });

    describe('component rendering and data display', function () {
        test('renders the configured table header and search prompt', function () {
            livewire(Main::class)
                ->assertSee('Add Stable')
                ->assertSeeHtml('placeholder="Search stables"');
        });

        test('renders stables with different lifecycle states', function () {
            Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
            Stable::factory()->retired()->create(['name' => 'D-Generation X']);
            Stable::factory()->inactive()->create(['name' => 'The New World Order']);

            $component = livewire(Main::class);

            $component
                ->assertSee('The Four Horsemen')
                ->assertSee('D-Generation X')
                ->assertSee('The New World Order');
        });
    });

    describe('filtering and search functionality', function () {
        test('search functionality filters stables correctly', function () {
            Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
            Stable::factory()->active()->create(['name' => 'New World Order']);
            Stable::factory()->active()->create(['name' => 'D-Generation X']);

            $component = livewire(Main::class);

            $component
                ->set('search', 'Horsemen')
                ->assertSee('The Four Horsemen')
                ->assertDontSee('New World Order')
                ->assertDontSee('D-Generation X');

            $component
                ->set('search', 'New')
                ->assertSee('New World Order')
                ->assertDontSee('The Four Horsemen')
                ->assertDontSee('D-Generation X');
        });

        test('status filter works correctly', function () {
            $stables = [
                StableStatus::Unformed->value => Stable::factory()->unactivated()->create(['name' => 'Unformed Stable']),
                StableStatus::PendingEstablishment->value => Stable::factory()
                    ->has(
                        ActivityPeriod::factory()
                            ->started(now()->subDays(4))
                            ->ended(now()->subDays(2)),
                        'activityPeriods',
                    )
                    ->has(ActivityPeriod::factory()->started(now()->addDays(2)), 'activityPeriods')
                    ->create(['name' => 'Pending Stable']),
                StableStatus::Active->value => Stable::factory()->active()->create(['name' => 'Active Stable']),
                StableStatus::Inactive->value => Stable::factory()->disbanded()->create(['name' => 'Inactive Stable']),
                StableStatus::Retired->value => Stable::factory()->retired()->create(['name' => 'Retired Stable']),
            ];

            foreach ($stables as $status => $visibleStable) {
                $component = livewire(Main::class)
                    ->set('filterValues.status', $status)
                    ->assertSee($visibleStable->name);

                foreach ($stables as $otherStatus => $hiddenStable) {
                    if ($otherStatus !== $status) {
                        $component->assertDontSee($hiddenStable->name);
                    }
                }
            }
        });
    });

    describe('stable business actions integration', function () {
        test('disbands an active stable', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            livewire(Main::class)
                ->call('disband', $stable)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('stables.index');

            expect(freshModel($stable)->status)->toBe(StableStatus::Inactive);
        });

        test('retires an active stable', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            livewire(Main::class)
                ->call('retire', $stable)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('stables.index');

            expect(freshModel($stable)->currentRetirement()->exists())->toBeTrue();
        });

        test('unretires a retired stable', function () {
            $stable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);

            livewire(Main::class)
                ->call('unretire', $stable)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('stables.index');

            expect(freshModel($stable)->currentActivityPeriod()->exists())->toBeTrue();
        });

        test('establishes an unformed stable', function () {
            $stable = Stable::factory()->withEmployedDefaultMembers()->unactivated()->create(['name' => 'Unformed Stable']);

            livewire(Main::class)
                ->call('establish', $stable)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('stables.index');

            expect(freshModel($stable)->currentActivityPeriod()->exists())->toBeTrue();
        });

        test('lifecycle actions ignore an external referrer when redirecting', function () {
            $stable = Stable::factory()->withEmployedDefaultMembers()->unactivated()->create(['name' => 'Unformed Stable']);
            request()->headers->set('Referer', 'https://attacker.example');

            livewire(Main::class)
                ->call('establish', $stable)
                ->assertRedirectToRoute('stables.index');
        });

        test('restores a deleted stable', function () {
            $stable = Stable::factory()->retired()->trashed()->create(['name' => 'Deleted Stable']);

            livewire(Main::class)
                ->call('restore', $stable->id)
                ->assertHasNoErrors()
                ->assertRedirectToRoute('stables.index');

            expect(Stable::find($stable->id))->not->toBeNull();
        });

        test('remains on the table when a stable cannot be restored', function () {
            Stable::factory()->create(['name' => 'Existing Stable']);
            $stable = Stable::factory()->trashed()->create(['name' => 'Existing Stable']);

            livewire(Main::class)
                ->call('restore', $stable->id)
                ->assertNoRedirect();

            expect(Stable::onlyTrashed()->find($stable->id))->not->toBeNull();
        });

        test('soft deletes an inactive stable', function () {
            $stable = Stable::factory()->inactive()->create(['name' => 'Test Stable']);

            livewire(Main::class)
                ->call('delete', $stable)
                ->assertHasNoErrors();

            expect(Stable::find($stable->id))->toBeNull()
                ->and(Stable::onlyTrashed()->find($stable->id))->not->toBeNull();
        });
    });

    describe('business rule enforcement', function () {
        test('does not establish an active stable', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            livewire(Main::class)
                ->call('establish', $stable)
                ->assertNoRedirect();

            expect(freshModel($stable)->status)->toBe(StableStatus::Active);
        });

        test('does not disband an inactive stable', function () {
            $stable = Stable::factory()->inactive()->create(['name' => 'Inactive Stable']);

            livewire(Main::class)
                ->call('disband', $stable)
                ->assertNoRedirect();

            expect(freshModel($stable)->status)->toBe(StableStatus::Inactive);
        });

        test('does not retire an already retired stable', function () {
            $stable = Stable::factory()->retired()->create(['name' => 'Retired Stable']);

            livewire(Main::class)
                ->call('retire', $stable)
                ->assertNoRedirect();

            expect(freshModel($stable)->status)->toBe(StableStatus::Retired);
        });

        test('does not unretire an active stable', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Active Stable']);

            livewire(Main::class)
                ->call('unretire', $stable)
                ->assertNoRedirect();

            expect(freshModel($stable)->status)->toBe(StableStatus::Active);
        });
    });

    describe('authorization integration', function () {
        test('forbids a basic user from viewing the component', function () {
            actingAs(basicUser());

            livewire(Main::class)
                ->assertForbidden();
        });

        test('forbids a guest from viewing the component', function () {
            Auth::logout();

            livewire(Main::class)
                ->assertForbidden();
        });
    });

    describe('data loading integration', function () {
        test('builder loads the first activity period and current activity state', function () {
            $stable = Stable::factory()->active()->create(['name' => 'Relationship Stable']);

            $loadedStable = app(Main::class)->builder()->findOrFail($stable->id);

            expect($loadedStable->relationLoaded('firstActivityPeriod'))->toBeTrue()
                ->and($loadedStable->status)->toBe(StableStatus::Active);
        });
    });
});
