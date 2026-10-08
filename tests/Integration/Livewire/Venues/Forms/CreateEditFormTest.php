<?php

declare(strict_types=1);

use App\Data\Events\VenueData;
use App\Enums\Shared\UnitedStatesState;
use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use JMac\Testing\Double;
use Livewire\Component;

describe('venue form data', function (): void {
    it('maps venue fields to validated application data', function (): void {
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        $form->name = 'Madison Square Garden';
        $form->street_address = '4 Pennsylvania Plaza';
        $form->city = 'New York';
        $form->state = UnitedStatesState::NewYork->value;
        $form->zipcode = '10001';

        $data = $form->toData();

        expect($data)->toBeInstanceOf(VenueData::class)
            ->and($data->name)->toBe('Madison Square Garden')
            ->and($data->address->streetAddress)->toBe('4 Pennsylvania Plaza')
            ->and($data->address->city)->toBe('New York')
            ->and($data->address->state)->toBe(UnitedStatesState::NewYork)
            ->and($data->address->zipcode)->toBe('10001');
    });

    it('resolves the venue selected for editing', function (): void {
        $form = new CreateEditForm(Double::for(Component::class), 'form');

        $venue = Venue::factory()->create();
        $form->setModel($venue);

        $selectedVenue = $form->venue();

        expect($selectedVenue->is($venue))->toBeTrue();
    });
});
