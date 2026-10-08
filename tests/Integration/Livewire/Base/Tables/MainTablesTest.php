<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Managers\Tables\Main as ManagersMain;
use App\Livewire\Referees\Tables\Main as RefereesMain;
use App\Models\Lifecycle\Injury;
use App\Models\Lifecycle\Suspension;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('main tables', function (): void {
    it('forbids users without access to the table', function (string $component, string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $table = livewire($component);

        // Assert
        $table->assertForbidden();
    })->with('livewire main tables')->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);

    it('searches by name and clears the search', function (
        string $component,
        string $model,
        Closure $attributes,
        ?string $state,
        string $term,
        string $visible,
        string $hidden,
    ): void {
        // Arrange
        foreach ([$visible, $hidden] as $name) {
            $factory = $model::factory();
            $factory = $state === null ? $factory : $factory->{$state}();
            $factory->create($attributes($name));
        }
        $table = livewire($component);

        // Act
        $table->set('search', $term);

        // Assert
        $table
            ->assertSee($visible)
            ->assertDontSee($hidden);

        // Act
        $table->set('search', '');

        // Assert
        $table
            ->assertSee($visible)
            ->assertSee($hidden);
    })->with('livewire main table searches');

    it('renders updated data after a refresh', function (
        string $component,
        string $model,
        Closure $attributes,
        ?string $state,
        string $noun,
    ): void {
        // Arrange
        $factory = $model::factory();
        $factory = $state === null ? $factory : $factory->{$state}();
        $record = $factory->create($attributes("Original {$noun}"));
        $table = livewire($component);
        $table->assertSee("Original {$noun}");
        $record->update($attributes("Updated {$noun}"));

        // Act
        $table->call('$refresh');

        // Assert
        $table
            ->assertSee("Updated {$noun}")
            ->assertDontSee("Original {$noun}");
    })->with('livewire main table refreshes');
});

describe('employment main tables', function (): void {
    it('filters by employment status', function (
        EmploymentStatus $status,
        string $component,
        string $model,
        Closure $attributes,
        string $noun,
    ): void {
        // Arrange
        $matchingFactory = match ($status) {
            EmploymentStatus::Employed => $model::factory()->employed(),
            EmploymentStatus::Released => $model::factory()->released(),
            EmploymentStatus::Unemployed => $model::factory()->unemployed(),
            EmploymentStatus::Retired => $model::factory()->retired(),
            EmploymentStatus::FutureEmployment => $model::factory()->withFutureEmployment(),
        };
        $hiddenFactory = $status === EmploymentStatus::Employed
            ? $model::factory()->released()
            : $model::factory()->employed();
        $matchingFactory->create($attributes("Matching {$noun}"));
        $hiddenFactory->create($attributes("Hidden {$noun}"));
        $table = livewire($component);

        // Act
        $table->set('filterValues.status', $status->value);

        // Assert
        $table
            ->assertSee("Matching {$noun}")
            ->assertDontSee("Hidden {$noun}");
    })->with(EmploymentStatus::cases())->with('livewire employment main tables');

    it('loads the employment state used by the table', function (
        string $component,
        string $model,
        Closure $attributes,
        string $noun,
    ): void {
        // Arrange
        $record = $model::factory()->employed()->create();

        // Act
        $loaded = app($component)->builder()->findOrFail($record->id);

        // Assert
        expect($loaded->relationLoaded('firstEmployment'))->toBeTrue()
            ->and($loaded->status)->toBe(EmploymentStatus::Employed);
    })->with('livewire employment main tables');
});

describe('individual main tables', function (): void {
    it('clears search and filters', function (string $component, string $model, Closure $attributes): void {
        // Arrange
        $table = livewire($component);
        $table->set('search', 'Bobby');
        $table->set('filterValues.status', EmploymentStatus::Employed->value);
        $table->set('filterValues.employment_date', [
            'minDate' => '2020-01-01',
            'maxDate' => '2020-12-31',
        ]);

        // Act
        $table->call('clearFilters');

        // Assert
        $table
            ->assertSet('search', '')
            ->assertSet('filterValues.status', '')
            ->assertSet('filterValues.employment_date', []);
    })->with('livewire individual main tables');

    it('labels injured and suspended records without changing their employment status', function (
        bool $injured,
        bool $suspended,
        string $component,
        string $model,
        Closure $attributes,
    ): void {
        // Arrange
        $record = $model::factory()->employed()->create($attributes('Availability Individual'));

        if ($injured) {
            Injury::factory()->for($record, 'injurable')->create();
        }

        if ($suspended) {
            Suspension::factory()->for($record, 'suspendable')->create();
        }

        // Act
        $table = livewire($component);

        // Assert
        $table->assertSee('Employed');

        expect($table->html())
            ->when($injured, fn ($html) => $html->toContain('data-test="availability-injured"'))
            ->unless($injured, fn ($html) => $html->not->toContain('data-test="availability-injured"'))
            ->when($suspended, fn ($html) => $html->toContain('data-test="availability-suspended"'))
            ->unless($suspended, fn ($html) => $html->not->toContain('data-test="availability-suspended"'));
    })->with([
        'injured' => [true, false],
        'suspended' => [false, true],
        'injured and suspended' => [true, true],
        'available' => [false, false],
    ])->with('livewire individual main tables');
});

describe('manager and referee main tables', function (): void {
    it('renders the configured table controls and attributes', function (
        string $component,
        string $model,
        string $singular,
        string $plural,
        string $firstName,
        string $lastName,
    ): void {
        // Arrange
        $model::factory()->employed()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
        $name = "{$firstName} {$lastName}";

        // Act
        $table = livewire($component);

        // Assert
        $table
            ->assertSuccessful()
            ->assertSee("Add {$singular}")
            ->assertSeeHtml("data-test=\"{$plural}-table\"")
            ->assertSee("Filter {$plural} by status")
            ->assertDontSee("All {$singular}s")
            ->assertSeeHtml("placeholder=\"Search {$plural}\"")
            ->assertSeeHtml('wire:model.live="filterValues.employment_date.minDate"')
            ->assertSeeHtml('wire:model.live="filterValues.employment_date.maxDate"')
            ->assertSeeHtml("aria-label=\"Actions for {$name}\"")
            ->assertSeeHtml("wire:confirm=\"Remove {$name}?\"")
            ->assertSeeHtml('role="group"')
            ->assertSee($name)
            ->assertSee(EmploymentStatus::Employed->label());
    })->with([
        'managers' => [ManagersMain::class, Manager::class, 'Manager', 'managers', 'Bobby', 'Heenan'],
        'referees' => [RefereesMain::class, Referee::class, 'Referee', 'referees', 'Earl', 'Hebner'],
    ]);
});
