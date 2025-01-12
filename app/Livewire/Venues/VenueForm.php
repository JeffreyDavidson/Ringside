<?php

declare(strict_types=1);

namespace App\Livewire\Venues;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Venue;
use Livewire\Attributes\Validate;

class VenueForm extends LivewireBaseForm
{
    protected string $formModelType = Venue::class;

    public ?Venue $formModel;

    #[Validate('required|string|min:5|max:255', as: 'venues.name')]
    public string $name = '';

    #[Validate('required|string|max:255', as: 'venues.street_address')]
    public string $street_address = '';

    #[Validate('required|string|max:255', as: 'venues.city')]
    public string $city;

    #[Validate('required|string|max:255', as: 'venues.state')]
    public string $state;

    #[Validate('required|integer|digits:5', as: 'venues.zipcode')]
    public int $zipcode;

    public function store(): bool
    {
        $this->validate();

        if (! isset($this->formModel)) {
            $this->formModel = new Venue([
                'name' => $this->name,
                'street_address' => $this->street_address,
                'city' => $this->city,
                'state' => $this->state,
                'zipcode' => $this->zipcode,
            ]);
            $this->formModel->save();
        } else {
            $this->formModel->update([
                'name' => $this->name,
                'street_address' => $this->street_address,
                'city' => $this->city,
                'state' => $this->state,
                'zipcode' => $this->zipcode,
            ]);
        }

        return true;
    }
}
