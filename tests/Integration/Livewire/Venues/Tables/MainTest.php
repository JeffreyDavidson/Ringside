<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\Auth;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders venue details and excludes deleted venues', function (): void {
    // Arrange
    Venue::factory()->create([
        'name' => 'Madison Square Garden',
        'street_address' => '4 Pennsylvania Plaza',
        'city' => 'New York',
        'state' => 'New York',
        'zipcode' => '10001',
    ]);
    $deletedVenue = Venue::factory()->create(['name' => 'Deleted Arena']);
    $deletedVenue->delete();

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('Madison Square Garden')
        ->assertSee('4 Pennsylvania Plaza')
        ->assertSee('New York')
        ->assertSee('10001')
        ->assertDontSee('Deleted Arena');
});

it('searches across venue name and address fields', function (string $searchTerm): void {
    // Arrange
    Venue::factory()->create([
        'name' => 'Search Target Arena',
        'street_address' => '927 Searchable Way',
        'city' => 'Needle City',
        'state' => 'Nevada',
        'zipcode' => '88901',
    ]);
    Venue::factory()->create([
        'name' => 'Control Venue',
        'street_address' => '100 Other Road',
        'city' => 'Elsewhere',
        'state' => 'Ohio',
        'zipcode' => '43001',
    ]);
    $component = livewire(Main::class);

    // Act
    $component->set('search', $searchTerm);

    // Assert
    $component
        ->assertSee('Search Target Arena')
        ->assertDontSee('Control Venue');
})->with([
    'name' => ['Target Arena'],
    'street address' => ['Searchable Way'],
    'city' => ['Needle City'],
    'state' => ['Nevada'],
]);

it('clears a venue search', function (): void {
    // Arrange
    Venue::factory()->create(['name' => 'Alpha Arena']);
    Venue::factory()->create(['name' => 'Bravo Arena']);
    $component = livewire(Main::class);

    // Act
    $component->set('search', 'Alpha');

    // Assert
    $component
        ->assertSee('Alpha Arena')
        ->assertDontSee('Bravo Arena');

    // Act
    $component->set('search', '');

    // Assert
    $component
        ->assertSee('Alpha Arena')
        ->assertSee('Bravo Arena');
});

it('orders venues alphabetically', function (): void {
    // Arrange
    Venue::factory()->create(['name' => 'Zebra Arena']);
    Venue::factory()->create(['name' => 'Alpha Arena']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component->assertSeeInOrder([
        'Alpha Arena',
        'Zebra Arena',
    ]);
});

it('soft deletes a venue and reports success', function (): void {
    // Arrange
    $venue = Venue::factory()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('delete', $venue);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('venues.actions.deleted'),
        );
    $this->assertSoftDeleted($venue);
});

it('restores a venue and reports success', function (): void {
    // Arrange
    $venue = Venue::factory()->trashed()->create();
    $component = livewire(Main::class);

    // Act
    $component->call('restore', $venue->id);

    // Assert
    $component
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('venues.actions.restored'),
        )
        ->assertRedirectToRoute('venues.index');
    $this->assertNotSoftDeleted($venue);
});

it('renders an empty state when there are no venues', function (): void {
    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee('No records found.');
});

it('forbids users without administrative access', function (string $actor): void {
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
