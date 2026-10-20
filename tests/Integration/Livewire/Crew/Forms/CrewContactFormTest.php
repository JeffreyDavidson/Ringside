<?php

declare(strict_types=1);

use App\Livewire\Crew\Components\Directory;
use App\Livewire\Crew\Forms\CrewContactForm;
use App\Models\Crew\CrewContact;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('loads a contact and turns its trimmed input into data with blanks as null', function (): void {
    // Arrange
    actingAs(administrator());
    $contact = CrewContact::factory()
        ->create(['name' => 'Alex Sound', 'phone' => null, 'email' => null, 'notes' => null]);
    $component = Livewire::test(Directory::class);
    $form = new CrewContactForm($component->instance(), 'form');
    $form->load($contact);

    // Act
    $loaded = [$form->clientId, $form->name, $form->phone, $form->email, $form->notes];
    $form->name = '  Alex Operator  ';
    $form->phone = '   ';
    $form->email = ' alex@example.com ';
    $data = $form->toData();

    // Assert
    expect($loaded)->toBe([$contact->client_id, 'Alex Sound', '', '', ''])
        ->and($data->name)->toBe('Alex Operator')
        ->and($data->phone)->toBeNull()
        ->and($data->email)->toBe('alex@example.com')
        ->and($data->notes)->toBeNull();
});
