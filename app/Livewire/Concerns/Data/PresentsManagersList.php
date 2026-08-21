<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Roster\Managers\Manager;
use Livewire\Attributes\Computed;

trait PresentsManagersList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed]
    public function getManagers(): array
    {
        return Manager::query()
            ->pluck('full_name', 'id')
            ->all();
    }
}
