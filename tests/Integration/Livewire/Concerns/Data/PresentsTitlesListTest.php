<?php

declare(strict_types=1);

use App\Livewire\Matches\Modals\FormModal;
use App\Models\Events\Event;
use App\Models\Titles\Title;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('returns titles keyed by their identifiers', function () {
    // Arrange
    $title = Title::factory()->create(['name' => 'World Title']);

    // Act
    $titles = app(FormModal::class)->getTitles();

    // Assert
    expect($titles)->toBe([$title->id => $title->name]);
});

it('presents titles keyed by their identifiers', function () {
    $event = Event::factory()->create();
    $title = Title::factory()->singles()->create([
        'name' => 'World Heavyweight Title',
    ]);

    $component = livewire(FormModal::class, ['eventId' => $event->id])
        ->call('openModal');

    $component
        ->assertSeeHtml("value=\"{$title->id}\"")
        ->assertSee('World Heavyweight Title');
});

it('excludes deleted titles from the presented list', function () {
    $event = Event::factory()->create();
    $title = Title::factory()->singles()->create([
        'name' => 'Deleted Championship Title',
    ]);
    $title->delete();

    $component = livewire(FormModal::class, ['eventId' => $event->id])
        ->call('openModal');

    $component->assertDontSee('Deleted Championship Title');
});
