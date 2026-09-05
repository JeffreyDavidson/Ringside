<?php

declare(strict_types=1);

use App\Enums\Stables\StableStatus;
use App\Livewire\Stables\Tables\Main;
use App\Models\Lifecycle\ActivityPeriod;
use App\Models\Roster\Stables\Stable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders the configured table controls and stable attributes', function (): void {
    // Arrange
    Stable::factory()->active()->create(['name' => 'The Four Horsemen']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Add Stable')
        ->assertSeeHtml('placeholder="Search stables"')
        ->assertSee('The Four Horsemen')
        ->assertSee(StableStatus::Active->label());
});

it('filters stables by name and clears the search', function (): void {
    // Arrange
    Stable::factory()->active()->create(['name' => 'The Four Horsemen']);
    Stable::factory()->active()->create(['name' => 'New World Order']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'Horsemen');

    // Assert
    $component
        ->assertSee('The Four Horsemen')
        ->assertDontSee('New World Order');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('The Four Horsemen')
        ->assertSee('New World Order');
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

it('disbands an active stable', function (): void {
    // Arrange
    $stable = Stable::factory()->active()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('disband', $stable);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('stables.index');
    expect(freshModel($stable)->status)->toBe(StableStatus::Inactive);
});

it('retires an active stable', function (): void {
    // Arrange
    $stable = Stable::factory()->active()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('retire', $stable);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('stables.index');
    expect(freshModel($stable)->currentRetirement()->exists())->toBeTrue();
});

it('unretires a retired stable', function (): void {
    // Arrange
    $stable = Stable::factory()->retired()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('unretire', $stable);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('stables.index');
    expect(freshModel($stable)->currentActivityPeriod()->exists())->toBeTrue();
});

it('establishes an unformed stable', function (): void {
    // Arrange
    $stable = Stable::factory()->withEmployedDefaultMembers()->unactivated()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('establish', $stable);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('stables.index');
    expect(freshModel($stable)->currentActivityPeriod()->exists())->toBeTrue();
});

it('ignores an external referrer when redirecting after a lifecycle action', function (): void {
    // Arrange
    $stable = Stable::factory()->withEmployedDefaultMembers()->unactivated()->create();
    request()->headers->set('Referer', 'https://attacker.example');
    $component = livewire(Main::class);

    // Act
    $component->call('establish', $stable);

    // Assert
    $component->assertRedirectToRoute('stables.index');
});

it('restores a deleted stable', function (): void {
    // Arrange
    $stable = Stable::factory()->retired()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $stable->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertRedirectToRoute('stables.index');
    expect(Stable::find($stable->id))->not->toBeNull();
});

it('remains on the table when a stable cannot be restored', function (): void {
    // Arrange
    Stable::factory()->create(['name' => 'Existing Stable']);
    $stable = Stable::factory()->trashed()->create(['name' => 'Existing Stable']);
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $stable->id);

    // Assert
    $component->assertNoRedirect();
    expect(Stable::onlyTrashed()->find($stable->id))->not->toBeNull();
});

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

it('does not establish an active stable', function (): void {
    // Arrange
    $stable = Stable::factory()->active()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('establish', $stable);

    // Assert
    $component->assertNoRedirect();
    expect(freshModel($stable)->status)->toBe(StableStatus::Active);
});

it('does not disband an inactive stable', function (): void {
    // Arrange
    $stable = Stable::factory()->inactive()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('disband', $stable);

    // Assert
    $component->assertNoRedirect();
    expect(freshModel($stable)->status)->toBe(StableStatus::Inactive);
});

it('does not retire an already retired stable', function (): void {
    // Arrange
    $stable = Stable::factory()->retired()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('retire', $stable);

    // Assert
    $component->assertNoRedirect();
    expect(freshModel($stable)->status)->toBe(StableStatus::Retired);
});

it('does not unretire an active stable', function (): void {
    // Arrange
    $stable = Stable::factory()->active()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('unretire', $stable);

    // Assert
    $component->assertNoRedirect();
    expect(freshModel($stable)->status)->toBe(StableStatus::Active);
});

it('forbids users without stable access', function (string $actor): void {
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

it('loads the activity state used by the table', function (): void {
    // Arrange
    $stable = Stable::factory()->active()->create();

    // Act
    $loadedStable = app(Main::class)->builder()->findOrFail($stable->id);

    // Assert
    expect($loadedStable->relationLoaded('firstActivityPeriod'))->toBeTrue()
        ->and($loadedStable->status)->toBe(StableStatus::Active);
});
