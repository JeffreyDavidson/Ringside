<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Matches\MatchType;
use Livewire\Attributes\Computed;

trait PresentsMatchTypesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'match-types-list', seconds: 180)]
    public function getMatchTypes(): array
    {
        return MatchType::select('id', 'name')->pluck('name', 'id')->toArray();
    }
}
