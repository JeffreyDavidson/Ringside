<?php

declare(strict_types=1);

namespace App\Traits\Data;

use App\Models\Referee;
use Livewire\Attributes\Computed;

trait PresentsRefereesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'referees-list', seconds: 180)]
    public function getReferees(): array
    {
        return Referee::select('id', 'full_name')->pluck('full_name', 'id')->toArray();
    }
}
