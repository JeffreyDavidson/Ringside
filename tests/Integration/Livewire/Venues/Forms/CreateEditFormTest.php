<?php

declare(strict_types=1);

use App\Data\Events\VenueData;
use App\Enums\Shared\UnitedStatesState;
use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;
use JMac\Testing\Double;
use Livewire\Component;

beforeEach(function () {
    $this->form = new CreateEditForm(Double::for(Component::class), 'form');
});

it('maps venue fields to validated application data', function () {
    $this->form->name = 'Madison Square Garden';
    $this->form->street_address = '4 Pennsylvania Plaza';
    $this->form->city = 'New York';
    $this->form->state = UnitedStatesState::NewYork->value;
    $this->form->zipcode = '10001';

    $data = $this->form->toData();

    expect($data)->toBeInstanceOf(VenueData::class)
        ->and($data->name)->toBe('Madison Square Garden')
        ->and($data->address->streetAddress)->toBe('4 Pennsylvania Plaza')
        ->and($data->address->city)->toBe('New York')
        ->and($data->address->state)->toBe(UnitedStatesState::NewYork)
        ->and($data->address->zipcode)->toBe('10001');
});

it('resolves the venue selected for editing', function () {
    $venue = Venue::factory()->create();
    $this->form->setModel($venue);

    $selectedVenue = $this->form->venue();

    expect($selectedVenue->is($venue))->toBeTrue();
});
