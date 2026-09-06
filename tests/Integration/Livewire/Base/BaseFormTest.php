<?php

declare(strict_types=1);

use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use JMac\Testing\Double;
use Livewire\Component;

describe('base form state', function (): void {
    beforeEach(function (): void {
        $this->form = new CreateEditForm(Double::for(Component::class), 'form');
    });

    it('starts in creating state', function (): void {
        // Act
        $isCreating = $this->form->isCreating();
        $isEditing = $this->form->isEditing();

        // Assert
        expect($isCreating)->toBeTrue()
            ->and($isEditing)->toBeFalse();
    });

    it('loads a persisted model into editing state', function (): void {
        // Arrange
        $venue = Venue::factory()->create([
            'name' => 'Madison Square Garden',
            'street_address' => '4 Pennsylvania Plaza',
            'city' => 'New York',
            'state' => 'New York',
            'zipcode' => '10001',
        ]);

        // Act
        $this->form->setModel($venue);

        // Assert
        expect($this->form->isCreating())->toBeFalse()
            ->and($this->form->isEditing())->toBeTrue()
            ->and($this->form->modelId)->toBe($venue->id)
            ->and($this->form->name)->toBe('Madison Square Garden')
            ->and($this->form->street_address)->toBe('4 Pennsylvania Plaza')
            ->and($this->form->city)->toBe('New York')
            ->and($this->form->state)->toBe('New York')
            ->and($this->form->zipcode)->toBe('10001');
    });

    it('returns to creating state when its model is cleared', function (): void {
        // Arrange
        $venue = Venue::factory()->create();
        $this->form->setModel($venue);

        // Act
        $this->form->setModel(null);

        // Assert
        expect($this->form->modelId)->toBeNull()
            ->and($this->form->isCreating())->toBeTrue()
            ->and($this->form->isEditing())->toBeFalse();
    });
});
