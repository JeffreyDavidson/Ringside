<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Venues\VenueForm;
use App\Models\Venue;
use Illuminate\Support\Str;

class FormModal extends BaseModal
{
    protected string $modelType = Venue::class;

    protected string $modalLanguagePath = 'venues';

    protected string $modalFormPath = 'venues.modals.form-modal';

    public VenueForm $modelForm;

    public function fillDummyFields()
    {
        $this->modelForm->name = Str::of(fake()->words(2, true))->title()->append(' Arena')->value();
        $this->modelForm->street_address = fake()->streetAddress();
        $this->modelForm->city = fake()->city();
        $this->modelForm->state = fake()->state();
        $this->modelForm->zipcode = (int) Str::of(fake()->postcode())->limit(5)->value();
    }
}
