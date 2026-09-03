<?php

declare(strict_types=1);

use App\Livewire\Venues\Tables\Main;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('configures an index table for its resource', function () {
    actingAs(administrator());

    $table = livewire(Main::class);

    $table
        ->assertSuccessful()
        ->assertSeeHtml('placeholder="Search venues"')
        ->assertSee('Venues')
        ->assertSee('Add Venue')
        ->assertSee(__('core.actions'));
});

it('restricts index table pagination to the shared options', function () {
    actingAs(administrator());
    $table = livewire(Main::class);

    $table->set('perPage', 999);

    $table->assertSet('perPage', 5);
});
