<?php

declare(strict_types=1);

use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use JMac\Testing\Double;
use Livewire\Component;

it('starts in creating state', function (): void {
    // Arrange
    $form = new CreateEditForm(Double::for(Component::class), 'form');

    // Act
    $isCreating = $form->isCreating();
    $isEditing = $form->isEditing();

    // Assert
    expect($isCreating)->toBeTrue()
        ->and($isEditing)->toBeFalse();
});

it('loads a persisted model into editing state', function (): void {
    // Arrange
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $venue = Venue::factory()->create([
        'name' => 'Madison Square Garden',
        'street_address' => '4 Pennsylvania Plaza',
        'city' => 'New York',
        'state' => 'New York',
        'zipcode' => '10001',
    ]);

    // Act
    $form->setModel($venue);

    // Assert
    expect($form->isCreating())->toBeFalse()
        ->and($form->isEditing())->toBeTrue()
        ->and($form->modelId)->toBe($venue->id)
        ->and($form->name)->toBe('Madison Square Garden')
        ->and($form->street_address)->toBe('4 Pennsylvania Plaza')
        ->and($form->city)->toBe('New York')
        ->and($form->state)->toBe('New York')
        ->and($form->zipcode)->toBe('10001');
});

it('returns to creating state when its model is cleared', function (): void {
    // Arrange
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $venue = Venue::factory()->create();
    $form->setModel($venue);

    // Act
    $form->setModel(null);

    // Assert
    expect($form->modelId)->toBeNull()
        ->and($form->isCreating())->toBeTrue()
        ->and($form->isEditing())->toBeFalse();
});
