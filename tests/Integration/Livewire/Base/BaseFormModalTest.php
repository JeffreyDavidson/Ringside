<?php

declare(strict_types=1);

use App\Enums\Shared\UnitedStatesState;
use App\Livewire\Events\Modals\FormModal;
use App\Models\Events\Venue;
use Tests\Integration\Livewire\Base\StubFormModal;

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

describe('inherited form hooks', function (): void {
    it('requires modals relying on the shared workflow to define createForm', function (): void {
        // Arrange
        $modal = livewire(StubFormModal::class)
            ->call('openModal')
            ->set('form.name', 'Stub Arena')
            ->set('form.street_address', '1 Main Street')
            ->set('form.city', 'Springfield')
            ->set('form.state', UnitedStatesState::Illinois->value)
            ->set('form.zipcode', '62701');

        // Act
        $save = fn () => $modal->call('save');

        // Assert
        expect($save)->toThrow(LogicException::class, 'A form modal must define createForm().');
    });

    it('requires modals relying on the shared workflow to define updateForm', function (): void {
        // Arrange
        $venue = Venue::factory()->create(['zipcode' => '62701']);
        $modal = livewire(StubFormModal::class)
            ->call('openModal', $venue->getKey());

        // Act
        $save = fn () => $modal->call('save');

        // Assert
        expect($save)->toThrow(LogicException::class, 'A form modal must define updateForm().');
    });
});
