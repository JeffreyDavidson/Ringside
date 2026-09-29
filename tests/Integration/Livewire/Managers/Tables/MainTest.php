<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Managers\Tables\Main;
use App\Models\Roster\Managers\Manager;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('managers table', function (): void {
    it('renders the configured table controls and manager attributes', function (): void {
        // Arrange
        Manager::factory()->employed()->create([
            'first_name' => 'Bobby',
            'last_name' => 'Heenan',
        ]);

        // Act
        $component = livewire(Main::class);

        // Assert
        $component
            ->assertSuccessful()
            ->assertSee('Add Manager')
            ->assertSeeHtml('data-test="managers-table"')
            ->assertSee('Filter managers by status')
            ->assertDontSee('All Managers')
            ->assertSeeHtml('placeholder="Search managers"')
            ->assertSeeHtml('wire:model.live="filterValues.employment_date.minDate"')
            ->assertSeeHtml('wire:model.live="filterValues.employment_date.maxDate"')
            ->assertSeeHtml('aria-label="Actions for Bobby Heenan"')
            ->assertSeeHtml('role="group"')
            ->assertSee('Bobby Heenan')
            ->assertSee(EmploymentStatus::Employed->label());
    });

    it('filters managers by name and clears the search', function (): void {
        // Arrange
        Manager::factory()->create(['first_name' => 'Paul', 'last_name' => 'Bearer']);
        Manager::factory()->create(['first_name' => 'Jimmy', 'last_name' => 'Hart']);
        $component = livewire(Main::class);

        // Act
        $component->set('search', 'Paul');

        // Assert
        $component
            ->assertSee('Paul Bearer')
            ->assertDontSee('Jimmy Hart');

        // Act
        $component->set('search', '');

        // Assert
        $component
            ->assertSee('Paul Bearer')
            ->assertSee('Jimmy Hart');
    });

    it('filters managers by employment status', function (EmploymentStatus $status): void {
        // Arrange
        $visibleManagerFactory = match ($status) {
            EmploymentStatus::Employed => Manager::factory()->employed(),
            EmploymentStatus::Released => Manager::factory()->released(),
            EmploymentStatus::Unemployed => Manager::factory()->unemployed(),
            EmploymentStatus::Retired => Manager::factory()->retired(),
            EmploymentStatus::FutureEmployment => Manager::factory()->withFutureEmployment(),
        };
        $hiddenManagerFactory = $status === EmploymentStatus::Employed
            ? Manager::factory()->released()
            : Manager::factory()->employed();
        $visibleManagerFactory->create(['first_name' => 'Matching', 'last_name' => 'Manager']);
        $hiddenManagerFactory->create(['first_name' => 'Hidden', 'last_name' => 'Manager']);
        $component = livewire(Main::class);

        // Act
        $component->set('filterValues.status', $status->value);

        // Assert
        $component
            ->assertSee('Matching Manager')
            ->assertDontSee('Hidden Manager');
    })->with(EmploymentStatus::cases());

    it('clears manager search and filters', function (): void {
        // Arrange
        $component = livewire(Main::class);
        $component->set('search', 'Bobby');
        $component->set('filterValues.status', EmploymentStatus::Employed->value);
        $component->set('filterValues.employment_date', [
            'minDate' => '2020-01-01',
            'maxDate' => '2020-12-31',
        ]);

        // Act
        $component->call('clearFilters');

        // Assert
        $component
            ->assertSet('search', '')
            ->assertSet('filterValues.status', '')
            ->assertSet('filterValues.employment_date', []);
    });

    it('loads the employment state used by the table', function (): void {
        // Arrange
        $manager = Manager::factory()->employed()->create();

        // Act
        $loadedManager = app(Main::class)->builder()->findOrFail($manager->id);

        // Assert
        expect($loadedManager->relationLoaded('firstEmployment'))->toBeTrue()
            ->and($loadedManager->status)->toBe(EmploymentStatus::Employed);
    });

    it('renders updated manager data after a refresh', function (): void {
        // Arrange
        $manager = Manager::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'Manager',
        ]);
        $component = livewire(Main::class);
        $component->assertSee('Original Manager');
        $manager->update(['first_name' => 'Updated']);

        // Act
        $component->call('$refresh');

        // Assert
        $component
            ->assertSee('Updated Manager')
            ->assertDontSee('Original Manager');
    });

    it('forbids users without manager access', function (string $actor): void {
        // Arrange
        if ($actor === 'guest') {
            Auth::logout();
        } else {
            actingAs(basicUser());
        }

        // Act
        $component = livewire(Main::class);

        // Assert
        $component->assertForbidden();
    })->with([
        'guest' => ['guest'],
        'basic user' => ['basic user'],
    ]);
});
