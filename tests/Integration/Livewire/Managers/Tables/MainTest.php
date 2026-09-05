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
        ->assertSeeHtml('placeholder="Search managers"')
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
    $visibleManager = match ($status) {
        EmploymentStatus::Employed => Manager::factory()->employed()->create(['first_name' => 'Matching', 'last_name' => 'Manager']),
        EmploymentStatus::Released => Manager::factory()->released()->create(['first_name' => 'Matching', 'last_name' => 'Manager']),
        EmploymentStatus::Unemployed => Manager::factory()->unemployed()->create(['first_name' => 'Matching', 'last_name' => 'Manager']),
        EmploymentStatus::Retired => Manager::factory()->retired()->create(['first_name' => 'Matching', 'last_name' => 'Manager']),
        EmploymentStatus::FutureEmployment => Manager::factory()->withFutureEmployment()->create(['first_name' => 'Matching', 'last_name' => 'Manager']),
    };
    $hiddenManager = $status === EmploymentStatus::Employed
        ? Manager::factory()->released()->create(['first_name' => 'Hidden', 'last_name' => 'Manager'])
        : Manager::factory()->employed()->create(['first_name' => 'Hidden', 'last_name' => 'Manager']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.status', $status->value);

    // Assert
    $component
        ->assertSee($visibleManager->full_name)
        ->assertDontSee($hiddenManager->full_name);
})->with(EmploymentStatus::cases());

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

it('employs an unemployed manager while preserving table state', function (): void {
    // Arrange
    $manager = Manager::factory()->unemployed()->create([
        'first_name' => 'Employment',
        'last_name' => 'Manager',
    ]);
    $component = livewire(Main::class)
        ->set('search', 'Employment')
        ->set('filterValues.status', EmploymentStatus::Unemployed->value);

    // Act
    $component->call('employ', $manager);

    // Assert
    $component
        ->assertSet('search', 'Employment')
        ->assertSet('filterValues.status', EmploymentStatus::Unemployed->value)
        ->assertHasNoErrors();
    expect(freshModel($manager)->status)->toBe(EmploymentStatus::Employed);
});

it('restores a deleted manager and redirects to the index', function (): void {
    // Arrange
    $manager = Manager::factory()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $manager->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('managers.index');
    expect(Manager::find($manager->id))->not->toBeNull();
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
