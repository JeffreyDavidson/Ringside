<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Modals\FormModal;
use App\Models\Roster\Managers\Manager;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAs(administrator());
});

it('returns managers keyed by their identifiers', function () {
    // Arrange
    $manager = Manager::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);
    $manager->refresh();

    // Act
    $managers = app(FormModal::class)->getManagers();

    // Assert
    expect($managers)->toBe([$manager->id => $manager->full_name]);
});

it('presents managers keyed by their identifiers', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'Bobby',
        'last_name' => 'Heenan',
    ]);

    $component = livewire(FormModal::class)
        ->call('openModal');

    $component
        ->assertSeeHtml("value=\"{$manager->id}\"")
        ->assertSee('Bobby Heenan');
});

it('excludes deleted managers from the presented list', function () {
    $manager = Manager::factory()->create([
        'first_name' => 'Deleted',
        'last_name' => 'Manager',
    ]);
    $manager->delete();

    $component = livewire(FormModal::class)
        ->call('openModal');

    $component->assertDontSee('Deleted Manager');
});
