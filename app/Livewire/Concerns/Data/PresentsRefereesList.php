<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Referees\Referee;
use Livewire\Attributes\Computed;

trait PresentsRefereesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: false)]
    public function getReferees(): array
    {
        return Referee::select('id', 'first_name', 'last_name')
            ->get()
            ->pluck('full_name', 'id')
            ->toArray();
    }
}
