<?php

declare(strict_types=1);

use App\Livewire\Events\Modals\FormModal;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(administrator());
});

describe('modal lifecycle', function (): void {
    it('opens the modal', function (): void {
        // Arrange
        $modal = livewire(FormModal::class)
            ->assertSet('isModalOpen', false);

        // Act
        $modal->call('openModal');

        // Assert
        $modal->assertSet('isModalOpen', true);
    });

    it('closes the modal', function (): void {
        // Arrange
        $modal = livewire(FormModal::class)
            ->call('openModal')
            ->assertSet('isModalOpen', true);

        // Act
        $modal->call('closeModal');

        // Assert
        $modal->assertSet('isModalOpen', false);
    });
});

it('completes the shared form submission workflow', function (): void {
    // Arrange
    $modal = livewire(FormModal::class);
    $modal
        ->call('openModal')
        ->set('form.name', 'Shared Modal Event')
        ->set('form.venue_id', null);

    // Act
    $modal->call('save');

    // Assert
    $modal
        ->assertHasNoErrors()
        ->assertSet('isModalOpen', false)
        ->assertDispatched('refreshDatatable')
        ->assertDispatched('closeModal')
        ->assertDispatched('form-submitted');

    $this->assertDatabaseHas('events', [
        'name' => 'Shared Modal Event',
    ]);
});
