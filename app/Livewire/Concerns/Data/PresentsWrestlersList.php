<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Wrestlers\Wrestler;
use Livewire\Attributes\Computed;

trait PresentsWrestlersList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'wrestlers-list', seconds: 180)]
    public function getWrestlers(): array
    {
        return Wrestler::select('id', 'name')->pluck('name', 'id')->toArray();
    }
}
