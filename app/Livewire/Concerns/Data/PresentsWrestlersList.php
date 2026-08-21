<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Roster\Wrestlers\Wrestler;
use Livewire\Attributes\Computed;

trait PresentsWrestlersList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed]
    public function getWrestlers(): array
    {
        return Wrestler::query()
            ->pluck('name', 'id')
            ->all();
    }
}
