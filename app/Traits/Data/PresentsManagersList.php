<?php

declare(strict_types=1);

namespace App\Traits\Data;

use App\Models\Manager;
use Livewire\Attributes\Computed;

trait PresentsManagersList
{
    #[Computed(cache: true, key: 'managers-list', seconds: 180)]
    public function getManagers(): array
    {
        return Manager::all()->pluck('name', 'id')->toArray();
    }
}
