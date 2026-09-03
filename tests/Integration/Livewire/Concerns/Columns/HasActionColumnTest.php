<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('renders the default actions for a table record', function () {
    actingAs(administrator());
    $venue = Venue::factory()->create(['name' => 'Action Arena']);

    $component = livewire(Main::class);

    $component
        ->assertSee(__('core.actions'))
        ->assertSee('View')
        ->assertSee('Edit')
        ->assertSee('Remove')
        ->assertSeeHtml(route('venues.show', $venue))
        ->assertSeeHtml("wire:click=\"delete({$venue->id})\"");
});
