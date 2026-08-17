<?php

declare(strict_types=1);

namespace Tests\Integration\Livewire\Venues\Forms\Support;

use App\Livewire\Venues\Forms\CreateEditForm;

final class VenueFormTestProxy extends CreateEditForm
{
    /** @return array<string, array<int, mixed>> */
    public function rulesForTesting(): array
    {
        return $this->rules();
    }

    /** @return array<string, string> */
    public function validationAttributesForTesting(): array
    {
        return $this->validationAttributes();
    }
}
