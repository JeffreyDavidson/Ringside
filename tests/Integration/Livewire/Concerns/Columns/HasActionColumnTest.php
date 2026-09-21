<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;
use App\Models\Events\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('renders the default actions for a table record', function (): void {
    // Arrange
    $venue = Venue::factory()->create(['name' => 'Action Arena']);

    // Act
    $component = livewire(Main::class);

    // Assert
    $component
        ->assertSuccessful()
        ->assertSee(__('core.actions'))
        ->assertSee('View')
        ->assertSee('Edit')
        ->assertSee('Remove')
        ->assertSeeHtml(route('venues.show', $venue))
        ->assertSeeHtml("component: 'venues.modals.form-modal'")
        ->assertSeeHtml("'modelId': '{$venue->id}'")
        ->assertSeeHtml('wire:confirm')
        ->assertSeeHtml("wire:click=\"delete({$venue->id})\"");
});
