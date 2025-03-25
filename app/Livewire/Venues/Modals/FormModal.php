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

    protected VenueForm $modelForm;

    public function fillDummyFields(): void
    {
        /**
         * @var string $state
         *
         * @phpstan-ignore-next-line
         */
        $state = fake('en_US')->state();

        $this->modelForm->name = Str::of(fake()->sentence(2))->title()->append(' Arena')->value();
        $this->modelForm->street_address = fake()->streetAddress();
        $this->modelForm->city = fake()->city();
        $this->modelForm->state = $state;
        $this->modelForm->zipcode = (int) Str::of(fake()->postcode())->limit(5)->value();
    }
}
