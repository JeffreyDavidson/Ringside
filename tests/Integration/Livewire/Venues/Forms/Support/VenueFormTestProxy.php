<?php

declare(strict_types=1);

namespace Tests\Integration\Livewire\Venues\Forms\Support;

use App\Livewire\Venues\Forms\CreateEditForm;
use App\Models\Events\Venue;

final class VenueFormTestProxy extends CreateEditForm
{
    /** @return array<string, array<int, mixed>> */
    public function rulesForTesting(): array
    {
        return $this->rules();
    }

    /** @return array<string, mixed> */
    public function modelDataForTesting(): array
    {
        return $this->getModelData();
    }

    /** @return class-string<Venue> */
    public function modelClassForTesting(): string
    {
        return $this->getModelClass();
    }

    /** @return array<string, string> */
    public function validationAttributesForTesting(): array
    {
        return $this->validationAttributes();
    }
}
