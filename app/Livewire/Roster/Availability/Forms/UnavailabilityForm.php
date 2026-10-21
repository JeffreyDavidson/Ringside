<?php

declare(strict_types=1);

namespace App\Livewire\Roster\Availability\Forms;

use App\Data\Roster\UnavailabilityData;
use Livewire\Form;

class UnavailabilityForm extends Form
{
    public string $startsOn = '';

    public string $endsOn = '';

    public string $reason = '';

    /** @return array<string, list<string>> */
    protected function rules(): array
    {
        return [
            'startsOn' => ['required', 'date_format:Y-m-d'],
            'endsOn' => ['required', 'date_format:Y-m-d', 'after_or_equal:startsOn'],
            'reason' => ['string', 'max:255'],
        ];
    }

    /** Fingerprint of the entered values, used to tell whether the reviewed values changed. */
    public function signature(): string
    {
        return hash('sha256', serialize([$this->startsOn, $this->endsOn, $this->reason]));
    }

    public function toData(): UnavailabilityData
    {
        $this->validate();

        return new UnavailabilityData($this->startsOn, $this->endsOn, $this->reason === '' ? null : $this->reason);
    }
}
