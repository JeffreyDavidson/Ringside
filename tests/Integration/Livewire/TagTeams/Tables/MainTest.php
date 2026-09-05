<?php

declare(strict_types=1);

use App\Enums\Shared\EmploymentStatus;
use App\Livewire\TagTeams\Tables\Main;
use App\Models\Roster\TagTeams\TagTeam;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders the configured table controls and tag team attributes', function (): void {
    // Arrange
    TagTeam::factory()->employed()->create(['name' => 'The Hardy Boyz']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Add Tag Team')
        ->assertSeeHtml('placeholder="Search tag teams"')
        ->assertSee('The Hardy Boyz')
        ->assertSee(EmploymentStatus::Employed->label());
});

it('filters tag teams by name and clears the search', function (): void {
    // Arrange
    TagTeam::factory()->create(['name' => 'The Hardy Boyz']);
    TagTeam::factory()->create(['name' => 'The Dudley Boyz']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'Hardy');

    // Assert
    $component
        ->assertSee('The Hardy Boyz')
        ->assertDontSee('The Dudley Boyz');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('The Hardy Boyz')
        ->assertSee('The Dudley Boyz');
});

it('filters tag teams by employment status', function (EmploymentStatus $status): void {
    // Arrange
    $visibleTagTeam = match ($status) {
        EmploymentStatus::Employed => TagTeam::factory()->employed()->create(['name' => 'Matching Tag Team']),
        EmploymentStatus::Released => TagTeam::factory()->released()->create(['name' => 'Matching Tag Team']),
        EmploymentStatus::Unemployed => TagTeam::factory()->unemployed()->create(['name' => 'Matching Tag Team']),
        EmploymentStatus::Retired => TagTeam::factory()->retired()->create(['name' => 'Matching Tag Team']),
        EmploymentStatus::FutureEmployment => TagTeam::factory()->withFutureEmployment()->create(['name' => 'Matching Tag Team']),
    };
    $hiddenTagTeam = $status === EmploymentStatus::Employed
        ? TagTeam::factory()->released()->create(['name' => 'Hidden Tag Team'])
        : TagTeam::factory()->employed()->create(['name' => 'Hidden Tag Team']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.status', $status->value);

    // Assert
    $component
        ->assertSee($visibleTagTeam->name)
        ->assertDontSee($hiddenTagTeam->name);
})->with(EmploymentStatus::cases());

it('loads the employment state used by the table', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->employed()->create();

    // Act
    $loadedTagTeam = app(Main::class)->builder()->findOrFail($tagTeam->id);

    // Assert
    expect($loadedTagTeam->relationLoaded('firstEmployment'))->toBeTrue()
        ->and($loadedTagTeam->status)->toBe(EmploymentStatus::Employed);
});

it('renders updated tag team data after a refresh', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create(['name' => 'Original Tag Team']);
    $component = livewire(Main::class);
    $component->assertSee('Original Tag Team');
    $tagTeam->update(['name' => 'Updated Tag Team']);

    // Act
    $component->call('$refresh');

    // Assert
    $component
        ->assertSee('Updated Tag Team')
        ->assertDontSee('Original Tag Team');
});

it('employs an unemployed tag team while preserving table state', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->unemployed()->create(['name' => 'Employment Tag Team']);
    $component = livewire(Main::class)
        ->set('search', 'Employment')
        ->set('filterValues.status', EmploymentStatus::Unemployed->value);

    // Act
    $component->call('employ', $tagTeam);

    // Assert
    $component
        ->assertSet('search', 'Employment')
        ->assertSet('filterValues.status', EmploymentStatus::Unemployed->value)
        ->assertHasNoErrors();
    expect(freshModel($tagTeam)->status)->toBe(EmploymentStatus::Employed);
});

it('restores a deleted tag team and redirects to the index', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $tagTeam->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('tag-teams.index');
    expect(TagTeam::find($tagTeam->id))->not->toBeNull();
});

it('forbids users without tag team access', function (string $actor): void {
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
