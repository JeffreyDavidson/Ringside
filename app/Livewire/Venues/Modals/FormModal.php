<?php

declare(strict_types=1);

namespace App\Livewire\Venues\Modals;

use App\Livewire\Concerns\BaseModal;
use App\Livewire\Venues\VenueForm;
use App\Models\Venue;

class FormModal extends BaseModal
{
    protected string $modelType = Venue::class;

    protected string $modalLanguagePath = 'venues';

    protected string $modalFormPath = 'venues.modals.form-modal';

    public VenueForm $modelForm;
}
