<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Roster\Referees\Referee;
use Livewire\Attributes\Computed;

trait PresentsRefereesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: false)]
    public function getReferees(): array
    {
        return Referee::query()
            ->get(['id', 'full_name'])
            ->mapWithKeys(fn (Referee $referee): array => [$referee->id => $referee->full_name])
            ->all();
    }
}
