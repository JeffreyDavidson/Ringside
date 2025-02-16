<?php

declare(strict_types=1);

namespace App\Livewire\Venues;

use App\Livewire\Base\LivewireBaseForm;
use App\Models\Venue;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;

class VenueForm extends LivewireBaseForm
{
    protected string $formModelType = Venue::class;

    public ?Venue $formModel;

    public string $name = '';

    public string $street_address = '';

    public string $city = '';

    public string $state = '';

    public int|string $zipcode = '';

    /**
     * Undocumented function
     *
     * @return array<string, list<Unique|Exists|string>>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('venues')->ignore($this->formModel ?? '')],
            'street_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255', Rule::exists('App\Models\State', 'name')],
            'zipcode' => ['required', 'integer', 'digits:5'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'street_address' => 'street address',
            'zipcode' => 'zip code',
        ];
    }

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
