<?php

declare(strict_types=1);

namespace App\Traits\Data;

use App\Models\Wrestler;
use Livewire\Attributes\Computed;

trait PresentsWrestlersList
{
    /**
     * Undocumented function
     *
     * @return array{id: int, name: string}
     */
    #[Computed(cache: true, key: 'wrestlers-list', seconds: 180)]
    public function getWrestlers(): array
    {
        return Wrestler::query()->pluck('name', 'id')->toArray();
    }
}
