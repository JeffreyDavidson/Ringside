<?php

declare(strict_types=1);

namespace App\Livewire\Concerns\Data;

use App\Models\Titles\Title;
use Livewire\Attributes\Computed;

trait PresentsTitlesList
{
    /**
     * @return array<int|string,string|null>
     */
    #[Computed]
    public function getTitles(): array
    {
        return Title::query()
            ->pluck('name', 'id')
            ->all();
    }
}
