<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\Referees\Tables\Main;
use App\Models\Roster\Referees\Referee;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders the configured table controls and referee attributes', function (): void {
    // Arrange
    Referee::factory()->employed()->create([
        'first_name' => 'Earl',
        'last_name' => 'Hebner',
    ]);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Add Referee')
        ->assertSeeHtml('placeholder="Search referees"')
        ->assertSee('Earl Hebner')
        ->assertSee(EmploymentStatus::Employed->label());
});

it('filters referees by name and clears the search', function (): void {
    // Arrange
    Referee::factory()->create(['first_name' => 'Earl', 'last_name' => 'Hebner']);
    Referee::factory()->create(['first_name' => 'Mike', 'last_name' => 'Chioda']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'Earl');

    // Assert
    $component
        ->assertSee('Earl Hebner')
        ->assertDontSee('Mike Chioda');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('Earl Hebner')
        ->assertSee('Mike Chioda');
});

it('filters referees by employment status', function (EmploymentStatus $status): void {
    // Arrange
    $visibleReferee = match ($status) {
        EmploymentStatus::Employed => Referee::factory()->employed()->create(['first_name' => 'Matching', 'last_name' => 'Referee']),
        EmploymentStatus::Released => Referee::factory()->released()->create(['first_name' => 'Matching', 'last_name' => 'Referee']),
        EmploymentStatus::Unemployed => Referee::factory()->unemployed()->create(['first_name' => 'Matching', 'last_name' => 'Referee']),
        EmploymentStatus::Retired => Referee::factory()->retired()->create(['first_name' => 'Matching', 'last_name' => 'Referee']),
        EmploymentStatus::FutureEmployment => Referee::factory()->withFutureEmployment()->create(['first_name' => 'Matching', 'last_name' => 'Referee']),
    };
    $hiddenReferee = $status === EmploymentStatus::Employed
        ? Referee::factory()->released()->create(['first_name' => 'Hidden', 'last_name' => 'Referee'])
        : Referee::factory()->employed()->create(['first_name' => 'Hidden', 'last_name' => 'Referee']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.status', $status->value);

    // Assert
    $component
        ->assertSee($visibleReferee->full_name)
        ->assertDontSee($hiddenReferee->full_name);
})->with(EmploymentStatus::cases());

it('loads the employment state used by the table', function (): void {
    // Arrange
    $referee = Referee::factory()->employed()->create();

    // Act
    $loadedReferee = app(Main::class)->builder()->findOrFail($referee->id);

    // Assert
    expect($loadedReferee->relationLoaded('firstEmployment'))->toBeTrue()
        ->and($loadedReferee->status)->toBe(EmploymentStatus::Employed);
});

it('renders updated referee data after a refresh', function (): void {
    // Arrange
    $referee = Referee::factory()->create([
        'first_name' => 'Original',
        'last_name' => 'Referee',
    ]);
    $component = livewire(Main::class);
    $component->assertSee('Original Referee');
    $referee->update(['first_name' => 'Updated']);

    // Act
    $component->call('$refresh');

    // Assert
    $component
        ->assertSee('Updated Referee')
        ->assertDontSee('Original Referee');
});

it('employs an unemployed referee while preserving table state', function (): void {
    // Arrange
    $referee = Referee::factory()->unemployed()->create([
        'first_name' => 'Employment',
        'last_name' => 'Referee',
    ]);
    $component = livewire(Main::class)
        ->set('search', 'Employment')
        ->set('filterValues.status', EmploymentStatus::Unemployed->value);

    // Act
    $component->call('employ', $referee);

    // Assert
    $component
        ->assertSet('search', 'Employment')
        ->assertSet('filterValues.status', EmploymentStatus::Unemployed->value)
        ->assertHasNoErrors();
    expect(freshModel($referee)->status)->toBe(EmploymentStatus::Employed);
});

it('restores a deleted referee and redirects to the index', function (): void {
    // Arrange
    $referee = Referee::factory()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $referee->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('referees.index');
    expect(Referee::find($referee->id))->not->toBeNull();
});

it('forbids users without referee access', function (string $actor): void {
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
