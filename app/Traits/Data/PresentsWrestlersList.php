<?php

declare(strict_types=1);

namespace App\Traits\Data;

use App\Models\Wrestler;
use Livewire\Attributes\Computed;

trait PresentsWrestlersList
{
    #[Computed(cache: true, key: 'wrestlers-list', seconds: 180)]
    public function getWrestlers(): array
    {
        return Wrestler::all()->pluck('name', 'id')->toArray();
    }
}
