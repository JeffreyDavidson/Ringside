<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Roster\Managers\Manager;
use Livewire\Attributes\Computed;

trait PresentsManagersList
{
    /**
     * @return array<int|string,string>
     */
    #[Computed]
    public function getManagers(): array
    {
        return Manager::query()
            ->get(['id', 'full_name'])
            ->mapWithKeys(fn (Manager $manager): array => [$manager->id => $manager->full_name ?? ''])
            ->all();
    }
}
