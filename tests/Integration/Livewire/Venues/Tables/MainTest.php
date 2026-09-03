<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('renders venue details for administrators and excludes deleted venues', function () {
    $venue = Venue::factory()->create([
        'name' => 'Madison Square Garden',
        'street_address' => '4 Pennsylvania Plaza',
        'city' => 'New York',
        'state' => 'New York',
        'zipcode' => '10001',
    ]);
    $deletedVenue = Venue::factory()->create(['name' => 'Deleted Arena']);
    $deletedVenue->delete();
    actingAs(administrator());

    $table = livewire(Main::class);

    $table
        ->assertSuccessful()
        ->assertSee($venue->name)
        ->assertSee($venue->street_address)
        ->assertSee($venue->city)
        ->assertSee($venue->state)
        ->assertSee($venue->zipcode)
        ->assertDontSee($deletedVenue->name);
});

it('forbids users without administrative access', function (string $actor) {
    if ($actor === 'basic user') {
        actingAs(basicUser());
    }

    $table = livewire(Main::class);

    $table->assertForbidden();
})->with([
    'guest' => ['guest'],
    'basic user' => ['basic user'],
]);

it('searches across venue address fields', function (string $searchTerm) {
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
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->set('search', $searchTerm);

    $table
        ->assertSee('Search Target Arena')
        ->assertDontSee('Control Venue');
})->with([
    'name' => ['Target Arena'],
    'street address' => ['Searchable Way'],
    'city' => ['Needle City'],
    'state' => ['Nevada'],
]);

it('clears a venue search', function () {
    Venue::factory()->create(['name' => 'Alpha Arena']);
    Venue::factory()->create(['name' => 'Bravo Arena']);
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->set('search', 'Alpha');
    $table->set('search', '');

    $table
        ->assertSee('Alpha Arena')
        ->assertSee('Bravo Arena');
});

it('orders venues alphabetically', function () {
    Venue::factory()->create(['name' => 'Zebra Arena']);
    Venue::factory()->create(['name' => 'Alpha Arena']);
    actingAs(administrator());

    $table = livewire(Main::class);

    $table->assertSeeInOrder([
        'Alpha Arena',
        'Zebra Arena',
    ]);
});

it('soft deletes a venue and reports success', function () {
    $venue = Venue::factory()->create();
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->call('delete', $venue);

    $table
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('venues.actions.deleted'),
        );
    $this->assertSoftDeleted($venue);
});

it('restores a venue and reports success', function () {
    $venue = Venue::factory()->create();
    $venue->delete();
    actingAs(administrator());

    $table = livewire(Main::class);
    $table->call('restore', $venue->id);

    $table
        ->assertHasNoErrors()
        ->assertDispatched(
            'flash-message',
            type: 'status',
            message: __('venues.actions.restored'),
        )
        ->assertRedirectToRoute('venues.index');
    $this->assertNotSoftDeleted($venue);
});

it('renders an empty state when there are no venues', function () {
    actingAs(administrator());

    $table = livewire(Main::class);

    $table
        ->assertSuccessful()
        ->assertSee('No records found.');
});
