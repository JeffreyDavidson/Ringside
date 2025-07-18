<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Managers\Manager;
use Livewire\Attributes\Computed;

trait PresentsManagersList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'managers-list', seconds: 180)]
    public function getManagers(): array
    {
        return Manager::select('id', 'name')->pluck('name', 'id')->toArray();
    }
}
