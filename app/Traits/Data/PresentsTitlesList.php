<?php

declare(strict_types=1);

namespace App\Traits\Data;

use App\Models\Title;
use Livewire\Attributes\Computed;

trait PresentsTitlesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed(cache: true, key: 'titles-list', seconds: 180)]
    public function getTitles(): array
    {
        return Title::select('id', 'name')->pluck('name', 'id')->toArray();
    }
}
