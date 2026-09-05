<?php

declare(strict_types=1);

use App\Enums\Titles\TitleLifecycleTransition;
use App\Enums\Titles\TitleStatus;
use App\Enums\Titles\TitleType;
use App\Livewire\Titles\Tables\Main;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use App\Models\Titles\TitleChampionship;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders the configured table controls and title attributes', function (): void {
    // Arrange
    $title = Title::factory()->active()->singles()->create(['name' => 'World Title']);
    $champion = Wrestler::factory()->bookable()->create(['name' => 'Current Champion']);
    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($champion, 'champion')
        ->current()
        ->create();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Add Title')
        ->assertSeeHtml('placeholder="Search titles"')
        ->assertSee($title->name)
        ->assertSee(TitleStatus::Active->label())
        ->assertSee($champion->name);
});

it('filters titles by name and clears the search', function (): void {
    // Arrange
    Title::factory()->create(['name' => 'World Heavyweight Title']);
    Title::factory()->create(['name' => 'Intercontinental Title']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'World');

    // Assert
    $component
        ->assertSee('World Heavyweight Title')
        ->assertDontSee('Intercontinental Title');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('World Heavyweight Title')
        ->assertSee('Intercontinental Title');
});

it('filters titles by status', function (TitleStatus $status): void {
    // Arrange
    $visibleTitle = match ($status) {
        TitleStatus::Undebuted => Title::factory()->undebuted()->create(['name' => 'Matching Title']),
        TitleStatus::PendingDebut => Title::factory()->withFutureDebut()->create(['name' => 'Matching Title']),
        TitleStatus::Active => Title::factory()->active()->create(['name' => 'Matching Title']),
        TitleStatus::Inactive => Title::factory()->inactive()->create(['name' => 'Matching Title']),
        TitleStatus::Retired => Title::factory()->retired()->create(['name' => 'Matching Title']),
    };
    $hiddenTitle = $status === TitleStatus::Active
        ? Title::factory()->inactive()->create(['name' => 'Hidden Title'])
        : Title::factory()->active()->create(['name' => 'Hidden Title']);
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.status', $status->value);

    // Assert
    $component
        ->assertSee($visibleTitle->name)
        ->assertDontSee($hiddenTitle->name);
})->with(TitleStatus::cases());

it('filters titles by type', function (TitleType $type): void {
    // Arrange
    $visibleTitle = match ($type) {
        TitleType::Singles => Title::factory()->singles()->create(['name' => 'Singles Title']),
        TitleType::TagTeam => Title::factory()->tagTeam()->create(['name' => 'Tag Team Titles']),
    };
    $hiddenTitle = match ($type) {
        TitleType::Singles => Title::factory()->tagTeam()->create(['name' => 'Hidden Tag Team Titles']),
        TitleType::TagTeam => Title::factory()->singles()->create(['name' => 'Hidden Singles Title']),
    };
    $component = livewire(Main::class);

    // Act
    $component->set('filterValues.type', $type->value);

    // Assert
    $component
        ->assertSee($visibleTitle->name)
        ->assertDontSee($hiddenTitle->name);
})->with(TitleType::cases());

it('remains on the table when a lifecycle action is rejected', function (TitleLifecycleTransition $transition): void {
    // Arrange
    $title = match ($transition) {
        TitleLifecycleTransition::Debut => Title::factory()->active()->create(),
        TitleLifecycleTransition::Pull => Title::factory()->inactive()->create(),
        TitleLifecycleTransition::Retire => Title::factory()->retired()->create(),
        TitleLifecycleTransition::Unretire, TitleLifecycleTransition::Reinstate => Title::factory()->active()->create(),
    };
    $action = match ($transition) {
        TitleLifecycleTransition::Debut => 'debut',
        TitleLifecycleTransition::Pull => 'putOnHold',
        TitleLifecycleTransition::Reinstate => 'reinstate',
        TitleLifecycleTransition::Retire => 'retire',
        TitleLifecycleTransition::Unretire => 'unretire',
    };
    $component = livewire(Main::class);

    // Act
    $component->call($action, $title);

    // Assert
    $component->assertNoRedirect();
})->with(TitleLifecycleTransition::cases());

it('restores a deleted title and redirects to the index', function (): void {
    // Arrange
    $title = Title::factory()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $title->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('titles.index');
    expect(Title::find($title->id))->not->toBeNull();
});

it('renders only the current champion', function (): void {
    // Arrange
    $title = Title::factory()->active()->singles()->create(['name' => 'Historical Title']);
    $formerChampion = Wrestler::factory()->create(['name' => 'Former Champion']);
    $currentChampion = Wrestler::factory()->create(['name' => 'Current Champion']);
    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($formerChampion, 'champion')
        ->ended()
        ->create();
    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($currentChampion, 'champion')
        ->current()
        ->create();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSee($title->name)
        ->assertSee($currentChampion->name)
        ->assertDontSee($formerChampion->name);
});

it('renders an active title without a champion as vacant', function (): void {
    // Arrange
    $title = Title::factory()->active()->create(['name' => 'Vacant Title']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSee($title->name)
        ->assertSee('Vacant');
});

it('loads the current championship used by the table', function (): void {
    // Arrange
    $title = Title::factory()->active()->singles()->create();
    $champion = Wrestler::factory()->create();
    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($champion, 'champion')
        ->current()
        ->create();

    // Act
    $loadedTitle = app(Main::class)->builder()->findOrFail($title->id);

    // Assert
    expect($loadedTitle->relationLoaded('currentChampionship'))->toBeTrue()
        ->and($loadedTitle->currentChampionship?->relationLoaded('champion'))->toBeTrue();
});

it('renders updated title data after a refresh', function (): void {
    // Arrange
    $title = Title::factory()->create(['name' => 'Original Title']);
    $component = livewire(Main::class);
    $component->assertSee('Original Title');
    $title->update(['name' => 'Updated Title']);

    // Act
    $component->call('$refresh');

    // Assert
    $component
        ->assertSee('Updated Title')
        ->assertDontSee('Original Title');
});

it('renders a newly assigned champion after a refresh', function (): void {
    // Arrange
    $title = Title::factory()->active()->singles()->create(['name' => 'Championship Title']);
    $champion = Wrestler::factory()->create(['name' => 'New Champion']);
    $component = livewire(Main::class);
    $component->assertSee('Vacant');
    TitleChampionship::factory()
        ->for($title, 'title')
        ->for($champion, 'champion')
        ->current()
        ->create();

    // Act
    $component->call('$refresh');

    // Assert
    $component->assertSee($champion->name);
});

it('forbids users without title access', function (string $actor): void {
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
