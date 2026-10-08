<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Forms;

use App\Data\Events\VenueData;
use App\Enums\Shared\UnitedStatesState;
use App\Livewire\Base\BaseForm;
use App\Models\Events\Venue;
use Illuminate\Validation\Rule;

/** @extends BaseForm<Venue> */
class CreateEditForm extends BaseForm
{
    public string $name = '';

    public string $street_address = '';

    public string $city = '';

    public string $state = '';

    public int|string|null $zipcode = '';

    public string $timezone = 'UTC';

    public function toData(): VenueData
    {
        return new VenueData(
            name: $this->name,
            street_address: $this->street_address,
            city: $this->city,
            state: $this->state,
            zipcode: (string) $this->zipcode,
            timezone: $this->timezone,
        );
    }

    public function venue(): Venue
    {
        return Venue::query()->findOrFail($this->modelId);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('venues', 'name')->ignore($this->modelId)],
            'street_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', Rule::enum(UnitedStatesState::class)],
            'zipcode' => ['required', 'digits:5'],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ];
    }

    #[\Override]
    protected function validationAttributes(): array
    {
        return [
            'street_address' => __('venues.validation.attributes.street_address'),
            'zipcode' => __('venues.validation.attributes.zip_code'),
        ];
    }
}
