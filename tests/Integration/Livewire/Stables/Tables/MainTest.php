<?php

declare(strict_types=1);

use App\Enums\Shared\DeletedFilter;
use App\Enums\Stables\StableStatus;
use App\Livewire\Stables\Tables\Main;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('stables table', function (): void {
    it('renders the configured table controls and stable attributes', function (): void {
        // Arrange
        Stable::factory()->active()->create(['name' => 'The Four Horsemen']);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Add Stable')
            ->assertSeeHtml('data-test="stables-table"')
            ->assertSeeHtml('placeholder="Search stables"')
            ->assertSee(__('stables.activation_date'))
            ->assertSeeHtml('id="stables-date-from"')
            ->assertSeeHtml('id="stables-date-to"')
            ->assertSeeHtml('wire:model.live="filterValues.activation_date.minDate"')
            ->assertSeeHtml('wire:model.live="filterValues.activation_date.maxDate"')
            ->assertSeeHtml('aria-label="Actions for The Four Horsemen"')
            ->assertSeeHtml('wire:confirm="Remove The Four Horsemen?"')
            ->assertSeeHtml('role="group"')
            ->assertSee('The Four Horsemen')
            ->assertSee(StableStatus::Active->label());
    });

    it('renders the shared empty state when no stables exist', function (): void {
        // Arrange
        $component = livewire(Main::class);

        // Act
        $component->assertSuccessful();

        // Assert
        $component
            ->assertSee(__('stables.empty_title'))
            ->assertSee(__('stables.empty_description'))
            ->assertSeeHtml('data-test="stables-empty-state"');
    });

    it('filters stables by activation date range', function (): void {
        // Arrange
        Stable::factory()
            ->has(ActivityPeriod::factory()->started(Date::parse('2026-01-15')), 'activityPeriods')
            ->create(['name' => 'January Stable']);
        Stable::factory()
            ->has(ActivityPeriod::factory()->started(Date::parse('2026-02-15')), 'activityPeriods')
            ->create(['name' => 'February Stable']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.activation_date', [
            'minDate' => '2026-01-01',
            'maxDate' => '2026-01-31',
        ]);

        // Assert
        $component
            ->assertSee('January Stable')
            ->assertDontSee('February Stable');
    });

    it('clears search, status, and activation date filters together', function (): void {
        // Arrange
        Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
        Stable::factory()->retired()->create(['name' => 'The Heenan Family']);
        $component = livewire(Main::class)
            ->set('search', 'Missing')
            ->set('filterValues.status', StableStatus::Retired->value)
            ->set('filterValues.activation_date', [
                'minDate' => '2026-01-01',
                'maxDate' => '2026-12-31',
            ]);

        // Act
        $component->call('clearFilters');

        // Assert
        $component
            ->assertSet('search', '')
            ->assertSet('filterValues.status', '')
            ->assertSet('filterValues.activation_date', [])
            ->assertSee('The Four Horsemen')
            ->assertSee('The Heenan Family');
    });

    it('filters stables by status', function (StableStatus $status): void {
        // Arrange
        $visibleStable = match ($status) {
            StableStatus::Unformed => Stable::factory()->unactivated()->create(['name' => 'Matching Stable']),
            StableStatus::PendingEstablishment => Stable::factory()
                ->has(
                    ActivityPeriod::factory()
                        ->started(Date::now()->subDays(4))
                        ->ended(Date::now()->subDays(2)),
                    'activityPeriods',
                )
                ->has(ActivityPeriod::factory()->started(Date::now()->addDays(2)), 'activityPeriods')
                ->create(['name' => 'Matching Stable']),
            StableStatus::Active => Stable::factory()->active()->create(['name' => 'Matching Stable']),
            StableStatus::Inactive => Stable::factory()->disbanded()->create(['name' => 'Matching Stable']),
            StableStatus::Retired => Stable::factory()->retired()->create(['name' => 'Matching Stable']),
        };
        $hiddenStable = $status === StableStatus::Active
            ? Stable::factory()->inactive()->create(['name' => 'Hidden Stable'])
            : Stable::factory()->active()->create(['name' => 'Hidden Stable']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.status', $status->value);

        // Assert
        $component
            ->assertSee($visibleStable->name)
            ->assertDontSee($hiddenStable->name);
    })->with(StableStatus::cases());

    it('soft deletes an inactive stable', function (): void {
        // Arrange
        $stable = Stable::factory()->inactive()->create();
        $component = livewire(Main::class);

        // Act
        $component->call('delete', $stable);

        // Assert
        $component->assertHasNoErrors();
        expect(Stable::find($stable->id))->toBeNull()
            ->and(Stable::onlyTrashed()->find($stable->id))->not->toBeNull();
    });

    it('loads the activity state used by the table', function (): void {
        // Arrange
        $stable = Stable::factory()->active()->create();

        // Act
        $loadedStable = app(Main::class)->builder()->findOrFail($stable->id);

        // Assert
        expect($loadedStable->relationLoaded('firstActivityPeriod'))->toBeTrue()
            ->and($loadedStable->relationLoaded('currentWrestlers'))->toBeTrue()
            ->and($loadedStable->relationLoaded('currentTagTeams'))->toBeTrue()
            ->and($loadedStable->status)->toBe(StableStatus::Active);
    });
});

describe('stables table metadata', function (): void {
    it('uses every stable status as a metadata and filter value', function (): void {
        // Arrange
        Stable::factory()->active()->create();
        Stable::factory()->retired()->create();
        Stable::factory()->withFutureActivation()->create();

        Stable::factory()->active()->trashed()->create();

        $table = new Main;

        // Act
        $metadata = $table->metadata();

        // Assert
        $statuses = collect($metadata['statuses'])->keyBy('value');

        expect($metadata['total'])->toBe(3)
            ->and($statuses->keys()->all())->toBe([
                ...array_map(
                    static fn (StableStatus $status): string => $status->value,
                    StableStatus::cases(),
                ),
                DeletedFilter::Deleted->value,
            ])
            ->and(data_get($statuses->get(DeletedFilter::Deleted->value), 'count'))->toBe(1)
            ->and($statuses->get(StableStatus::Active->value))->toBe([
                'value' => StableStatus::Active->value,
                'label' => StableStatus::Active->label(),
                'count' => 1,
            ])
            ->and($statuses->get(StableStatus::PendingEstablishment->value))->toBe([
                'value' => StableStatus::PendingEstablishment->value,
                'label' => StableStatus::PendingEstablishment->label(),
                'count' => 1,
            ])
            ->and($statuses->get(StableStatus::Retired->value))->toBe([
                'value' => StableStatus::Retired->value,
                'label' => StableStatus::Retired->label(),
                'count' => 1,
            ]);
    });
});
