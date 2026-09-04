<?php

declare(strict_types=1);

use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use JMac\Testing\Double;
use Livewire\Component;

it('tracks whether a form is creating or editing a model', function () {
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $venue = Venue::factory()->create([
        'name' => 'Madison Square Garden',
        'street_address' => '4 Pennsylvania Plaza',
        'city' => 'New York',
        'state' => 'New York',
        'zipcode' => '10001',
    ]);

    expect($form->isCreating())->toBeTrue()
        ->and($form->isEditing())->toBeFalse();

    $form->setModel($venue);

    expect($form->isCreating())->toBeFalse()
        ->and($form->isEditing())->toBeTrue()
        ->and($form->modelId)->toBe($venue->id)
        ->and($form->name)->toBe('Madison Square Garden')
        ->and($form->street_address)->toBe('4 Pennsylvania Plaza')
        ->and($form->city)->toBe('New York')
        ->and($form->state)->toBe('New York')
        ->and($form->zipcode)->toBe('10001');
});

it('returns to creating state when its model is cleared', function () {
    $form = new CreateEditForm(Double::for(Component::class), 'form');
    $venue = Venue::factory()->create();

    $form->setModel($venue);
    $form->setModel(null);

    expect($form->modelId)->toBeNull()
        ->and($form->isCreating())->toBeTrue()
        ->and($form->isEditing())->toBeFalse();
});
