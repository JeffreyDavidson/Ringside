<?php

declare(strict_types=1);

use App\Livewire\TagTeams\Modals\FormModal;
use App\Models\Roster\TagTeams\TagTeam;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

it('clears an unsaved creation form', function (): void {
    // Arrange
    $component = livewire(FormModal::class)
        ->call('openModal')
        ->set('form.name', 'Unsaved Team');

    // Act
    $component->call('clear');

    // Assert
    $component
        ->assertSet('form.name', '')
        ->assertSee('Add TagTeam');
});

it('restores the persisted model when clearing an edit form', function (): void {
    // Arrange
    $tagTeam = TagTeam::factory()->create(['name' => 'The Originals']);
    $component = livewire(FormModal::class)
        ->call('openModal', $tagTeam->id)
        ->set('form.name', 'Unsaved Rename');

    // Act
    $component->call('clear');

    // Assert
    $component
        ->assertSet('form.name', 'The Originals')
        ->assertSee('Edit The Originals');
});
