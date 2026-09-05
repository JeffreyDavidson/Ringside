<?php

declare(strict_types=1);

use App\Enums\Roster\RosterLifecycleAction;
use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Wrestlers\Tables\Main;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders the configured table controls and wrestler attributes', function (): void {
    // Arrange
    Wrestler::factory()->create([
        'name' => 'Big Wrestler',
        'height' => 78,
        'weight' => 300,
        'hometown' => 'Test City, TX',
    ]);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Add Wrestler')
        ->assertSeeHtml('placeholder="Search wrestlers"')
        ->assertSee('Big Wrestler')
        ->assertSee('6\'6"')
        ->assertSee('300')
        ->assertSee('Test City, TX');
});

it('filters wrestlers by name and clears the search', function (): void {
    // Arrange
    Wrestler::factory()->create(['name' => 'John Cena']);
    Wrestler::factory()->create(['name' => 'The Rock']);

    $component = livewire(Main::class);

    // Act
    $component->set('search', 'John');

    // Assert
    $component
        ->assertSee('John Cena')
        ->assertDontSee('The Rock');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('John Cena')
        ->assertSee('The Rock');
});

it('filters wrestlers by employment status', function (EmploymentStatus $status): void {
    // Arrange
    $visibleWrestler = match ($status) {
        EmploymentStatus::Employed => Wrestler::factory()->employed()->create(['name' => 'Matching Wrestler']),
        EmploymentStatus::Released => Wrestler::factory()->released()->create(['name' => 'Matching Wrestler']),
        EmploymentStatus::Unemployed => Wrestler::factory()->unemployed()->create(['name' => 'Matching Wrestler']),
        EmploymentStatus::Retired => Wrestler::factory()->retired()->create(['name' => 'Matching Wrestler']),
        EmploymentStatus::FutureEmployment => Wrestler::factory()->withFutureEmployment()->create(['name' => 'Matching Wrestler']),
    };
    $hiddenWrestler = $status === EmploymentStatus::Employed
        ? Wrestler::factory()->released()->create(['name' => 'Hidden Wrestler'])
        : Wrestler::factory()->employed()->create(['name' => 'Hidden Wrestler']);

    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.status', $status->value);

    // Assert
    $component
        ->assertSee($visibleWrestler->name)
        ->assertDontSee($hiddenWrestler->name);
})->with(EmploymentStatus::cases());

it('loads the employment state used by the table', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->employed()->create();

    // Act
    $loadedWrestler = app(Main::class)->builder()->findOrFail($wrestler->id);

    // Assert
    expect($loadedWrestler->relationLoaded('firstEmployment'))->toBeTrue()
        ->and($loadedWrestler->status)->toBe(EmploymentStatus::Employed);
});

it('renders updated wrestler data after a refresh', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->create(['name' => 'Original Wrestler']);
    $component = livewire(Main::class);
    $component->assertSee('Original Wrestler');
    $wrestler->update(['name' => 'Updated Wrestler']);

    // Act
    $component->call('$refresh');

    // Assert
    $component
        ->assertSee('Updated Wrestler')
        ->assertDontSee('Original Wrestler');
});

it('employs an unemployed wrestler while preserving table state', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->unemployed()->create(['name' => 'Employment Wrestler']);
    $component = livewire(Main::class)
        ->set('search', 'Employment')
        ->set('filterValues.status', EmploymentStatus::Unemployed->value);

    // Act
    $component->call('handleWrestlerAction', RosterLifecycleAction::Employ->value, $wrestler->id);

    // Assert
    $component
        ->assertSet('search', 'Employment')
        ->assertSet('filterValues.status', EmploymentStatus::Unemployed->value)
        ->assertHasNoErrors();
    expect(freshModel($wrestler)->status)->toBe(EmploymentStatus::Employed);
});

it('releases an employed wrestler', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->bookable()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('handleWrestlerAction', RosterLifecycleAction::Release->value, $wrestler->id);

    // Assert
    $component->assertHasNoErrors();
    expect(freshModel($wrestler)->status)->toBe(EmploymentStatus::Released);
});

it('retires an employed wrestler', function (): void {
    // Arrange
    $wrestler = Wrestler::factory()->bookable()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('handleWrestlerAction', RosterLifecycleAction::Retire->value, $wrestler->id);

    // Assert
    $component->assertHasNoErrors();
    expect(freshModel($wrestler)->status)->toBe(EmploymentStatus::Retired);
});

it('forbids users without wrestler access', function (string $actor): void {
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
